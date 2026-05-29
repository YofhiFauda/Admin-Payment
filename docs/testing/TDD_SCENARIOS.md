# Test-Driven Development (TDD) Scenarios

## Overview
Dokumen ini berisi skenario TDD untuk Admin Payment Application dengan fokus pada input/output testing untuk kasus positif dan negatif.

---

## 1. Transaction Management

### 1.1 Create Transaction (Pembelian)

#### Positive Scenarios

**Scenario 1.1.1: Create valid purchase transaction**
```php
// Input
$input = [
    'branch_id' => 1,
    'transaction_category_id' => 2,
    'amount' => 150000,
    'description' => 'Pembelian bahan baku',
    'transaction_date' => '2026-05-23',
    'items' => [
        ['name' => 'Tepung', 'quantity' => 10, 'unit_price' => 15000]
    ]
];

// Expected Output
$expected = [
    'status' => 'success',
    'transaction_id' => 'TRX-2026-05-XXXXX',
    'status_code' => 'pending',
    'message' => 'Transaction created successfully'
];
```

**Scenario 1.1.2: Create transaction with multiple items**
```php
// Input
$input = [
    'branch_id' => 1,
    'transaction_category_id' => 2,
    'amount' => 500000,
    'items' => [
        ['name' => 'Tepung', 'quantity' => 10, 'unit_price' => 15000],
        ['name' => 'Gula', 'quantity' => 20, 'unit_price' => 17500]
    ]
];

// Expected Output
$expected = [
    'status' => 'success',
    'items_count' => 2,
    'total_amount' => 500000
];
```

#### Negative Scenarios

**Scenario 1.1.3: Create transaction with invalid branch**
```php
// Input
$input = [
    'branch_id' => 9999, // Non-existent branch
    'amount' => 150000
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'branch_id' => ['The selected branch is invalid.']
    ],
    'status_code' => 422
];
```

**Scenario 1.1.4: Create transaction with negative amount**
```php
// Input
$input = [
    'branch_id' => 1,
    'amount' => -50000
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'amount' => ['The amount must be greater than 0.']
    ],
    'status_code' => 422
];
```

**Scenario 1.1.5: Create transaction without required fields**
```php
// Input
$input = [
    'description' => 'Test'
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'branch_id' => ['The branch id field is required.'],
        'amount' => ['The amount field is required.'],
        'transaction_category_id' => ['The transaction category id field is required.']
    ],
    'status_code' => 422
];
```

---

## 2. OCR Processing

### 2.1 OCR Nota Processing

#### Positive Scenarios

**Scenario 2.1.1: Process valid receipt image**
```php
// Input
$input = [
    'image' => UploadedFile::fake()->image('receipt.jpg', 1024, 768),
    'branch_id' => 1
];

// Expected Output
$expected = [
    'status' => 'success',
    'ocr_status' => 'processing',
    'job_id' => 'uuid-string',
    'message' => 'OCR processing started'
];
```

**Scenario 2.1.2: Process receipt with clear text**
```php
// Input
$input = [
    'image' => 'high_quality_receipt.jpg',
    'branch_id' => 1
];

// Expected Output (after processing)
$expected = [
    'status' => 'completed',
    'extracted_data' => [
        'merchant_name' => 'Toko ABC',
        'total_amount' => 150000,
        'items' => [
            ['name' => 'Item 1', 'price' => 50000],
            ['name' => 'Item 2', 'price' => 100000]
        ],
        'date' => '2026-05-23'
    ],
    'confidence_score' => 0.95
];
```

#### Negative Scenarios

**Scenario 2.1.3: Process invalid image format**
```php
// Input
$input = [
    'image' => UploadedFile::fake()->create('document.pdf', 1000),
    'branch_id' => 1
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'image' => ['The image must be a file of type: jpeg, png, jpg, gif.']
    ],
    'status_code' => 422
];
```

**Scenario 2.1.4: Process oversized image**
```php
// Input
$input = [
    'image' => UploadedFile::fake()->image('large.jpg')->size(15000), // 15MB
    'branch_id' => 1
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'image' => ['The image must not be greater than 10240 kilobytes.']
    ],
    'status_code' => 422
];
```

**Scenario 2.1.5: Process blurry/unreadable receipt**
```php
// Input
$input = [
    'image' => 'blurry_receipt.jpg',
    'branch_id' => 1
];

// Expected Output (after processing)
$expected = [
    'status' => 'failed',
    'ocr_status' => 'failed',
    'error_message' => 'Unable to extract text from image',
    'confidence_score' => 0.15
];
```

---

## 3. Payment Verification

### 3.1 Verify Payment

#### Positive Scenarios

**Scenario 3.1.1: Verify valid payment with matching amount**
```php
// Input
$input = [
    'transaction_id' => 'TRX-2026-05-00001',
    'payment_proof' => UploadedFile::fake()->image('payment.jpg'),
    'paid_amount' => 150000,
    'payment_date' => '2026-05-23',
    'payment_method' => 'bank_transfer'
];

// Expected Output
$expected = [
    'status' => 'success',
    'verification_status' => 'verified',
    'discrepancy' => 0,
    'message' => 'Payment verified successfully'
];
```

**Scenario 3.1.2: Verify payment with minor discrepancy (within tolerance)**
```php
// Input
$input = [
    'transaction_id' => 'TRX-2026-05-00001',
    'expected_amount' => 150000,
    'paid_amount' => 149500, // 500 difference
    'payment_proof' => 'payment.jpg'
];

// Expected Output
$expected = [
    'status' => 'success',
    'verification_status' => 'verified_with_note',
    'discrepancy' => -500,
    'discrepancy_percentage' => 0.33,
    'requires_review' => false
];
```

#### Negative Scenarios

**Scenario 3.1.3: Verify payment with major discrepancy**
```php
// Input
$input = [
    'transaction_id' => 'TRX-2026-05-00001',
    'expected_amount' => 150000,
    'paid_amount' => 100000, // 50000 difference
    'payment_proof' => 'payment.jpg'
];

// Expected Output
$expected = [
    'status' => 'warning',
    'verification_status' => 'discrepancy_detected',
    'discrepancy' => -50000,
    'discrepancy_percentage' => 33.33,
    'requires_review' => true,
    'audit_created' => true
];
```

**Scenario 3.1.4: Verify payment without proof**
```php
// Input
$input = [
    'transaction_id' => 'TRX-2026-05-00001',
    'paid_amount' => 150000
    // Missing payment_proof
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'payment_proof' => ['The payment proof field is required.']
    ],
    'status_code' => 422
];
```

**Scenario 3.1.5: Verify non-existent transaction**
```php
// Input
$input = [
    'transaction_id' => 'TRX-9999-99-99999',
    'paid_amount' => 150000,
    'payment_proof' => 'payment.jpg'
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Transaction not found',
    'status_code' => 404
];
```

---

## 4. Price Index & Anomaly Detection

### 4.1 Calculate Price Index

#### Positive Scenarios

**Scenario 4.1.1: Calculate price index for stable prices**
```php
// Input
$input = [
    'item_name' => 'Tepung Terigu',
    'branch_id' => 1,
    'current_price' => 15000,
    'quantity' => 10
];

// Expected Output
$expected = [
    'status' => 'success',
    'price_index' => 1.0,
    'average_price' => 15000,
    'deviation' => 0,
    'anomaly_detected' => false
];
```

**Scenario 4.1.2: Calculate price index with slight increase**
```php
// Input
$input = [
    'item_name' => 'Gula Pasir',
    'branch_id' => 1,
    'current_price' => 18000,
    'historical_average' => 17000
];

// Expected Output
$expected = [
    'status' => 'success',
    'price_index' => 1.059,
    'price_change_percentage' => 5.9,
    'anomaly_detected' => false
];
```

#### Negative Scenarios

**Scenario 4.1.3: Detect price anomaly (spike)**
```php
// Input
$input = [
    'item_name' => 'Tepung Terigu',
    'branch_id' => 1,
    'current_price' => 30000,
    'historical_average' => 15000
];

// Expected Output
$expected = [
    'status' => 'warning',
    'price_index' => 2.0,
    'price_change_percentage' => 100,
    'anomaly_detected' => true,
    'anomaly_type' => 'spike',
    'notification_sent' => true
];
```

**Scenario 4.1.4: Detect price anomaly (drop)**
```php
// Input
$input = [
    'item_name' => 'Gula Pasir',
    'branch_id' => 1,
    'current_price' => 8000,
    'historical_average' => 17000
];

// Expected Output
$expected = [
    'status' => 'warning',
    'price_index' => 0.47,
    'price_change_percentage' => -52.9,
    'anomaly_detected' => true,
    'anomaly_type' => 'drop',
    'requires_investigation' => true
];
```

**Scenario 4.1.5: Calculate with insufficient historical data**
```php
// Input
$input = [
    'item_name' => 'New Item',
    'branch_id' => 1,
    'current_price' => 25000
];

// Expected Output
$expected = [
    'status' => 'info',
    'price_index' => null,
    'message' => 'Insufficient historical data for price index calculation',
    'data_points' => 1,
    'minimum_required' => 5
];
```

---

## 5. User Authentication & Authorization

### 5.1 User Login

#### Positive Scenarios

**Scenario 5.1.1: Login with valid credentials**
```php
// Input
$input = [
    'email' => 'admin@example.com',
    'password' => 'correct_password'
];

// Expected Output
$expected = [
    'status' => 'success',
    'token' => 'jwt_token_string',
    'user' => [
        'id' => 1,
        'name' => 'Admin User',
        'role' => 'admin'
    ],
    'expires_in' => 3600
];
```

#### Negative Scenarios

**Scenario 5.1.2: Login with invalid password**
```php
// Input
$input = [
    'email' => 'admin@example.com',
    'password' => 'wrong_password'
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Invalid credentials',
    'status_code' => 401
];
```

**Scenario 5.1.3: Login with non-existent email**
```php
// Input
$input = [
    'email' => 'nonexistent@example.com',
    'password' => 'any_password'
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Invalid credentials',
    'status_code' => 401
];
```

**Scenario 5.1.4: Login with missing fields**
```php
// Input
$input = [
    'email' => 'admin@example.com'
    // Missing password
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'password' => ['The password field is required.']
    ],
    'status_code' => 422
];
```

---

## 6. Telegram Bot Integration

### 6.1 Telegram Webhook

#### Positive Scenarios

**Scenario 6.1.1: Receive valid transaction notification**
```php
// Input
$input = [
    'message' => [
        'chat' => ['id' => 123456789],
        'text' => '/status TRX-2026-05-00001'
    ]
];

// Expected Output
$expected = [
    'status' => 'success',
    'response_sent' => true,
    'message' => 'Transaction TRX-2026-05-00001 status: pending'
];
```

**Scenario 6.1.2: Receive approval command**
```php
// Input
$input = [
    'message' => [
        'chat' => ['id' => 123456789],
        'text' => '/approve TRX-2026-05-00001'
    ],
    'user_role' => 'owner'
];

// Expected Output
$expected = [
    'status' => 'success',
    'transaction_updated' => true,
    'new_status' => 'approved',
    'notification_sent' => true
];
```

#### Negative Scenarios

**Scenario 6.1.3: Unauthorized approval attempt**
```php
// Input
$input = [
    'message' => [
        'chat' => ['id' => 987654321],
        'text' => '/approve TRX-2026-05-00001'
    ],
    'user_role' => 'staff'
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Unauthorized: Only owners can approve transactions',
    'response_sent' => true
];
```

**Scenario 6.1.4: Invalid command format**
```php
// Input
$input = [
    'message' => [
        'chat' => ['id' => 123456789],
        'text' => '/approve'
        // Missing transaction ID
    ]
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Invalid command format. Usage: /approve <transaction_id>',
    'response_sent' => true
];
```

---

## 7. Branch Management

### 7.1 Create Branch

#### Positive Scenarios

**Scenario 7.1.1: Create valid branch**
```php
// Input
$input = [
    'name' => 'Cabang Jakarta Selatan',
    'code' => 'JKT-S',
    'address' => 'Jl. Sudirman No. 123',
    'phone' => '021-12345678'
];

// Expected Output
$expected = [
    'status' => 'success',
    'branch_id' => 5,
    'message' => 'Branch created successfully'
];
```

#### Negative Scenarios

**Scenario 7.1.2: Create branch with duplicate code**
```php
// Input
$input = [
    'name' => 'Cabang Baru',
    'code' => 'JKT-S', // Already exists
    'address' => 'Jl. Test'
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'code' => ['The code has already been taken.']
    ],
    'status_code' => 422
];
```

**Scenario 7.1.3: Create branch with invalid phone format**
```php
// Input
$input = [
    'name' => 'Cabang Test',
    'code' => 'TST',
    'phone' => 'invalid-phone'
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'phone' => ['The phone format is invalid.']
    ],
    'status_code' => 422
];
```

---

## 8. Salary Management

### 8.1 Process Salary

#### Positive Scenarios

**Scenario 8.1.1: Process valid salary payment**
```php
// Input
$input = [
    'user_id' => 5,
    'branch_id' => 1,
    'amount' => 5000000,
    'period' => '2026-05',
    'payment_date' => '2026-05-25'
];

// Expected Output
$expected = [
    'status' => 'success',
    'salary_record_id' => 123,
    'transaction_created' => true,
    'message' => 'Salary processed successfully'
];
```

#### Negative Scenarios

**Scenario 8.1.2: Process duplicate salary for same period**
```php
// Input
$input = [
    'user_id' => 5,
    'branch_id' => 1,
    'amount' => 5000000,
    'period' => '2026-05' // Already processed
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Salary for this period has already been processed',
    'existing_record_id' => 122,
    'status_code' => 409
];
```

**Scenario 8.1.3: Process salary with zero amount**
```php
// Input
$input = [
    'user_id' => 5,
    'amount' => 0,
    'period' => '2026-05'
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'amount' => ['The amount must be greater than 0.']
    ],
    'status_code' => 422
];
```

---

## 9. Activity Logging

### 9.1 Log Activity

#### Positive Scenarios

**Scenario 9.1.1: Log transaction creation**
```php
// Input
$input = [
    'user_id' => 1,
    'action' => 'create',
    'model' => 'Transaction',
    'model_id' => 100,
    'changes' => ['status' => 'pending']
];

// Expected Output
$expected = [
    'status' => 'success',
    'log_id' => 500,
    'logged_at' => '2026-05-23 10:30:00'
];
```

#### Negative Scenarios

**Scenario 9.1.2: Log with invalid model**
```php
// Input
$input = [
    'user_id' => 1,
    'action' => 'create',
    'model' => 'NonExistentModel',
    'model_id' => 100
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Invalid model type',
    'status_code' => 422
];
```

---

## 10. Dashboard Statistics

### 10.1 Get Dashboard Data

#### Positive Scenarios

**Scenario 10.1.1: Get dashboard for admin**
```php
// Input
$input = [
    'user_role' => 'admin',
    'date_range' => ['2026-05-01', '2026-05-31']
];

// Expected Output
$expected = [
    'status' => 'success',
    'data' => [
        'total_transactions' => 150,
        'pending_approvals' => 12,
        'total_amount' => 50000000,
        'branches_count' => 5
    ]
];
```

**Scenario 10.1.2: Get dashboard for branch manager**
```php
// Input
$input = [
    'user_role' => 'manager',
    'branch_id' => 1,
    'date_range' => ['2026-05-01', '2026-05-31']
];

// Expected Output
$expected = [
    'status' => 'success',
    'data' => [
        'branch_transactions' => 30,
        'branch_pending' => 3,
        'branch_total_amount' => 10000000
    ]
];
```

#### Negative Scenarios

**Scenario 10.1.3: Get dashboard with invalid date range**
```php
// Input
$input = [
    'user_role' => 'admin',
    'date_range' => ['2026-05-31', '2026-05-01'] // End before start
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'date_range' => ['End date must be after start date.']
    ],
    'status_code' => 422
];
```

---

## Implementation Guidelines

### Test Structure
```php
// tests/Feature/TransactionTest.php
class TransactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_valid_purchase_transaction()
    {
        // Arrange
        $user = User::factory()->create();
        $branch = Branch::factory()->create();
        
        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/transactions', [
                'branch_id' => $branch->id,
                'amount' => 150000,
                // ... other fields
            ]);
        
        // Assert
        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);
    }

    /** @test */
    public function it_rejects_transaction_with_negative_amount()
    {
        // Arrange
        $user = User::factory()->create();
        
        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/transactions', [
                'amount' => -50000,
            ]);
        
        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }
}
```

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/TransactionTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter it_creates_valid_purchase_transaction
```

### Test Database Setup
Ensure `phpunit.xml` or `.env.testing` has:
```xml
<env name="DB_DATABASE" value="admin_payment_testing"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="CACHE_DRIVER" value="array"/>
```

---

## Notes

1. **Positive scenarios** validate expected behavior with valid inputs
2. **Negative scenarios** ensure proper error handling and validation
3. All scenarios should be implemented as automated tests
4. Use factories for test data generation
5. Mock external services (Telegram, OCR APIs) in tests
6. Follow AAA pattern: Arrange, Act, Assert
7. Keep tests isolated and independent
8. Clean up test data after each test (RefreshDatabase trait)

---

## 11. Rembush (Reimbursement) Management

### 11.1 Create Rembush with OCR

#### Positive Scenarios

**Scenario 11.1.1: Upload valid receipt for OCR processing**
```php
// Input
$input = [
    'file' => UploadedFile::fake()->image('receipt.jpg', 1024, 768),
];

// Expected Output
$expected = [
    'status' => 'success',
    'upload_id' => 'UP-20260523-00001',
    'redirect' => 'rembush.form',
    'session_data' => [
        'upload_id',
        'upload_file_path',
        'upload_file_base64',
        'upload_file_mime'
    ]
];
```

**Scenario 11.1.2: Create rembush with transfer_teknisi payment**
```php
// Input
$input = [
    'customer' => 'Toko ABC',
    'category' => 'Pembelian Bahan',
    'amount' => 150000,
    'payment_method' => 'transfer_teknisi',
    'description' => 'Pembelian bahan baku',
    'date' => '2026-05-23',
    'branches' => [
        ['branch_id' => 1, 'allocation_percent' => 100]
    ]
];

// Expected Output
$expected = [
    'status' => 'success',
    'transaction_id' => 'TRX-2026-05-00001',
    'invoice_number' => 'INV-20260523-00001',
    'ai_status' => 'queued',
    'ocr_job_dispatched' => true
];
```

**Scenario 11.1.3: Create rembush with transfer_penjual (bank details)**
```php
// Input
$input = [
    'customer' => 'Supplier XYZ',
    'category' => 'Pembelian Bahan',
    'amount' => 500000,
    'payment_method' => 'transfer_penjual',
    'bank_name' => 'BCA',
    'account_name' => 'PT Supplier XYZ',
    'account_number' => '1234567890',
    'branches' => [
        ['branch_id' => 1, 'allocation_percent' => 100]
    ]
];

// Expected Output
$expected = [
    'status' => 'success',
    'specs' => [
        'bank_name' => 'BCA',
        'account_name' => 'PT SUPPLIER XYZ',
        'account_number' => '1234567890'
    ]
];
```

#### Negative Scenarios

**Scenario 11.1.4: Upload rate limit exceeded**
```php
// Input (6th upload within 1 minute)
$input = [
    'file' => UploadedFile::fake()->image('receipt.jpg'),
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Terlalu banyak upload. Tunggu X detik.',
    'throttled' => true,
    'retry_after' => 45
];
```

**Scenario 11.1.5: Create rembush without bank details for transfer_penjual**
```php
// Input
$input = [
    'customer' => 'Supplier XYZ',
    'amount' => 500000,
    'payment_method' => 'transfer_penjual',
    // Missing bank_name, account_name, account_number
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'bank_details' => ['Nama Bank, Nama Rekening, dan Nomor Rekening wajib diisi untuk Transfer ke Penjual.']
    ],
    'status_code' => 422
];
```

**Scenario 11.1.6: Create rembush with invalid branch allocation**
```php
// Input
$input = [
    'customer' => 'Toko ABC',
    'amount' => 150000,
    'payment_method' => 'cash',
    'branches' => [
        ['branch_id' => 1, 'allocation_percent' => 60],
        ['branch_id' => 2, 'allocation_percent' => 30]
        // Total = 90%, not 100%
    ]
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'branches' => ['Total alokasi alokasi harus 100%.']
    ],
    'status_code' => 422
];
```

---

## 12. Other Expenditure (Pengeluaran Lain)

### 12.1 Bayar Hutang (Debt Payment)

#### Positive Scenarios

**Scenario 12.1.1: Create debt payment record**
```php
// Input
$input = [
    'jenis' => 'bayar_hutang',
    'tanggal' => '2026-05-23',
    'nominal' => 1000000,
    'branch_id' => 1, // Creditor branch
    'dari_cabang_id' => 2, // Debtor branch
    'keterangan' => 'Pelunasan hutang pembelian',
    'bukti_transfer' => UploadedFile::fake()->image('transfer.jpg')
];

// Expected Output
$expected = [
    'status' => 'success',
    'invoice_number' => 'PL-20260523-00001',
    'jenis' => 'bayar_hutang',
    'status_record' => 'pending'
];
```

#### Negative Scenarios

**Scenario 12.1.2: Create debt payment without required branches**
```php
// Input
$input = [
    'jenis' => 'bayar_hutang',
    'tanggal' => '2026-05-23',
    'nominal' => 1000000,
    // Missing branch_id and dari_cabang_id
];

// Expected Output
$expected = [
    'status' => 'error',
    'errors' => [
        'branch_id' => ['The branch id field is required.'],
        'dari_cabang_id' => ['The dari cabang id field is required.']
    ],
    'status_code' => 422
];
```

### 12.2 Prive (Owner Withdrawal)

#### Positive Scenarios

**Scenario 12.2.1: Create prive record (Owner only)**
```php
// Input
$input = [
    'jenis' => 'prive',
    'tanggal' => '2026-05-23',
    'nominal' => 5000000,
    'dari_cabang_id' => 1,
    'rekening_tujuan' => 'BCA - 1234567890 - John Doe',
    'keterangan' => 'Prive bulan Mei',
    'bukti_transfer' => UploadedFile::fake()->image('transfer.jpg')
];

// Expected Output
$expected = [
    'status' => 'success',
    'invoice_number' => 'PL-20260523-00002',
    'jenis' => 'prive'
];
```

#### Negative Scenarios

**Scenario 12.2.2: Unauthorized access to prive (non-owner)**
```php
// Input
$input = [
    'user_role' => 'teknisi', // Not owner/atasan
    'jenis' => 'prive'
];

// Expected Output
$expected = [
    'status' => 'error',
    'message' => 'Hanya Atasan dan Owner yang bisa mengakses Prive.',
    'status_code' => 403
];
```

**Scenario 12.2.3: Delete non-pending record**
```php
// Input
$input = [
    'record_id' => 123,
    'record_status' => 'completed'
];

// Expected Output
$expected = [
    'status' => 'warning',
    'message' => '⚠️ Hanya record dengan status Pending yang bisa dihapus.'
];
```

---

## 13. Branch Debt Management

### 13.1 Inter-Branch Debt Tracking

#### Positive Scenarios

**Scenario 13.1.1: Create branch debt from multi-branch transaction**
```php
// Input
$input = [
    'transaction_id' => 100,
    'debtor_branch_id' => 2,
    'creditor_branch_id' => 1,
    'amount' => 500000,
    'description' => 'Alokasi pembelian bersama'
];

// Expected Output
$expected = [
    'status' => 'success',
    'debt_id' => 50,
    'status_debt' => 'pending',
    'debtor_branch' => 'Cabang B',
    'creditor_branch' => 'Cabang A'
];
```

**Scenario 13.1.2: Mark debt as paid**
```php
// Input
$input = [
    'debt_id' => 50,
    'paid_by' => 5, // User ID
    'payment_proof' => UploadedFile::fake()->image('payment.jpg'),
    'paid_at' => '2026-05-23 14:30:00'
];

// Expected Output
$expected = [
    'status' => 'success',
    'debt_status' => 'paid',
    'paid_by_name' => 'Admin User',
    'notification_sent' => true
];
```

#### Negative Scenarios

**Scenario 13.1.3: Query debts with invalid status filter**
```php
// Input
$input = [
    'debt_status' => 'invalid_status'
];

// Expected Output
$expected = [
    'status' => 'success',
    'debts' => [], // Returns all debts, ignores invalid filter
    'filter_applied' => false
];
```

---

## 14. Transaction Versioning & Revision

### 14.1 Management Edit Tracking

#### Positive Scenarios

**Scenario 14.1.1: Management edits pengajuan items**
```php
// Input
$input = [
    'transaction_id' => 100,
    'editor_user_id' => 2, // Owner/Atasan
    'items' => [
        [
            'customer' => 'Laptop Dell (Edited)',
            'estimated_price' => 12000000, // Changed from 15000000
            'quantity' => 1
        ]
    ]
];

// Expected Output
$expected = [
    'status' => 'success',
    'is_edited_by_management' => true,
    'revision_count' => 1,
    'items_snapshot' => [/* original items */],
    'changes' => [
        [
            'index' => 0,
            'type' => 'modified',
            'fields' => [
                'customer' => ['old' => 'Laptop Dell', 'new' => 'Laptop Dell (Edited)'],
                'estimated_price' => ['old' => 15000000, 'new' => 12000000]
            ]
        ]
    ]
];
```

**Scenario 14.1.2: Get revision history**
```php
// Input
$input = [
    'transaction_id' => 100
];

// Expected Output
$expected = [
    'status' => 'success',
    'has_revisions' => true,
    'revision_count' => 2,
    'original_version' => [/* teknisi version */],
    'management_version' => [/* edited version */],
    'editor_name' => 'Owner User',
    'edited_at' => '2026-05-23 10:30:00'
];
```

#### Negative Scenarios

**Scenario 14.1.3: Get changes for non-edited transaction**
```php
// Input
$input = [
    'transaction_id' => 101,
    'is_edited_by_management' => false
];

// Expected Output
$expected = [
    'status' => 'success',
    'has_revisions' => false,
    'changes' => []
];
```

---

## 15. Real-time Features & Broadcasting

### 15.1 Transaction Events

#### Positive Scenarios

**Scenario 15.1.1: Broadcast transaction created event**
```php
// Input
$input = [
    'transaction_id' => 100,
    'event' => 'TransactionCreated'
];

// Expected Output
$expected = [
    'status' => 'success',
    'broadcast_sent' => true,
    'channels' => ['transactions'],
    'data' => [
        'transaction' => [/* transaction data */]
    ]
];
```

**Scenario 15.1.2: Broadcast OCR status update**
```php
// Input
$input = [
    'upload_id' => 'UP-20260523-00001',
    'ocr_status' => 'completed',
    'confidence' => 95
];

// Expected Output
$expected = [
    'status' => 'success',
    'event' => 'OcrStatusUpdated',
    'broadcast_sent' => true
];
```

#### Negative Scenarios

**Scenario 15.1.3: Broadcast fails (non-fatal)**
```php
// Input
$input = [
    'transaction_id' => 100,
    'broadcast_service' => 'down'
];

// Expected Output
$expected = [
    'status' => 'success', // Transaction still saved
    'transaction_created' => true,
    'broadcast_sent' => false,
    'broadcast_error' => 'Connection refused',
    'logged' => true
];
```

---

## 16. Image Compression Service

### 16.1 Compress Uploaded Images

#### Positive Scenarios

**Scenario 16.1.1: Compress large JPEG image**
```php
// Input
$input = [
    'file_path' => 'storage/app/public/UP-20260523-00001.jpg',
    'original_size' => 5242880, // 5MB
];

// Expected Output
$expected = [
    'status' => 'success',
    'compressed' => true,
    'original_size' => 5242880,
    'compressed_size' => 1048576, // ~1MB
    'compression_ratio' => 80
];
```

**Scenario 16.1.2: Skip compression for PDF**
```php
// Input
$input = [
    'file_path' => 'storage/app/public/UP-20260523-00001.pdf',
];

// Expected Output
$expected = [
    'status' => 'skipped',
    'reason' => 'PDF files are not compressed',
    'original_size' => 2097152
];
```

#### Negative Scenarios

**Scenario 16.1.3: Compression fails for corrupted image**
```php
// Input
$input = [
    'file_path' => 'storage/app/public/corrupted.jpg',
];

// Expected Output
$expected = [
    'status' => 'error',
    'compressed' => false,
    'error' => 'Unable to read image file',
    'original_file_preserved' => true
];
```

---

## 17. Master Item Catalog & Autocomplete

### 17.1 Item Matching Service

#### Positive Scenarios

**Scenario 17.1.1: Find best match for existing item**
```php
// Input
$input = [
    'search_term' => 'tepung terigu',
    'category_id' => 'bahan_baku'
];

// Expected Output
$expected = [
    'status' => 'success',
    'match_found' => true,
    'master_item_id' => 50,
    'display_name' => 'Tepung Terigu',
    'similarity_score' => 0.95
];
```

**Scenario 17.1.2: Create pending item for new term**
```php
// Input
$input = [
    'search_term' => 'Barang Baru Belum Ada',
    'category_id' => 'peralatan',
    'submitted_by' => 5
];

// Expected Output
$expected = [
    'status' => 'success',
    'master_item_id' => 150,
    'display_name' => 'Barang Baru Belum Ada',
    'approval_status' => 'pending_approval',
    'created_by' => 5
];
```

#### Negative Scenarios

**Scenario 17.1.3: Search with empty term**
```php
// Input
$input = [
    'search_term' => '',
    'category_id' => 'bahan_baku'
];

// Expected Output
$expected = [
    'status' => 'error',
    'match_found' => false,
    'error' => 'Search term cannot be empty'
];
```

---

## 18. Cache Management

### 18.1 Transaction Stats Cache

#### Positive Scenarios

**Scenario 18.1.1: Cache hit for transaction stats**
```php
// Input
$input = [
    'user_id' => 5,
    'cache_key' => 'transactions_stats_teknisi_5'
];

// Expected Output
$expected = [
    'status' => 'success',
    'cache_hit' => true,
    'data' => [
        'total_transactions' => 50,
        'pending' => 5,
        'approved' => 30,
        'completed' => 15
    ],
    'ttl' => 300
];
```

**Scenario 18.1.2: Cache invalidation on transaction update**
```php
// Input
$input = [
    'transaction_id' => 100,
    'submitted_by' => 5,
    'action' => 'update'
];

// Expected Output
$expected = [
    'status' => 'success',
    'caches_cleared' => [
        'transactions_stats_global',
        'transactions_stats_teknisi_5'
    ]
];
```

#### Negative Scenarios

**Scenario 18.1.3: Cache miss - fetch from database**
```php
// Input
$input = [
    'user_id' => 5,
    'cache_key' => 'transactions_stats_teknisi_5'
];

// Expected Output
$expected = [
    'status' => 'success',
    'cache_hit' => false,
    'data_source' => 'database',
    'cache_stored' => true,
    'query_time_ms' => 45
];
```

---

## 19. Session Management

### 19.1 Upload Session Data

#### Positive Scenarios

**Scenario 19.1.1: Store upload session data**
```php
// Input
$input = [
    'upload_id' => 'UP-20260523-00001',
    'file_path' => 'temp-uploads/UP-20260523-00001.jpg',
    'base64' => 'data:image/jpeg;base64,...',
    'mime' => 'image/jpeg'
];

// Expected Output
$expected = [
    'status' => 'success',
    'session_stored' => true,
    'keys' => ['upload_id', 'upload_file_path', 'upload_file_base64', 'upload_file_mime']
];
```

**Scenario 19.1.2: Clear session after transaction save**
```php
// Input
$input = [
    'transaction_saved' => true
];

// Expected Output
$expected = [
    'status' => 'success',
    'session_cleared' => true,
    'keys_removed' => ['upload_id', 'upload_file_path', 'upload_file_base64', 'upload_file_mime', 'ai_data']
];
```

#### Negative Scenarios

**Scenario 19.1.3: Access form without upload session**
```php
// Input
$input = [
    'route' => 'rembush.form',
    'session_upload_id' => null
];

// Expected Output
$expected = [
    'status' => 'redirect',
    'redirect_to' => 'transactions.create',
    'reason' => 'No upload session found'
];
```

---

## 20. Frontend JavaScript Testing

### 20.1 Search Engine (Client-side)

#### Positive Scenarios

**Scenario 20.1.1: Filter transactions by status**
```javascript
// Input
const input = {
    transactions: [...], // 100 transactions
    filter: { status: 'pending' }
};

// Expected Output
const expected = {
    filtered_count: 12,
    results: [/* pending transactions */],
    execution_time_ms: 5
};
```

**Scenario 20.1.2: Search by invoice number**
```javascript
// Input
const input = {
    transactions: [...],
    search_term: 'INV-2026'
};

// Expected Output
const expected = {
    matched_count: 25,
    results: [/* matching transactions */]
};
```

#### Negative Scenarios

**Scenario 20.1.3: Search with no results**
```javascript
// Input
const input = {
    transactions: [...],
    search_term: 'NONEXISTENT'
};

// Expected Output
const expected = {
    matched_count: 0,
    results: [],
    message: 'No transactions found'
};
```

### 20.2 Real-time Updates (Echo/Reverb)

#### Positive Scenarios

**Scenario 20.2.1: Receive transaction created event**
```javascript
// Input
const input = {
    channel: 'transactions',
    event: 'TransactionCreated',
    data: { transaction: {...} }
};

// Expected Output
const expected = {
    ui_updated: true,
    notification_shown: true,
    transaction_added_to_list: true
};
```

#### Negative Scenarios

**Scenario 20.2.2: Connection lost - reconnect**
```javascript
// Input
const input = {
    connection_status: 'disconnected'
};

// Expected Output
const expected = {
    reconnect_attempted: true,
    retry_count: 3,
    fallback_to_polling: true
};
```

---

## Test Implementation Priority

### High Priority (Core Features)
1. ✅ Transaction Management (Create, Update, Delete)
2. ✅ OCR Processing & Rembush Flow
3. ✅ Payment Verification
4. ✅ User Authentication & Authorization
5. ✅ Branch Allocation & Debt Management

### Medium Priority (Business Logic)
6. ✅ Price Index & Anomaly Detection
7. ✅ Transaction Versioning & Revision
8. ✅ Other Expenditure (Pengeluaran Lain)
9. ✅ Telegram Bot Integration
10. ✅ Master Item Catalog

### Low Priority (Supporting Features)
11. ✅ Image Compression
12. ✅ Cache Management
13. ✅ Session Management
14. ✅ Activity Logging
15. ✅ Real-time Broadcasting

### Frontend Testing
16. ✅ JavaScript Unit Tests (Vitest)
17. ✅ Search Engine Logic
18. ✅ Real-time Updates
19. ✅ Form Validation

---

## Additional Test Types

### Integration Tests
```php
/** @test */
public function it_completes_full_rembush_workflow()
{
    // 1. Upload receipt
    // 2. OCR processing
    // 3. Create transaction
    // 4. Approve transaction
    // 5. Upload payment proof
    // 6. Verify payment
    // 7. Complete transaction
}
```

### Performance Tests
```php
/** @test */
public function it_handles_large_transaction_list_efficiently()
{
    // Create 10,000 transactions
    // Measure query time
    // Assert < 100ms response time
}
```

### Security Tests
```php
/** @test */
public function it_prevents_unauthorized_access_to_prive()
{
    $teknisi = User::factory()->create(['role' => 'teknisi']);
    
    $response = $this->actingAs($teknisi)
        ->get(route('pengeluaran-lain.prive.index'));
    
    $response->assertStatus(403);
}
```

---

## Next Steps

1. ✅ Implement test cases for each scenario (prioritize High Priority first)
2. ✅ Add integration tests for complex workflows (Rembush, Pengajuan, Payment)
3. ✅ Set up CI/CD pipeline to run tests automatically
4. ✅ Monitor test coverage (aim for >80%)
5. ✅ Add frontend tests using Vitest for JavaScript modules
6. ✅ Implement performance tests for critical queries
7. ✅ Add security tests for authorization and access control
8. ✅ Update scenarios as new features are added
9. ✅ Document test data factories and seeders
10. ✅ Create test utilities for common assertions
