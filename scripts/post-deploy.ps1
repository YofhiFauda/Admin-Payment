# ═══════════════════════════════════════════════════════════════════
# Post-Deploy Script (PowerShell)
# Automated steps after deployment
# ═══════════════════════════════════════════════════════════════════

$ErrorActionPreference = "Stop"

# Auto-detect container name
$CONTAINER_NAME = docker ps --format "{{.Names}}" | Select-String -Pattern "(admin-payment-app|whusnet-app)" | Select-Object -First 1 | ForEach-Object { $_.ToString() }

if (-not $CONTAINER_NAME) {
    Write-Host "Error: Container not found. Looking for 'admin-payment-app' or 'whusnet-app'" -ForegroundColor Red
    exit 1
}

Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  POST-DEPLOY AUTOMATION" -ForegroundColor Cyan
Write-Host "  Using container: $CONTAINER_NAME" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# ─── Step 1: Clear All Caches ──────────────────────────────────────
Write-Host "[Step 1/8] Clearing all caches..." -ForegroundColor Blue
docker exec $CONTAINER_NAME php artisan config:clear | Out-Null
docker exec $CONTAINER_NAME php artisan cache:clear | Out-Null
docker exec $CONTAINER_NAME php artisan route:clear | Out-Null
docker exec $CONTAINER_NAME php artisan view:clear | Out-Null
docker exec $CONTAINER_NAME php artisan event:clear | Out-Null
Write-Host "✓ All caches cleared" -ForegroundColor Green
Write-Host ""

# ─── Step 2: Rebuild Caches ────────────────────────────────────────
Write-Host "[Step 2/8] Rebuilding caches..." -ForegroundColor Blue

$configCache = docker exec $CONTAINER_NAME php artisan config:cache 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Config cached" -ForegroundColor Green
} else {
    Write-Host "✗ Config cache failed (check for serialization issues)" -ForegroundColor Red
    Write-Host "Error: $configCache" -ForegroundColor Red
    exit 1
}

docker exec $CONTAINER_NAME php artisan route:cache | Out-Null
Write-Host "✓ Routes cached" -ForegroundColor Green

docker exec $CONTAINER_NAME php artisan view:cache | Out-Null
Write-Host "✓ Views cached" -ForegroundColor Green

docker exec $CONTAINER_NAME php artisan event:cache | Out-Null
Write-Host "✓ Events cached" -ForegroundColor Green
Write-Host ""

# ─── Step 3: Check Database Connection ─────────────────────────────
Write-Host "[Step 3/8] Checking database connection..." -ForegroundColor Blue
$dbName = docker exec $CONTAINER_NAME php artisan tinker --execute="echo DB::connection()->getDatabaseName();" 2>&1 | Select-Object -Last 1

if ($dbName) {
    Write-Host "✓ Database connected: $dbName" -ForegroundColor Green
} else {
    Write-Host "✗ Database connection failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# ─── Step 4: Check Redis Connection ────────────────────────────────
Write-Host "[Step 4/8] Checking Redis connection..." -ForegroundColor Blue
$redisPing = docker exec $CONTAINER_NAME php artisan redis:ping 2>&1 | Select-Object -Last 1

if ($redisPing -match "PONG") {
    Write-Host "✓ Redis connected" -ForegroundColor Green
} else {
    Write-Host "✗ Redis connection failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# ─── Step 5: Run Migrations ────────────────────────────────────────
Write-Host "[Step 5/8] Running database migrations..." -ForegroundColor Blue
$migrationOutput = docker exec $CONTAINER_NAME php artisan migrate --force 2>&1

if ($migrationOutput -match "Nothing to migrate") {
    Write-Host "✓ Database up-to-date" -ForegroundColor Green
} elseif ($migrationOutput -match "Migrated") {
    Write-Host "✓ Migrations completed" -ForegroundColor Green
    $migrationOutput | Select-String "Migrated:" | ForEach-Object { Write-Host $_ -ForegroundColor Gray }
} else {
    Write-Host "⚠ Migration output:" -ForegroundColor Yellow
    Write-Host $migrationOutput -ForegroundColor Gray
}
Write-Host ""

# ─── Step 6: Verify Configuration ──────────────────────────────────
Write-Host "[Step 6/8] Verifying configuration..." -ForegroundColor Blue

# Check APP_URL
$appUrl = docker exec $CONTAINER_NAME php artisan tinker --execute="echo config('app.url');" 2>&1 | Select-Object -Last 1
Write-Host "  APP_URL: $appUrl" -ForegroundColor Gray

# Check Session config
$sessionInfo = docker exec $CONTAINER_NAME php artisan tinker --execute="echo config('session.driver') . '|' . (config('session.encrypt') ? 'true' : 'false') . '|' . config('session.same_site');" 2>&1 | Select-Object -Last 1

$sessionParts = $sessionInfo -split '\|'
$sessionDriver = $sessionParts[0]
$sessionEncrypt = $sessionParts[1]
$sessionSameSite = $sessionParts[2]

Write-Host "  Session: driver=$sessionDriver, encrypt=$sessionEncrypt, same_site=$sessionSameSite" -ForegroundColor Gray

if ($sessionDriver -eq "redis" -and $sessionEncrypt -eq "false" -and $sessionSameSite -eq "lax") {
    Write-Host "✓ Session configuration correct" -ForegroundColor Green
} else {
    Write-Host "⚠ Session configuration may need review" -ForegroundColor Yellow
}
Write-Host ""

# ─── Step 7: Restart Services ──────────────────────────────────────
Write-Host "[Step 7/8] Restarting services..." -ForegroundColor Blue
docker-compose restart app nginx horizon reverb scheduler pulse | Out-Null
Write-Host "✓ Services restarted" -ForegroundColor Green
Write-Host ""

# Wait for services to be ready
Write-Host "  Waiting for services to be ready..." -ForegroundColor Gray
Start-Sleep -Seconds 10

# ─── Step 8: Verify Services ───────────────────────────────────────
Write-Host "[Step 8/8] Verifying services..." -ForegroundColor Blue

# Check container status
$containers = @("app", "nginx", "horizon", "reverb", "scheduler", "pulse")
$allRunning = $true

foreach ($container in $containers) {
    $status = docker ps --filter "name=whusnet-$container" --format "{{.Status}}" 2>&1
    if ($status -match "Up") {
        Write-Host "  ✓ $container`: Running" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $container`: Not running" -ForegroundColor Red
        $allRunning = $false
    }
}

if (-not $allRunning) {
    Write-Host ""
    Write-Host "Some services are not running. Check logs:" -ForegroundColor Red
    Write-Host "  docker logs whusnet-app --tail=50" -ForegroundColor Yellow
    exit 1
}
Write-Host ""

# ─── Test Application ──────────────────────────────────────────────
Write-Host "Testing application..." -ForegroundColor Blue

# Test health endpoint
try {
    $healthResponse = Invoke-WebRequest -Uri "http://localhost:8000/up" -UseBasicParsing -TimeoutSec 5
    if ($healthResponse.StatusCode -eq 200) {
        Write-Host "✓ Health check: OK" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠ Health check: Failed" -ForegroundColor Yellow
}

# Test main page
try {
    $mainResponse = Invoke-WebRequest -Uri "http://localhost:8000" -UseBasicParsing -TimeoutSec 5 -MaximumRedirection 0 -ErrorAction SilentlyContinue
    if ($mainResponse.StatusCode -eq 200 -or $mainResponse.StatusCode -eq 302) {
        Write-Host "✓ Main page: OK" -ForegroundColor Green
    }
} catch {
    if ($_.Exception.Response.StatusCode.value__ -eq 302) {
        Write-Host "✓ Main page: OK (redirect)" -ForegroundColor Green
    } else {
        Write-Host "⚠ Main page: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Yellow
    }
}
Write-Host ""

# ═══════════════════════════════════════════════════════════════════
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "  ✓ POST-DEPLOY COMPLETED" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Test website di browser:" -ForegroundColor White
Write-Host "   - Akses: $appUrl" -ForegroundColor Gray
Write-Host "   - Login dan verify session persists" -ForegroundColor Gray
Write-Host "   - Check DevTools Console (no errors)" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Test admin tools:" -ForegroundColor White
Write-Host "   - Log Viewer: $appUrl/log-viewer" -ForegroundColor Gray
Write-Host "   - Pulse: $appUrl/pulse" -ForegroundColor Gray
Write-Host "   - Horizon: $appUrl/horizon" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Monitor logs:" -ForegroundColor White
Write-Host "   docker logs whusnet-app -f" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Check Pulse dashboard for metrics" -ForegroundColor White
Write-Host ""
Write-Host "If any issues, check:" -ForegroundColor Yellow
Write-Host "  - POST_DEPLOY_CHECKLIST.md" -ForegroundColor Gray
Write-Host "  - docker logs whusnet-app --tail=100" -ForegroundColor Gray
Write-Host ""
