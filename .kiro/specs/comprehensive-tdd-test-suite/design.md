# Design Document: Comprehensive TDD Test Suite untuk AdminPay

## Overview

This design document outlines the architecture and implementation strategy for a comprehensive Test-Driven Development (TDD) test suite for the AdminPay system. AdminPay is a Laravel 12-based financial transaction management system with multi-role access control, approval workflows, OCR processing, price anomaly detection, and inter-branch debt management.

### Design Goals

1. **Comprehensive Coverage**: Test all critical business logic, security boundaries, and data integrity constraints
2. **Fast Execution**: Enable rapid feedback loops with optimized test execution strategies
3. **Maintainability**: Create clear, organized test structures that are easy to understand and extend
4. **Reliability**: Ensure tests are deterministic, isolated, and free from flakiness
5. **CI/CD Integration**: Support automated testing in continuous integration pipelines

### Key Challenges

- **Complex Business Logic**: Multi-step approval workflows with role-based state transitions
- **External Dependencies**: Gemini AI OCR, Telegram notifications, N8N webhooks
- **Data Integrity**: Multi-table transactions, branch allocations, inter-branch debt calculations
- **Real-time Features**: Broadcasting events, WebSocket connections
- **Performance**: Testing with large datasets (10,000+ transactions)

### Testing Philosophy

This test suite follows a **dual testing approach**:

1. **Unit Tests**: Verify individual components in isolation with mocked dependencies
2. **Integration Tests**: Verify component interactions and end-to-end workflows

**Property-Based Testing (PBT) is NOT applicable** for this feature because:
- This is testing infrastructure, not application logic
- We are building test cases, not testing universal properties
- The feature involves test organization and execution strategy, not algorithmic correctness

---

## Architecture

### Test Suite Structure

```
tests/
├── Unit/                           # Isolated unit tests
│   ├── Models/                     # Model logic tests
│   │   ├── TransactionTest.php
│   │   ├── UserTest.php
│   │   ├── BranchTest.php
│   │   └── PriceIndexTest.php
│   ├── Services/                   # Service layer tests
│   │   ├── PriceIndexServiceTest.php
│   │   ├── ItemMatchingServiceTest.php
│   │   ├── IdGeneratorServiceTest.php
│   │   └── ImageCompressionServiceTest.php
│   ├── Jobs/                       # Job tests
│   │   ├── OcrProcessingJobTest.php
│   │   └── PriceIndex/
│   │       ├── CalculatePriceIndexJobTest.php
│   │       └── SendPriceAnomalyNotificationJobTest.php
│   └── Helpers/                    # Helper function tests
│       └── LogHelperTest.php
│
├── Feature/                        # Integration & feature tests
│   ├── Auth/                       # Authentication & authorization
│   │   ├── AuthenticationTest.php
│   │   ├── RoleBasedAccessTest.php
│   │   └── SessionManagementTest.php
│   ├── Transactions/               # Transaction workflows
│   │   ├── RembushCreationTest.php
│   │   ├── PengajuanCreationTest.php
│   │   ├── PembelianCreationTest.php
│   │   ├── StatusTransitionTest.php
│   │   ├── ApprovalWorkflowTest.php
│   │   └── TransactionValidationTest.php
│   ├── Payment/                    # Payment verification
│   │   ├── PaymentVerificationTest.php
│   │   ├── DiscrepancyDetectionTest.php
│   │   └── PaymentCallbackTest.php
│   ├── PriceAnomaly/              # Price anomaly detection
│   │   ├── AnomalyDetectionTest.php
│   │   ├── SeverityClassificationTest.php
│   │   └── PriceIndexManagementTest.php
│   ├── BranchAllocation/          # Branch allocation & debt
│   │   ├── AllocationCalculationTest.php
│   │   ├── InterBranchDebtTest.php
│   │   └── DebtSettlementTest.php
│   ├── OCR/                       # OCR processing
│   │   ├── OcrProcessingTest.php
│   │   ├── ConfidenceScoringTest.php
│   │   └── RateLimitingTest.php
│   ├── FileUpload/                # File upload & security
│   │   ├── FileUploadTest.php
│   │   ├── FileSecurityTest.php
│   │   └── FileAccessControlTest.php
│   ├── API/                       # API endpoints
│   │   ├── RateLimitingTest.php
│   │   ├── InputValidationTest.php
│   │   ├── WebhookSecurityTest.php
│   │   └── AutocompleteTest.php
│   ├── Broadcasting/              # Real-time updates
│   │   ├── EventBroadcastingTest.php
│   │   └── NotificationTest.php
│   └── EdgeCases/                 # Edge cases & error handling
│       ├── ConcurrencyTest.php
│       ├── ErrorHandlingTest.php
│       └── DataIntegrityTest.php
│
├── Security/                       # Security-focused tests
│   ├── InjectionAttackTest.php
│   ├── AuthorizationBypassTest.php
│   ├── CsrfProtectionTest.php
│   └── FileUploadSecurityTest.php
│
├── Performance/                    # Performance & load tests
│   ├── SearchPerformanceTest.php
│   ├── DashboardLoadTest.php
│   └── BatchProcessingTest.php
│
├── Support/                        # Test support classes
│   ├── Factories/                  # Custom factories
│   │   ├── TransactionFactory.php
│   │   ├── UserFactory.php
│   │   └── BranchFactory.php
│   ├── Traits/                     # Reusable test traits
│   │   ├── CreatesTransactions.php
│   │   ├── MocksExternalAPIs.php
│   │   └── AssertsTransactionState.php
│   ├── Mocks/                      # Mock implementations
│   │   ├── MockGeminiClient.php
│   │   ├── MockTelegramBot.php
│   │   └── MockN8nWebhook.php
│   └── Helpers/                    # Test helper functions
│       ├── TestDataBuilder.php
│       └── AssertionHelpers.php
│
└── TestCase.php                    # Base test case
```

### Test Organization Principles

1. **Separation by Type**: Unit tests are isolated from integration tests
2. **Feature-Based Grouping**: Tests are organized by feature domain (Auth, Transactions, Payment, etc.)
3. **Naming Convention**: Test classes end with `Test.php`, test methods start with `test_`
4. **One Assertion Per Concept**: Each test method verifies one specific behavior
5. **Descriptive Names**: Test names clearly describe what is being tested and expected outcome

---

## Components and Interfaces

### Base Test Case

**File**: `tests/TestCase.php`

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Traits\MocksExternalAPIs;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use MocksExternalAPIs;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable external API calls by default
        $this->mockExternalAPIs();
        
        // Seed essential data
        $this->seedEssentialData();
    }
    
    protected function seedEssentialData(): void
    {
        // Seed roles, branches, categories
        $this->artisan('db:seed', ['--class' => 'TestDataSeeder']);
    }
}
```

### Test Data Factories

**Enhanced Model Factories** with realistic test data:

```php
// database/factories/TransactionFactory.php
class TransactionFactory extends Factory
{
    public function rembush(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_REMBUSH,
            'payment_method' => 'transfer_teknisi',
            'customer' => $this->faker->company,
            'items' => $this->generateRembushItems(),
        ]);
    }
    
    public function pengajuan(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_PENGAJUAN,
            'vendor' => $this->faker->company,
            'items' => $this->generatePengajuanItems(),
        ]);
    }
    
    public function withPriceAnomaly(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_price_anomaly' => true,
        ])->afterCreating(function (Transaction $transaction) {
            PriceAnomaly::factory()->create([
                'transaction_id' => $transaction->id,
            ]);
        });
    }
}
```

### Mock Implementations

**Gemini OCR Mock**:

```php
// tests/Support/Mocks/MockGeminiClient.php
class MockGeminiClient
{
    public static function mockSuccessfulOcr(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'invoice_number' => 'INV-001',
                                'date' => '2024-01-15',
                                'items' => [/* ... */],
                                'total' => 500000,
                                'confidence' => 0.95,
                            ])
                        ]]
                    ]
                ]]
            ], 200)
        ]);
    }
    
    public static function mockLowConfidenceOcr(): void
    {
        // Similar implementation with confidence < 0.8
    }
    
    public static function mockFailedOcr(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500)
        ]);
    }
}
```

**Telegram Bot Mock**:

```php
// tests/Support/Mocks/MockTelegramBot.php
class MockTelegramBot
{
    public static function mockSuccessfulSend(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 123]
            ], 200)
        ]);
    }
}
```

### Test Traits

**CreatesTransactions Trait**:

```php
// tests/Support/Traits/CreatesTransactions.php
trait CreatesTransactions
{
    protected function createRembushTransaction(array $overrides = []): Transaction
    {
        return Transaction::factory()
            ->rembush()
            ->for(User::factory()->teknisi(), 'submitter')
            ->hasAttached(Branch::factory())
            ->create($overrides);
    }
    
    protected function createPengajuanWithAnomaly(): Transaction
    {
        return Transaction::factory()
            ->pengajuan()
            ->withPriceAnomaly()
            ->create();
    }
}
```

**AssertsTransactionState Trait**:

```php
// tests/Support/Traits/AssertsTransactionState.php
trait AssertsTransactionState
{
    protected function assertTransactionStatus(Transaction $transaction, string $expectedStatus): void
    {
        $this->assertEquals($expectedStatus, $transaction->fresh()->status);
    }
    
    protected function assertTransactionHasReviewer(Transaction $transaction): void
    {
        $this->assertNotNull($transaction->fresh()->reviewed_by);
        $this->assertNotNull($transaction->fresh()->reviewed_at);
    }
}
```

---

## Data Models

### Test Database Configuration

**In-Memory SQLite for Speed**:

```xml
<!-- phpunit.xml -->
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="BROADCAST_CONNECTION" value="null"/>
</php>
```

### Test Data Seeders

**TestDataSeeder**:

```php
// database/seeders/TestDataSeeder.php
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create users for each role
        User::factory()->create([
            'email' => 'teknisi@test.com',
            'role' => 'teknisi',
        ]);
        
        User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);
        
        User::factory()->create([
            'email' => 'owner@test.com',
            'role' => 'owner',
        ]);
        
        // Create branches
        Branch::factory()->count(3)->create();
        
        // Create transaction categories
        TransactionCategory::factory()->count(10)->create();
        
        // Create price indexes
        PriceIndex::factory()->count(20)->create();
    }
}
```

### Test Data Fixtures

**Sample Images for OCR Testing**:

```
tests/Fixtures/
├── images/
│   ├── valid_nota.jpg          # Clear, readable nota
│   ├── low_quality_nota.jpg    # Blurry, low confidence
│   ├── invalid_format.txt      # Invalid file type
│   └── oversized_image.jpg     # Exceeds size limit
└── json/
    ├── ocr_response_success.json
    ├── ocr_response_low_confidence.json
    └── webhook_payload_payment.json
```

---

## Error Handling

### Test Error Scenarios

1. **Validation Errors**: Test all validation rules with invalid inputs
2. **Authorization Errors**: Test unauthorized access attempts
3. **Database Errors**: Test constraint violations, deadlocks
4. **External API Errors**: Test timeout, rate limiting, service unavailable
5. **File Upload Errors**: Test invalid file types, size limits, storage failures
6. **Concurrency Errors**: Test race conditions, optimistic locking failures

### Error Assertion Helpers

```php
// tests/Support/Helpers/AssertionHelpers.php
class AssertionHelpers
{
    public static function assertValidationError(
        TestResponse $response,
        string $field,
        string $errorMessage = null
    ): void {
        $response->assertStatus(422);
        $response->assertJsonValidationErrors($field);
        
        if ($errorMessage) {
            $response->assertJsonFragment([
                $field => [$errorMessage]
            ]);
        }
    }
    
    public static function assertUnauthorized(TestResponse $response): void
    {
        $response->assertStatus(403);
    }
    
    public static function assertDatabaseConstraintViolation(callable $action): void
    {
        try {
            $action();
            PHPUnit::fail('Expected database constraint violation');
        } catch (\Illuminate\Database\QueryException $e) {
            PHPUnit::assertTrue(true);
        }
    }
}
```

---

## Testing Strategy

### Unit Testing Strategy

**Scope**: Test individual methods and classes in isolation

**Approach**:
- Mock all external dependencies (database, APIs, file system)
- Test one method per test case
- Focus on business logic, calculations, and transformations
- Use data providers for testing multiple input scenarios

**Example**:

```php
// tests/Unit/Services/PriceIndexServiceTest.php
class PriceIndexServiceTest extends TestCase
{
    public function test_calculates_excess_percentage_correctly(): void
    {
        $service = new PriceIndexService();
        
        $result = $service->calculateExcessPercentage(
            inputPrice: 150000,
            maxPrice: 100000
        );
        
        $this->assertEquals(50.0, $result);
    }
    
    /**
     * @dataProvider severityDataProvider
     */
    public function test_classifies_severity_correctly(
        float $excessPercentage,
        string $expectedSeverity
    ): void {
        $service = new PriceIndexService();
        
        $severity = $service->classifySeverity($excessPercentage);
        
        $this->assertEquals($expectedSeverity, $severity);
    }
    
    public static function severityDataProvider(): array
    {
        return [
            'low severity' => [10.0, 'low'],
            'medium severity' => [30.0, 'medium'],
            'critical severity' => [60.0, 'critical'],
        ];
    }
}
```

### Integration Testing Strategy

**Scope**: Test component interactions and end-to-end workflows

**Approach**:
- Use real database (in-memory SQLite)
- Mock only external APIs (Gemini, Telegram, N8N)
- Test complete user workflows
- Verify database state changes
- Test event broadcasting

**Example**:

```php
// tests/Feature/Transactions/ApprovalWorkflowTest.php
class ApprovalWorkflowTest extends TestCase
{
    use CreatesTransactions;
    use AssertsTransactionState;
    
    public function test_admin_can_approve_pending_rembush_under_1_million(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $transaction = $this->createRembushTransaction([
            'amount' => 500000,
            'status' => 'pending',
        ]);
        
        // Act
        $response = $this->actingAs($admin)
            ->post("/transactions/{$transaction->id}/approve");
        
        // Assert
        $response->assertRedirect();
        $this->assertTransactionStatus($transaction, 'completed');
        $this->assertTransactionHasReviewer($transaction);
        $this->assertEquals($admin->id, $transaction->fresh()->reviewed_by);
    }
    
    public function test_owner_approval_required_for_transaction_over_1_million(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $transaction = $this->createRembushTransaction([
            'amount' => 1500000,
            'status' => 'pending',
        ]);
        
        // Act
        $response = $this->actingAs($admin)
            ->post("/transactions/{$transaction->id}/approve");
        
        // Assert
        $response->assertRedirect();
        $this->assertTransactionStatus($transaction, 'waiting_payment');
        $this->assertNotEquals('completed', $transaction->fresh()->status);
    }
}
```

### Security Testing Strategy

**Scope**: Test authentication, authorization, and security boundaries

**Approach**:
- Test all role-based access control rules
- Test CSRF protection
- Test SQL injection prevention
- Test XSS prevention
- Test file upload security
- Test API authentication and rate limiting

**Example**:

```php
// tests/Security/AuthorizationBypassTest.php
class AuthorizationBypassTest extends TestCase
{
    public function test_teknisi_cannot_approve_transactions(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        $transaction = Transaction::factory()->pending()->create();
        
        $response = $this->actingAs($teknisi)
            ->post("/transactions/{$transaction->id}/approve");
        
        $response->assertStatus(403);
        $this->assertTransactionStatus($transaction, 'pending');
    }
    
    public function test_teknisi_cannot_create_pembelian(): void
    {
        $teknisi = User::factory()->teknisi()->create();
        
        $response = $this->actingAs($teknisi)
            ->post('/transactions/pembelian', [
                'type' => Transaction::TYPE_GUDANG,
                'amount' => 500000,
                // ... other fields
            ]);
        
        $response->assertStatus(403);
        $this->assertDatabaseMissing('transactions', [
            'type' => Transaction::TYPE_GUDANG,
            'submitted_by' => $teknisi->id,
        ]);
    }
    
    public function test_sql_injection_prevention_in_search(): void
    {
        $user = User::factory()->admin()->create();
        
        $response = $this->actingAs($user)
            ->get('/transactions/search?q=' . urlencode("'; DROP TABLE transactions; --"));
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', []); // Table still exists
    }
}
```

### Performance Testing Strategy

**Scope**: Test system performance under load

**Approach**:
- Benchmark critical operations
- Test with large datasets
- Measure query counts (N+1 detection)
- Test caching effectiveness
- Test pagination performance

**Example**:

```php
// tests/Performance/SearchPerformanceTest.php
class SearchPerformanceTest extends TestCase
{
    public function test_search_performs_within_acceptable_time_with_10k_transactions(): void
    {
        // Arrange
        Transaction::factory()->count(10000)->create();
        $user = User::factory()->admin()->create();
        
        // Act
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)
            ->get('/transactions/search?q=test');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to ms
        
        // Assert
        $response->assertStatus(200);
        $this->assertLessThan(2000, $executionTime, 
            "Search took {$executionTime}ms, expected < 2000ms");
    }
    
    public function test_dashboard_loads_without_n_plus_1_queries(): void
    {
        Transaction::factory()->count(100)->create();
        $user = User::factory()->owner()->create();
        
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $this->actingAs($user)->get('/dashboard');
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        $response->assertStatus(200);
        $this->assertLessThan(20, $queryCount,
            "Dashboard executed {$queryCount} queries, expected < 20");
    }
}
```

### Test Execution Strategy

**Parallel Execution**:

```bash
# Run tests in parallel using ParaTest
composer require --dev brianium/paratest
./vendor/bin/paratest --processes=4
```

**Test Groups**:

```php
// Group tests by execution speed
/**
 * @group fast
 */
class QuickUnitTest extends TestCase { }

/**
 * @group slow
 */
class IntegrationTest extends TestCase { }

/**
 * @group security
 */
class SecurityTest extends TestCase { }
```

**Run specific groups**:

```bash
# Run only fast tests during development
php artisan test --group=fast

# Run security tests before deployment
php artisan test --group=security

# Run all tests except slow ones
php artisan test --exclude-group=slow
```

### CI/CD Integration

**GitHub Actions Workflow**:

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo_sqlite
          
      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress
        
      - name: Run Tests
        run: php artisan test --parallel --coverage --min=80
        
      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

---

## Code Coverage & Reporting

### Coverage Targets

- **Overall Coverage**: 80% minimum
- **Critical Business Logic**: 90% minimum (approval workflows, payment verification, price anomaly)
- **Security Components**: 95% minimum (authentication, authorization, input validation)
- **Models**: 85% minimum
- **Controllers**: 75% minimum
- **Services**: 90% minimum

### Coverage Configuration

```xml
<!-- phpunit.xml -->
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <exclude>
        <directory>./app/Console</directory>
        <file>./app/Providers/RouteServiceProvider.php</file>
    </exclude>
    <report>
        <html outputDirectory="coverage-html"/>
        <clover outputFile="coverage.xml"/>
        <text outputFile="php://stdout" showUncoveredFiles="false"/>
    </report>
</coverage>
```

### Reporting Tools

1. **PHPUnit HTML Report**: Visual coverage report
2. **Codecov**: Cloud-based coverage tracking
3. **PHPStan**: Static analysis for type safety
4. **Laravel Pint**: Code style enforcement

```bash
# Generate coverage report
php artisan test --coverage --coverage-html=coverage-html

# Run static analysis
./vendor/bin/phpstan analyse

# Fix code style
./vendor/bin/pint
```

---

## Performance Testing Approach

### Load Testing Strategy

**Tools**: Apache JMeter or Laravel Dusk for browser testing

**Scenarios**:
1. **Concurrent User Load**: 100 concurrent users accessing dashboard
2. **Transaction Creation Load**: 50 transactions created per minute
3. **Search Load**: 200 search queries per minute
4. **API Load**: 1000 API requests per minute

**Metrics**:
- Response time (p50, p95, p99)
- Throughput (requests per second)
- Error rate
- Database query time
- Memory usage

### Benchmarking

```php
// tests/Performance/BenchmarkTest.php
class BenchmarkTest extends TestCase
{
    public function test_price_index_calculation_benchmark(): void
    {
        $transactions = Transaction::factory()
            ->pengajuan()
            ->count(100)
            ->create();
        
        $startTime = microtime(true);
        
        foreach ($transactions as $transaction) {
            app(PriceIndexService::class)->detectAnomalies($transaction);
        }
        
        $endTime = microtime(true);
        $avgTime = (($endTime - $startTime) / 100) * 1000;
        
        $this->assertLessThan(50, $avgTime,
            "Average price anomaly detection took {$avgTime}ms, expected < 50ms");
    }
}
```

---

## Security Testing Approach

### Penetration Testing Checklist

1. **Authentication Bypass**
   - Test login with SQL injection
   - Test session fixation
   - Test brute force protection

2. **Authorization Bypass**
   - Test direct object reference
   - Test privilege escalation
   - Test horizontal privilege escalation

3. **Input Validation**
   - Test SQL injection in all inputs
   - Test XSS in all text fields
   - Test command injection in file uploads

4. **File Upload Security**
   - Test malicious file upload (PHP, executable)
   - Test file type spoofing
   - Test directory traversal
   - Test file size limits

5. **API Security**
   - Test CSRF protection
   - Test rate limiting bypass
   - Test webhook signature validation
   - Test API authentication

### Vulnerability Scanning

**Tools**:
- **OWASP ZAP**: Automated security scanner
- **Snyk**: Dependency vulnerability scanner
- **Laravel Security Checker**: Check for known vulnerabilities

```bash
# Run security checker
composer require --dev enlightn/security-checker
php artisan security-check

# Scan dependencies
snyk test
```

---

## Testing Strategy

### Test Execution Order

1. **Unit Tests** (fastest, run first)
2. **Integration Tests** (medium speed)
3. **Security Tests** (critical, run before deployment)
4. **Performance Tests** (slowest, run periodically)

### Test Data Management

**Database Transactions**:
- Use `RefreshDatabase` trait for automatic rollback
- Each test runs in isolated transaction
- No test pollution between tests

**Test Isolation**:
- Clear cache between tests
- Reset queue jobs
- Clear event listeners
- Reset mocked HTTP responses

### Continuous Testing

**Pre-commit Hook**:

```bash
# .git/hooks/pre-commit
#!/bin/sh
php artisan test --group=fast
```

**Pre-push Hook**:

```bash
# .git/hooks/pre-push
#!/bin/sh
php artisan test
./vendor/bin/phpstan analyse
./vendor/bin/pint --test
```

---

## Implementation Roadmap

### Phase 1: Foundation (Week 1)
- Set up test infrastructure
- Create base test case and traits
- Implement mock classes for external APIs
- Create test data factories and seeders

### Phase 2: Unit Tests (Week 2)
- Write unit tests for models
- Write unit tests for services
- Write unit tests for jobs
- Achieve 80% unit test coverage

### Phase 3: Integration Tests (Week 3-4)
- Write authentication tests
- Write transaction workflow tests
- Write payment verification tests
- Write price anomaly tests
- Write branch allocation tests

### Phase 4: Security Tests (Week 5)
- Write authorization tests
- Write input validation tests
- Write file upload security tests
- Write API security tests

### Phase 5: Performance Tests (Week 6)
- Write performance benchmarks
- Write load tests
- Optimize slow tests
- Set up parallel execution

### Phase 6: CI/CD Integration (Week 7)
- Configure GitHub Actions
- Set up code coverage reporting
- Configure automated security scanning
- Document testing procedures

---

## Maintenance and Evolution

### Test Maintenance Guidelines

1. **Keep Tests Updated**: Update tests when requirements change
2. **Refactor Tests**: Apply DRY principle, extract common patterns
3. **Remove Obsolete Tests**: Delete tests for removed features
4. **Monitor Test Performance**: Identify and optimize slow tests
5. **Review Test Failures**: Investigate and fix flaky tests immediately

### Test Quality Metrics

- **Test Coverage**: Track coverage trends over time
- **Test Execution Time**: Monitor and optimize slow tests
- **Test Failure Rate**: Aim for < 1% flaky test rate
- **Test Maintenance Cost**: Time spent fixing broken tests

### Documentation

- **Test README**: Document test structure and conventions
- **Test Examples**: Provide examples for common test patterns
- **Troubleshooting Guide**: Document common test issues and solutions
- **CI/CD Documentation**: Document pipeline configuration and usage

---

## Conclusion

This comprehensive TDD test suite design provides a solid foundation for ensuring the quality, security, and reliability of the AdminPay system. By following the outlined architecture, testing strategies, and implementation roadmap, the development team can build confidence in the system's correctness and maintainability.

The test suite emphasizes:
- **Fast feedback loops** through optimized test execution
- **Comprehensive coverage** of critical business logic and security boundaries
- **Maintainability** through clear organization and reusable components
- **CI/CD integration** for automated quality assurance

With this test suite in place, the team can confidently refactor code, add new features, and deploy to production knowing that regressions will be caught early in the development cycle.
