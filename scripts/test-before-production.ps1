# ═══════════════════════════════════════════════════════════════════
# Pre-Production Testing Script (PowerShell)
# Test semua perubahan sebelum push ke production
# ═══════════════════════════════════════════════════════════════════

$ErrorActionPreference = "Stop"

# Auto-detect container name
$CONTAINER_NAME = docker ps --format "{{.Names}}" | Select-String -Pattern "(admin-payment-app|whusnet-app)" | Select-Object -First 1 | ForEach-Object { $_.ToString() }

if (-not $CONTAINER_NAME) {
    Write-Host "Error: Container not found. Looking for 'admin-payment-app' or 'whusnet-app'" -ForegroundColor Red
    exit 1
}

Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  PRE-PRODUCTION TESTING - LOG VIEWER & PULSE" -ForegroundColor Cyan
Write-Host "  Using container: $CONTAINER_NAME" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# ─── Test 1: Config Cache Compatibility ────────────────────────────
Write-Host "[Test 1/7] Testing config:cache compatibility..." -ForegroundColor Yellow

docker exec $CONTAINER_NAME php artisan config:clear | Out-Null
$configCacheResult = docker exec $CONTAINER_NAME php artisan config:cache 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ PASSED - Config cache berhasil" -ForegroundColor Green
} else {
    Write-Host "✗ FAILED - Config cache error (not serializable)" -ForegroundColor Red
    Write-Host "  Fix: Cek config/log-viewer.php dan config/pulse.php" -ForegroundColor Yellow
    Write-Host "  Error: $configCacheResult" -ForegroundColor Red
    exit 1
}
Write-Host ""

# ─── Test 2: Route Cache Compatibility ─────────────────────────────
Write-Host "[Test 2/7] Testing route:cache compatibility..." -ForegroundColor Yellow

docker exec $CONTAINER_NAME php artisan route:clear | Out-Null
$routeCacheResult = docker exec $CONTAINER_NAME php artisan route:cache 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ PASSED - Route cache berhasil" -ForegroundColor Green
    
    # Check log-viewer routes
    Write-Host "  Checking log-viewer routes..." -ForegroundColor Gray
    $routes = docker exec $CONTAINER_NAME php artisan route:list --path=log-viewer 2>&1 | Select-Object -First 5
    $routes | ForEach-Object { Write-Host "  $_" -ForegroundColor Gray }
} else {
    Write-Host "✗ FAILED - Route cache error" -ForegroundColor Red
    exit 1
}
Write-Host ""

# ─── Test 3: Redis Connections ─────────────────────────────────────
Write-Host "[Test 3/7] Testing Redis connections..." -ForegroundColor Yellow

# Main Redis
Write-Host "  Testing main Redis..." -ForegroundColor Gray
$mainRedis = docker exec $CONTAINER_NAME php artisan tinker --execute="try { Cache::store('redis')->put('test_main', 'ok', 60); echo Cache::store('redis')->get('test_main'); } catch (Exception `$e) { echo 'ERROR'; }" 2>&1 | Select-Object -Last 1

if ($mainRedis -match "ok") {
    Write-Host "  ✓ Main Redis: Connected" -ForegroundColor Green
} else {
    Write-Host "  ✗ Main Redis: Failed" -ForegroundColor Red
    exit 1
}

# Pulse Redis
Write-Host "  Testing Pulse Redis..." -ForegroundColor Gray
$pulseRedis = docker exec $CONTAINER_NAME php artisan tinker --execute="try { \Illuminate\Support\Facades\Redis::connection('pulse')->set('test_pulse', 'ok'); echo \Illuminate\Support\Facades\Redis::connection('pulse')->get('test_pulse'); } catch (Exception `$e) { echo 'ERROR'; }" 2>&1 | Select-Object -Last 1

if ($pulseRedis -match "ok") {
    Write-Host "  ✓ Pulse Redis: Connected" -ForegroundColor Green
} else {
    Write-Host "  ✗ Pulse Redis: Failed" -ForegroundColor Red
    Write-Host "  Fix: Cek PULSE_REDIS_HOST dan credentials di .env" -ForegroundColor Yellow
    exit 1
}
Write-Host ""

# ─── Test 4: Session Configuration ─────────────────────────────────
Write-Host "[Test 4/7] Testing session configuration..." -ForegroundColor Yellow

$sessionInfo = docker exec $CONTAINER_NAME php artisan tinker --execute="echo 'DRIVER=' . config('session.driver') . '|'; echo 'ENCRYPT=' . (config('session.encrypt') ? 'true' : 'false') . '|'; echo 'SAME_SITE=' . config('session.same_site');" 2>&1 | Select-Object -Last 1

Write-Host "  $sessionInfo" -ForegroundColor Gray

if ($sessionInfo -match "DRIVER=redis" -and $sessionInfo -match "ENCRYPT=false" -and $sessionInfo -match "SAME_SITE=lax") {
    Write-Host "✓ PASSED - Session configuration correct" -ForegroundColor Green
} else {
    Write-Host "✗ FAILED - Session configuration incorrect" -ForegroundColor Red
    Write-Host "  Expected: DRIVER=redis, ENCRYPT=false, SAME_SITE=lax" -ForegroundColor Yellow
    exit 1
}

# Test session persistence
Write-Host "  Testing session persistence..." -ForegroundColor Gray
$sessionTest = docker exec $CONTAINER_NAME php artisan tinker --execute='session()->put(\"test_session\", \"value123\"); echo session()->get(\"test_session\");' 2>&1 | Select-Object -Last 1

if ($sessionTest -match 'value123') {
    Write-Host "  ✓ Session persistence: Working" -ForegroundColor Green
} else {
    Write-Host "  ✗ Session persistence: Failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# ─── Test 5: Authorization ──────────────────────────────────────────
Write-Host "[Test 5/7] Testing authorization..." -ForegroundColor Yellow

# Check if owner exists
$ownerExists = docker exec $CONTAINER_NAME php artisan tinker --execute="echo App\Models\User::where('role', 'owner')->exists() ? 'yes' : 'no';" 2>&1 | Select-Object -Last 1

if ($ownerExists -match "yes") {
    Write-Host "  ✓ Owner user exists" -ForegroundColor Green
    
    # Test Gate
    $gateTest = docker exec $CONTAINER_NAME php artisan tinker --execute='$owner = App\Models\User::where(\"role\", \"owner\")->first(); echo \Gate::forUser($owner)->allows(\"viewLogViewer\") ? \"allowed\" : \"denied\";' 2>&1 | Select-Object -Last 1
    
    if ($gateTest -match 'allowed') {
        Write-Host "  ✓ Gate authorization: Working" -ForegroundColor Green
    } else {
        Write-Host "  ✗ Gate authorization: Failed" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "  ⚠ No owner user found (create one for testing)" -ForegroundColor Yellow
}
Write-Host ""

# ─── Test 6: Pulse Configuration ───────────────────────────────────
Write-Host "[Test 6/7] Testing Pulse configuration..." -ForegroundColor Yellow

$pulseEnabled = docker exec $CONTAINER_NAME php artisan tinker --execute="echo config('pulse.enabled') ? 'true' : 'false';" 2>&1 | Select-Object -Last 1

if ($pulseEnabled -match "true") {
    Write-Host "  ✓ Pulse: Enabled" -ForegroundColor Green
    
    # Check Pulse storage
    $pulseStorage = docker exec $CONTAINER_NAME php artisan tinker --execute="echo config('pulse.storage.driver');" 2>&1 | Select-Object -Last 1
    Write-Host "  Storage driver: $pulseStorage" -ForegroundColor Gray
    
    # Check Pulse ingest
    $pulseIngest = docker exec $CONTAINER_NAME php artisan tinker --execute="echo config('pulse.ingest.driver');" 2>&1 | Select-Object -Last 1
    Write-Host "  Ingest driver: $pulseIngest" -ForegroundColor Gray
    
    if ($pulseStorage -match "database" -and $pulseIngest -match "redis") {
        Write-Host "✓ PASSED - Pulse configuration correct" -ForegroundColor Green
    } else {
        Write-Host "✗ FAILED - Pulse configuration incorrect" -ForegroundColor Red
        Write-Host "  Expected: storage=database, ingest=redis" -ForegroundColor Yellow
        exit 1
    }
} else {
    Write-Host "  ⚠ Pulse disabled (set PULSE_ENABLED=true for testing)" -ForegroundColor Yellow
}
Write-Host ""

# ─── Test 7: Middleware Registration ───────────────────────────────
Write-Host "[Test 7/7] Testing middleware registration..." -ForegroundColor Yellow

# Check if log-viewer.auth middleware exists
$middlewareCheck = docker exec $CONTAINER_NAME php artisan route:list --path=log-viewer 2>&1 | Select-String -Pattern "log-viewer.auth" | Measure-Object | Select-Object -ExpandProperty Count

if ($middlewareCheck -gt 0) {
    Write-Host "✓ PASSED - log-viewer.auth middleware registered" -ForegroundColor Green
} else {
    Write-Host "✗ FAILED - log-viewer.auth middleware not found" -ForegroundColor Red
    Write-Host "  Fix: Cek bootstrap/app.php middleware alias" -ForegroundColor Yellow
    exit 1
}
Write-Host ""

# ═══════════════════════════════════════════════════════════════════
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "  ✓ ALL TESTS PASSED" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Test Log Viewer manually:" -ForegroundColor White
Write-Host "   - Login sebagai owner" -ForegroundColor Gray
Write-Host "   - Akses http://localhost/log-viewer" -ForegroundColor Gray
Write-Host "   - Cek DevTools Console (no 403 errors)" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Test Pulse manually:" -ForegroundColor White
Write-Host "   - Start: docker exec $CONTAINER_NAME php artisan pulse:work" -ForegroundColor Gray
Write-Host "   - Akses http://localhost/pulse" -ForegroundColor Gray
Write-Host "   - Verify metrics appear" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Jika semua manual test OK, ready untuk push ke production!" -ForegroundColor White
Write-Host ""
