# 🔍 Analisis Potensi Bug & Error dari Perubahan Hari Ini

**Date:** 21 Mei 2026  
**Version:** 4.5.5  
**Status:** ⚠️ REVIEW REQUIRED

---

## 📋 Summary Perubahan Hari Ini

### Backend Changes:
1. ✅ `OcrNotaController.php` - Set `ai_status` immediately on upload
2. ✅ `TransactionController.php` - Clear fields on reset, conditional reviewer logic
3. ✅ `Transaction.php` - Defensive check for "Menunggu Konfirmasi" label
4. ✅ `AiAutoFillController.php` - Remove invalid status mapping

### Frontend Changes:
5. ✅ `rendering.js` - Simplify AI badge logic (skip if payment proof exists)

### N8N Changes:
6. ✅ `OCR_Nota_Kontan_v4.5.json` - Send `"match"` instead of `"completed"`

---

## ⚠️ Potensi Issue #1: Upload Cash - ai_status Not Set

### Scenario:
```php
// OcrNotaController::uploadCash() - Line 656
$transaction->update([
    'foto_penyerahan' => $path,
    'status'          => $finalStatus, // 'pending_technician' or 'completed'
    'paid_by'         => auth()->id(),
    'paid_at'        => now(),
    // ❌ ai_status NOT SET (remains null or previous value)
]);
```

### Problem:
- Upload CASH tidak set `ai_status = null` explicitly
- Jika transaksi sebelumnya punya `ai_status = 'processing'` (dari OCR nota)
- Setelah upload cash, `ai_status` masih `'processing'`
- Badge "OCR Proses" bisa muncul (karena `ai_status = 'processing'`)

### Impact:
- **Severity:** LOW
- Badge mungkin muncul untuk cash payment (seharusnya tidak)
- Tapi karena ada `foto_penyerahan`, badge logic kita skip (line 123 rendering.js)
- Jadi sebenarnya OK, tapi tidak konsisten

### Recommendation:
```php
// ✅ FIX: Explicitly set ai_status = null for cash
$transaction->update([
    'foto_penyerahan' => $path,
    'status'          => $finalStatus,
    'ai_status'       => null, // ← Add this
    'paid_by'         => auth()->id(),
    'paid_at'         => now(),
]);
```

---

## ⚠️ Potensi Issue #2: Pembelian Upload Transfer - ai_status Set to null

### Scenario:
```php
// OcrNotaController::uploadTransfer() - Line 960
$transaction->update([
    'bukti_transfer' => $path,
    'status'         => $isPembelian ? 'completed' : 'waiting_payment',
    'ai_status'      => $isPembelian ? null : 'processing', // ← null for Pembelian
]);
```

### Analysis:
- Pembelian (Gudang) tidak perlu AI verification
- Set `ai_status = null` adalah BENAR ✅
- Status langsung `'completed'`

### Impact:
- **Severity:** NONE
- This is correct behavior
- No issue here ✅

---

## ⚠️ Potensi Issue #3: Reset Pending - Notification Sent?

### Scenario:
```php
// TransactionController::updateStatus() - Line 900+
if (in_array($newStatus, ['approved', 'rejected', 'completed', 'waiting_payment']) && $oldStatus !== $newStatus) {
    if ($transaction->submitter) {
        $transaction->submitter->notify(new TransactionStatusNotification($transaction, $newStatus));
    }
}
```

### Problem:
- Reset ke `'pending'` TIDAK masuk dalam array notifikasi
- Teknisi TIDAK dapat notifikasi saat transaksi di-reset
- Apakah ini intended behavior?

### Impact:
- **Severity:** LOW-MEDIUM
- Teknisi tidak tahu transaksinya di-reset
- Harus cek manual di dashboard

### Recommendation:
**Option 1:** Add notification for reset
```php
if (in_array($newStatus, ['pending', 'approved', 'rejected', 'completed', 'waiting_payment']) && $oldStatus !== $newStatus) {
    // Send notification
}
```

**Option 2:** Keep as is (no notification for reset)
- Reset adalah admin action
- Teknisi akan lihat status berubah di dashboard
- Tidak perlu notifikasi

**Decision:** Keep as is (Option 2) - Reset is admin action, no notification needed ✅

---

## ⚠️ Potensi Issue #4: N8N Callback - Backward Compatibility

### Scenario:
```json
// N8N sends: "status": "match"
// Laravel expects: "match" or "completed" (normalized)
```

### Analysis:
```php
// PaymentVerificationController.php - Line 40-50
private function normalizeStatus(string $status): string
{
    $normalized = [
        'completed' => 'match',    // ✅ Backward compatible
        'mismatch'  => 'flagged',  // ✅ Backward compatible
    ];
    
    return $normalized[strtolower($status)] ?? $status;
}
```

### Impact:
- **Severity:** NONE
- Backward compatible ✅
- Old N8N workflows sending `"completed"` will still work
- New workflows sending `"match"` will work
- No breaking change ✅

---

## ⚠️ Potensi Issue #5: Badge Logic - OCR Nota After Payment

### Scenario:
```
1. Upload nota → OCR processing → ai_status = 'processing'
2. Approve → status = 'waiting_payment'
3. Upload transfer → bukti_transfer = path, ai_status = 'processing' (overwrite)
4. Badge hidden (because bukti_transfer exists)
```

### Problem:
- Jika user upload nota DULU, lalu upload transfer
- `ai_status` dari nota OCR akan di-overwrite
- Nota OCR result mungkin hilang?

### Analysis:
**Actually NO PROBLEM:**
- Nota OCR result disimpan di `items` field (auto-fill)
- `ai_status` hanya untuk tracking current process
- Overwrite `ai_status` untuk transfer verification adalah BENAR
- Nota OCR result tetap ada di `items` ✅

### Impact:
- **Severity:** NONE
- This is correct behavior ✅

---

## ⚠️ Potensi Issue #6: Reset Pending - Activity Log

### Scenario:
```php
// TransactionController::updateStatus() - Line 895-905
$actionLabel = $newStatus === 'rejected' ? 'Reject' : 'Approve';
$description = $newStatus === 'rejected' 
    ? "Menolak status Transaksi..."
    : "Menyetujui status Transaksi...";
```

### Problem:
- Reset ke `'pending'` akan log sebagai "Approve" (misleading)
- Seharusnya log sebagai "Reset"

### Impact:
- **Severity:** LOW
- Activity log tidak akurat
- Bisa membingungkan saat audit

### Recommendation:
```php
// ✅ FIX: Better activity log for reset
if ($newStatus === 'pending') {
    $actionLabel = 'reset';
    $description = "Reset status Transaksi " . $transaction->invoice_number . " ke Pending";
} elseif ($newStatus === 'rejected') {
    $actionLabel = 'reject';
    $description = "Menolak status Transaksi...";
} else {
    $actionLabel = 'approve';
    $description = "Menyetujui status Transaksi...";
}
```

---

## ⚠️ Potensi Issue #7: Frontend Badge - ai_status = 'completed'

### Scenario:
```javascript
// rendering.js - Line 119
if (t.type !== 'rembush' || !['queued', 'pending', 'processing', 'completed', 'error'].includes(t.ai_status)) return '';
```

### Problem:
- Badge masih check `ai_status = 'completed'`
- Untuk OCR Nota yang sudah selesai, badge tetap muncul
- Apakah ini intended?

### Analysis:
**YES, this is INTENDED:**
- OCR Nota selesai → `ai_status = 'completed'`
- Badge "OCR Proses" berubah jadi badge lain (sesuai Config.ai)
- User tahu OCR sudah selesai
- Ini adalah correct behavior ✅

### Impact:
- **Severity:** NONE
- This is correct behavior ✅

---

## ⚠️ Potensi Issue #8: Race Condition - N8N Webhook Failure

### Scenario:
```php
// OcrNotaController::uploadTransfer() - Line 1023-1040
if ($response->successful()) {
    // ai_status already set, just broadcast
} else {
    // Rollback to original state
    $transaction->update($originalPaymentState);
    Storage::disk('public')->delete($path);
}
```

### Problem:
- Jika N8N webhook GAGAL, transaction di-rollback
- Tapi `ai_status` sudah di-set ke `'processing'` sebelumnya (line 960)
- Rollback tidak include `ai_status`

### Impact:
- **Severity:** MEDIUM
- Transaction stuck dengan `ai_status = 'processing'` tapi tidak ada N8N job
- User lihat "Sedang Diverifikasi AI" tapi tidak ada proses
- Stuck forever

### Recommendation:
```php
// ✅ FIX: Include ai_status in rollback
$originalPaymentState = [
    'bukti_transfer' => $transaction->bukti_transfer,
    'status'         => $transaction->status,
    'ai_status'      => $transaction->ai_status, // ← Add this
    'expected_total' => $transaction->expected_total,
    'paid_by'        => $transaction->paid_by,
    'paid_at'        => $transaction->paid_at,
];
```

**This is CRITICAL!** ⚠️

---

## ⚠️ Potensi Issue #9: Pengajuan Upload Invoice - ai_status

### Scenario:
```php
// OcrNotaController::uploadPengajuanInvoice() - Line 400+
$transaction->update([
    'invoice_file_path' => $path,
    'amount'            => $totalTransaksi,
    // ... other fields ...
    // ❌ ai_status NOT SET
]);
```

### Analysis:
- Pengajuan invoice upload tidak perlu AI verification
- `ai_status` should remain `null`
- This is correct ✅

### Impact:
- **Severity:** NONE
- No issue here ✅

---

## ⚠️ Potensi Issue #10: Transaction Model - Status Label Cache

### Scenario:
```php
// Transaction.php - getStatusLabelAttribute()
// This is an accessor, called every time $transaction->status_label is accessed
```

### Problem:
- Accessor dipanggil setiap kali akses `status_label`
- Bisa ada N+1 query untuk `branchDebts` check
- Performance issue untuk list view dengan banyak transaksi

### Analysis:
**Already optimized:**
```php
// Line 280-287
$hasPendingDebt = array_key_exists('has_branch_with_debt', $this->attributes)
    ? (bool) $this->attributes['has_branch_with_debt']
    : (/* fallback query */);
```
- Sudah ada optimization dengan `withExists`
- No N+1 query jika di-load dengan `withExists`
- This is good ✅

### Impact:
- **Severity:** NONE
- Already optimized ✅

---

## 🎯 Summary Potensi Issues

| # | Issue | Severity | Status | Action Required |
|---|-------|----------|--------|-----------------|
| 1 | Upload Cash - ai_status not set | LOW | ⚠️ | Optional fix |
| 2 | Pembelian ai_status = null | NONE | ✅ | No action |
| 3 | Reset - No notification | LOW | ✅ | Intended behavior |
| 4 | N8N Backward compatibility | NONE | ✅ | No action |
| 5 | Badge after payment | NONE | ✅ | No action |
| 6 | Reset - Activity log misleading | LOW | ⚠️ | Recommended fix |
| 7 | Badge ai_status completed | NONE | ✅ | No action |
| 8 | **N8N Webhook failure rollback** | **MEDIUM** | ⚠️ | **CRITICAL FIX** |
| 9 | Pengajuan ai_status | NONE | ✅ | No action |
| 10 | Status label performance | NONE | ✅ | No action |

---

## 🚨 CRITICAL FIXES REQUIRED

### Fix #1: N8N Webhook Failure Rollback (CRITICAL)

**File:** `app/Http/Controllers/Api/V1/OcrNotaController.php`  
**Line:** ~950

```php
// ✅ BEFORE creating $originalPaymentState
$originalPaymentState = [
    'bukti_transfer' => $transaction->bukti_transfer,
    'status'         => $transaction->status,
    'ai_status'      => $transaction->ai_status, // ← ADD THIS
    'expected_total' => $transaction->expected_total,
    'paid_by'        => $transaction->paid_by,
    'paid_at'        => $transaction->paid_at,
];
```

**Why Critical:**
- Prevents stuck transactions if N8N fails
- User won't see "Sedang Diverifikasi AI" forever
- Can retry upload

---

## 📝 RECOMMENDED FIXES

### Fix #2: Upload Cash - Set ai_status = null

**File:** `app/Http/Controllers/Api/V1/OcrNotaController.php`  
**Line:** ~656

```php
$transaction->update([
    'foto_penyerahan' => $path,
    'status'          => $finalStatus,
    'ai_status'       => null, // ← ADD THIS
    'paid_by'         => auth()->id(),
    'paid_at'         => now(),
]);
```

### Fix #3: Reset - Better Activity Log

**File:** `app/Http/Controllers/TransactionController.php`  
**Line:** ~895

```php
if ($newStatus === 'pending') {
    $actionLabel = 'reset';
    $description = "Reset status Transaksi " . $transaction->invoice_number . " ke Pending";
} elseif ($newStatus === 'rejected') {
    $actionLabel = 'reject';
    $description = "Menolak status Transaksi...";
} else {
    $actionLabel = 'approve';
    $description = "Menyetujui status Transaksi...";
}
```

---

## ✅ Conclusion

**Overall Assessment:** 
- 1 CRITICAL issue found (N8N rollback)
- 2 RECOMMENDED improvements
- 7 items are correct behavior

**Action Plan:**
1. ⚠️ **MUST FIX:** N8N webhook failure rollback (Issue #8)
2. 📝 **SHOULD FIX:** Upload cash ai_status (Issue #1)
3. 📝 **SHOULD FIX:** Reset activity log (Issue #6)
4. ✅ **NO ACTION:** Other items are correct

**Risk Level After Fixes:** **LOW**

---

**Last Updated:** 21 Mei 2026  
**Analyst:** Kiro AI  
**Status:** ⚠️ CRITICAL FIX REQUIRED

