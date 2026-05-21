# Kebijakan Notifikasi Telegram

Dokumen ini menjelaskan perubahan kebijakan pengiriman notifikasi Telegram pada aplikasi Admin Payment.

Perubahan ini dibuat untuk memisahkan event operasional management dari notifikasi pribadi teknisi, sekaligus menghapus ketergantungan transaksi internal terhadap akun Telegram pribadi owner/admin/atasan.

## Tujuan Perubahan

- Admin, Atasan, dan Owner tidak wajib mendaftarkan Telegram pribadi untuk membuat atau memproses transaksi.
- Alert operasional management tidak dikirim ke Telegram pribadi owner/admin/atasan.
- Channel management hanya dipakai untuk event management yang perlu terlihat bersama.
- Telegram pribadi teknisi tetap dipakai untuk update transaksi teknisi dan tombol konfirmasi cash.
- Cash internal tidak lagi pending karena menunggu konfirmasi Telegram teknisi.

## Kebijakan Saat Ini

### Channel Management

Channel management hanya menerima event berikut:

| Event | Method | Keterangan |
| --- | --- | --- |
| Flagged transfer | `notifyFlaggedTransaction()` | Selisih nominal atau transaksi perlu review khusus |
| Auto-reject | `notifyAutoReject()` | Transaksi ditolak otomatis oleh sistem |
| Force approve | `notifyForceApproved()` | Approval management untuk melanjutkan transaksi |
| Waiting owner approval | `notifyWaitingOwnerApproval()` | Ringkasan transaksi yang menunggu approval owner |

Channel management tidak dipakai untuk:

- Cash internal.
- Transfer complete teknisi.
- Payment processing teknisi.
- Transaksi ditolak untuk teknisi.
- Force approve selesai untuk teknisi.
- Fallback teknisi yang belum mendaftar Telegram.
- Price anomaly, sesuai keputusan terakhir channel dibatasi hanya untuk empat event management di atas.

### Telegram Pribadi Teknisi

Telegram pribadi teknisi tetap dipakai untuk:

| Event | Method | Keterangan |
| --- | --- | --- |
| Cash siap diambil | `notifyPaymentCash()` | Mengirim tombol `Terima` dan `Tolak` ke teknisi |
| Transfer selesai | `notifyPaymentComplete()` | Mengirim bukti/status transfer selesai |
| Force approve selesai | `notifyForceApprovedToTechnician()` | Memberi tahu teknisi bahwa otorisasi selesai |
| Transaksi ditolak | `notifyTransactionRejected()` | Mengirim alasan penolakan ke teknisi |
| Payment processing | `notifyPaymentProcessing()` | Memberi tahu teknisi bahwa pembayaran sedang diproses |
| Waiting owner approval | `notifyWaitingOwnerApproval()` | Memberi tahu teknisi bahwa transaksi menunggu owner |

Jika teknisi belum memiliki `telegram_chat_id`, sistem mencatat fallback ke log `ai_autofill`, tetapi tidak mengirim fallback ke channel management agar channel tidak berisi noise teknisi.

### Telegram Pribadi Owner/Admin/Atasan

Telegram pribadi owner/admin/atasan tidak dipakai untuk alert operasional otomatis.

Artinya:

- Owner tidak perlu `telegram_chat_id` agar alert management tetap berjalan.
- Admin dan Atasan tidak wajib Telegram pribadi untuk memproses cash/transfer.
- Alert management dikirim ke channel management, bukan ke private chat.

Broadcast manual berdasarkan role masih tersedia melalui method `broadcastToAllStaff()` dan `broadcastToRole()`, tetapi itu bukan bagian dari flow alert otomatis transaksi.

## Perubahan Flow Pembayaran

### Cash Teknisi

Cash untuk transaksi milik teknisi tetap memerlukan Telegram pribadi teknisi karena ada tombol konfirmasi penerimaan cash.

Kondisi:

- Jika submitter adalah teknisi dan belum memiliki `telegram_chat_id`, upload cash diblokir dengan response 422.
- Jika submitter adalah teknisi dan sudah memiliki `telegram_chat_id`, status menjadi `pending_technician` dan bot mengirim tombol konfirmasi ke private chat teknisi.

Kode utama:

- `App\Http\Controllers\Api\V1\OcrNotaController::submitterRequiresCashTelegram()`
- `App\Http\Controllers\Api\V1\OcrNotaController::uploadCash()`
- `App\Services\Telegram\TelegramBotService::notifyPaymentCash()`

### Cash Internal Admin/Atasan/Owner

Cash untuk submitter internal tidak wajib Telegram pribadi.

Kondisi:

- Submitter admin/atasan/owner tidak perlu `telegram_chat_id`.
- Upload cash tidak diblokir oleh validasi Telegram.
- Status transaksi langsung `completed`.
- Tidak ada pesan Telegram pribadi dan tidak ada pesan channel untuk cash internal.

### Transfer

Upload transfer tidak lagi diblokir oleh ketiadaan `telegram_chat_id`.

Kondisi:

- Teknisi tanpa Telegram tetap bisa diproses transfer.
- Admin/Atasan/Owner juga tidak perlu Telegram pribadi.
- Verifikasi transfer tetap mengikuti flow AI/n8n.

## Price Anomaly

Sesuai keputusan terakhir, price anomaly tidak dikirim ke channel management dan tidak dikirim ke Telegram pribadi owner.

Saat ini `notifyPriceAnomaly()` hanya mencatat log bahwa notifikasi dilewati oleh channel policy.

Jika di kemudian hari price anomaly ingin dianggap event management, rekomendasi teknisnya adalah mengirim ke channel management saja, bukan ke Telegram pribadi owner.

## Konfigurasi

Pastikan environment berikut tersedia jika channel management digunakan:

```env
TELEGRAM_BOT_TOKEN=...
TELEGRAM_GROUP_MONITORING_ID=...
```

`TELEGRAM_GROUP_MONITORING_ID` harus berisi chat ID channel/group management. Untuk supergroup/channel biasanya formatnya diawali `-100`.

## Testing Manual

### Test Channel Management

Gunakan command berikut untuk memastikan bot bisa mengirim ke channel management:

```powershell
docker compose exec -T app php artisan tinker --execute="app(\App\Services\Telegram\TelegramBotService::class)->sendToMonitoringGroup('[WAITING OWNER APPROVAL] TEST-CHANNEL - Simulasi event management masuk channel.')"
```

Ekspektasi:

- Pesan muncul di channel management.
- Pesan tidak masuk ke Telegram pribadi owner/admin/atasan.

### Test Private Teknisi

Gunakan command berikut untuk memastikan bot bisa mengirim ke private chat teknisi:

```powershell
docker compose exec -T app php artisan tinker --execute="app(\App\Services\Telegram\TelegramBotService::class)->sendMessage('ISI_CHAT_ID_TEKNISI', 'TEST PRIVATE TEKNISI - Notifikasi teknisi masuk chat pribadi teknisi.')"
```

Ekspektasi:

- Pesan muncul di Telegram pribadi teknisi sesuai `chat_id`.
- Pesan tidak muncul di channel management.

## File Terkait

- `app/Http/Controllers/Api/V1/OcrNotaController.php`
- `app/Services/Telegram/TelegramBotService.php`
- `tests/Feature/TelegramNotificationPolicyTest.php`

## Catatan Testing Otomatis

Test policy tersedia di `tests/Feature/TelegramNotificationPolicyTest.php`.

Saat dokumen ini dibuat, lint PHP sudah berhasil untuk file yang diubah:

```powershell
php -l app\Http\Controllers\Api\V1\OcrNotaController.php
php -l app\Services\Telegram\TelegramBotService.php
php -l tests\Feature\TelegramNotificationPolicyTest.php
```

PHPUnit penuh perlu dijalankan di environment yang memiliki dependency dev dan database testing MySQL yang siap, karena migration project menggunakan sintaks MySQL-specific seperti `ALTER TABLE ... MODIFY`.
