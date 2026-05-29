# ═══════════════════════════════════════════════════════════════════
#  Run Tests Locally (Outside Docker) - PowerShell
#  Script untuk menjalankan tests di host machine
# ═══════════════════════════════════════════════════════════════════

Write-Host "🧪 WHUSNET Admin Payment - Local Test Runner" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════════"

# Check if .env.testing exists
if (-not (Test-Path .env.testing)) {
    Write-Host "❌ File .env.testing tidak ditemukan!" -ForegroundColor Red
    Write-Host "📝 Silakan buat .env.testing terlebih dahulu" -ForegroundColor Yellow
    exit 1
}

# Check if vendor exists
if (-not (Test-Path vendor)) {
    Write-Host "📦 Installing dependencies..." -ForegroundColor Yellow
    composer install
}

# Parse command line arguments
$TEST_TYPE = if ($args[0]) { $args[0] } else { "all" }
$COVERAGE = if ($args[1]) { $args[1] } else { "no" }

Write-Host "🔧 Running tests locally (outside Docker)..." -ForegroundColor Cyan
Write-Host ""

switch ($TEST_TYPE) {
    "all" {
        Write-Host "📦 Running all tests..." -ForegroundColor Cyan
        if ($COVERAGE -eq "coverage") {
            php artisan test --env=testing --coverage
        } else {
            php artisan test --env=testing
        }
    }
    
    "critical" {
        Write-Host "🔥 Running Critical Priority tests..." -ForegroundColor Cyan
        php artisan test --env=testing `
            tests/Feature/PengajuanManagementTest.php `
            tests/Feature/PembelianManagementTest.php `
            tests/Feature/TransactionSearchTest.php `
            tests/Feature/NotificationSystemTest.php `
            tests/Feature/FileUploadTest.php
    }
    
    "high" {
        Write-Host "⚡ Running High Priority tests..." -ForegroundColor Cyan
        php artisan test --env=testing `
            tests/Feature/AiAutoFillTest.php `
            tests/Feature/ItemAutocompleteTest.php `
            tests/Feature/TransactionStatusTest.php `
            tests/Feature/UserBankAccountTest.php `
            tests/Feature/BranchBankAccountTest.php
    }
    
    "parallel" {
        Write-Host "⚡ Running tests in parallel..." -ForegroundColor Cyan
        php artisan test --env=testing --parallel
    }
    
    "fast" {
        Write-Host "🚀 Running fast tests (stop on failure)..." -ForegroundColor Cyan
        php artisan test --env=testing --stop-on-failure
    }
    
    default {
        Write-Host "Usage: .\scripts\run-tests-local.ps1 [test_type] [coverage]" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Test Types:"
        Write-Host "  all       - Run all tests (default)"
        Write-Host "  critical  - Run Critical Priority tests"
        Write-Host "  high      - Run High Priority tests"
        Write-Host "  parallel  - Run tests in parallel"
        Write-Host "  fast      - Run with stop-on-failure"
        Write-Host ""
        Write-Host "Coverage:"
        Write-Host "  coverage  - Generate coverage report"
        Write-Host ""
        Write-Host "Examples:"
        Write-Host "  .\scripts\run-tests-local.ps1 all"
        Write-Host "  .\scripts\run-tests-local.ps1 all coverage"
        Write-Host "  .\scripts\run-tests-local.ps1 critical"
        exit 1
    }
}

Write-Host ""
Write-Host "✅ Tests completed!" -ForegroundColor Green
