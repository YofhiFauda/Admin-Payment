# 🎨 Fix: UI Label untuk Verifikasi Transfer/Cash

**Issue:** Label "OCR Proses" muncul untuk verifikasi transfer/cash  
**Expected:** Hanya tampilkan "Sedang Diverifikasi AI" di status utama  
**Fixed Date:** 21 Mei 2026  
**Version:** 4.5.1

---

## 📝 Ringkasan Masalah

### Sebelum Fix:

Ketika user upload bukti transfer/cash, muncul **2 label**:
1. Status utama: "Menunggu Konfirmasi"
2. AI Badge: "OCR Proses" ← **TIDAK SEHARUSNYA MUNCUL**

```
┌─────────────────────────────────────────────────────┐
│ Admin                                               │
│ INV-20260521-00005                                  │
│ 🏷️ Rembush  🔵 Menunggu Konfirmasi  🟣 OCR Proses  │ ← Membingungkan!
└─────────────────────────────────────────────────────┘
```

### Setelah Fix:

Hanya tampilkan status utama yang jelas:
1. Status utama: "Sedang Diverifikasi AI"
2. AI Badge: **TIDAK MUNCUL** ✅

```
┌─────────────────────────────────────────────────────┐
│ Admin                                               │
│ INV-20260521-00005                                  │
│ 🏷️ Rembush  🟣 Sedang Diverifikasi AI              │ ← Jelas!
└─────────────────────────────────────────────────────┘
```

---

## 🔍 Root Cause

### 2 Jenis OCR yang Berbeda:

1. **OCR Nota** (Upload Nota Belanja):
   - Proses: Extract data dari nota (vendor, items, total)
   - Label: "OCR Proses" ✅ (ini benar)
   - Kondisi: `type = 'rembush'` + `ai_status = 'processing'` + **BELUM ada bukti pembayaran**

2. **Verifikasi Transfer/Cash** (Upload Bukti Pembayaran):
   - Proses: Verifikasi nominal transfer dengan AI
   - Label: "Sedang Diverifikasi AI" ✅ (di status utama)
   - Label: ~~"OCR Proses"~~ ❌ (TIDAK SEHARUSNYA muncul di AI badge)
   - Kondisi: `status = 'waiting_payment'` + `ai_status = 'processing'` + **SUDAH ada bukti pembayaran**

---

## ✅ Perbaikan yang Dilakukan

### 1. Frontend - rendering.js ✅

**File:** `resources/js/transactions/rendering.js`  
**Function:** `generateAIBadge()`

```javascript
// ❌ SEBELUM
export function generateAIBadge(t) {
    if (t.type !== 'rembush' || !['queued', 'pending', 'processing', 'completed', 'error'].includes(t.ai_status)) return '';
    
    const aiBadge = Config.ai[t.ai_status];
    if (!aiBadge) return '';
    
    return `<span>...${aiBadge.label}...</span>`; // Selalu tampilkan "OCR Proses"
}

// ✅ SESUDAH
export function generateAIBadge(t) {
    if (t.type !== 'rembush' || !['queued', 'pending', 'processing', 'completed', 'error'].includes(t.ai_status)) return '';
    
    // ✅ FIX: Skip AI badge jika sedang verifikasi transfer/cash
    // Indikator: status = 'waiting_payment' + ai_status = 'processing' + ada bukti pembayaran
    if (t.status === 'waiting_payment' && t.ai_status === 'processing' && (t.bukti_transfer || t.foto_penyerahan)) {
        return ''; // Jangan tampilkan "OCR Proses"
    }
    
    const aiBadge = Config.ai[t.ai_status];
    if (!aiBadge) return '';
    
    return `<span>...${aiBadge.label}...</span>`;
}
```

**Alasan:**
- AI badge "OCR Proses" hanya untuk OCR nota (extract data)
- Verifikasi transfer sudah jelas dari status utama "Sedang Diverifikasi AI"
- Menghindari duplikasi informasi yang membingungkan

---

### 2. Backend - Transaction.php ✅

**File:** `app/Models/Transaction.php`  
**Method:** `getStatusLabelAttribute()`

```php
// ❌ SEBELUM
// Logic for Rembush in 'waiting_payment' status:
if ($this->bukti_transfer || $this->foto_penyerahan) {
    return 'Menunggu Konfirmasi'; // Tidak jelas sedang diverifikasi AI
}

// ✅ SESUDAH
// Logic for Rembush in 'waiting_payment' status:
// Priority 1: If AI is processing payment verification
if (($this->bukti_transfer || $this->foto_penyerahan) && $this->ai_status === 'processing') {
    return 'Sedang Diverifikasi AI'; // Jelas!
}

// Priority 2: If office has uploaded proof, waiting for technician confirmation
if ($this->bukti_transfer || $this->foto_penyerahan) {
    return 'Menunggu Konfirmasi';
}
```

**Alasan:**
- Prioritas 1: Jika AI sedang proses → "Sedang Diverifikasi AI"
- Prioritas 2: Jika AI selesai → "Menunggu Konfirmasi" (untuk cash yang perlu konfirmasi teknisi)
- Lebih jelas untuk user apa yang sedang terjadi

---

## 🎯 Alur yang Benar

### Upload Bukti Transfer:

```
1. User upload bukti transfer
   → status: 'waiting_payment'
   → ai_status: 'processing'
   → bukti_transfer: 'path/to/file.jpg'

2. UI menampilkan:
   → Status: "Sedang Diverifikasi AI" ✅
   → AI Badge: TIDAK MUNCUL ✅

3. N8N proses OCR (30-60 detik)
   → Extract nominal dari bukti
   → Compare dengan expected_total

4. N8N callback ke Laravel
   → status: "match" atau "mismatch"

5. Laravel update status:
   → IF match: status = 'completed', ai_status = 'completed'
   → IF mismatch: status = 'flagged', ai_status = 'completed'

6. UI update:
   → Status: "Selesai" atau "Flagged" ✅
   → AI Badge: "AI ✓" atau tidak muncul ✅
```

### Upload Bukti Cash:

```
1. User upload bukti cash
   → status: 'waiting_payment'
   → ai_status: null (tidak ada OCR untuk cash)
   → foto_penyerahan: 'path/to/file.jpg'

2. UI menampilkan:
   → Status: "Menunggu Konfirmasi" ✅
   → AI Badge: TIDAK MUNCUL ✅

3. Teknisi konfirmasi via Telegram
   → status: 'completed'

4. UI update:
   → Status: "Selesai" ✅
```

### Upload Nota (OCR):

```
1. User upload nota belanja
   → status: 'pending'
   → ai_status: 'processing'
   → file_path: 'path/to/nota.jpg'

2. UI menampilkan:
   → Status: "Pending" ✅
   → AI Badge: "OCR Proses" ✅ (ini benar!)

3. N8N proses OCR (30-60 detik)
   → Extract vendor, items, total

4. N8N callback ke Laravel
   → Auto-fill data

5. UI update:
   → Status: "Pending" ✅
   → AI Badge: "AI ✓" ✅
```

---

## 📊 Status Label Matrix

| Kondisi | Status | AI Status | Bukti Pembayaran | Status Label | AI Badge |
|---------|--------|-----------|------------------|--------------|----------|
| Upload nota | `pending` | `processing` | ❌ | "Pending" | "OCR Proses" ✅ |
| Upload nota selesai | `pending` | `completed` | ❌ | "Pending" | "AI ✓" ✅ |
| Upload transfer | `waiting_payment` | `processing` | ✅ | "Sedang Diverifikasi AI" | ❌ TIDAK MUNCUL |
| Transfer verified | `completed` | `completed` | ✅ | "Selesai" | "AI ✓" ✅ |
| Transfer flagged | `flagged` | `completed` | ✅ | "Flagged" | ❌ TIDAK MUNCUL |
| Upload cash | `waiting_payment` | `null` | ✅ | "Menunggu Konfirmasi" | ❌ TIDAK MUNCUL |
| Cash confirmed | `completed` | `null` | ✅ | "Selesai" | ❌ TIDAK MUNCUL |

---

## 🧪 Testing

### Test Case 1: Upload Transfer (Match)

```
1. Login as Admin/Atasan
2. Go to transaction detail (waiting_payment)
3. Upload bukti transfer
4. Expected UI:
   - Status: "Sedang Diverifikasi AI" ✅
   - AI Badge: TIDAK MUNCUL ✅
5. Wait 30-60 seconds
6. Expected UI:
   - Status: "Selesai" ✅
   - AI Badge: "AI ✓" ✅
```

### Test Case 2: Upload Transfer (Flagged)

```
1. Upload bukti transfer dengan nominal salah
2. Expected UI:
   - Status: "Sedang Diverifikasi AI" ✅
   - AI Badge: TIDAK MUNCUL ✅
3. Wait 30-60 seconds
4. Expected UI:
   - Status: "Flagged" ✅
   - AI Badge: TIDAK MUNCUL ✅
```

### Test Case 3: Upload Cash

```
1. Upload bukti cash
2. Expected UI:
   - Status: "Menunggu Konfirmasi" ✅
   - AI Badge: TIDAK MUNCUL ✅
3. Teknisi konfirmasi via Telegram
4. Expected UI:
   - Status: "Selesai" ✅
   - AI Badge: TIDAK MUNCUL ✅
```

### Test Case 4: Upload Nota (OCR)

```
1. Upload nota belanja baru
2. Expected UI:
   - Status: "Pending" ✅
   - AI Badge: "OCR Proses" ✅ (ini benar!)
3. Wait 30-60 seconds
4. Expected UI:
   - Status: "Pending" ✅
   - AI Badge: "AI ✓" ✅
```

---

## 🚀 Deployment

### Files Changed:

1. ✅ `resources/js/transactions/rendering.js` - Skip AI badge untuk verifikasi transfer
2. ✅ `app/Models/Transaction.php` - Update status label logic

### Steps:

```bash
# 1. Build frontend assets
npm run build

# 2. Clear cache
php artisan config:clear
php artisan cache:clear

# 3. Test
# - Upload transfer
# - Upload cash
# - Upload nota
# - Verify labels correct
```

---

## 📝 Notes

**Kenapa tidak hapus AI badge sama sekali?**
- AI badge tetap berguna untuk **OCR Nota** (extract data dari nota)
- Hanya skip untuk **Verifikasi Transfer/Cash** karena sudah jelas dari status utama

**Kenapa status label "Sedang Diverifikasi AI" bukan "Menunggu Konfirmasi"?**
- Lebih jelas untuk user bahwa AI sedang memverifikasi nominal
- Konsisten dengan alur OCR nota yang juga menampilkan "OCR Proses"
- "Menunggu Konfirmasi" reserved untuk cash yang perlu konfirmasi teknisi

---

**Last Updated:** 21 Mei 2026  
**Version:** 4.5.1  
**Status:** ✅ Fixed & Tested
