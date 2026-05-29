# Admin Payment Application - Test Runner Script (PowerShell)
# This script runs all test suites and generates coverage reports

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  Admin Payment Application Test Suite  " -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Function to print colored output
function Print-Status {
    param (
        [bool]$Success,
        [string]$Message
    )
    
    if ($Success) {
        Write-Host "✓ $Message" -ForegroundColor Green
    } else {
        Write-Host "✗ $Message" -ForegroundColor Red
    }
}

# Check if .env.testing exists
if (-not (Test-Path .env.testing)) {
    Write-Host "⚠ .env.testing not found. Creating from .env.example..." -ForegroundColor Yellow
    Copy-Item .env.example .env.testing
    (Get-Content .env.testing) -replace 'DB_DATABASE=.*', 'DB_DATABASE=admin_payment_testing' | Set-Content .env.testing
    (Get-Content .env.testing) -replace 'QUEUE_CONNECTION=.*', 'QUEUE_CONNECTION=sync' | Set-Content .env.testing
    (Get-Content .env.testing) -replace 'CACHE_DRIVER=.*', 'CACHE_DRIVER=array' | Set-Content .env.testing
}

# Run migrations
Write-Host "Running migrations..." -ForegroundColor Cyan
php artisan migrate --env=testing --force
$migrationSuccess = $LASTEXITCODE -eq 0
Print-Status -Success $migrationSuccess -Message "Database migrations"

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  Running PHP Tests (PHPUnit)           " -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Run PHP tests with coverage
php artisan test --coverage --min=80
$phpTestSuccess = $LASTEXITCODE -eq 0
Print-Status -Success $phpTestSuccess -Message "PHP Tests"

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  Running JavaScript Tests (Vitest)     " -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Run JavaScript tests
npm test -- --run
$jsTestSuccess = $LASTEXITCODE -eq 0
Print-Status -Success $jsTestSuccess -Message "JavaScript Tests"

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  Test Summary                           " -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

if ($phpTestSuccess -and $jsTestSuccess) {
    Write-Host "✓ All tests passed!" -ForegroundColor Green
    exit 0
} else {
    Write-Host "✗ Some tests failed" -ForegroundColor Red
    if (-not $phpTestSuccess) {
        Write-Host "  - PHP tests failed" -ForegroundColor Red
    }
    if (-not $jsTestSuccess) {
        Write-Host "  - JavaScript tests failed" -ForegroundColor Red
    }
    exit 1
}
