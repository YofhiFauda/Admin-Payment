# OCR NOTA KONTAN - API Documentation
**Version 4.5 | Laravel + n8n + Gemini AI**
*Sistem OCR Nota Material Bangunan dengan 4-Layer Security & Integrasi Pembayaran*

Base URL: `http://localhost:8000`
Format: `multipart/form-data`, `JSON`
Updated: Maret 2026

---

## 1. Overview & Arsitektur
Sistem OCR Nota Kontan menggabungkan Laravel sebagai backend API dan n8n sebagai automation engine. Setiap nota yang di-upload melewati 3 layer verifikasi sebelum data di-auto-fill. Selain itu, terdapat 2 flow pembayaran (CASH dan TRANSFER) yang divalidasi oleh AI (Transfer) atau Teknisi via Telegram (Cash).

### 1.1 Alur Sistem Utama
| # | Flow | Trigger | Endpoint | Hasil Akhir |
|---|---|---|---|---|
| 1 | Upload Nota | POST | `/api/v1/nota/upload` | `pending` (Auto-fill) / `auto-reject` |
| 2a | CASH - Upload | POST | `/api/v1/payment/cash/upload` | `Menunggu Konfirmasi Teknisi` |
| 2b | CASH - Konfirmasi | POST | `/api/v1/payment/cash/konfirmasi` | `completed` / `rejected` |
| 3a | TRANSFER - Upload | POST | `/api/v1/payment/transfer/upload` | `Sedang Diverifikasi AI` |
| 3b | TRANSFER - Callback | POST | `/api/payment/verify` | `completed` (MATCH) / `flagged` (MISMATCH) |

### 1.2 Status Transaksi (Standardized)
- **pending**: OCR selesai, menunggu submit data oleh user.
- **waiting_payment**: Disetujui, menunggu admin meng-upload bukti bayar.
- **auto-reject**: Ditolak otomatis oleh Layer 1 (Duplikat) atau Layer 2 (Tanggal Telat).
- **flagged**: Selisih nominal pada bukti transfer oleh AI Layer 4.
- **approved**: Menunggu konfirmasi final Owner (untuk ≥ Rp 1 Jt).
- **completed**: Transaksi selesai & pembayaran terverifikasi.
- **rejected**: Ditolak manual oleh Admin/Owner.
- **Menunggu Konfirmasi Teknisi**: Menunggu teknisi klik tombol di Telegram.
- **Sedang Diverifikasi AI**: Proses verifikasi struk transfer oleh Gemini.

---

## 2. Authentication

### 2.1 Web-Session Auth
Digunakan oleh Admin/Owner saat memanggil API dari dashboard:
- Header: `X-CSRF-TOKEN` (Session-based).
- Middleware: `auth:web`.

### 2.2 n8n Callback Auth
Digunakan oleh n8n workflow:
- Header: `X-SECRET` (Cocokkan dengan `.env.N8N_SECRET`).
- Middleware: `N8nSecretMiddleware`.

---

## 3. Endpoints Detail

### 3.1 Flow 1: Nota Processing
- **`POST /api/v1/nota/upload`**: Upload nota awal.
- **`GET /api/ai/auto-fill/status/{uploadId}`**: Polling status OCR.
- **`POST /api/ai/auto-fill`**: (Callback n8n) Menyimpan hasil ekstraksi Gemini ke Redis.

### 3.2 Flow 2: Cash Payment
- **`POST /api/v1/payment/cash/upload`**: Upload bukti penyerahan. Memicu notifikasi Telegram ke Teknisi.
- **`POST /api/v1/payment/cash/konfirmasi`**: Update status manual jika Telegram bot bermasalah.

### 3.3 Flow 3: Transfer Payment
- **`POST /api/v1/payment/transfer/upload`**: Upload struk m-banking. Memicu n8n Layer 4.
- **`POST /api/payment/verify`**: (Callback n8n) Menerima hasil perbandingan nominal AI.

### 3.4 Administrative & Bypass
- **`POST /transactions/{id}/override`**: Menghidupkan kembali `auto-reject`.
- **`POST /transactions/{id}/force-approve`**: Menyetujui transaksi `flagged`.
- **`GET /api/admin/ocr-status`**: Statistik antrian Redis & Rate Limit Gemini.

---

## 4. Webhook Telegram
- **`POST /api/telegram/webhook`**: Menerima callback dari bot Telegram (klik tombol konfirmasi cash).

---

## 5. Master Data
- **`POST /api/v1/banks`**: Tambah bank/e-wallet baru. Payload: `{"name": "BCA"}`.

---
*End of Documentation v4.5 | WHUSNET API*
