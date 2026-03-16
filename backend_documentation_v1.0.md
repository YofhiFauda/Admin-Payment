# Back-End (BE) Documentation
**Version 4.5 | Laravel 12 + MySQL + Redis + Reverb**
*Dokumentasi Logika Bisnis, Arsitektur, Keamanan, dan Infrastruktur*

---

## 1. Arsitektur Sistem & Data Flow

Sistem ini dirancang sebagai mesin pemrosesan nota otomatis dengan alur kerja asinkron yang sangat terintegrasi:
- **Backend**: Laravel 12 (PHP 8.4).
- **Automation Logic**: n8n (Self-hosted) untuk koordinasi OCR Layer 1-3.
- **AI Engine**: Google Gemini AI (Pro 1.5/2.0) via n8n.
- **Queue/Cache**: Redis 7.2 untuk Job processing, Session, dan Atomic ID Generation.
- **Real-time**: Laravel Reverb (WebSocket) untuk UI updates & Notifications.
- **Audit**: Database-driven audit trail for financial accountability.

### 1.1 Business Logic Flow (OCR Nota)
1. **Frontend**: User upload image ke `/api/v1/nota/upload`.
2. **Backend**: 
   - Simpan record awal (status: `Antrean`).
   - Generate `upload_id` via `IdGeneratorService` (Redis-based).
   - Dispatch `OcrProcessingJob`.
3. **Queue (Redis)**: Job mengirim request ke **n8n Webhook** dengan `X-SECRET` security header.
4. **n8n Workflow**:
   - **Layer 1 (Security)**: Pengecekan duplikasi file (MD5 Hash).
   - **Layer 2 (Logic)**: Validasi tanggal nota (max 2 hari ke belakang).
   - **Layer 3 (AI)**: Gemini Pro mengekstrak data JSON (Vendor, Items, Total, Date).
5. **Callback**: n8n mengirim hasil ke `/api/ai/auto-fill`.
6. **Backend**: Update status ke `pending`, broadcast via Reverb, dan kirim notifikasi Telegram jika terjadi `auto-reject`.

---

## 2. Skema Database & Relasi

### 2.1 Entity Utama
| Tabel | Fungsi | Key Fields |
|---|---|---|
| `users` | Role-based Access Control | `role` (owner, admin, atasan, teknisi), `telegram_chat_id` |
| `transactions` | Pusat data transaksi | `type`, `status`, `ai_status`, `amount`, `selisih`, `ocr_result` |
| `branches` | Master data lokasi | `name`, `code` |
| `activity_logs` | Audit trail sistem | `user_id`, `action`, `description` |
| `payment_discrepancy_audits` | Laporan kebocoran dana | `expected_total`, `actual_total`, `selisih`, `resolution` |

### 2.2 Relasi Kunci
- **Transaction ↔ User**: `submitted_by` (Teknisi) dan `reviewed_by` (Admin/Owner).
- **Transaction ↔ Branch**: Many-to-Many via `transaction_branches` dengan alokasi (nominal/persen).
- **Transaction ↔ Audit**: One-to-One untuk transaksi yang terkena `flagged` nominal mismatch.

---

## 3. Keamanan & Otorisasi

### 3.1 Authentication Strategy
1. **Stateful (Web)**: Laravel Session untuk Dashboard.
2. **Stateless (API/Webhook)**: `N8nSecretMiddleware` memverifikasi header `X-SECRET` pada callback dari n8n.

### 3.2 Role-Based Access Control (RBAC)
- **Teknisi**: Input transaksi, melihat history sendiri, konfirmasi cash via Telegram.
- **Admin**: Verifikasi nota, input data, override auto-reject. Approval limit: < Rp 1.000.000.
- **Atasan**: Menyetujui transaksi yang ditugaskan (assigned) padanya.
- **Owner**: Final approval (wajib untuk ≥ Rp 1 Jt), force approve flagged transactions, manage all data.

---

## 4. Payment Verification Logic (Flow 2 & 3)

### 4.1 Cash Pathway
- Admin upload foto penyerahan uang.
- Sistem mengirim pesan Telegram ke Teknisi dengan tombol **[✅ Terima]** dan **[❌ Tolak]**.
- `TelegramWebhookController` menerima callback dan mengupdate status transaksi menjadi `completed` atau `rejected`.

### 4.2 Transfer Pathway (AI Verification)
- Admin upload bukti transfer (struk m-banking).
- Sistem memicu n8n untuk OCR bukti transfer (Layer 4).
- Gemini membandingkan nominal struk vs nominal yang diajukan.
- Jika **Match**: Status otomatis `completed` (atau `approved` jika ≥ 1 Jt).
- Jika **Mismatch**: Status menjadi `flagged`, memicu notifikasi alert ke Owner via Telegram.

---

## 5. Integrasi Telegram Service

`TelegramBotService` menangani semua komunikasi outbound:
- **Interactive Buttons**: Menggunakan `inline_keyboard` untuk konfirmasi cash.
- **Broadcast**: Fitur kirim pesan massal ke role tertentu.
- **Alert System**: Alert otomatis untuk `auto-reject`, `flagged`, dan `force-approve`.

---

## 6. Maintenance & Monitoring
- **Laravel Horizon**: Pantau antrean OCR. Jika macet, cek konektivitas n8n atau rate limit Gemini.
- **Redis Cache**: Digunakan untuk menyimpan status sementara OCR sebelum user melakukan submit form.
- **Atomic IDs**: `IdGeneratorService` menjamin tidak ada duplikasi nomor invoice meskipun banyak user upload secara bersamaan.

---
*Dokumentasi BE v4.5 | WHUSNET Admin Payment*
