# Back-End (BE) Documentation
**Version 1.0 | Laravel 12 + MySQL + Redis + Reverb**
*Dokumentasi Logika Bisnis, Arsitektur, Keamanan, dan Infrastruktur*

---

## 1. Arsitektur Sistem & Data Flow

Sistem ini dirancang sebagai mesin pemrosesan nota otomatis dengan alur kerja asinkron:
- **Backend**: Laravel 12 (PHP 8.4).
- **Automation Logic**: n8n (untuk koordinasi OCR Layer 1-3).
- **AI Engine**: Google Gemini AI (via n8n).
- **Queue/Cache**: Redis (untuk Job processing dan Session management).
- **Real-time**: Laravel Reverb (WebSocket server).

### 1.1 Business Logic Flow (OCR Nota)
1. **Frontend**: Upload image ke `/api/v1/nota/upload`.
2. **Backend**: Simpan record transaksi awal (status: `Antrean`), dispatch `OcrProcessingJob`.
3. **Queue**: Job mengirim request ke **n8n Webhook**.
4. **n8n**: Menjalankan 3-Layer Verification (Duplicate check -> Logical check -> Gemini AI).
5. **Callback**: n8n mengirim hasil ke `/api/ai/auto-fill`.
6. **Backend**: Update database, kirim Notifikasi, dan Broadcast event via Reverb.

---

## 2. Skema Database (ERD Highlights)

### 2.1 Tabel Utama
| Tabel | Deskripsi | Relasi Utama |
|---|---|---|
| `users` | Akun pengguna & role sistem | `belongsTo(branches)` |
| `branches` | Daftar cabang operasional | `hasMany(users)`, `hasMany(transactions)` |
| `transactions` | Pusat data pengeluaran/reimbursement | `belongsTo(users)`, `belongsToMany(branches)` |
| `transaction_branches` | Pivot untuk alokasi dana per cabang | Tabel penghubung |
| `activity_logs` | Audit trail aksi administratif | `belongsTo(users)` |
| `banks` | Master data bank & e-wallet | Digunakan di profil user/transaksi |
| `payment_discrepancy_audits` | Log audit selisih nominal AI | `belongsTo(transactions)` |

---

## 3. Keamanan (Auth & Authz)

### 3.1 Authentication
1. **Web (Standard)**: Menggunakan Laravel Session (`guards.web`). Login via email/password.
2. **N8N Callback**: Menggunakan `N8nSecretMiddleware`. Wajib menyertakan header `X-SECRET` yang dicocokkan dengan `.env.N8N_SECRET`.

### 3.2 Authorization (RBAC)
Role yang tersedia (disimpan di kolom `role` pada table `users`):
- **teknisi**: Hanya bisa Input & Melihat datanya sendiri.
- **admin**: Verifikasi nota & Kelola data master.
- **atasan**: Approval transaksi level menengah.
- **owner**: Approval final (wajib untuk nominal ≥ Rp 1 Jt).

---

## 4. Konfigurasi Environment

Variabel penting di file `.env`:
- `N8N_SECRET`: Token keamanan untuk integrasi n8n.
- `GEMINI_RPM_LIMIT`: Batasan request per menit ke AI (default: 12 untuk Free Tier).
- `REVERB_HOST`: Nama host untuk server WebSocket (digunakan oleh Laravel Reverb).
- `QUEUE_CONNECTION`: Disarankan diisi `redis` untuk performa asinkron yang optimal.
- `OCR_MAX_QUEUE_SIZE`: Kapasitas maksimum antrean OCR sebelum sistem menolak upload baru.

---

## 5. Deployment & Infrastructure
- **Server**: Docker (opsional) atau PHP 8.4 Server.
- **Background Worker**: `php artisan horizon` (untuk memantau Redis queue).
- **WebSocket Server**: `php artisan reverb:start`.
- **Database Migrations**: `php artisan migrate` (Wajib dijalankan saat update versi).

---
*Dokumentasi BE v1.0*
