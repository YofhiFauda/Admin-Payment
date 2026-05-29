# Running Tests - Panduan Lengkap

## 📋 Overview

Panduan ini menjelaskan cara menjalankan test suite menggunakan **database Docker yang sudah ada**, tanpa perlu membuat database baru.

## ⚠️ PENTING: Jalankan Tests di Host (Bukan di Docker)

**Tests harus dijalankan di host machine (Windows/Linux/Mac), BUKAN di dalam Docker container.**

**Alasan:**
- File `phpunit.xml` dan folder `tests/` di-exclude dari Docker image (`.dockerignore`)
- Docker image production tidak include test files untuk efisiensi
- Tests di host lebih cepat dan mudah di-debug

**Cara yang BENAR:**
```bash
# ✅ Di host machine (Windows PowerShell/CMD/Terminal)
php artisan test --env=testing
composer run test
```

**Cara yang SALAH:**
```bash
# ❌ JANGAN jalankan di dalam Docker
docker exec -it admin-payment-app-1 php artisan test
```

---

## 🔧 Setup Awal (Hanya Sekali)

### 1. Pastikan Database Docker Berjalan

```bash
# Check Docker containers
docker ps

# Pastikan MySQL container berjalan
# Container name: s4g9fygoajcwzuphriodko8z
```

### 2. Buat Database Testing

**Windows (PowerShell) - RECOMMENDED:**
```powershell
# Buat database testing
.\scripts\create-test-database.ps1
```

**Linux/Mac:**
```bash
chmod +x scripts/create-test-database.sh
./scripts/create-test-database.sh
```

**Manual (jika script gagal):**
```bash
# Connect ke MySQL Docker
mysql -h s4g9fygoajcwzuphriodko8z -P 3306 -u digitalconnexa -p

# Masukkan password: OPgQ9KFQVYeKakOz8YUeGJmBVDIusw1w0Fsvf7FSkUFUtECJxiUOSCC18dsryXn7

# Buat database testing
CREATE DATABASE IF NOT EXISTS admin_payment_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Keluar
exit;
```

### 3. Jalankan Migration untuk Testing Database

**⚠️ PENTING: Jalankan di HOST, bukan di Docker!**

```bash
# Di host machine (Windows PowerShell/Terminal)
php artisan migrate --env=testing
```

### 4. (Optional) Seed Data Testing

```bash
php artisan db:seed --env=testing
```

---

## 🚀 Menjalankan Tests (Di Host Machine)

### ⚠️ CRITICAL: Semua commands harus dijalankan di HOST, BUKAN di Docker!

### Opsi 1: Menggunakan Composer Scripts (Recommended)

```bash
# Run all tests
composer run test

# Run with coverage
composer run test:coverage

# Generate HTML coverage report
composer run test:coverage-html

# Run tests in parallel (faster)
composer run test:parallel

# Run Critical Priority tests only
composer run test:critical

# Run Feature tests only
composer run test:feature

# Run Unit tests only
composer run test:unit

# Fast mode (stop on first failure)
composer run test:fast
```

### Opsi 2: Menggunakan PHP Artisan

```bash
# Run all tests
php artisan test --env=testing

# Run with coverage
php artisan test --env=testing --coverage

# Run with minimum coverage threshold
php artisan test --env=testing --coverage --min=80

# Run specific test file
php artisan test --env=testing tests/Feature/TransactionApprovalTest.php

# Run specific test method
php artisan test --env=testing --filter it_requires_owner_approval

# Run tests matching pattern
php artisan test --env=testing --filter approval
php artisan test --env=testing --filter price
php artisan test --env=testing --filter telegram

# Verbose output
php artisan test --env=testing --verbose

# Stop on first failure
php artisan test --env=testing --stop-on-failure

# Run in parallel
php artisan test --env=testing --parallel

# Show test execution time
php artisan test --env=testing --profile
```

### Opsi 3: Menggunakan Shell Script

**Linux/Mac:**
```bash
chmod +x scripts/run-tests.sh

# Run all tests
./scripts/run-tests.sh all

# Run with coverage
./scripts/run-tests.sh all coverage

# Run by priority
./scripts/run-tests.sh critical
./scripts/run-tests.sh high
./scripts/run-tests.sh medium
./scripts/run-tests.sh low

# Run by type
./scripts/run-tests.sh feature
./scripts/run-tests.sh unit

# Run in parallel
./scripts/run-tests.sh parallel

# Fast mode
./scripts/run-tests.sh fast
```

### Opsi 4: Menggunakan PHPUnit Directly

```bash
# Run all tests
./vendor/bin/phpunit

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage

# Run specific test
./vendor/bin/phpunit tests/Feature/TransactionApprovalTest.php

# Run with filter
./vendor/bin/phpunit --filter it_requires_owner_approval
```

---

## 📊 Test Suites

### Critical Priority (5 files, 85 tests)
```bash
composer run test:critical

# Or manually:
php artisan test --env=testing \
    tests/Feature/PengajuanManagementTest.php \
    tests/Feature/PembelianManagementTest.php \
    tests/Feature/TransactionSearchTest.php \
    tests/Feature/NotificationSystemTest.php \
    tests/Feature/FileUploadTest.php
```

### High Priority (5 files, 104 tests)
```bash
php artisan test --env=testing \
    tests/Feature/AiAutoFillTest.php \
    tests/Feature/ItemAutocompleteTest.php \
    tests/Feature/TransactionStatusTest.php \
    tests/Feature/UserBankAccountTest.php \
    tests/Feature/BranchBankAccountTest.php
```

### Medium Priority (5 files, 125 tests)
```bash
php artisan test --env=testing \
    tests/Feature/TransactionExportTest.php \
    tests/Feature/RembushEditTest.php \
    tests/Feature/TransactionConfirmationTest.php \
    tests/Feature/EventBroadcastingTest.php \
    tests/Feature/JobQueueTest.php
```

### Low Priority (7 files, 193 tests)
```bash
php artisan test --env=testing \
    tests/Feature/RateLimitingTest.php \
    tests/Feature/MiddlewareTest.php \
    tests/Unit/IdGeneratorServiceTest.php \
    tests/Feature/FormValidationTest.php \
    tests/Feature/ApiVersioningTest.php \
    tests/Feature/ErrorHandlingTest.php \
    tests/Performance/LoadTest.php
```

---

## 🎯 Workflow Recommendations

### During Development
```bash
# Quick feedback - run specific test
php artisan test --env=testing --filter your_test_name

# Or run specific file
php artisan test --env=testing tests/Feature/YourTest.php
```

### Before Commit
```bash
# Run all tests, stop on failure
composer run test:fast
```

### Before Push
```bash
# Run with coverage
composer run test:coverage
```

### Before Production Deploy
```bash
# Full test suite with HTML coverage
composer run test:coverage-html

# Then open: coverage/index.html
```

---

## 🔍 Troubleshooting

### Database Connection Error

**Problem:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Solution:**
```bash
# 1. Check if MySQL Docker is running
docker ps | grep mysql

# 2. Check database credentials in .env.testing
cat .env.testing | grep DB_

# 3. Test connection manually
mysql -h s4g9fygoajcwzuphriodko8z -P 3306 -u digitalconnexa -p
```

### Database Not Found

**Problem:**
```
SQLSTATE[HY000] [1049] Unknown database 'admin_payment_testing'
```

**Solution:**
```bash
# Create database
./scripts/create-test-database.sh

# Run migrations
php artisan migrate --env=testing
```

### Tests Running Slow

**Solution:**
```bash
# Use parallel execution
composer run test:parallel

# Or run specific priority
composer run test:critical
```

### Cache Issues

**Solution:**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Then run tests
composer run test
```

### Permission Issues (Linux/Mac)

**Solution:**
```bash
# Make scripts executable
chmod +x scripts/create-test-database.sh
chmod +x scripts/run-tests.sh

# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

---

## 📈 Coverage Reports

### Generate Coverage Report

```bash
# HTML report (recommended)
composer run test:coverage-html

# Then open in browser:
# coverage/index.html
```

### View Coverage in Terminal

```bash
composer run test:coverage
```

### Coverage Thresholds

Current target: **>80% coverage**

```bash
# Enforce minimum coverage
php artisan test --env=testing --coverage --min=80
```

---

## 🔄 Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: admin_payment_testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql
          
      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist
        
      - name: Copy .env.testing
        run: cp .env.testing.example .env.testing
        
      - name: Run Migrations
        run: php artisan migrate --env=testing
        
      - name: Run Tests
        run: composer run test:coverage
```

---

## 📝 Best Practices

### 1. Always Use Testing Environment

```bash
# ✅ Good
php artisan test --env=testing

# ❌ Bad (uses production database!)
php artisan test
```

### 2. Run Tests Before Commit

```bash
# Add to .git/hooks/pre-commit
#!/bin/bash
composer run test:fast
```

### 3. Keep Tests Fast

- Use `RefreshDatabase` trait (already implemented)
- Mock external services (already implemented)
- Use factories for test data (already implemented)
- Run in parallel for large test suites

### 4. Monitor Coverage

```bash
# Check coverage regularly
composer run test:coverage

# Target: >80% coverage
```

### 5. Isolate Tests

- Each test should be independent
- Use `RefreshDatabase` to reset database
- Don't rely on test execution order

---

## 📊 Test Statistics

```
Total Test Files:        25
Total Test Cases:        580+
Estimated Coverage:      >85%
Execution Time:          ~2-3 minutes
Parallel Execution:      ~1-2 minutes
```

---

## 🎉 Quick Start

**Untuk pertama kali:**

```bash
# 1. Buat database testing
./scripts/create-test-database.sh

# 2. Run migrations
php artisan migrate --env=testing

# 3. Run tests
composer run test
```

**Untuk development sehari-hari:**

```bash
# Run all tests
composer run test

# Run specific test
php artisan test --env=testing --filter your_test_name

# Run with coverage
composer run test:coverage
```

---

## 📚 Additional Resources

- [TDD Scenarios](./TDD_SCENARIOS.md) - Complete test specifications
- [Test Implementation Summary](../../FINAL_TEST_IMPLEMENTATION_SUMMARY.md) - Implementation details
- [Laravel Testing Documentation](https://laravel.com/docs/12.x/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

**Status:** ✅ Ready to use  
**Last Updated:** 2026-05-23  
**Database:** Docker MySQL (s4g9fygoajcwzuphriodko8z)
