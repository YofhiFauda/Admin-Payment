-- ═══════════════════════════════════════════════════════════════
--  Check Stuck Transactions - Payment Verification
-- 
--  Query untuk memeriksa transaksi yang застрял dengan status invalid
-- ═══════════════════════════════════════════════════════════════

-- 1. Cari transaksi dengan status "Sedang Diverifikasi AI"
SELECT 
    id,
    upload_id,
    invoice_number,
    status,
    ai_status,
    amount,
    expected_total,
    actual_total,
    payment_method,
    submitted_by,
    paid_by,
    paid_at,
    created_at,
    updated_at
FROM transactions
WHERE status = 'Sedang Diverifikasi AI'
ORDER BY created_at DESC;

-- 2. Cari transaksi dengan status tidak valid (selain status enum yang benar)
SELECT 
    id,
    upload_id,
    invoice_number,
    status,
    ai_status,
    created_at
FROM transactions
WHERE status NOT IN (
    'pending', 
    'approved', 
    'waiting_payment', 
    'completed', 
    'rejected', 
    'flagged', 
    'pending_technician'
)
ORDER BY created_at DESC;

-- 3. Distribusi status transaksi (untuk monitoring)
SELECT 
    status,
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM transactions
GROUP BY status
ORDER BY count DESC;

-- 4. Transaksi yang sedang diproses AI (lebih dari 1 jam)
SELECT 
    id,
    upload_id,
    invoice_number,
    status,
    ai_status,
    created_at,
    TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
FROM transactions
WHERE ai_status IN ('queued', 'processing')
  AND created_at < NOW() - INTERVAL 1 HOUR
ORDER BY created_at DESC;

-- 5. Transaksi dengan bukti transfer tapi status masih waiting_payment (lebih dari 1 jam)
SELECT 
    id,
    upload_id,
    invoice_number,
    status,
    ai_status,
    bukti_transfer,
    created_at,
    updated_at,
    TIMESTAMPDIFF(MINUTE, updated_at, NOW()) as minutes_since_update
FROM transactions
WHERE status = 'waiting_payment'
  AND bukti_transfer IS NOT NULL
  AND updated_at < NOW() - INTERVAL 1 HOUR
ORDER BY updated_at DESC;

-- ═══════════════════════════════════════════════════════════════
--  FIX QUERIES (Jalankan setelah verifikasi manual)
-- ═══════════════════════════════════════════════════════════════

-- OPSI 1: Reset ke waiting_payment (User perlu upload ulang)
-- UPDATE transactions
-- SET status = 'waiting_payment',
--     ai_status = 'error'
-- WHERE status = 'Sedang Diverifikasi AI';

-- OPSI 2: Manual complete (Jika sudah diverifikasi manual)
-- UPDATE transactions
-- SET status = 'completed',
--     ai_status = 'completed',
--     actual_total = expected_total,
--     confidence = 100
-- WHERE status = 'Sedang Diverifikasi AI'
--   AND id IN (123, 456, 789); -- Ganti dengan ID transaksi yang sudah diverifikasi

-- OPSI 3: Reset transaksi yang застрял lebih dari 2 jam
-- UPDATE transactions
-- SET status = 'waiting_payment',
--     ai_status = 'error'
-- WHERE status = 'Sedang Diverifikasi AI'
--   AND created_at < NOW() - INTERVAL 2 HOUR;
