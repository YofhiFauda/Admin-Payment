# Script untuk memperbaiki Log Viewer 403 Error (Windows PowerShell)
# Usage: .\scripts\fix-log-viewer.ps1

Write-Host "🔧 Fixing Log Viewer 403 Error..." -ForegroundColor Cyan
Write-Host ""

# Fungsi untuk print dengan warna
function Print-Success {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor Green
}

function Print-Warning {
    param([string]$Message)
    Write-Host "⚠ $Message" -ForegroundColor Yellow
}

function Print-Error {
    param([string]$Message)
    Write-Host "✗ $Message" -ForegroundColor Red
}

# Cek apakah Docker berjalan
try {
    docker ps | Out-Null
    Write-Host "Docker is running" -ForegroundColor Green
} catch {
    Print-Error "Docker is not running. Please start Docker first."
    exit 1
}

# Step 1: Clear all caches
Write-Host ""
Write-Host "Step 1: Clearing all caches..." -ForegroundColor Cyan
try {
    docker exec admin-payment-app php artisan config:clear
    docker exec admin-payment-app php artisan cache:clear
    docker exec admin-payment-app php artisan route:clear
    docker exec admin-payment-app php artisan view:clear
    Print-Success "All caches cleared"
} catch {
    Print-Error "Failed to clear caches: $_"
}

# Step 2: Rebuild config cache
Write-Host ""
Write-Host "Step 2: Rebuilding config cache..." -ForegroundColor Cyan
try {
    docker exec admin-payment-app php artisan config:cache
    Print-Success "Config cache rebuilt"
} catch {
    Print-Error "Failed to rebuild config cache: $_"
}

# Step 3: Test Redis connection
Write-Host ""
Write-Host "Step 3: Testing Redis connection..." -ForegroundColor Cyan
try {
    docker exec admin-payment-app php artisan tinker --execute="echo Cache::store('redis')->ping() ? 'Redis OK' : 'Redis FAIL';"
} catch {
    Print-Warning "Could not test Redis connection: $_"
}

# Step 4: Check log-viewer routes
Write-Host ""
Write-Host "Step 4: Checking log-viewer routes..." -ForegroundColor Cyan
try {
    docker exec admin-payment-app php artisan route:list --path=log-viewer
} catch {
    Print-Warning "Could not list routes: $_"
}

# Step 5: Verify session configuration
Write-Host ""
Write-Host "Step 5: Verifying session configuration..." -ForegroundColor Cyan
try {
    $sessionConfig = @"
echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
echo 'SESSION_ENCRYPT: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
echo 'SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
"@
    docker exec admin-payment-app php artisan tinker --execute="$sessionConfig"
} catch {
    Print-Warning "Could not verify session config: $_"
}

Write-Host ""
Print-Success "Fix completed!"
Write-Host ""
Write-Host "📝 Next steps:" -ForegroundColor Cyan
Write-Host "1. Login as owner user"
Write-Host "2. Access /log-viewer"
Write-Host "3. Check browser console for any 403 errors"
Write-Host ""
Write-Host "If still having issues, check FIX_LOG_VIEWER_403.md for troubleshooting" -ForegroundColor Yellow
