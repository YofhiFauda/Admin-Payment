# Enhancement: High-Impact Approval & Rejection Validation

Analisis pada project ini menunjukkan bahwa tindakan **Approve** dan **Reject** adalah gerbang keuangan utama. Kesalahan di sini berdampak pada kerugian finansial, audit yang berantakan, dan integritas data. Rencana ini memperkenalkan "Multi-Layered Validation" untuk memastikan setiap aksi memiliki landasan yang kuat.

## User Review Required

> [!IMPORTANT]
> **Skenario Validasi Berdampak Besar (High-Impact Scenarios):**
>
> **A. Approval & Rejection (Status Workflow):**
> 1. **Data Integrity Check (Mismatch):** Jika nominal input manual berbeda dengan hasil baca AI (OCR), sistem akan mewajibkan **Alasan Rekonsiliasi**.
> 2. **Financial Threshold Alert (>= 1 Juta):** Transaksi besar memicu modal konfirmasi "High Value Alert".
> 3. **AI Confidence Shield:** Peringatan jika Gemini `confidence < 60%`.
> 4. **Policy Compliance (Date):** Nota > 2 hari ditandai sebagai "Late Submission".
>
> **B. Payment Upload (Cash & Transfer Verification):**
> 5. **Payment Amount Lock:** Validasi ketat agar nominal di bukti transfer **harus sama** dengan nominal yang disetujui. Jika berbeda, sistem masuk ke status `flagged`.
> 6. **Duplicate Payment Proof:** Pengecekan Hashing (Layer 1) pada gambar bukti transfer untuk mencegah penggunaan satu bukti untuk dua transaksi berbeda.
> 7. **Bank Profile Integrity:** Untuk `transfer_teknisi`, sistem akan memvalidasi apakah nomor rekening tujuan benar-benar terdaftar atas nama teknisi tersebut untuk mencegah salah transfer.

## Proposed Changes

### [Component] Backend (Laravel Core)

#### [MODIFY] [OcrNotaController.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/app/Http/Controllers/Api/V1/OcrNotaController.php)
- Tambahkan logic pengecekan hash pada `uploadTransfer` dan `uploadCash`.
- Perketat validasi `expected_nominal` agar sinkron dengan database `transactions.amount`.

### [Component] Frontend (Blade & JavaScript)

#### [MODIFY] [index.blade.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/resources/views/transactions/index.blade.php)
- Integrasi *SweetAlert2* untuk menggantikan `confirm()` standar agar UI lebih premium dan informatif.
- Penambahan logic pada fungsi `renderPage()` untuk memberikan warna berbeda pada baris transaksi yang memiliki `low_confidence` atau `nominal_mismatch`.

#### [NEW] [TransactionSecurity.js](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/public/js/transaction-security.js)
- Script khusus untuk menghitung selisih secara real-time di sisi client sebelum tombol Approve diklik.

## Proposed Changes

### [Component] Backend Logic (Laravel)

#### [MODIFY] [TransactionController.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/app/Http/Controllers/TransactionController.php)
- Add backend validation to ensure `rejection_reason` is present when status is `rejected`.
- Implement a check for `nominal_mismatch` during the `approved` action. If a mismatch exists, ensure the request contains a `mismatch_reason`.
- Log these specific validation overrides in the `ActivityLog`.

### [Component] Frontend UI (Blade & JavaScript)

#### [MODIFY] [index.blade.php](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/resources/views/transactions/index.blade.php)
- **Enhance `performStatusAction`:** Before sending the request, perform client-side checks:
    - Compare `transaction.amount` with `transaction.ocr_amount`.
    - Check if `transaction.amount >= 1000000`.
    - Check if `transaction.date` is > 2 days old.
- **New Confirmation Dialogs:** Replace simple `confirm()` with a more professional SweetAlert2 or custom modal that displays the specific warnings (e.g., "Warning: Nominal Mismatch Detected").
- **Mandatory Rejection Reason:** Ensure the Reject Modal cannot be submitted without text.

#### [NEW] [ApprovalValidationService.js](file:///d:/Whusnet/Testing%20Runnig%20Background/Admin-Payment/public/js/approval-validation.js)
- Create a dedicated JS helper to handle the complex logic of "Should I show a warning?". This keeps the Blade file clean.

## Open Questions

> [!NOTE]
> 1. **Threshold Adjustment:** Is Rp 1.000.000 the strict final threshold for all branches, or should it be configurable?
> 2. **Mismatch Tolerance:** Should we allow a small tolerance for nominal mismatches (e.g., Rp 500 for rounding) before triggering the warning?

## Verification Plan

### Automated Tests
- Trigger an approval for a transaction with mismatching nominal and verify the warning appears.
- Attempt to reject without a reason and verify it is blocked.
- Test the ≥ 1jt logic role-by-role (Admin vs Owner).

### Manual Verification
- Testing the UI flow on mobile to ensure the new modals are responsive.
- Reviewing the `ActivityLog` to ensure "Reason for mismatch" is stored correctly.
