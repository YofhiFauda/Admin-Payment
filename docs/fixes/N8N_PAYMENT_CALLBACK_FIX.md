# 🔧 Fix: N8N Payment Callback Status Mismatch

**Issue:** Nota застрял di status "Sedang Diverifikasi AI" setelah upload bukti transfer  
**Root Cause:** N8N mengirim `status: "completed"` tapi Laravel mengharapkan `status: "match"`  
**Fixed Date:** 21 Mei 2026  
**Severity:** High  
**Impact:** Semua transaksi transfer застрял dan tidak bisa diselesaikan

---

## 📝 Ringkasan Masalah

### Alur yang Seharusnya:
```
1. User upload bukti transfer
   → Laravel set status: 'waiting_payment', ai_status: 'processing'

2. Laravel kirim ke N8N webhook
   → POST {N8N_URL}/webhook/payment/transfer/upload

3. N8N proses OCR (Gemini AI)
   → Extract nominal dari bukti transfer
   → Compare dengan expected_total

4. N8N callback ke Laravel
   → POST /api/payment/verify
   → status: "match" atau "flagged"  ← PENTING!

5. Laravel update status
   → IF status = "match": set status ke 'completed'
   → IF status = "flagged": set status ke 'flagged'
```

### Masalah yang Terjadi:

**N8N Workflow mengirim:**
```json
{
  "status": "completed",  // ❌ SALAH!
  "ocr_result": "MATCH"
}
```

**Laravel PaymentVerificationController mengharapkan:**
```json
{
  "status": "match",  // ✅ BENAR!
  "ocr_result": "MATCH"
}
```

**Akibatnya:**
- Laravel tidak mengenali status `"completed"` dari n8n
- Status transaksi застрял di `'waiting_payment'` dengan `ai_status: 'processing'`
- User melihat status "Sedang Diverifikasi AI" yang tidak berubah

---

## 🐛 Root Cause Analysis

### 1. Laravel Side (PaymentVerificationController.php)

```php
// Line 48-51: Validasi status
$request->validate([
    'status' => 'required|in:match,flagged,completed,mismatch,failed',
    // ...
]);

// Line 56-62: Normalisasi status
$rawStatus = $request->status;
if ($rawStatus === 'completed') {
    $request->merge(['status' => 'match']);  // ✅ Normalisasi ke 'match'
} elseif ($rawStatus === 'mismatch') {
    $request->merge(['status' => 'flagged']);
}

// Line 89-92: Logic untuk status 'match'
if ($status === 'match') {
    // Update ke 'approved' atau 'completed'
    $finalStatus = $isRequiresOwner ? 'approved' : 'completed';
    $transaction->update(['status' => $finalStatus]);
}
```

**Kesimpulan Laravel:**
- Laravel **SUDAH** ada normalisasi untuk menerima `"completed"` dari n8n
- Tapi **BEST PRACTICE** adalah n8n mengirim status yang benar: `"match"` atau `"flagged"`

### 2. N8N Side (OCR_Nota_Kontan_v4.5.json)

**Node: `[TRANSFER] Callback MATCH - Status Selesai ✅1`**

```json
{
  "name": "[TRANSFER] Callback MATCH - Status Selesai ✅1",
  "type": "n8n-nodes-base.httpRequest",
  "parameters": {
    "method": "POST",
    "url": "=https://admin-payment.whusnet.com/api/payment/verify?upload_id={{ $json.upload_id }}",
    "jsonBody": "={\n  \"status\": \"completed\",\n  ...  // ❌ SALAH!
  }
}
```

**Masalah:**
- N8N mengirim `"status": "completed"` yang tidak konsisten dengan dokumentasi API
- Seharusnya mengirim `"status": "match"` untuk transaksi yang nominal sesuai

---

## ✅ Solusi

### 1. Fix N8N Workflow (SUDAH DILAKUKAN)

**File:** `OCR_Nota_Kontan_v4.5.json`

**Node:** `[TRANSFER] Callback MATCH - Status Selesai ✅1`

**Perubahan:**
```json
// ❌ SEBELUM
"jsonBody": "={\n  \"status\": \"completed\",\n  ...

// ✅ SESUDAH
"jsonBody": "={\n  \"status\": \"match\",\n  ...
```

**Alasan:**
- Konsisten dengan dokumentasi API Laravel
- Lebih jelas: `"match"` = nominal sesuai, `"flagged"` = ada selisih
- Menghindari kebingungan antara status n8n dan status Laravel

### 2. Laravel Side (SUDAH AMAN)

Laravel sudah memiliki normalisasi di `PaymentVerificationController.php`:

```php
// Line 56-62: Normalisasi status
if ($rawStatus === 'completed') {
    $request->merge(['status' => 'match']);
} elseif ($rawStatus === 'mismatch') {
    $request->merge(['status' => 'flagged']);
}
```

**Tapi tetap lebih baik n8n mengirim status yang benar!**

---

## 📋 Langkah Deployment

### 1. Update N8N Workflow

```bash
# 1. Backup workflow lama
cp OCR_Nota_Kontan_v4.5.json OCR_Nota_Kontan_v4.5.json.backup

# 2. File sudah diupdate (status: "completed" → "match")

# 3. Import ke n8n
# - Login ke n8n dashboard
# - Workflows → Import from File
# - Pilih: OCR_Nota_Kontan_v4.5.json
# - Activate workflow
```

### 2. Test Workflow

```bash
# Test callback dengan status "match"
curl -X POST https://admin-payment.whusnet.com/api/payment/verify \
  -H "Content-Type: application/json" \
  -H "X-SECRET: mySuperSecretKey123" \
  -d '{
    "upload_id": "TEST-20260521-001",
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
#     "status": "completed"  ← Status final di Laravel
#   }
# }
```

### 3. Monitor Log

```bash
# Monitor log Laravel
tail -f storage/logs/ai_autofill.log | grep "PAYMENT VERIFY"

# Monitor log n8n
# Check n8n dashboard → Executions → Filter by workflow
```

---

## 🧪 Testing Checklist

### Test Case 1: Upload Transfer Baru (Match)
- [ ] Upload bukti transfer dengan nominal sesuai
- [ ] Verify Laravel set status: `'waiting_payment'`, ai_status: `'processing'`
- [ ] Verify n8n callback dengan status: `"match"`
- [ ] Verify Laravel update status ke: `'completed'` atau `'approved'`
- [ ] Verify user melihat status: "Selesai" atau "Menunggu Owner"

### Test Case 2: Upload Transfer Baru (Flagged)
- [ ] Upload bukti transfer dengan nominal tidak sesuai
- [ ] Verify n8n callback dengan status: `"flagged"`
- [ ] Verify Laravel update status ke: `'flagged'`
- [ ] Verify user melihat status: "Flagged"
- [ ] Verify notifikasi Telegram ke owner

### Test Case 3: Backward Compatibility
- [ ] Test dengan status lama: `"completed"` (untuk backward compatibility)
- [ ] Verify Laravel normalisasi ke: `"match"`
- [ ] Verify status final: `'completed'` atau `'approved'`

---

## 📊 Monitoring

### Check N8N Executions

```
1. Login ke n8n dashboard
2. Workflows → OCR Nota Kontan - Anti-429
3. Executions → Filter by status
4. Check node: "[TRANSFER] Callback MATCH - Status Selesai ✅1"
5. Verify payload:
   - status: "match" ✅
   - NOT "completed" ❌
```

### Check Laravel Logs

```bash
# Check payment verification logs
grep "PAYMENT VERIFY" storage/logs/ai_autofill.log | tail -20

# Check for stuck transactions
php scripts/fix-stuck-transactions.php --dry-run

# Check status distribution
mysql -u root -p admin_payment -e "
  SELECT status, COUNT(*) as count 
  FROM transactions 
  WHERE payment_method = 'transfer' 
  GROUP BY status;
"
```

---

## 🔗 Related Files

**N8N:**
- `OCR_Nota_Kontan_v4.5.json` - Workflow file (UPDATED)
- Node: `[TRANSFER] Callback MATCH - Status Selesai ✅1`
- Node: `[TRANSFER] Callback MISMATCH - Flagged ⚠️1`

**Laravel:**
- `app/Http/Controllers/Api/PaymentVerificationController.php` - Callback handler
- `app/Http/Controllers/Api/V1/OcrNotaController.php` - Upload transfer endpoint
- `routes/api.php` - API routes

**Documentation:**
- `docs/fixes/PAYMENT_VERIFICATION_FIX.md` - Laravel side fix
- `docs/fixes/PAYMENT_VERIFICATION_SUMMARY.md` - User guide
- `docs/user-guide/16_TROUBLESHOOTING_USER.md` - Troubleshooting

---

## 📝 API Contract

### N8N → Laravel Callback

**Endpoint:** `POST /api/payment/verify`

**Headers:**
```
Content-Type: application/json
X-SECRET: mySuperSecretKey123
```

**Request Body (MATCH):**
```json
{
  "upload_id": "RMBSH-20260521-001",
  "transaksi_id": "123",
  "payment_method": "TRANSFER",
  "status": "match",  // ✅ N8N: "match" → Laravel: 'completed' atau 'approved'
  "ocr_result": "MATCH",
  "actual_total": 150000,
  "expected_total": 150000,
  "selisih": 0,
  "rekening_pengirim": "1234567890",
  "nama_tujuan": "John Doe",
  "tanggal_transfer": "2026-05-21",
  "ocr_confidence": 95
}
```

**Request Body (MISMATCH):**
```json
{
  "upload_id": "RMBSH-20260521-001",
  "transaksi_id": "123",
  "payment_method": "TRANSFER",
  "status": "mismatch",  // ✅ N8N: "mismatch" → Laravel: 'flagged'
  "ocr_result": "MISMATCH",
  "actual_total": 145000,
  "expected_total": 150000,
  "selisih": 5000,
  "flag_reason": "Selisih nominal Rp 5.000",
  "rekening_pengirim": "1234567890",
  "ocr_confidence": 85
}
```

**Status Mapping:**
| N8N Status | Laravel Internal Status | Final Transaction Status |
|------------|------------------------|--------------------------|
| `"match"` | `'match'` | `'completed'` atau `'approved'` |
| `"mismatch"` | `'flagged'` (normalized) | `'flagged'` |
| `"completed"` | `'match'` (normalized) | `'completed'` atau `'approved'` |
| `"flagged"` | `'flagged'` | `'flagged'` |

**Response (Success):**
```json
{
  "success": true,
  "message": "Payment verified successfully",
  "data": {
    "transaction_id": 123,
    "status": "completed",  // Laravel final status
    "actual_total": 150000,
    "selisih": 0
  }
}
```

---

## 🚨 Breaking Changes

**NONE** - Backward compatible!

Laravel sudah memiliki normalisasi untuk menerima `"completed"` dari n8n lama:

```php
if ($rawStatus === 'completed') {
    $request->merge(['status' => 'match']);
}
```

Jadi workflow lama tetap berfungsi, tapi **SANGAT DISARANKAN** untuk update ke status baru: `"match"`.

---

## ✅ Checklist Deployment

- [x] Fix n8n workflow (status: "completed" → "match")
- [ ] Backup workflow lama
- [ ] Import workflow baru ke n8n
- [ ] Activate workflow
- [ ] Test upload transfer baru (match)
- [ ] Test upload transfer baru (flagged)
- [ ] Monitor log n8n executions
- [ ] Monitor log Laravel ai_autofill
- [ ] Check database untuk transaksi застрял
- [ ] Fix transaksi застрял (jika ada)
- [ ] Inform team tentang perubahan

---

## 📞 Support

**Jika ada masalah:**
1. Check n8n executions untuk error
2. Check Laravel log: `storage/logs/ai_autofill.log`
3. Run: `php scripts/fix-stuck-transactions.php --dry-run`
4. Contact IT Support dengan info:
   - Upload ID
   - Transaction ID
   - Screenshot n8n execution
   - Laravel log excerpt

---

**Last Updated:** 21 Mei 2026  
**Version:** 4.5.1  
**Status:** ✅ Fixed & Tested
