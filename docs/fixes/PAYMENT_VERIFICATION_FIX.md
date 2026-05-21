# Fix: Nota Stuck di "Sedang Diverifikasi AI"

## 🔍 Masalah

Nota застрял (stuck) di status **"Sedang Diverifikasi AI"** setelah upload bukti transfer ke endpoint:
```
https://admin-payment.whusnet.com/api/payment/verify?upload_id={{ $json.upload_id }}
```

## 🐛 Root Cause

Di file `app/Http/Controllers/Api/V1/OcrNotaController.php` method `uploadTransfer()` (baris 1024), terdapat **hard-coded status yang tidak valid**:

```php
// ❌ SALAH: Status ini BUKAN status valid dalam sistem
$transaction->update(['status' => 'Sedang Diverifikasi AI']);
```

### Status Valid dalam Sistem:
Berdasarkan `app/Models/Transaction.php`, status yang valid adalah:
- `pending` - Transaksi baru, menunggu review
- `approved` - Disetujui, menunggu owner
- `waiting_payment` - Menunggu pembayaran/pelunasan
- `completed` - Selesai
- `rejected` - Ditolak
- `flagged` - Ada masalah (untuk payment verification)
- `pending_technician` - Menunggu konfirmasi teknisi (untuk cash)

Status `"Sedang Diverifikasi AI"` **BUKAN** status valid, sehingga:
1. UI tidak bisa menampilkan status dengan benar
2. Callback dari n8n ke `/api/payment/verify` tidak bisa update status
3. Transaksi застрял di status yang tidak dikenali sistem

## ✅ Solusi

### 1. Perbaikan Kode (SUDAH DILAKUKAN)

Ganti status hard-coded dengan status valid + ai_status untuk tracking:

```php
// ✅ BENAR: Gunakan status valid dengan ai_status untuk tracking
$transaction->update([
    'status' => 'waiting_payment',
    'ai_status' => 'processing'
]);
```

**Penjelasan:**
- `status = 'waiting_payment'` → Status valid yang dikenali sistem
- `ai_status = 'processing'` → Field terpisah untuk tracking proses AI/OCR
- Ketika n8n callback ke `/api/payment/verify`, status akan diupdate ke `match` atau `flagged`

### 2. Alur yang Benar

```
┌─────────────────────────────────────────────────────────────┐
│ 1. User Upload Bukti Transfer                              │
│    POST /api/v1/payment/transfer/upload                    │
│    → status: 'waiting_payment'                             │
│    → ai_status: 'processing'                               │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Laravel Kirim ke N8N Webhook                            │
│    POST {N8N_URL}/webhook/payment/transfer/upload          │
│    → Attach binary file (bukti transfer)                   │
│    → callback_url: /api/payment/verify                     │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. N8N Proses OCR (Gemini AI)                              │
│    → Extract nominal dari bukti transfer                   │
│    → Compare dengan expected_total                         │
│    → Hitung selisih                                        │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. N8N Callback ke Laravel                                 │
│    POST /api/payment/verify                                │
│    {                                                       │
│      "upload_id": "RMBSH-20260313-001",                   │
│      "status": "match" | "flagged",                       │
│      "actual_total": 150000,                              │
│      "expected_total": 150000,                            │
│      "selisih": 0,                                        │
│      "confidence": 95                                     │
│    }                                                       │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. PaymentVerificationController Update Status             │
│    IF status = 'match':                                    │
│      → status: 'approved' atau 'completed'                │
│      → Kirim notif Telegram ke teknisi                    │
│    IF status = 'flagged':                                 │
│      → status: 'flagged'                                  │
│      → Create PaymentDiscrepancyAudit                     │
│      → Kirim notif Telegram ke semua owner               │
└─────────────────────────────────────────────────────────────┘
```

## 🔧 Langkah Perbaikan untuk Transaksi yang Sudah Stuck

Jika ada transaksi yang sudah застрял dengan status `"Sedang Diverifikasi AI"`, jalankan query berikut:

### Opsi 1: Reset ke waiting_payment (Recommended)
```sql
-- Cari transaksi yang stuck
SELECT id, upload_id, invoice_number, status, ai_status, created_at
FROM transactions
WHERE status = 'Sedang Diverifikasi AI';

-- Reset status ke waiting_payment
UPDATE transactions
SET status = 'waiting_payment',
    ai_status = 'error'
WHERE status = 'Sedang Diverifikasi AI';
```

Setelah reset, user perlu **upload ulang bukti transfer** karena callback dari n8n kemungkinan sudah gagal.

### Opsi 2: Manual Approve (Jika sudah diverifikasi manual)
```sql
-- Jika sudah diverifikasi manual oleh admin/owner
UPDATE transactions
SET status = 'completed',
    ai_status = 'completed',
    actual_total = expected_total,
    confidence = 100
WHERE status = 'Sedang Diverifikasi AI'
  AND id = <transaction_id>;
```

## 📊 Monitoring

### Check Status Transaksi
```sql
-- Lihat distribusi status transaksi
SELECT status, COUNT(*) as count
FROM transactions
GROUP BY status
ORDER BY count DESC;

-- Lihat transaksi dengan status tidak valid
SELECT id, upload_id, invoice_number, status, ai_status, created_at
FROM transactions
WHERE status NOT IN ('pending', 'approved', 'waiting_payment', 'completed', 'rejected', 'flagged', 'pending_technician')
ORDER BY created_at DESC;
```

### Check AI Status
```sql
-- Lihat transaksi yang sedang diproses AI
SELECT id, upload_id, invoice_number, status, ai_status, created_at
FROM transactions
WHERE ai_status IN ('queued', 'processing')
  AND created_at > NOW() - INTERVAL 1 HOUR
ORDER BY created_at DESC;
```

## 🧪 Testing

### 1. Test Upload Transfer
```bash
# Upload bukti transfer
curl -X POST https://admin-payment.whusnet.com/api/v1/payment/transfer/upload \
  -H "Content-Type: multipart/form-data" \
  -H "X-CSRF-TOKEN: <token>" \
  -F "bukti_transfer=@/path/to/image.jpg" \
  -F "upload_id=RMBSH-20260521-001" \
  -F "transaksi_id=123" \
  -F "expected_nominal=150000" \
  -F "kode_unik=123" \
  -F "biaya_admin=0"

# Expected Response:
# {
#   "success": true,
#   "message": "Bukti transfer diterima. AI sedang memverifikasi nominal.",
#   "status": "waiting_payment"  ← HARUS 'waiting_payment', BUKAN 'Sedang Diverifikasi AI'
# }
```

### 2. Test N8N Callback
```bash
# Simulate n8n callback (untuk testing)
curl -X POST https://admin-payment.whusnet.com/api/payment/verify \
  -H "Content-Type: application/json" \
  -H "X-N8N-Secret: <secret>" \
  -d '{
    "upload_id": "RMBSH-20260521-001",
    "status": "match",
    "actual_total": 150000,
    "expected_total": 150000,
    "selisih": 0,
    "confidence": 95
  }'

# Expected Response:
# {
#   "success": true,
#   "message": "Payment verified successfully",
#   "data": {
#     "transaction_id": 123,
#     "status": "completed"  ← Status final setelah verifikasi
#   }
# }
```

## 📝 Checklist

- [x] Fix hard-coded status di `uploadTransfer()`
- [ ] Check database untuk transaksi yang stuck
- [ ] Reset transaksi yang stuck (jika ada)
- [ ] Test upload transfer baru
- [ ] Monitor log n8n callback
- [ ] Verify Telegram notification terkirim

## 🔗 Related Files

- `app/Http/Controllers/Api/V1/OcrNotaController.php` - Upload transfer endpoint
- `app/Http/Controllers/Api/PaymentVerificationController.php` - N8N callback handler
- `app/Models/Transaction.php` - Transaction model dengan status enum
- `routes/api.php` - API routes

## 📚 References

- N8N Webhook URL: `{N8N_URL}/webhook/payment/transfer/upload`
- Callback URL: `/api/payment/verify`
- Status Valid: `pending`, `approved`, `waiting_payment`, `completed`, `rejected`, `flagged`, `pending_technician`
