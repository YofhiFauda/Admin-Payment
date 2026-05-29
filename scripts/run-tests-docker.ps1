# ═══════════════════════════════════════════════════════════════════
#  Run Tests in Docker
#  Script untuk menjalankan tests di Docker container
# ═══════════════════════════════════════════════════════════════════

Write-Host "🧪 WHUSNET Admin Payment - Docker Test Runner" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════════"

# Check if docker-compose.testing.yaml exists
if (-not (Test-Path docker-compose.testing.yaml)) {
    Write-Host "❌ File docker-compose.testing.yaml tidak ditemukan!" -ForegroundColor Red
    exit 1
}

Write-Host "🔧 Building test container..." -ForegroundColor Yellow
docker-compose -f docker-compose.testing.yaml build

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Failed to build test container!" -ForegroundColor Red
    exit 1
}

Write-Host "🚀 Starting test container..." -ForegroundColor Yellow
docker-compose -f docker-compose.testing.yaml up -d

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Failed to start test container!" -ForegroundColor Red
    exit 1
}

Write-Host "⏳ Waiting for container to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

Write-Host "📦 Running migrations..." -ForegroundColor Cyan
docker-compose -f docker-compose.testing.yaml exec app-test php artisan migrate --env=testing --force

Write-Host ""
Write-Host "🧪 Running tests..." -ForegroundColor Cyan
Write-Host ""

# Parse command line arguments
$TEST_TYPE = if ($args[0]) { $args[0] } else { "all" }

switch ($TEST_TYPE) {
    "all" {
        docker-compose -f docker-compose.testing.yaml exec app-test php artisan test --env=testing
    }
    
    "coverage" {
        docker-compose -f docker-compose.testing.yaml exec app-test php artisan test --env=testing --coverage
    }
    
    "critical" {
        docker-compose -f docker-compose.testing.yaml exec app-test php artisan test --env=testing `
            tests/Feature/PengajuanManagementTest.php `
            tests/Feature/PembelianManagementTest.php `
            tests/Feature/TransactionSearchTest.php `
            tests/Feature/NotificationSystemTest.php `
            tests/Feature/FileUploadTest.php
    }
    
    "stop" {
        Write-Host "🛑 Stopping test container..." -ForegroundColor Yellow
        docker-compose -f docker-compose.testing.yaml down
        Write-Host "✅ Test container stopped!" -ForegroundColor Green
        exit 0
    }
    
    default {
        Write-Host "Usage: .\scripts\run-tests-docker.ps1 [command]" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Commands:"
        Write-Host "  all       - Run all tests (default)"
        Write-Host "  coverage  - Run with coverage"
        Write-Host "  critical  - Run Critical Priority tests"
        Write-Host "  stop      - Stop test container"
        Write-Host ""
        Write-Host "Examples:"
        Write-Host "  .\scripts\run-tests-docker.ps1 all"
        Write-Host "  .\scripts\run-tests-docker.ps1 coverage"
        Write-Host "  .\scripts\run-tests-docker.ps1 stop"
        exit 1
    }
}

Write-Host ""
Write-Host "✅ Tests completed!" -ForegroundColor Green
Write-Host ""
Write-Host "💡 To stop the test container, run:" -ForegroundColor Cyan
Write-Host "   .\scripts\run-tests-docker.ps1 stop" -ForegroundColor White
