# ═══════════════════════════════════════════════════════════════════════════════
#  MANUAL TESTING GUIDE — OCR Nota Kontan v4.4
#  Semua Endpoint: Laravel API + n8n Webhook
#
#  Ganti variabel berikut sebelum testing:
#    LARAVEL  = https://your-domain.com   (domain Laravel production)
#    N8N      = https://your-n8n.com      (domain n8n)
#    SECRET   = mySuperSecretKey123       (X-SECRET header)
#    COOKIE   = laravel_session=xxx       (dari browser DevTools)
#    TOKEN    = csrf_token_value          (dari <meta name="csrf-token">)
# ═══════════════════════════════════════════════════════════════════════════════

# ┌─────────────────────────────────────┐
# │  VARIABEL — Isi sebelum testing     │
# └─────────────────────────────────────┘
OUTPUT="Output_testing_guide.txt"

echo "===== TESTING START $(date) =====" > $OUTPUT



LARAVEL="https://wealth-eating-timeline-fonts.trycloudflare.com"
N8N="https://ropier-raphael-unsalacious.ngrok-free.dev"
SECRET="mySuperSecretKey123"
# Laravel
COOKIE="eyJpdiI6IlNWR1RxU3JMaWJUL3VPaTZETWFjOFE9PSIsInZhbHVlIjoiczBZajRhKzVYVVpJbEtlMGN0bW5jd1BScEtSNnRIMnhuaUx2WklwMk9TS3FkNFJodCtFcEo2c3BCbk5XcmJhQXdlNklYQVhhMGo4OWt6MEtrVlI1UGE1UmxsamlvZzBPTVY3dXVrMWJGV1FoVUdLM0pzSTdEZi9aS2RNZkJLQXEiLCJtYWMiOiJjYTA0MjBiOTk1ODZkMGZmMDVkYjE4MGY0ZDc1YjY3OGI5YWY3YjM5MGY0NjA0NTRkMGMyMmY3NGYyMmE1ZmU3IiwidGFnIjoiIn0%3D"
# CSRF Token
TOKEN="OmlLq568MJhoAWNXBRDhF7lQOw5U4S0FW3KccMVx"


# ═══════════════════════════════════════════════════════════════════════════════
#
#  FLOW 1 — UPLOAD NOTA + OCR 3 LAYER
#
#  Urutan test:
#    1A → 1B → 1C → (tunggu n8n) → 1D atau 1E atau 1F
#
# ═══════════════════════════════════════════════════════════════════════════════


# ─── 1A. Upload Nota (Laravel → n8n) ─────────────────────────────────────────
#
# Endpoint : POST /api/v1/nota/upload
# Auth     : Tidak perlu (public)
# Type     : multipart/form-data
# Trigger  : Teknisi upload foto nota
# Expected : 202 Accepted + upload_id + ai_status: queued
#
# Setelah ini, Laravel akan:
#   1. Simpan file + buat record Transaction
#   2. Kirim webhook ke n8n /webhook/upload-nota
#   3. n8n jalankan Layer 1 → Layer 2 → Layer 3
#   4. n8n callback ke /api/ai/auto-fill

curl -X POST "${LARAVEL}/api/v1/nota/upload" \
  -F "foto_nota=./image.jpg" \
  -F "expected_nominal=150000" \
  -F "payment_method=cash" \
  -F "branch_id=1"

# Expected Response (202):
# {
#   "success": true,
#   "message": "Nota sedang diproses (3 Layer Verification)",
#   "upload_id": "UP-1234567",
#   "transaksi_id": "uuid-xxx",
#   "transaction_id": 42,
#   "status": "pending",
#   "ai_status": "queued",
#   "polling_url": "https://your-domain.com/api/v1/transaksi/42"
# }
#
# ⚠️  CATAT: upload_id dan transaction_id → dipakai di test selanjutnya


# ─── 1B. Polling Status OCR (Frontend → Laravel) ─────────────────────────────
#
# Endpoint : GET /api/ai/auto-fill/status/{uploadId}
# Auth     : Tidak perlu
# Trigger  : loading.blade.php polling setiap 2 detik
# Expected : Status berubah: queued → processing → completed / error / auto-reject

curl -X GET "${LARAVEL}/api/ai/auto-fill/status/UP-1234567"

# Expected Response saat QUEUED:
# { "status": "queued", "phase": "queued", "message": "Menunggu dalam antrian...", "estimated_wait": 30 }

# Expected Response saat PROCESSING:
# { "status": "processing", "phase": "processing", "message": "Sedang memproses dengan AI...", "estimated_wait": 15 }

# Expected Response saat COMPLETED:
# {
#   "status": "completed",
#   "data": {
#     "customer": "CV Maju Jaya",
#     "amount": 150000,
#     "date": "2026-03-08",
#     "items": [{ "nama_barang": "Pipa PVC", "qty": 5, ... }],
#     "confidence": 88,
#     "total_items": 3
#   }
# }

# Expected Response saat ERROR:
# { "status": "error", "message": "AI tidak dapat membaca nota..." }

# Expected Response saat AUTO-REJECT:
# { "status": "auto-reject", "message": "TANGGAL TELAT (>3 hari): ..." }


# ─── 1C. Cek Detail Transaksi ────────────────────────────────────────────────
#
# Endpoint : GET /api/v1/transaksi/{id}
# Auth     : Tidak perlu
# Trigger  : Polling atau manual check

curl -X GET "${LARAVEL}/api/v1/transaksi/42" | tee -a $OUTPUT

# Expected Response (200):
# {
#   "success": true,
#   "data": {
#     "id": 42,
#     "upload_id": "UP-1234567",
#     "status": "pending",
#     "confidence_label": "HIGH",
#     "overall_confidence": 88,
#     "field_confidence": { "vendor": 87, "material": 92, ... },
#     "nama_vendor": "CV Maju Jaya",
#     "tanggal_nota": "08/03/2026",
#     "total_belanja": 150000,
#     "items_json": [...]
#   }
# }


# ═══════════════════════════════════════════════════════════════════════════════
#
#  N8N CALLBACKS — Simulasi response dari n8n ke Laravel
#
#  Gunakan ini untuk test tanpa perlu n8n berjalan.
#  Ini mensimulasikan apa yang n8n kirim setelah proses Layer 1/2/3.
#
# ═══════════════════════════════════════════════════════════════════════════════


# ─── 1D. Callback SUCCESS — High Confidence (n8n → Laravel) ──────────────────
#
# Endpoint : POST /api/ai/auto-fill?upload_id={uploadId}
# Auth     : X-SECRET header
# Trigger  : n8n Layer 3 selesai, confidence > 70
# Expected : Transaction status → 'pending', ai_status → 'completed'

curl -X POST "${LARAVEL}/api/ai/auto-fill?upload_id=UP-1234567" \
  -H "Content-Type: application/json" \
  -H "X-SECRET: ${SECRET}" \
  -d '{
    "upload_id": "UP-1234567",
    "status": "success",
    "confidence": 88,
    "confidence_label": "HIGH",
    "overall_confidence": 88,
    "field_confidence": {
      "vendor": 87,
      "material": 92,
      "jumlah": 88,
      "satuan": 95,
      "nominal": 91
    },
    "vendor": "CV Maju Jaya",
    "tanggal": "08/03/2026",
    "total_belanja": 150000,
    "items": [
      {
        "nama_barang": "Pipa PVC 4 inch",
        "qty": 5,
        "satuan": "Batang",
        "harga_satuan": 30000,
        "total_harga": 150000,
        "nama_barang_confidence": 92,
        "qty_confidence": 88,
        "satuan_confidence": 95,
        "harga_satuan_confidence": 91,
        "total_harga_confidence": 90
      }
    ]
  }'

# Expected Response (200):
# {
#   "success": true,
#   "upload_id": "UP-1234567",
#   "status": "completed",
#   "confidence": 88,
#   "confidence_label": "HIGH"
# }
#
# ✅ VERIFIKASI DI DATABASE:
#   SELECT status, ai_status, confidence FROM transactions WHERE upload_id = 'UP-1234567';
#   → status='pending', ai_status='completed', confidence=88


# ─── 1E. Callback SUCCESS — Low Confidence (n8n → Laravel) ───────────────────
#
# Endpoint : POST /api/ai/auto-fill?upload_id={uploadId}
# Auth     : X-SECRET header
# Trigger  : n8n Layer 3 selesai, confidence <= 70
# Expected : Sama seperti 1D, tapi confidence_label = LOW

curl -X POST "${LARAVEL}/api/ai/auto-fill?upload_id=UP-1234567" \
  -H "Content-Type: application/json" \
  -H "X-SECRET: ${SECRET}" \
  -d '{
    "upload_id": "UP-1234567",
    "status": "success",
    "confidence": 45,
    "confidence_label": "LOW",
    "overall_confidence": 45,
    "field_confidence": {
      "vendor": 40,
      "material": 50,
      "jumlah": 35,
      "satuan": 55,
      "nominal": 45
    },
    "vendor": "Toko ???",
    "tanggal": "08/03/2026",
    "total_belanja": 75000,
    "items": [
      {
        "nama_barang": "Semen(?)",
        "qty": 2,
        "satuan": "Sak",
        "harga_satuan": 37500,
        "total_harga": 75000
      }
    ]
  }'

# Expected: success=true, confidence=45, confidence_label="LOW"


# ─── 1F. Callback AUTO-REJECT — Layer 1 Duplikat (n8n → Laravel) ─────────────
#
# Endpoint : POST /api/ai/auto-fill?upload_id={uploadId}
# Auth     : X-SECRET header
# Trigger  : n8n Layer 1 mendeteksi hash gambar duplikat
# Expected : Transaction status → 'auto-reject', ai_status → 'completed'

curl -X POST "${LARAVEL}/api/ai/auto-fill?upload_id=UP-1234567" \
  -H "Content-Type: application/json" \
  -H "X-SECRET: ${SECRET}" \
  -d '{
    "upload_id": "UP-1234567",
    "status": "auto_reject",
    "reason": "DUPLIKAT - File identik 100% (MD5 Full Match)",
    "dup_original_upload_id": "UP-9999999",
    "hash_results": {
      "exactHit": true,
      "boundaryHit": true,
      "visualHit": true
    },
    "stage": "layer1_security",
    "confidence": 0
  }'

# Expected Response (200):
# { "success": true, "status": "auto-reject" }
#
# ✅ VERIFIKASI DI DATABASE:
#   SELECT status, ai_status, rejection_reason FROM transactions WHERE upload_id = 'UP-1234567';
#   → status='auto-reject', ai_status='completed', rejection_reason='DUPLIKAT: ...'


# ─── 1G. Callback AUTO-REJECT — Layer 2 Tanggal Telat (n8n → Laravel) ────────
#
# Endpoint : POST /api/ai/auto-fill?upload_id={uploadId}
# Auth     : X-SECRET header
# Trigger  : n8n Layer 2 mendeteksi tanggal nota > 2 hari lalu
# Expected : Transaction status → 'auto-reject'

curl -X POST "${LARAVEL}/api/ai/auto-fill?upload_id=UP-1234567" \
  -H "Content-Type: application/json" \
  -H "X-SECRET: ${SECRET}" \
  -d '{
    "upload_id": "UP-1234567",
    "status": "auto_reject",
    "reason": "Nota terlambat 5 hari (batas 2 hari)",
    "tanggal_nota": "03/03/2026",
    "selisih_hari": 5,
    "stage": "layer2_logic",
    "confidence": 0
  }'

# Expected: { "success": true, "status": "auto-reject" }


# ─── 1H. Callback FAILED — Error umum (n8n → Laravel) ────────────────────────
#
# Endpoint : POST /api/ai/auto-fill?upload_id={uploadId}
# Auth     : X-SECRET header
# Trigger  : n8n error (Gemini down, resize gagal, dll)
# Expected : Transaction status → 'pending' (isi manual), ai_status → 'error'

curl -X POST "${LARAVEL}/api/ai/auto-fill?upload_id=UP-1234567" \
  -H "Content-Type: application/json" \
  -H "X-SECRET: ${SECRET}" \
  -d '{
    "upload_id": "UP-1234567",
    "status": "failed",
    "reason": "Gemini API tidak merespons setelah 120 detik",
    "stage": "layer3_parse",
    "confidence": 0
  }'

# Expected: { "success": true, "status": "failed" }
# DB: status='pending', ai_status='error'


# ═══════════════════════════════════════════════════════════════════════════════
#
#  FLOW 2 — VERIFIKASI & APPROVAL (Admin/Owner)
#
#  Prereq: Transaksi sudah status 'pending' (dari callback 1D/1E)
#  Auth  : Session cookie (login dulu di browser, ambil cookie)
#
# ═══════════════════════════════════════════════════════════════════════════════


# ─── 2A. Approve Transaksi (Admin) ───────────────────────────────────────────
#
# Endpoint : POST /transactions/{id}/status  (web route, bukan API)
# Auth     : Session cookie + CSRF token
# Trigger  : Klik tombol ✓ di index.blade.php
# Expected : status → 'approved' (jika ≥ 1jt, menunggu Owner)
#            status → 'waiting_payment' (jika < 1jt, langsung bayar)
#
# ⚠️  Endpoint ini ada di web.php (TransactionController), bukan api.php
#     Gunakan cookie dari browser

curl -X POST "${LARAVEL}/transactions/42/status" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: ${TOKEN}" \
  -H "Accept: application/json" \
  -H "Cookie: ${COOKIE}" \
  -d '{
    "_method": "PATCH",
    "status": "approved"
  }'

# Expected: { "success": true, "message": "Status transaksi berhasil diperbarui." }


# ─── 2B. Reject Transaksi (Admin) ────────────────────────────────────────────
#
# Endpoint : POST /transactions/{id}/status  (web route)
# Auth     : Session cookie + CSRF token
# Trigger  : Klik tombol ✗ → isi alasan → konfirmasi
# Expected : status → 'rejected', rejection_reason terisi

curl -X POST "${LARAVEL}/transactions/42/status" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: ${TOKEN}" \
  -H "Accept: application/json" \
  -H "Cookie: ${COOKIE}" \
  -d '{
    "_method": "PATCH",
    "status": "rejected",
    "rejection_reason": "Nota tidak jelas, vendor tidak dikenal"
  }'

# Expected: { "success": true }
# DB: status='rejected', rejection_reason='Nota tidak jelas...'


# ─── 2C. Reset ke Pending (Owner only) ───────────────────────────────────────
#
# Endpoint : POST /transactions/{id}/status  (web route)
# Auth     : Session cookie + CSRF (harus login sebagai Owner)
# Trigger  : Tombol "Reset ke Pending" di view modal

curl -X POST "${LARAVEL}/transactions/42/status" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: ${TOKEN}" \
  -H "Accept: application/json" \
  -H "Cookie: ${COOKIE}" \
  -d '{
    "_method": "PATCH",
    "status": "pending"
  }'


# ─── 2D. Override Auto-Reject (Admin/Owner) ──────────────────────────────────
#
# Endpoint : POST /api/v1/transaksi/{id}/override
# Auth     : Session cookie + CSRF (auth:web)
# Trigger  : Tombol "Request Override" pada transaksi auto-reject
# Expected : status berubah dari 'auto-reject' → 'waiting_payment'
#
# ⚠️  Prereq: Transaksi HARUS berstatus 'auto-reject'

curl -X POST "${LARAVEL}/api/v1/transaksi/42/override" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: ${TOKEN}" \
  -H "Accept: application/json" \
  -H "Cookie: ${COOKIE}" \
  -d '{
    "override_reason": "Nota memang telat tapi pembelian sudah diverifikasi langsung oleh supervisor lapangan"
  }'

# Expected Response (200):
# {
#   "success": true,
#   "message": "Override disetujui, transaksi dilanjutkan ke pembayaran.",
#   "data": { ... }
# }
#
# ✅ VERIFIKASI:
#   SELECT status, reviewed_by, description FROM transactions WHERE id = 42;
#   → status='waiting_payment', description LIKE '%Override Reason:%'

# ⚠️  Jika transaksi BUKAN auto-reject:
# Expected Response (400):
# { "success": false, "message": "Override hanya bisa dilakukan pada nota yang berstatus Auto-Reject." }


# ═══════════════════════════════════════════════════════════════════════════════
#
#  FLOW 3A — PEMBAYARAN CASH
#
#  Prereq: Transaksi sudah status 'waiting_payment' atau 'approved'
#  Urutan: 3A1 → (tunggu teknisi) → 3A2
#
# ═══════════════════════════════════════════════════════════════════════════════


# ─── 3A1. Upload Foto Penyerahan Cash (Admin → Laravel → n8n) ────────────────
#
# Endpoint : POST /api/v1/payment/cash/upload
# Auth     : Tidak perlu (public API)
# Trigger  : Admin upload foto wajah teknisi + uang
# Expected : status → 'Menunggu Konfirmasi Teknisi'
#            n8n menerima webhook → kirim Telegram ke teknisi

curl -X POST "${LARAVEL}/api/v1/payment/cash/upload" \
  -F "file=@/path/to/foto-penyerahan.jpg" \
  -F "upload_id=UP-1234567" \
  -F "transaksi_id=42" \
  -F "teknisi_id=5" \
  -F "catatan=Diserahkan langsung di kantor cabang Ponorogo"

# Expected Response (202):
# {
#   "success": true,
#   "message": "Foto penyerahan diterima. Menunggu konfirmasi teknisi.",
#   "upload_id": "UP-1234567",
#   "transaksi_id": 42,
#   "pembayaran_id": 42,
#   "status": "Menunggu Konfirmasi Teknisi"
# }
#
# ✅ VERIFIKASI DI DATABASE:
#   SELECT status, foto_penyerahan FROM transactions WHERE id = 42;
#   → status='Menunggu Konfirmasi Teknisi', foto_penyerahan='payments/cash/xxx.jpg'
#
# ✅ VERIFIKASI N8N:
#   Cek execution log n8n → node "Webhook - CASH Upload Foto Penyerahan1"
#   Harus ada execution baru dengan data upload_id, transaksi_id


# ─── 3A2. Teknisi Konfirmasi Terima (Teknisi → Laravel) ─────────────────────
#
# Endpoint : POST /api/v1/payment/cash/konfirmasi
# Auth     : Tidak perlu (dipanggil via web/Telegram)
# Trigger  : Teknisi klik "Terima" di web atau Telegram Bot
# Expected : status → 'completed'

curl -X POST "${LARAVEL}/api/v1/payment/cash/konfirmasi" \
  -H "Content-Type: application/json" \
  -d '{
    "upload_id": "UP-1234567",
    "transaksi_id": "42",
    "teknisi_id": "5",
    "action": "terima",
    "catatan": "Uang sudah diterima dengan baik"
  }'

# Expected Response (200):
# {
#   "success": true,
#   "message": "Pembayaran dikonfirmasi. Status: Selesai.",
#   "transaksi_id": 42,
#   "status": "completed",
#   "konfirmasi_at": "2026-03-10T14:30:00.000000Z"
# }


# ─── 3A3. Teknisi TOLAK Cash (alternatif 3A2) ───────────────────────────────
#
# Trigger  : Teknisi klik "Tolak" — uang tidak sesuai / tidak diterima
# Expected : status → 'Ditolak Teknisi'

curl -X POST "${LARAVEL}/api/v1/payment/cash/konfirmasi" \
  -H "Content-Type: application/json" \
  -d '{
    "upload_id": "UP-1234567",
    "transaksi_id": "42",
    "teknisi_id": "5",
    "action": "tolak",
    "catatan": "Nominal tidak sesuai, kurang Rp 20.000"
  }'

# Expected Response (200):
# {
#   "success": true,
#   "message": "Pembayaran ditolak oleh teknisi.",
#   "transaksi_id": 42,
#   "status": "Ditolak Teknisi",
#   "konfirmasi_at": "2026-03-10T14:30:00.000000Z"
# }


# ═══════════════════════════════════════════════════════════════════════════════
#
#  FLOW 3B — PEMBAYARAN TRANSFER
#
#  Prereq: Transaksi sudah status 'waiting_payment' atau 'approved'
#  Urutan: 3B1 → (n8n OCR struk) → 3B2 atau 3B3
#
# ═══════════════════════════════════════════════════════════════════════════════


# ─── 3B1. Upload Bukti Transfer (Admin → Laravel → n8n) ─────────────────────
#
# Endpoint : POST /api/v1/payment/transfer/upload
# Auth     : Tidak perlu (public API)
# Trigger  : Admin upload screenshot M-Banking
# Expected : status → 'Sedang Diverifikasi AI'
#            n8n menerima webhook → Gemini OCR baca struk

curl -X POST "${LARAVEL}/api/v1/payment/transfer/upload" \
  -F "file=@/path/to/bukti-transfer.jpg" \
  -F "upload_id=UP-1234567" \
  -F "transaksi_id=42" \
  -F "expected_nominal=150000" \
  -F "kode_unik=123" \
  -F "biaya_admin=6500" \
  -F "rekening_tujuan=1234567890" \
  -F "nama_bank_tujuan=BCA" \
  -F "rekening_bank=BCA" \
  -F "rekening_nomor=1234567890" \
  -F "rekening_nama=John Doe"

# Expected Response (202):
# {
#   "success": true,
#   "message": "Bukti transfer diterima. AI sedang memverifikasi nominal.",
#   "upload_id": "UP-1234567",
#   "transaksi_id": 42,
#   "pembayaran_id": 42,
#   "expected_total": 156623,
#   "status": "Sedang Diverifikasi AI",
#   "detail": {
#     "nominal": 150000,
#     "kode_unik": 123,
#     "biaya_admin": 6500,
#     "total": 156623
#   }
# }
#
# ✅ VERIFIKASI N8N:
#   Cek execution log → "Webhook - Transfer Upload Bukti1"


# ─── 3B2. Callback Transfer MATCH (n8n → Laravel) ───────────────────────────
#
# Endpoint : POST /api/pembayaran/update-status
# Auth     : X-SECRET header (n8n middleware)
# Trigger  : n8n Gemini OCR membaca nominal = match (selisih ≤ Rp 500)
# Expected : status → 'completed'

curl -X POST "${LARAVEL}/api/pembayaran/update-status" \
  -H "Content-Type: application/json" \
  -H "X-SECRET: ${SECRET}" \
  -d '{
    "upload_id": "UP-1234567",
    "transaksi_id": "42",
    "payment_method": "TRANSFER",
    "status": "completed",
    "ocr_result": "MATCH",
    "actual_total": 156500,
    "expected_total": 156623,
    "selisih": 123,
    "ocr_confidence": 95
  }'

# Expected Response (200):
# { "success": true, "message": "Status pembayaran berhasil diperbarui" }
#
# ✅ VERIFIKASI:
#   SELECT status, ocr_result, actual_total, selisih FROM transactions WHERE id = 42;
#   → status='completed', ocr_result='MATCH', actual_total=156500, selisih=123


# ─── 3B3. Callback Transfer MISMATCH (n8n → Laravel) ────────────────────────
#
# Endpoint : POST /api/pembayaran/update-status
# Auth     : X-SECRET header
# Trigger  : n8n Gemini OCR membaca nominal ≠ match (selisih > Rp 500)
# Expected : status → 'flagged'

curl -X POST "${LARAVEL}/api/pembayaran/update-status" \
  -H "Content-Type: application/json" \
  -H "X-SECRET: ${SECRET}" \
  -d '{
    "upload_id": "UP-1234567",
    "transaksi_id": "42",
    "payment_method": "TRANSFER",
    "status": "flagged",
    "ocr_result": "MISMATCH",
    "actual_total": 100000,
    "expected_total": 156623,
    "selisih": 56623,
    "flag_reason": "Selisih Rp 56.623 - nominal transfer tidak sesuai",
    "ocr_confidence": 82
  }'

# Expected Response (200):
# { "success": true, "message": "Status pembayaran berhasil diperbarui" }
# DB: status='flagged', ocr_result='MISMATCH', flag_reason='Selisih Rp 56.623...'


# ═══════════════════════════════════════════════════════════════════════════════
#
#  FLOW 4 — HANDLING SELISIH & FORCE APPROVE
#
#  Prereq: Transaksi sudah status 'flagged' (dari 3B3)
#
# ═══════════════════════════════════════════════════════════════════════════════


# ─── 4A. Force Approve (Owner/Admin) ─────────────────────────────────────────
#
# Endpoint : POST /api/v1/transaksi/{id}/force-approve
# Auth     : Session cookie + CSRF (auth:web, role:admin,owner)
# Trigger  : Tombol "Force Approve" pada transaksi flagged
# Expected : status → 'completed'
#
# ⚠️  Prereq: Transaksi HARUS berstatus 'flagged'

curl -X POST "${LARAVEL}/api/v1/transaksi/42/force-approve" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: ${TOKEN}" \
  -H "Accept: application/json" \
  -H "Cookie: ${COOKIE}" \
  -d '{
    "force_approve_reason": "Selisih karena biaya admin bank berbeda, sudah dikonfirmasi via telepon ke teknisi"
  }'

# Expected Response (200):
# {
#   "success": true,
#   "message": "Force Approve berhasil, transaksi selesai.",
#   "data": { ... }
# }
# DB: status='completed', description LIKE '%Force Approve Reason:%'

# ⚠️  Jika transaksi BUKAN flagged:
# Expected Response (400):
# { "success": false, "message": "Force Approve hanya bisa dilakukan pada transaksi yang di-flag (ada selisih)." }


# ═══════════════════════════════════════════════════════════════════════════════
#
#  FLOW 5 — N8N WEBHOOK ENDPOINTS (test langsung ke n8n)
#
#  Gunakan ini untuk test apakah n8n menerima & memproses webhook dengan benar.
#  ⚠️  Pastikan workflow sudah ACTIVE di n8n sebelum test.
#
# ═══════════════════════════════════════════════════════════════════════════════


# ─── 5A. Test n8n Upload Nota Webhook ─────────────────────────────────────────
#
# n8n Path  : /webhook/upload-nota
# Method    : POST multipart/form-data
# Binary    : field "data" (foto nota)
# Trigger   : OcrProcessingJob atau OcrNotaController

curl -X POST "${N8N}/webhook/upload-nota" \
  -F "data=@/path/to/nota.jpg" \
  -F "upload_id=UP-TEST-001" \
  -F "priority=normal" \
  -F "secret=${SECRET}"

# Expected: 202 Accepted (dari node "Respond 202 Accepted")
# Cek n8n execution log → harus melewati Layer 1 → 2 → 3
# Setelah selesai, n8n akan callback ke /api/ai/auto-fill


# ─── 5B. Test n8n Cash Upload Webhook ────────────────────────────────────────
#
# n8n Path  : /webhook/payment/cash/upload
# Method    : POST multipart/form-data
# Binary    : field "data" (foto penyerahan)

curl -X POST "${N8N}/webhook/payment/cash/upload" \
  -F "data=@/path/to/foto-cash.jpg" \
  -F "upload_id=UP-TEST-001" \
  -F "transaksi_id=42" \
  -F "teknisi_id=5"

# Expected: 202 (dari "Respond 202 - CASH1")
# n8n → validate → update status → send Telegram ke teknisi


# ─── 5C. Test n8n Cash Konfirmasi Webhook ────────────────────────────────────
#
# n8n Path  : /webhook/payment/cash/konfirmasi
# Method    : POST JSON

curl -X POST "${N8N}/webhook/payment/cash/konfirmasi" \
  -H "Content-Type: application/json" \
  -d '{
    "upload_id": "UP-TEST-001",
    "transaksi_id": "42",
    "teknisi_id": "5",
    "action": "terima"
  }'

# Expected: 200 (dari "Respond 200 - Konfirmasi Diterima1")
# n8n → validate → update status via callback → Telegram admin


# ─── 5D. Test n8n Transfer Upload Webhook ────────────────────────────────────
#
# n8n Path  : /webhook/payment/transfer/upload
# Method    : POST multipart/form-data
# Binary    : field "data" (screenshot M-Banking)

curl -X POST "${N8N}/webhook/payment/transfer/upload" \
  -F "data=@/path/to/bukti-transfer.jpg" \
  -F "upload_id=UP-TEST-001" \
  -F "transaksi_id=42" \
  -F "expected_nominal=150000" \
  -F "kode_unik=123" \
  -F "biaya_admin=6500" \
  -F "rekening_tujuan=1234567890" \
  -F "nama_bank_tujuan=BCA"

# Expected: 202 (dari "Respond 202 - Transfer1")
# n8n → validate → resize → Gemini OCR → parse → MATCH/MISMATCH callback


# ═══════════════════════════════════════════════════════════════════════════════
#
#  MONITORING & UTILITY ENDPOINTS
#
# ═══════════════════════════════════════════════════════════════════════════════


# ─── 6A. Admin OCR Status Monitoring ─────────────────────────────────────────
#
# Endpoint : GET /api/admin/ocr-status
# Auth     : Session cookie (auth:web, role: admin/owner)
# Trigger  : Dashboard admin

curl -X GET "${LARAVEL}/api/admin/ocr-status" \
  -H "Accept: application/json" \
  -H "Cookie: ${COOKIE}"

# Expected Response (200):
# {
#   "rate_limiter": { "current_slots": 2, "max_slots": 5, ... },
#   "queue_stats": {
#     "default": 3,
#     "ocr_high": 0,
#     "ocr_normal": 2,
#     "ocr_low": 1
#   },
#   "timestamp": "2026-03-10T14:30:00.000000Z"
# }


# ─── 6B. Daftar Semua Transaksi (dengan filter) ─────────────────────────────
#
# Endpoint : GET /api/v1/transaksi
# Auth     : Tidak perlu

# Semua transaksi
curl -X GET "${LARAVEL}/api/v1/transaksi" | tee -a $OUTPUT

# Filter by status
curl -X GET "${LARAVEL}/api/v1/transaksi?status=pending" | tee -a $OUTPUT
curl -X GET "${LARAVEL}/api/v1/transaksi?status=auto-reject" | tee -a $OUTPUT
curl -X GET "${LARAVEL}/api/v1/transaksi?status=flagged" | tee -a $OUTPUT
curl -X GET "${LARAVEL}/api/v1/transaksi?status=completed" | tee -a $OUTPUT

# Filter by vendor
curl -X GET "${LARAVEL}/api/v1/transaksi?vendor=Maju%20Jaya" | tee -a $OUTPUT


# ─── 6C. Unread Notifications Count ─────────────────────────────────────────
#
# Endpoint : GET /api/notifications/unread-count
# Auth     : Session cookie

curl -X GET "${LARAVEL}/api/notifications/unread-count" \
  -H "Accept: application/json" \
  -H "Cookie: ${COOKIE}"

# Expected: { "count": 3 }


# ═══════════════════════════════════════════════════════════════════════════════
#
#  TESTING SCENARIOS — End-to-End Flow
#
#  Jalankan skenario berikut secara berurutan untuk test lengkap.
#
# ═══════════════════════════════════════════════════════════════════════════════

# ┌──────────────────────────────────────────────────────────┐
# │  SKENARIO A: Happy Path — Upload → OCR → Approve → Cash │
# └──────────────────────────────────────────────────────────┘
#
#  1. Jalankan 1A (upload nota)                    → catat upload_id & transaction_id
#  2. Jalankan 1B (polling) beberapa kali          → lihat status berubah
#  3. Jalankan 1D (simulasi callback success)      → status = pending
#  4. Jalankan 2A (approve)                        → status = approved/waiting_payment
#  5. Jalankan 3A1 (upload cash)                   → status = Menunggu Konfirmasi Teknisi
#  6. Jalankan 3A2 (teknisi terima)                → status = completed ✅

# ┌──────────────────────────────────────────────────────────────┐
# │  SKENARIO B: Auto-Reject → Override → Transfer → Force Appr │
# └──────────────────────────────────────────────────────────────┘
#
#  1. Jalankan 1A (upload nota)                    → catat upload_id & transaction_id
#  2. Jalankan 1G (simulasi auto-reject tanggal)   → status = auto-reject
#  3. Jalankan 2D (override)                       → status = waiting_payment
#  4. Jalankan 3B1 (upload bukti transfer)         → status = Sedang Diverifikasi AI
#  5. Jalankan 3B3 (simulasi MISMATCH callback)    → status = flagged
#  6. Jalankan 4A (force approve)                  → status = completed ✅

# ┌─────────────────────────────────────────────────────────────┐
# │  SKENARIO C: Duplikat Nota → Auto-Reject (tidak bisa lanjut)│
# └─────────────────────────────────────────────────────────────┘
#
#  1. Jalankan 1A (upload nota pertama)             → sukses
#  2. Jalankan 1D (simulasi callback success)       → status = pending
#  3. Jalankan 1A lagi (upload NOTA SAMA)           → upload_id baru
#  4. Jalankan 1F (simulasi duplikat callback)      → status = auto-reject
#  5. Coba jalankan 2A (approve)                    → harusnya tetap auto-reject
#  6. Jalankan 2D (override jika perlu)             → lanjut ke payment

# ┌──────────────────────────────────────────────────────────────┐
# │  SKENARIO D: Transfer MATCH (otomatis selesai)               │
# └──────────────────────────────────────────────────────────────┘
#
#  1. Jalankan 1A → 1D → 2A                        → status = waiting_payment
#  2. Jalankan 3B1 (upload bukti transfer)          → status = Sedang Diverifikasi AI
#  3. Jalankan 3B2 (simulasi MATCH callback)        → status = completed ✅

# ┌──────────────────────────────────────────────────────────────┐
# │  SKENARIO E: Error OCR → Isi Manual                         │
# └──────────────────────────────────────────────────────────────┘
#
#  1. Jalankan 1A                                   → queued
#  2. Jalankan 1H (simulasi error callback)         → ai_status = error
#  3. Buka form edit di browser                     → isi data manual
#  4. Jalankan 2A (approve)                         → lanjut normal
