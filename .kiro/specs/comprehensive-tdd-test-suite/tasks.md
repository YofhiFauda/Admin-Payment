# Implementation Plan: Comprehensive TDD Test Suite untuk AdminPay

## Overview

This implementation plan breaks down the creation of a comprehensive Test-Driven Development (TDD) test suite for the AdminPay system into actionable, sequential tasks. The test suite will cover unit tests, integration tests, security tests, performance tests, and CI/CD integration following Laravel/PHPUnit best practices.

The implementation follows a 7-phase roadmap:
1. **Foundation Setup** - Test infrastructure, base classes, mocks, factories
2. **Unit Tests** - Models, services, jobs, helpers
3. **Integration Tests** - Auth, transactions, payment, OCR, branch allocation, file upload, API, broadcasting
4. **Security Tests** - Authorization bypass, injection attacks, CSRF, file upload security
5. **Performance Tests** - Search performance, dashboard load, batch processing, N+1 detection
6. **CI/CD Integration** - GitHub Actions, coverage reporting, security scanning
7. **Documentation** - README, examples, troubleshooting guide

## Tasks

### Phase 1: Foundation Setup

- [ ] 1. Set up test infrastructure and configuration
  - [ ] 1.1 Configure PHPUnit for in-memory SQLite testing
    - Update `phpunit.xml` with SQLite in-memory database configuration
    - Configure test environment variables (CACHE_STORE=array, QUEUE_CONNECTION=sync, etc.)
    - Set up code coverage reporting configuration
    - _Requirements: All requirements depend on proper test infrastructure_
  
  - [ ] 1.2 Create base TestCase class with common setup
    - Extend Laravel's base TestCase with RefreshDatabase trait
    - Implement `seedEssentialData()` method for test data seeding
    - Add `mockExternalAPIs()` method to disable external calls by default
    - _Requirements: All requirements_
  
  - [ ] 1.3 Create TestDataSeeder for essential test data
    - Seed users for all roles (teknisi, admin, atasan, owner)
    - Seed branches with bank accounts
    - Seed transaction categories
    - Seed price indexes with various scenarios
    - _Requirements: 1.6, 1.7, 2.1, 2.2, 2.3_

- [ ] 2. Create test support infrastructure
  - [ ] 2.1 Create mock implementations for external APIs
    - Implement `MockGeminiClient` with success, low confidence, and failure scenarios
    - Implement `MockTelegramBot` with successful and failed send scenarios
    - Implement `MockN8nWebhook` for payment verification callbacks
    - _Requirements: 7.1, 7.2, 7.8, 11.7_
  
  - [ ] 2.2 Create reusable test traits
    - Implement `CreatesTransactions` trait with helper methods for creating test transactions
    - Implement `AssertsTransactionState` trait with custom assertions for transaction state
    - Implement `MocksExternalAPIs` trait for consistent API mocking
    - _Requirements: 2.1, 2.2, 2.3, 3.1_
  
  - [ ] 2.3 Enhance model factories with realistic test data
    - Extend `TransactionFactory` with `rembush()`, `pengajuan()`, `pembelian()` states
    - Add `withPriceAnomaly()` state to TransactionFactory
    - Extend `UserFactory` with role-specific states (teknisi, admin, atasan, owner)
    - Extend `BranchFactory` with bank account relationships
    - _Requirements: 2.1, 2.2, 2.3, 5.1_
  
  - [ ] 2.4 Create test fixtures and sample data
    - Create sample images for OCR testing (valid_nota.jpg, low_quality_nota.jpg, invalid_format.txt, oversized_image.jpg)
    - Create JSON fixtures for OCR responses and webhook payloads
    - Store fixtures in `tests/Fixtures/` directory
    - _Requirements: 7.1, 7.2, 7.3, 8.1_

- [ ] 3. Checkpoint - Verify foundation setup
  - Ensure all tests pass, ask the user if questions arise.

### Phase 2: Unit Tests

- [ ] 4. Write unit tests for models
  - [ ] 4.1 Create TransactionTest for Transaction model
    - Test invoice number generation uniqueness
    - Test upload ID generation uniqueness
    - Test status transition validation logic
    - Test amount calculations and validations
    - Test relationship accessors (submitter, reviewer, branches)
    - _Requirements: 2.5, 2.6, 3.5, 13.1, 13.2, 13.3_
  
  - [ ]* 4.2 Write unit tests for Transaction model edge cases
    - Test zero amount validation
    - Test negative amount validation
    - Test maximum amount validation
    - Test future date validation
    - _Requirements: 13.1, 13.2, 13.3, 13.4_
  
  - [ ] 4.3 Create UserTest for User model
    - Test role validation
    - Test authentication methods
    - Test relationship accessors (transactions, branches)
    - _Requirements: 1.1, 1.2, 1.3, 1.6_
  
  - [ ] 4.4 Create BranchTest for Branch model
    - Test branch allocation calculations
    - Test bank account relationships
    - Test debt calculations
    - _Requirements: 6.1, 6.2, 6.3, 6.8_
  
  - [ ] 4.5 Create PriceIndexTest for PriceIndex model
    - Test price range validations
    - Test item name matching logic
    - Test needs_initial_review flag
    - _Requirements: 5.1, 5.2, 5.7, 5.10_

- [ ] 5. Write unit tests for services
  - [ ] 5.1 Create PriceIndexServiceTest
    - Test `calculateExcessPercentage()` with various inputs
    - Test `classifySeverity()` for low, medium, critical thresholds
    - Test `detectAnomalies()` logic with mocked PriceIndex data
    - Use data providers for multiple input scenarios
    - _Requirements: 5.3, 5.4, 5.5, 5.6_
  
  - [ ] 5.2 Create ItemMatchingServiceTest
    - Test case-insensitive item name matching
    - Test fuzzy matching logic
    - Test matching with special characters
    - _Requirements: 5.10_
  
  - [ ] 5.3 Create IdGeneratorServiceTest
    - Test invoice number generation format
    - Test upload ID generation format
    - Test uniqueness guarantees
    - _Requirements: 2.5, 2.6_
  
  - [ ] 5.4 Create ImageCompressionServiceTest
    - Test image compression quality
    - Test file size reduction
    - Test format preservation
    - _Requirements: 8.9_

- [ ] 6. Write unit tests for jobs
  - [ ] 6.1 Create OcrProcessingJobTest
    - Test successful OCR extraction with mocked Gemini API
    - Test low confidence scenario handling
    - Test OCR failure handling
    - Test confidence score calculation
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.7_
  
  - [ ] 6.2 Create CalculatePriceIndexJobTest
    - Test batch price index calculation logic
    - Test anomaly detection during calculation
    - Test database transaction handling
    - _Requirements: 5.1, 5.2, 5.8_
  
  - [ ] 6.3 Create SendPriceAnomalyNotificationJobTest
    - Test notification creation for detected anomalies
    - Test Telegram notification sending with mocked bot
    - Test notification failure handling
    - _Requirements: 5.9, 11.7_

- [ ] 7. Write unit tests for helpers
  - [ ] 7.1 Create LogHelperTest
    - Test log formatting functions
    - Test log level filtering
    - Test log context enrichment
    - _Requirements: 10.10_

- [ ] 8. Checkpoint - Verify unit tests
  - Ensure all tests pass, ask the user if questions arise.

### Phase 3: Integration Tests

- [ ] 9. Write authentication and authorization tests
  - [ ] 9.1 Create AuthenticationTest
    - Test login with valid credentials creates session
    - Test login with invalid credentials returns error
    - Test login with mismatched role is rejected
    - Test session expiration invalidates access
    - Test logout invalidates session and regenerates CSRF token
    - _Requirements: 1.1, 1.2, 1.3, 1.9, 1.10_
  
  - [ ] 9.2 Create RoleBasedAccessTest
    - Test teknisi can only access transaction creation routes
    - Test admin/atasan/owner can access dashboard and management routes
    - Test only owner can access price index management
    - Test unauthenticated users are redirected to login
    - Test users with insufficient role receive 403 Forbidden
    - _Requirements: 1.4, 1.5, 1.6, 1.7, 1.8_
  
  - [ ]* 9.3 Write integration tests for session management
    - Test concurrent session handling
    - Test session fixation prevention
    - Test remember me functionality
    - _Requirements: 1.9_

- [ ] 10. Write transaction workflow tests
  - [ ] 10.1 Create RembushCreationTest
    - Test teknisi can create Rembush transaction with valid data
    - Test Rembush transaction is created with status pending
    - Test invoice number and upload ID are generated
    - Test branch allocation data is stored correctly
    - Test file upload is stored securely
    - _Requirements: 2.1, 2.5, 2.6, 2.9, 2.11_
  
  - [ ] 10.2 Create PengajuanCreationTest
    - Test teknisi can create Pengajuan transaction with valid data
    - Test Pengajuan transaction is created with status pending
    - Test price anomaly detection is triggered
    - Test has_price_anomaly flag is set when anomalies detected
    - _Requirements: 2.2, 5.8, 5.9_
  
  - [ ] 10.3 Create PembelianCreationTest
    - Test atasan/owner can create Pembelian transaction
    - Test teknisi cannot create Pembelian transaction (403 error)
    - Test Pembelian transaction validation
    - _Requirements: 2.3, 2.4_
  
  - [ ] 10.4 Create StatusTransitionTest
    - Test valid status transitions (pending → approved, pending → rejected)
    - Test invalid status transitions are prevented
    - Test reviewer_id and reviewed_at are recorded on approval
    - Test status changes on completed transactions are prevented
    - _Requirements: 3.1, 3.2, 3.5, 3.9, 3.10_
  
  - [ ] 10.5 Create ApprovalWorkflowTest
    - Test admin/atasan can approve pending transaction under 1 million (status → completed)
    - Test admin/atasan approval of transaction >= 1 million (status → waiting_payment)
    - Test owner approval of transaction >= 1 million (status → waiting_payment)
    - Test teknisi cannot approve their own transactions
    - Test rejection workflow with rejection_reason
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.6_
  
  - [ ]* 10.6 Write integration tests for transaction validation
    - Test required field validation
    - Test validation error messages
    - Test transaction creation rollback on validation failure
    - _Requirements: 2.7, 2.8_

- [ ] 11. Write payment verification tests
  - [ ] 11.1 Create PaymentVerificationTest
    - Test payment amount matches expected_total within tolerance (status → completed)
    - Test payment amount differs beyond tolerance (status → flagged)
    - Test selisih calculation accuracy
    - Test PaymentDiscrepancyAudit record creation for flagged transactions
    - Test n8n match override when backend detects discrepancy
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_
  
  - [ ] 11.2 Create DiscrepancyDetectionTest
    - Test exact tolerance boundary (1000) scenarios
    - Test zero amount payment handling
    - Test negative amount payment handling
    - _Requirements: 4.3, 13.1, 13.2_
  
  - [ ] 11.3 Create PaymentCallbackTest
    - Test N8N webhook secret validation
    - Test payment verification callback updates transaction
    - Test actual_total and confidence score recording
    - Test Telegram notification to teknisi on successful payment
    - Test Telegram notification to owners on flagged payment
    - Test real-time broadcast event after payment verification
    - _Requirements: 4.6, 4.7, 4.8, 4.9, 4.10, 9.8_

- [ ] 12. Write price anomaly detection tests
  - [ ] 12.1 Create AnomalyDetectionTest
    - Test anomaly creation when item price exceeds max_price
    - Test no anomaly when price is within min_price and max_price range
    - Test anomaly detection for all items in Pengajuan transaction
    - Test case-insensitive item name matching
    - _Requirements: 5.1, 5.2, 5.8, 5.10_
  
  - [ ] 12.2 Create SeverityClassificationTest
    - Test excess_percentage calculation formula
    - Test critical severity classification (excess >= 50%)
    - Test medium severity classification (20% <= excess < 50%)
    - Test low severity classification (excess < 20%)
    - _Requirements: 5.3, 5.4, 5.5, 5.6_
  
  - [ ] 12.3 Create PriceIndexManagementTest
    - Test new PriceIndex creation when item has no record
    - Test needs_initial_review flag on new PriceIndex
    - Test PriceIndex update with concurrent access
    - _Requirements: 5.7, 10.3_

- [ ] 13. Write branch allocation and debt tests
  - [ ] 13.1 Create AllocationCalculationTest
    - Test allocation_amount calculation as (allocation_percent / 100) * transaction_amount
    - Test total allocation_percent validation equals 100
    - Test sum of allocation_amount equals transaction_amount
    - Test negative allocation amount prevention
    - Test allocation to non-existent branches prevention
    - _Requirements: 6.1, 6.2, 6.3, 6.8, 6.9_
  
  - [ ] 13.2 Create InterBranchDebtTest
    - Test BranchDebt creation when Pengajuan is paid from different branch
    - Test debt record creation for each allocated branch except paying branch
    - Test debt amount calculation based on allocation_percent
    - Test status_label display based on branch debt status
    - _Requirements: 6.4, 6.5, 6.6, 6.10_
  
  - [ ]* 13.3 Write integration tests for debt settlement
    - Test debt status update to settled
    - Test debt settlement workflow
    - _Requirements: 6.7_

- [ ] 14. Write OCR processing tests
  - [ ] 14.1 Create OcrProcessingTest
    - Test nota image upload queues OcrProcessingJob
    - Test successful OCR extraction (invoice_number, date, items, total)
    - Test overall_confidence score calculation from field-level scores
    - Test ai_status marking as high_confidence when confidence >= 80%
    - Test ai_status marking as low_confidence when confidence < 80%
    - Test ocr_result JSON field storage
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_
  
  - [ ] 14.2 Create ConfidenceScoringTest
    - Test confidence score calculation logic
    - Test field-level confidence aggregation
    - Test manual override for low confidence results
    - _Requirements: 7.3, 7.4, 7.5, 7.10_
  
  - [ ] 14.3 Create RateLimitingTest
    - Test Gemini API rate limiting via GeminiRateLimiter
    - Test OCR failure handling and ai_status marking as failed
    - Test OcrStatusUpdated event broadcasting after processing
    - _Requirements: 7.7, 7.8, 7.9_

- [ ] 15. Write file upload and security tests
  - [ ] 15.1 Create FileUploadTest
    - Test valid file type upload (jpg, jpeg, png, pdf)
    - Test file size validation against configured maximum
    - Test unique filename generation to prevent collisions
    - Test file storage in secure storage path
    - Test image compression for storage optimization
    - _Requirements: 8.1, 8.2, 8.4, 8.5, 8.9_
  
  - [ ] 15.2 Create FileSecurityTest
    - Test filename sanitization to prevent directory traversal
    - Test executable file rejection
    - Test script file rejection
    - Test file type spoofing prevention
    - _Requirements: 8.3, 8.8_
  
  - [ ] 15.3 Create FileAccessControlTest
    - Test user permission verification for file access
    - Test authenticated file serving through controller
    - Test unauthorized file access prevention
    - Test temporary upload cleanup after timeout
    - _Requirements: 8.6, 8.7, 8.10_

- [ ] 16. Write API endpoint tests
  - [ ] 16.1 Create RateLimitingTest
    - Test rate limiting of 60 requests per minute for price index lookup
    - Test rate limiting of 60 requests per minute for item autocomplete
    - Test 429 Too Many Requests error when rate limit exceeded
    - _Requirements: 9.1, 9.2, 9.3_
  
  - [ ] 16.2 Create InputValidationTest
    - Test input parameter validation against expected types and formats
    - Test SQL injection prevention through input sanitization
    - Test XSS prevention through input sanitization
    - Test appropriate HTTP status codes for different error types
    - Test failed validation attempt logging
    - _Requirements: 9.4, 9.5, 9.6, 9.9, 9.10_
  
  - [ ] 16.3 Create WebhookSecurityTest
    - Test CSRF token validation for state-changing requests
    - Test N8N webhook secret validation for payment callbacks
    - _Requirements: 9.7, 9.8_
  
  - [ ]* 16.4 Write integration tests for autocomplete API
    - Test item autocomplete functionality
    - Test autocomplete result accuracy
    - _Requirements: 9.2_

- [ ] 17. Write broadcasting and notification tests
  - [ ] 17.1 Create EventBroadcastingTest
    - Test TransactionUpdated event broadcast on status change
    - Test TransactionCreated event broadcast on new transaction
    - Test PriceAnomalyDetected event broadcast on anomaly detection
    - Test OcrStatusUpdated event broadcast on OCR completion
    - Test events sent to appropriate channels based on user roles
    - Test connection failure handling
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.10_
  
  - [ ] 17.2 Create NotificationTest
    - Test database notification creation for relevant users
    - Test Telegram notification sending when configured
    - Test notification mark as read functionality
    - Test unread count API endpoint
    - _Requirements: 11.6, 11.7, 11.8, 11.9_

- [ ] 18. Write edge case and error handling tests
  - [ ] 18.1 Create ConcurrencyTest
    - Test concurrent transaction updates with optimistic/pessimistic locking
    - Test race condition prevention in payment verification via Redis lock
    - Test deadlock handling with retry logic
    - _Requirements: 10.3, 10.4, 10.9, 13.9_
  
  - [ ] 18.2 Create ErrorHandlingTest
    - Test external API failure handling (Gemini, Telegram)
    - Test database connection loss error messages
    - Test Redis connection loss fallback to database
    - Test invalid JSON rejection with clear error messages
    - _Requirements: 13.6, 13.7, 13.8, 13.10_
  
  - [ ] 18.3 Create DataIntegrityTest
    - Test database transaction wrapping for multiple operations
    - Test rollback on operation failure
    - Test referential integrity for foreign key relationships
    - Test cascade delete for related records
    - Test unique constraint validation
    - Test orphaned record prevention in pivot tables
    - _Requirements: 10.1, 10.2, 10.5, 10.6, 10.7, 10.8_

- [ ] 19. Checkpoint - Verify integration tests
  - Ensure all tests pass, ask the user if questions arise.

### Phase 4: Security Tests

- [ ] 20. Write authorization bypass tests
  - [ ] 20.1 Create AuthorizationBypassTest
    - Test teknisi cannot approve transactions (403 error)
    - Test teknisi cannot create Pembelian transactions (403 error)
    - Test teknisi cannot access owner-only routes (403 error)
    - Test direct object reference prevention
    - Test horizontal privilege escalation prevention
    - Test vertical privilege escalation prevention
    - _Requirements: 1.5, 2.4, 3.6_

- [ ] 21. Write injection attack tests
  - [ ] 21.1 Create InjectionAttackTest
    - Test SQL injection prevention in search queries
    - Test SQL injection prevention in all input fields
    - Test XSS prevention in text fields
    - Test command injection prevention in file uploads
    - Test LDAP injection prevention if applicable
    - _Requirements: 9.5, 9.6_

- [ ] 22. Write CSRF protection tests
  - [ ] 22.1 Create CsrfProtectionTest
    - Test CSRF token validation for POST requests
    - Test CSRF token validation for PUT requests
    - Test CSRF token validation for DELETE requests
    - Test CSRF token regeneration after logout
    - Test CSRF token validation failure returns 419 error
    - _Requirements: 9.7, 1.10_

- [ ] 23. Write file upload security tests
  - [ ] 23.1 Create FileUploadSecurityTest
    - Test malicious file upload prevention (PHP, executable)
    - Test file type spoofing detection
    - Test directory traversal prevention in file paths
    - Test file size limit enforcement
    - Test unauthorized file access prevention
    - _Requirements: 8.1, 8.2, 8.3, 8.6, 8.7, 8.8_

- [ ] 24. Checkpoint - Verify security tests
  - Ensure all tests pass, ask the user if questions arise.

### Phase 5: Performance Tests

- [ ] 25. Write search performance tests
  - [ ] 25.1 Create SearchPerformanceTest
    - Test search with 10,000+ transactions completes within 2 seconds
    - Test search query optimization with proper indexes
    - Test pagination performance with large datasets
    - Benchmark search execution time
    - _Requirements: 14.1, 14.5_

- [ ] 26. Write dashboard load tests
  - [ ] 26.1 Create DashboardLoadTest
    - Test dashboard loads within 3 seconds with caching
    - Test dashboard query count (detect N+1 queries, expect < 20 queries)
    - Test dashboard with 100 concurrent users
    - Test cache effectiveness for expensive queries
    - _Requirements: 14.2, 14.6, 14.8_

- [ ] 27. Write batch processing tests
  - [ ] 27.1 Create BatchProcessingTest
    - Test price index batch calculation performance
    - Test OCR processing queue performance
    - Test job processing without memory leaks
    - Test async processing doesn't block user requests
    - _Requirements: 14.3, 14.4, 14.7_

- [ ] 28. Write API performance tests
  - [ ] 28.1 Create ApiPerformanceTest
    - Test API response time within 500ms for 95th percentile
    - Test API throughput under load
    - Test rate limiting effectiveness
    - _Requirements: 14.9, 9.1, 9.2_

- [ ] 29. Checkpoint - Verify performance tests
  - Ensure all tests pass, ask the user if questions arise.

### Phase 6: CI/CD Integration

- [ ] 30. Configure GitHub Actions workflow
  - [ ] 30.1 Create GitHub Actions test workflow
    - Create `.github/workflows/tests.yml` file
    - Configure PHP 8.2 setup with required extensions
    - Configure composer dependency installation
    - Configure parallel test execution
    - Configure test coverage generation
    - _Requirements: All requirements_
  
  - [ ] 30.2 Configure code coverage reporting
    - Set up Codecov integration
    - Configure coverage upload to Codecov
    - Set minimum coverage thresholds (80% overall, 90% critical, 95% security)
    - Configure HTML coverage report generation
    - _Requirements: All requirements_
  
  - [ ] 30.3 Configure automated security scanning
    - Set up Laravel Security Checker in CI pipeline
    - Configure Snyk dependency vulnerability scanning
    - Configure PHPStan static analysis
    - Configure Laravel Pint code style enforcement
    - _Requirements: 9.1-9.10, 8.1-8.10_

- [ ] 31. Configure test execution optimization
  - [ ] 31.1 Set up parallel test execution
    - Install and configure ParaTest for parallel execution
    - Configure test groups (fast, slow, security)
    - Create composer scripts for different test scenarios
    - _Requirements: All requirements_
  
  - [ ] 31.2 Configure pre-commit and pre-push hooks
    - Create pre-commit hook to run fast tests
    - Create pre-push hook to run full test suite
    - Configure PHPStan and Pint in pre-push hook
    - _Requirements: All requirements_

- [ ] 32. Checkpoint - Verify CI/CD integration
  - Ensure all tests pass, ask the user if questions arise.

### Phase 7: Documentation

- [ ] 33. Create test suite documentation
  - [ ] 33.1 Write comprehensive test README
    - Document test suite structure and organization
    - Document test naming conventions
    - Document how to run tests (all, specific groups, specific files)
    - Document test data seeding and fixtures
    - Document mock implementations and usage
    - _Requirements: All requirements_
  
  - [ ] 33.2 Create test examples and patterns
    - Provide examples for unit test patterns
    - Provide examples for integration test patterns
    - Provide examples for security test patterns
    - Provide examples for performance test patterns
    - Document common test traits and helpers usage
    - _Requirements: All requirements_
  
  - [ ] 33.3 Write troubleshooting guide
    - Document common test failures and solutions
    - Document flaky test debugging strategies
    - Document performance optimization techniques
    - Document CI/CD troubleshooting
    - Document test data management best practices
    - _Requirements: All requirements_
  
  - [ ] 33.4 Document CI/CD pipeline
    - Document GitHub Actions workflow configuration
    - Document coverage reporting setup
    - Document security scanning tools
    - Document deployment process with test gates
    - _Requirements: All requirements_

- [ ] 34. Final checkpoint - Complete test suite verification
  - Run full test suite and verify all tests pass
  - Verify code coverage meets minimum thresholds (80% overall)
  - Verify security tests cover all critical scenarios
  - Verify CI/CD pipeline runs successfully
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at the end of each phase
- Unit tests focus on isolated component testing with mocked dependencies
- Integration tests verify component interactions with real database (in-memory SQLite)
- Security tests ensure no unauthorized access or data manipulation is possible
- Performance tests establish baseline metrics and detect regressions
- All tests use PHPUnit and Laravel testing utilities
- External APIs (Gemini, Telegram, N8N) are mocked in all tests
- Test data is seeded via factories and seeders for consistency
- CI/CD integration ensures automated testing on every push/PR

## Test Coverage Goals

- **Overall Coverage**: 80% minimum
- **Critical Business Logic**: 90% minimum (approval workflows, payment verification, price anomaly)
- **Security Components**: 95% minimum (authentication, authorization, input validation)
- **Models**: 85% minimum
- **Controllers**: 75% minimum
- **Services**: 90% minimum

## Execution Strategy

1. **Development**: Run fast tests frequently (`php artisan test --group=fast`)
2. **Pre-commit**: Run fast tests via git hook
3. **Pre-push**: Run full test suite via git hook
4. **CI/CD**: Run full test suite with coverage and security scanning
5. **Deployment**: Require all tests passing and coverage thresholds met
