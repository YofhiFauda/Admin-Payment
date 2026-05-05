    # Requirements Document: Comprehensive TDD Test Suite untuk AdminPay

## Introduction

Dokumen ini mendefinisikan requirements untuk comprehensive Test-Driven Development (TDD) test suite yang akan menguji seluruh aspek kritis dari sistem AdminPay. Test suite ini dirancang untuk mendeteksi celah keamanan, bug logic bisnis, dan memastikan integritas data melalui skenario testing positif dan negatif yang menyeluruh.

AdminPay adalah sistem manajemen transaksi keuangan berbasis Laravel 12 dengan fitur multi-role (Teknisi, Admin, Atasan, Owner), workflow approval bertingkat, OCR processing, price anomaly detection, dan inter-branch debt management.

## Glossary

- **Test_Suite**: Kumpulan test cases yang terorganisir untuk menguji fungsionalitas sistem
- **Positive_Test**: Test case yang memverifikasi sistem berfungsi dengan benar dengan input valid
- **Negative_Test**: Test case yang memverifikasi sistem menangani error, invalid input, dan edge cases dengan benar
- **Security_Test**: Test case yang memverifikasi keamanan sistem terhadap unauthorized access dan data manipulation
- **Integration_Test**: Test case yang memverifikasi interaksi antar komponen sistem
- **Unit_Test**: Test case yang memverifikasi fungsi individual secara terisolasi
- **Transaction**: Entitas transaksi keuangan (Rembush, Pengajuan, Pembelian)
- **Role_Based_Access_Control**: Sistem otorisasi berdasarkan role pengguna
- **Workflow_State**: Status transaksi dalam approval workflow (pending, approved, waiting_payment, completed, rejected)
- **Price_Anomaly**: Deteksi harga yang melebihi threshold referensi
- **Branch_Allocation**: Pembagian biaya transaksi ke multiple cabang
- **OCR_Processing**: Optical Character Recognition untuk ekstraksi data dari nota
- **Payment_Verification**: Verifikasi pembayaran dengan deteksi selisih nominal
- **Inter_Branch_Debt**: Hutang piutang antar cabang dari transaksi multi-branch

## Requirements

### Requirement 1: Authentication & Authorization Testing

**User Story:** Sebagai security tester, saya ingin memverifikasi sistem authentication dan authorization, sehingga tidak ada unauthorized access yang dapat terjadi.

#### Acceptance Criteria

1. WHEN a user attempts login with valid credentials, THE Authentication_System SHALL authenticate the user and create a valid session
2. WHEN a user attempts login with invalid credentials, THE Authentication_System SHALL reject the login and return appropriate error message
3. WHEN a user attempts login with mismatched role, THE Authentication_System SHALL reject the login even if credentials are valid
4. WHEN an unauthenticated user attempts to access protected routes, THE Authorization_System SHALL redirect to login page
5. WHEN a user with insufficient role attempts to access restricted routes, THE Authorization_System SHALL return 403 Forbidden error
6. THE Role_Based_Access_Control SHALL enforce teknisi can only access transaction creation routes
7. THE Role_Based_Access_Control SHALL enforce admin/atasan/owner can access dashboard and management routes
8. THE Role_Based_Access_Control SHALL enforce only owner can access price index management
9. WHEN a user session expires, THE Authentication_System SHALL invalidate the session and require re-authentication
10. WHEN a user logs out, THE Authentication_System SHALL invalidate all session data and regenerate CSRF token

### Requirement 2: Transaction Creation Workflow Testing

**User Story:** Sebagai QA engineer, saya ingin memverifikasi transaction creation workflow, sehingga semua jenis transaksi dapat dibuat dengan benar dan data integrity terjaga.

#### Acceptance Criteria

1. WHEN teknisi creates a Rembush transaction with valid data, THE Transaction_System SHALL create transaction with status pending
2. WHEN teknisi creates a Pengajuan transaction with valid data, THE Transaction_System SHALL create transaction with status pending
3. WHEN atasan/owner creates a Pembelian transaction with valid data, THE Transaction_System SHALL create transaction with status pending
4. WHEN teknisi attempts to create Pembelian transaction, THE Authorization_System SHALL reject with 403 error
5. THE Transaction_System SHALL generate unique invoice_number for each transaction
6. THE Transaction_System SHALL generate unique upload_id for each transaction
7. THE Transaction_System SHALL validate required fields before creating transaction
8. WHEN transaction creation fails validation, THE Transaction_System SHALL return validation errors without creating database record
9. THE Transaction_System SHALL store branch allocation data correctly for multi-branch transactions
10. THE Transaction_System SHALL calculate total allocation_amount equal to transaction amount
11. WHEN file upload is provided, THE Transaction_System SHALL store file securely and record file_path
12. WHEN file upload exceeds size limit, THE Transaction_System SHALL reject upload with appropriate error

### Requirement 3: Transaction Status Transition Testing

**User Story:** Sebagai business analyst, saya ingin memverifikasi status transition workflow, sehingga transaksi hanya dapat berpindah status sesuai business rules yang valid.

#### Acceptance Criteria

1. WHEN admin/atasan approves pending transaction, THE Transaction_System SHALL transition status to approved or waiting_payment based on amount threshold
2. WHEN admin/atasan rejects pending transaction, THE Transaction_System SHALL transition status to rejected and record rejection_reason
3. WHEN owner approves transaction >= 1 juta, THE Transaction_System SHALL transition status to waiting_payment
4. WHEN transaction < 1 juta is approved, THE Transaction_System SHALL transition status to completed
5. THE Transaction_System SHALL prevent invalid status transitions (e.g., rejected to approved)
6. THE Transaction_System SHALL prevent teknisi from approving their own transactions
7. WHEN payment is verified with matching amount, THE Transaction_System SHALL transition to completed
8. WHEN payment is verified with mismatched amount, THE Transaction_System SHALL transition to flagged status
9. THE Transaction_System SHALL record reviewer_id and reviewed_at timestamp on approval
10. THE Transaction_System SHALL prevent status changes on completed transactions

### Requirement 4: Payment Verification & Discrepancy Detection Testing

**User Story:** Sebagai financial controller, saya ingin memverifikasi payment verification logic, sehingga selisih pembayaran dapat terdeteksi dengan akurat.

#### Acceptance Criteria

1. WHEN payment amount matches expected_total within tolerance (1000), THE Payment_Verification_System SHALL mark transaction as match
2. WHEN payment amount differs from expected_total beyond tolerance, THE Payment_Verification_System SHALL flag transaction for review
3. THE Payment_Verification_System SHALL calculate selisih as absolute difference between expected and actual
4. THE Payment_Verification_System SHALL create PaymentDiscrepancyAudit record for flagged transactions
5. WHEN n8n reports match but backend detects discrepancy, THE Payment_Verification_System SHALL override to flagged status
6. THE Payment_Verification_System SHALL prevent duplicate processing via Redis lock mechanism
7. WHEN payment verification callback is received, THE Payment_Verification_System SHALL update transaction with actual_total and confidence score
8. THE Payment_Verification_System SHALL send Telegram notification to teknisi on successful payment
9. THE Payment_Verification_System SHALL send Telegram notification to all owners on flagged payment
10. THE Payment_Verification_System SHALL broadcast real-time update event after payment verification

### Requirement 5: Price Anomaly Detection Testing

**User Story:** Sebagai procurement manager, saya ingin memverifikasi price anomaly detection, sehingga harga yang tidak wajar dapat terdeteksi dan direview.

#### Acceptance Criteria

1. WHEN item price exceeds max_price in PriceIndex, THE Price_Anomaly_System SHALL create PriceAnomaly record
2. WHEN item price is within min_price and max_price range, THE Price_Anomaly_System SHALL not create anomaly
3. THE Price_Anomaly_System SHALL calculate excess_percentage as ((input_price - max_price) / max_price) * 100
4. THE Price_Anomaly_System SHALL classify severity as critical when excess >= 50%
5. THE Price_Anomaly_System SHALL classify severity as medium when excess >= 20% and < 50%
6. THE Price_Anomaly_System SHALL classify severity as low when excess < 20%
7. WHEN item has no PriceIndex record, THE Price_Anomaly_System SHALL create new PriceIndex with needs_initial_review flag
8. THE Price_Anomaly_System SHALL detect anomalies for all items in Pengajuan transaction
9. THE Price_Anomaly_System SHALL set has_price_anomaly flag on transaction when anomalies detected
10. THE Price_Anomaly_System SHALL use case-insensitive matching for item names

### Requirement 6: Branch Allocation & Inter-Branch Debt Testing

**User Story:** Sebagai finance manager, saya ingin memverifikasi branch allocation calculations, sehingga pembagian biaya dan hutang antar cabang akurat.

#### Acceptance Criteria

1. WHEN transaction is allocated to multiple branches, THE Branch_Allocation_System SHALL calculate allocation_amount as (allocation_percent / 100) * transaction_amount
2. THE Branch_Allocation_System SHALL validate total allocation_percent equals 100
3. THE Branch_Allocation_System SHALL validate sum of allocation_amount equals transaction_amount
4. WHEN Pengajuan is paid from different branch, THE Inter_Branch_Debt_System SHALL create BranchDebt records
5. THE Inter_Branch_Debt_System SHALL create debt record for each allocated branch except paying branch
6. THE Inter_Branch_Debt_System SHALL calculate debt amount based on allocation_percent
7. WHEN branch debt is settled, THE Inter_Branch_Debt_System SHALL update debt status to settled
8. THE Inter_Branch_Debt_System SHALL prevent negative allocation amounts
9. THE Inter_Branch_Debt_System SHALL prevent allocation to non-existent branches
10. THE Transaction_System SHALL display correct status_label based on branch debt status

### Requirement 7: OCR Processing & Confidence Scoring Testing

**User Story:** Sebagai system integrator, saya ingin memverifikasi OCR processing workflow, sehingga data extraction dari nota akurat dan reliable.

#### Acceptance Criteria

1. WHEN nota image is uploaded, THE OCR_System SHALL queue OcrProcessingJob for async processing
2. THE OCR_System SHALL extract invoice_number, date, items, and total from nota image
3. THE OCR_System SHALL calculate overall_confidence score from field-level confidence scores
4. WHEN overall_confidence >= 80%, THE OCR_System SHALL mark ai_status as high_confidence
5. WHEN overall_confidence < 80%, THE OCR_System SHALL mark ai_status as low_confidence
6. THE OCR_System SHALL store extracted data in ocr_result JSON field
7. THE OCR_System SHALL handle OCR failures gracefully and mark ai_status as failed
8. THE OCR_System SHALL respect Gemini API rate limits via GeminiRateLimiter
9. THE OCR_System SHALL broadcast OcrStatusUpdated event after processing
10. THE OCR_System SHALL allow manual override for low confidence results

### Requirement 8: File Upload & Storage Security Testing

**User Story:** Sebagai security engineer, saya ingin memverifikasi file upload security, sehingga tidak ada malicious files yang dapat di-upload atau diakses unauthorized.

#### Acceptance Criteria

1. THE File_Upload_System SHALL validate file type is image (jpg, jpeg, png, pdf)
2. THE File_Upload_System SHALL validate file size does not exceed configured maximum
3. THE File_Upload_System SHALL sanitize filename to prevent directory traversal attacks
4. THE File_Upload_System SHALL store uploaded files in secure storage path
5. THE File_Upload_System SHALL generate unique filename to prevent collisions
6. WHEN user requests file access, THE File_Access_System SHALL verify user has permission to access the file
7. THE File_Access_System SHALL serve files through controller with authentication check
8. THE File_Upload_System SHALL reject executable files and scripts
9. THE File_Upload_System SHALL compress images to optimize storage
10. THE File_Upload_System SHALL clean up temporary uploads after configured timeout

### Requirement 9: API Rate Limiting & Input Validation Testing

**User Story:** Sebagai API security specialist, saya ingin memverifikasi API endpoints security, sehingga tidak ada abuse atau injection attacks yang dapat terjadi.

#### Acceptance Criteria

1. THE API_System SHALL enforce rate limiting of 60 requests per minute for price index lookup
2. THE API_System SHALL enforce rate limiting of 60 requests per minute for item autocomplete
3. WHEN rate limit is exceeded, THE API_System SHALL return 429 Too Many Requests error
4. THE API_System SHALL validate all input parameters against expected types and formats
5. THE API_System SHALL sanitize input to prevent SQL injection attacks
6. THE API_System SHALL sanitize input to prevent XSS attacks
7. THE API_System SHALL validate CSRF token for all state-changing requests
8. THE API_System SHALL validate N8N webhook secret for payment verification callbacks
9. THE API_System SHALL return appropriate HTTP status codes for different error types
10. THE API_System SHALL log all failed validation attempts for security monitoring

### Requirement 10: Database Transaction & Rollback Testing

**User Story:** Sebagai database administrator, saya ingin memverifikasi database transaction handling, sehingga data integrity terjaga bahkan saat terjadi error.

#### Acceptance Criteria

1. WHEN transaction creation involves multiple database operations, THE Database_System SHALL wrap operations in database transaction
2. WHEN any operation in transaction fails, THE Database_System SHALL rollback all changes
3. THE Database_System SHALL use pessimistic locking for concurrent price index updates
4. THE Database_System SHALL prevent race conditions in payment verification via Redis lock
5. THE Database_System SHALL maintain referential integrity for foreign key relationships
6. WHEN transaction is deleted, THE Database_System SHALL cascade delete related records appropriately
7. THE Database_System SHALL prevent orphaned records in pivot tables
8. THE Database_System SHALL validate unique constraints before insert
9. THE Database_System SHALL handle deadlock scenarios gracefully with retry logic
10. THE Database_System SHALL maintain audit trail for all critical data changes

### Requirement 11: Real-time Broadcasting & Notification Testing

**User Story:** Sebagai frontend developer, saya ingin memverifikasi real-time updates, sehingga UI dapat update tanpa refresh manual.

#### Acceptance Criteria

1. WHEN transaction status changes, THE Broadcasting_System SHALL broadcast TransactionUpdated event
2. WHEN new transaction is created, THE Broadcasting_System SHALL broadcast TransactionCreated event
3. WHEN price anomaly is detected, THE Broadcasting_System SHALL broadcast PriceAnomalyDetected event
4. WHEN OCR processing completes, THE Broadcasting_System SHALL broadcast OcrStatusUpdated event
5. THE Broadcasting_System SHALL send events to appropriate channels based on user roles
6. THE Notification_System SHALL create database notification for relevant users
7. THE Notification_System SHALL send Telegram notification when configured
8. THE Notification_System SHALL mark notifications as read when user acknowledges
9. THE Notification_System SHALL provide unread count API endpoint
10. THE Broadcasting_System SHALL handle connection failures gracefully

### Requirement 12: Data Export & Reporting Testing

**User Story:** Sebagai business analyst, saya ingin memverifikasi data export functionality, sehingga laporan dapat di-generate dengan akurat.

#### Acceptance Criteria

1. WHEN user exports transactions to CSV, THE Export_System SHALL include all visible columns
2. THE Export_System SHALL apply current filters to exported data
3. THE Export_System SHALL format currency values correctly in export
4. THE Export_System SHALL format dates in readable format
5. THE Export_System SHALL handle large datasets without timeout
6. THE Export_System SHALL stream data for memory efficiency
7. THE Export_System SHALL include proper CSV headers
8. THE Export_System SHALL escape special characters in CSV fields
9. THE Export_System SHALL enforce authorization for export operations
10. THE Export_System SHALL log all export operations for audit trail

### Requirement 13: Edge Cases & Error Handling Testing

**User Story:** Sebagai QA lead, saya ingin memverifikasi edge cases handling, sehingga sistem robust terhadap unexpected scenarios.

#### Acceptance Criteria

1. WHEN transaction amount is zero, THE Transaction_System SHALL reject with validation error
2. WHEN transaction amount is negative, THE Transaction_System SHALL reject with validation error
3. WHEN transaction amount exceeds maximum allowed, THE Transaction_System SHALL reject with validation error
4. WHEN date is in future, THE Transaction_System SHALL reject with validation error
5. WHEN required relationships are missing, THE Transaction_System SHALL handle gracefully with default values
6. WHEN external API (Gemini, Telegram) fails, THE System SHALL log error and continue operation
7. WHEN database connection is lost, THE System SHALL return appropriate error message
8. WHEN Redis connection is lost, THE System SHALL fallback to database for critical operations
9. WHEN concurrent updates occur, THE System SHALL handle with optimistic or pessimistic locking
10. WHEN invalid JSON is provided, THE System SHALL reject with clear error message

### Requirement 14: Performance & Load Testing

**User Story:** Sebagai performance engineer, saya ingin memverifikasi system performance, sehingga sistem dapat handle expected load.

#### Acceptance Criteria

1. WHEN searching 10,000+ transactions, THE Search_System SHALL return results within 2 seconds
2. THE Dashboard_System SHALL load statistics within 3 seconds with caching
3. THE Price_Index_System SHALL process batch calculations within acceptable time
4. THE OCR_System SHALL process images asynchronously without blocking user requests
5. THE Database_System SHALL use proper indexes for frequently queried columns
6. THE Cache_System SHALL cache expensive queries with appropriate TTL
7. THE Queue_System SHALL process jobs without memory leaks
8. THE System SHALL handle 100 concurrent users without degradation
9. THE API_System SHALL respond within 500ms for 95th percentile requests
10. THE System SHALL maintain response time under load testing scenarios

### Requirement 15: Regression Testing & Backward Compatibility

**User Story:** Sebagai release manager, saya ingin memverifikasi backward compatibility, sehingga updates tidak break existing functionality.

#### Acceptance Criteria

1. THE System SHALL handle old transaction records with legacy category format
2. THE System SHALL normalize old Pengajuan items with purchase_reason field
3. THE System SHALL handle transactions without items_snapshot field
4. THE System SHALL handle users without telegram_chat_id gracefully
5. THE System SHALL handle branches without bank accounts gracefully
6. THE System SHALL migrate data format changes transparently
7. THE System SHALL maintain API contract compatibility
8. THE System SHALL handle missing optional fields with sensible defaults
9. THE System SHALL validate data migrations with rollback capability
10. THE System SHALL provide deprecation warnings for legacy features

## Special Requirements Guidance

### Parser and Serializer Requirements

**OCR Data Parser**: Sistem menggunakan Gemini AI untuk parsing nota images. Parser harus:
- Extract structured data dari unstructured image
- Validate extracted data format
- Handle various nota formats and layouts
- Provide confidence scores per field

**JSON Serialization**: Transaction items dan specs disimpan sebagai JSON. Serializer harus:
- Validate JSON structure before save
- Handle nested arrays and objects
- Preserve data types (integers, strings, arrays)
- Provide clear error messages for invalid JSON

**Round-trip Property**: 
```php
// For all valid Transaction objects:
$transaction = Transaction::find($id);
$json = $transaction->toJson();
$decoded = json_decode($json, true);
$reconstructed = new Transaction($decoded);
// $reconstructed should be equivalent to $transaction
```

### Critical Testing Notes

1. **Authentication Testing**: Test semua kombinasi role dan route permissions
2. **Transaction Workflow**: Test semua valid dan invalid state transitions
3. **Payment Verification**: Test edge cases seperti zero amount, negative amount, exact tolerance boundary
4. **Price Anomaly**: Test cold start scenario (no price index), boundary conditions
5. **Branch Allocation**: Test rounding errors, percentage validation, debt calculations
6. **OCR Processing**: Test various image qualities, formats, and extraction failures
7. **File Security**: Test directory traversal, file type spoofing, unauthorized access
8. **API Security**: Test injection attacks, rate limit bypass attempts, CSRF validation
9. **Database Integrity**: Test concurrent updates, deadlocks, constraint violations
10. **Real-time Updates**: Test connection drops, reconnection, event delivery

### Test Data Requirements

Test suite harus include:
- Seeded users untuk semua roles (teknisi, admin, atasan, owner)
- Seeded branches dengan bank accounts
- Seeded transaction categories
- Seeded price indexes dengan various scenarios
- Sample images untuk OCR testing
- Mock responses untuk external APIs (Gemini, Telegram, N8N)

### Test Coverage Goals

- **Unit Tests**: 80%+ code coverage untuk business logic
- **Integration Tests**: Cover all critical user workflows
- **Security Tests**: Cover all authentication and authorization scenarios
- **Performance Tests**: Baseline metrics untuk regression detection
- **Edge Case Tests**: Cover all identified edge cases dan error scenarios

## Iteration and Feedback Rules

- Semua requirements dapat dimodifikasi berdasarkan feedback
- Acceptance criteria dapat ditambah atau direvisi
- Test scenarios dapat diperluas untuk coverage yang lebih baik
- Performance thresholds dapat disesuaikan berdasarkan infrastructure

## Phase Completion

Requirements document ini telah selesai dan siap untuk review. Silakan berikan feedback jika ada:
- Requirements yang perlu ditambahkan
- Acceptance criteria yang perlu diperjelas
- Test scenarios yang perlu diperluas
- Edge cases yang belum tercakup

Setelah approval, kita akan lanjut ke fase Design untuk merancang test suite architecture dan implementation strategy.
