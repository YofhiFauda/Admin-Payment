# OCR NOTA KONTAN - API Documentation
**Version 4.5 | Laravel + n8n + Gemini AI**
*Sistem OCR Nota Material Bangunan dengan 3-Layer Security & Integrasi Pembayaran*

Base URL: `http://localhost:8000`
Format: `multipart/form-data`, `JSON`
Updated: Maret 2026

---

## 1. Overview & Arsitektur
Sistem OCR Nota Kontan menggabungkan Laravel sebagai backend API dan n8n sebagai automation engine. Setiap nota yang di-upload melewati 3 layer verifikasi sebelum data di-auto-fill. Selain itu, terdapat 2 flow pembayaran (CASH dan TRANSFER) yang divalidasi oleh AI.

### 1.1 Alur Sistem Utama
| # | Flow | Trigger | Endpoint | Hasil Akhir |
|---|---|---|---|---|
| 1 | Upload Nota | POST | `/api/v1/nota/upload` | `pending` (Auto-fill) / `auto-reject` |
| 2a | CASH - Upload | POST | `/api/v1/payment/cash/upload` | `Menunggu Konfirmasi Teknisi` |
| 2b | CASH - Konfirmasi | POST | `/api/v1/payment/cash/konfirmasi` | `completed` / `rejected` |
| 3 | TRANSFER - Upload | POST | `/api/v1/payment/transfer/upload` | `completed` (MATCH) / `flagged` (MISMATCH) |

### 1.2 Status Transaksi (Normalized)
Sistem menggunakan key status berikut (case-insensitive di database, namun disarankan lowercase):
- **pending**: OCR selesai, menunggu submit/approval awal.
- **waiting_payment**: Menunggu admin meng-upload bukti bayar.
- **auto-reject**: Ditolak otomatis oleh Layer 1 (Duplikat) atau Layer 2 (Tanggal Telat > 2 hari).
- **flagged**: Terdeteksi selisih nominal pada bukti transfer.
- **approved**: Disetujui Admin/Atasan, menunggu konfirmasi final Owner (untuk ≥ Rp 1 Jt).
- **completed**: Transaksi selesai dan pembayaran terverifikasi.
- **rejected**: Ditolak secara manual oleh Admin/Atasan/Owner.

---

## 2. Authentication
Endpoint callback/webhook dari n8n dilindungi middleware `N8nSecretMiddleware`.

### 2.1 Endpoint Publik / Frontend
- `POST /api/v1/nota/upload`
- `POST /api/v1/payment/cash/upload`
- `POST /api/v1/payment/transfer/upload`
- `GET /api/v1/transaksi/{id}`
- `GET /api/ai/auto-fill/status/{uploadId}` (Polling)

### 2.2 Endpoint N8N Callback (`X-SECRET` Required)
WAJIB menggunakan header `X-SECRET` sesuai value `.env.N8N_SECRET`.
- `POST /api/ai/auto-fill`
- `POST /api/pembayaran/update-status`

---

## 3. Flow 1: Upload & OCR (3 Layer Verification)

### `POST /api/v1/nota/upload`
**Payload:**
- `foto_nota` (file, required)
- `expected_nominal` (numeric, optional)

**Response:** `202 Accepted`
```json
{
  "success": true,
  "message": "Nota sedang diproses (3 Layer Verification)",
  "upload_id": "UPL-20260312-00001",
  "status": "Diproses"
}
```

### `GET /api/ai/auto-fill/status/{uploadId}`
Digunakan untuk polling status OCR dari frontend.
**Response (Success):**
```json
{
  "status": "completed",
  "data": {
    "customer": "Toko Bangunan Jaya",
    "amount": 150000,
    "items": [...],
    "confidence": 85
  }
}
```
**Response (Auto-Reject):**
```json
{
  "status": "auto-reject",
  "message": "Nota ditolak otomatis: Duplikat dengan UPL-..."
}
```

---

## 4. Administrative Endpoints (New in v4.5)
Endpoint ini digunakan untuk memotong jalur (bypass) jika terjadi kesalahan deteksi AI.

### `POST /transactions/{id}/override`
Status: **Login Required (auth:web)**
Digunakan untuk menghidupkan kembali nota yang terkena `auto-reject`.
- `override_reason` (string, min 5 chars)
- **Effect**: Status berubah dari `auto-reject` ke `waiting_payment`.

### `POST /transactions/{id}/force-approve`
Status: **Login Required (auth:web)**
Digunakan untuk menyetujui transaksi yang terkena `flagged` (selisih nominal).
- `force_approve_reason` (string, min 5 chars)
- **Effect**: Status berubah dari `flagged` ke `completed`.

---

## 5. Webhook Specifications (For n8n Developers)

### `POST /api/ai/auto-fill`
**Payload:**
- `upload_id` (string)
- `status` (`success`, `failed`, `auto_reject`, `low_confidence`)
- `vendor`, `tanggal`, `amount`, `items` (jika success)
- `reason`, `stage` (jika failed/auto_reject)

### `POST /api/pembayaran/update-status`
**Payload:**
- `upload_id` (string)
- `payment_method` (`CASH` / `TRANSFER`)
- `status` (`Selesai`, `Flagged - Selisih Nominal`, `Menunggu Konfirmasi Teknisi`)
- `actual_total`, `expected_total`, `selisih` (khusus Transfer)

---
*End of Documentation v4.5*
