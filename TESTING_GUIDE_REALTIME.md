# 🧪 Real-Time Testing Guide

**Tanggal:** 8 Mei 2026  
**Purpose:** Panduan lengkap untuk testing fitur real-time

---

## 📋 Pre-Testing Checklist

Sebelum mulai testing, pastikan:

- [ ] `BROADCAST_CONNECTION=reverb` di .env
- [ ] Config cache sudah di-clear dan rebuild
- [ ] Reverb server running
- [ ] Queue worker running
- [ ] Assets sudah di-build

**Quick Check:**
```bash
# Check broadcasting driver
php artisan tinker --execute="echo config('broadcasting.default');"
# Expected: reverb

# Check Reverb server
docker ps | grep reverb
# Expected: Container running

# Check assets
ls public/build/manifest.json
# Expected: File exists
```

---

## 🎭 Test Scenario 1: Transaction Created (Teknisi → Owner)

### Objective:
Verify bahwa Owner melihat transaksi baru secara real-time ketika Teknisi membuat transaksi.

### Setup:
1. **Browser 1:** Login sebagai **Teknisi** (user: teknisi1)
2. **Browser 2:** Login sebagai **Owner** (user: owner)

### Steps:

#### Browser 2 (Owner):
1. Buka halaman `/transactions`
2. Buka Developer Tools (F12)
3. Buka tab Console
4. Pastikan muncul log:
   ```
   📡 [REALTIME] Echo listener initialized on channel: transactions
   ```
5. Jika tidak muncul, refresh page (Ctrl+R)

#### Browser 1 (Teknisi):
1. Buka halaman `/transactions/create`
2. Pilih "Pengajuan"
3. Isi form dengan data:
   - Kategori: Operasional
   - Cabang: Cabang A
   - Item: Test Item
   - Jumlah: 100000
   - Keterangan: Testing Real-Time
4. Upload foto (optional)
5. Klik "Submit"

### Expected Results:

#### Browser 2 (Owner) - Console:
```
🆕 [REALTIME] Transaction Created: {
  id: 123,
  invoice_number: "PL-20260508-00001",
  category: "Operasional",
  ...
}
```

#### Browser 2 (Owner) - UI:
- Grid auto-refresh dalam < 1 detik
- Transaksi baru muncul di top of list
- Badge "Pending" berwarna kuning
- **TIDAK PERLU MANUAL REFRESH!**

#### Laravel Log:
```bash
tail -f storage/logs/laravel.log | grep BROADCAST
```

Expected:
```
[2026-05-08 19:00:00] local.INFO: 🔔 [BROADCAST] TransactionCreated event constructed {"id":123,"invoice_number":"PL-20260508-00001","broadcast_driver":"reverb"}
```

### ✅ Pass Criteria:
- [ ] Console log muncul
- [ ] Grid auto-refresh
- [ ] Transaksi baru muncul
- [ ] No manual refresh needed
- [ ] Laravel log menunjukkan event di-broadcast

---

## 🎭 Test Scenario 2: Transaction Updated (Owner → Teknisi)

### Objective:
Verify bahwa Teknisi menerima notifikasi real-time ketika Owner approve/reject transaksi mereka.

### Setup:
1. **Browser 1:** Login sebagai **Owner**
2. **Browser 2:** Login sebagai **Teknisi** (yang buat transaksi)

### Steps:

#### Browser 2 (Teknisi):
1. Buka halaman `/transactions`
2. Buka Developer Tools → Console
3. Pastikan muncul log:
   ```
   📡 [REALTIME] Echo listener initialized on channel: transactions.{user_id}
   ```

#### Browser 1 (Owner):
1. Buka halaman `/transactions`
2. Cari transaksi yang dibuat oleh Teknisi
3. Klik "Detail"
4. Klik "Approve" atau "Reject"

### Expected Results:

#### Browser 2 (Teknisi) - Console:
```
🔄 [REALTIME] Transaction Updated: {
  id: 123,
  invoice_number: "PL-20260508-00001",
  status: "approved",
  ...
}
```

#### Browser 2 (Teknisi) - UI:
- Grid auto-refresh
- Status badge berubah dari "Pending" ke "Approved" (hijau) atau "Rejected" (merah)
- Notifikasi toast muncul (optional)

#### Laravel Log:
```
[2026-05-08 19:05:00] local.INFO: 🔔 [BROADCAST] TransactionUpdated event constructed {"id":123,"invoice_number":"PL-20260508-00001","broadcast_driver":"reverb"}
```

### ✅ Pass Criteria:
- [ ] Console log muncul
- [ ] Grid auto-refresh
- [ ] Status badge berubah warna
- [ ] No manual refresh needed

---

## 🎭 Test Scenario 3: Transaction Deleted (Owner → Atasan)

### Objective:
Verify bahwa Atasan melihat transaksi hilang secara real-time ketika Owner delete transaksi.

### Setup:
1. **Browser 1:** Login sebagai **Owner**
2. **Browser 2:** Login sebagai **Atasan**

### Steps:

#### Browser 2 (Atasan):
1. Buka halaman `/transactions`
2. Buka Developer Tools → Console
3. Pastikan muncul log:
   ```
   📡 [REALTIME] Echo listener initialized on channel: transactions
   ```

#### Browser 1 (Owner):
1. Buka halaman `/transactions`
2. Cari transaksi yang ingin dihapus
3. Klik "Delete" (icon trash)
4. Confirm delete

### Expected Results:

#### Browser 2 (Atasan) - Console:
```
🗑️ [REALTIME] Transaction Deleted: {
  id: 123,
  invoice_number: "PL-20260508-00001"
}
```

#### Browser 2 (Atasan) - UI:
- Grid auto-refresh
- Transaksi hilang dari list
- Row removed dengan smooth animation (optional)

#### Laravel Log:
```
[2026-05-08 19:10:00] local.INFO: 🔔 [BROADCAST] TransactionDeleted event constructed {"id":123,"invoice_number":"PL-20260508-00001","broadcast_driver":"reverb"}
```

### ✅ Pass Criteria:
- [ ] Console log muncul
- [ ] Grid auto-refresh
- [ ] Transaksi hilang dari list
- [ ] No manual refresh needed

---

## 🎭 Test Scenario 4: Multiple Users (Stress Test)

### Objective:
Verify bahwa real-time bekerja dengan baik ketika banyak user online bersamaan.

### Setup:
1. **Browser 1:** Login sebagai **Teknisi 1**
2. **Browser 2:** Login sebagai **Teknisi 2**
3. **Browser 3:** Login sebagai **Atasan**
4. **Browser 4:** Login sebagai **Owner**

### Steps:

#### All Browsers:
1. Buka halaman `/transactions`
2. Buka Developer Tools → Console

#### Actions (Rapid Fire):
1. **Browser 1:** Create Pengajuan
2. **Browser 2:** Create Rembush
3. **Browser 3:** Create Pembelian
4. **Browser 4:** Approve transaksi pertama
5. **Browser 4:** Reject transaksi kedua
6. **Browser 4:** Delete transaksi ketiga

### Expected Results:

#### All Browsers:
- Semua event diterima dengan benar
- Grid auto-refresh untuk setiap event
- No race condition
- No duplicate events
- No missing events

### ✅ Pass Criteria:
- [ ] All events received
- [ ] Correct order maintained
- [ ] No errors in console
- [ ] No performance degradation

---

## 🔍 Debugging Guide

### Issue: "Echo listener not initialized"

**Symptom:** Console tidak menunjukkan log `📡 [REALTIME] Echo listener initialized`

**Check:**
1. Apakah `resources/js/transactions/main.js` memanggil `initRealtime()`?
2. Apakah assets sudah di-build? (`npm run build`)
3. Apakah Echo loaded? Check console: `typeof window.Echo`

**Fix:**
```bash
npm run build
php artisan view:clear
```

---

### Issue: "WebSocket connection failed"

**Symptom:** Console error: `WebSocket connection to 'ws://...' failed`

**Check:**
1. Apakah Reverb server running?
   ```bash
   docker ps | grep reverb
   ```
2. Apakah port 8081 accessible?
3. Apakah firewall blocking?

**Fix:**
```bash
docker restart whusnet-reverb
docker logs whusnet-reverb
```

---

### Issue: "403 Forbidden on /broadcasting/auth"

**Symptom:** Console error: `POST /broadcasting/auth 403`

**Check:**
1. Apakah user sudah login?
2. Apakah CSRF token valid?
3. Apakah channel authorization benar?

**Fix:**
Check `routes/channels.php` dan `storage/logs/laravel.log`

---

### Issue: "Event received but grid not refreshing"

**Symptom:** Console log muncul tapi UI tidak update

**Check:**
1. Apakah `isIndexPage()` return true?
   ```javascript
   console.log('Is Index Page:', isIndexPage());
   ```
2. Apakah `SearchEngine` loaded?
   ```javascript
   console.log('SearchEngine:', typeof SearchEngine);
   ```
3. Apakah ada error JavaScript?

**Fix:**
Check browser console untuk error

---

## 📊 Performance Benchmarks

### Expected Latency:

| Metric | Target | Acceptable | Poor |
|--------|--------|------------|------|
| **Event Broadcast** | < 50ms | < 100ms | > 200ms |
| **WebSocket Delivery** | < 50ms | < 100ms | > 200ms |
| **Grid Refresh** | < 300ms | < 500ms | > 1s |
| **Total End-to-End** | < 500ms | < 1s | > 2s |

### How to Measure:

```javascript
// Browser console
const start = performance.now();

// Wait for event...
// When event received in listener:
const end = performance.now();
console.log('Total Latency:', end - start, 'ms');
```

---

## 📝 Test Report Template

```markdown
# Real-Time Test Report

**Date:** [Date]
**Tester:** [Name]
**Environment:** [Local/Staging/Production]

## Test Results:

### Scenario 1: Transaction Created
- [ ] Pass
- [ ] Fail
- Notes: ___________

### Scenario 2: Transaction Updated
- [ ] Pass
- [ ] Fail
- Notes: ___________

### Scenario 3: Transaction Deleted
- [ ] Pass
- [ ] Fail
- Notes: ___________

### Scenario 4: Multiple Users
- [ ] Pass
- [ ] Fail
- Notes: ___________

## Performance:
- Average Latency: ___ ms
- Max Latency: ___ ms
- Min Latency: ___ ms

## Issues Found:
1. ___________
2. ___________

## Conclusion:
[ ] All tests passed
[ ] Some tests failed
[ ] Major issues found
```

---

## 🎯 Success Criteria

Real-time dianggap **WORKING** jika:

- ✅ All test scenarios pass
- ✅ Average latency < 1 second
- ✅ No manual refresh needed
- ✅ No errors in console
- ✅ No errors in Laravel log
- ✅ Works for all roles
- ✅ Works for all modules

---

## 📞 Support

Jika ada masalah saat testing:

1. Check **REALTIME_TROUBLESHOOTING.md**
2. Check Laravel logs: `storage/logs/laravel.log`
3. Check Reverb logs: `docker logs whusnet-reverb`
4. Check browser console for errors
5. Share logs dan screenshots

---

*Testing guide dibuat pada 8 Mei 2026*
