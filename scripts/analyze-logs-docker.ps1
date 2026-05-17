# ═══════════════════════════════════════════════════════════════════
# Docker Log Analysis Script for WHUSNET Admin Payment (PowerShell)
# Run from host machine to analyze logs inside container
# ═══════════════════════════════════════════════════════════════════

$CONTAINER_NAME = "whusnet-app"
$TIMESTAMP = Get-Date -Format "yyyyMMdd-HHmmss"

Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "Docker Log Analysis Report - $(Get-Date)" -ForegroundColor Cyan
Write-Host "Container: $CONTAINER_NAME" -ForegroundColor Cyan
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Check if container is running
$containerRunning = docker ps --format "{{.Names}}" | Select-String -Pattern $CONTAINER_NAME
if (-not $containerRunning) {
    Write-Host "❌ Container $CONTAINER_NAME is not running!" -ForegroundColor Red
    exit 1
}

Write-Host "✓ Container is running" -ForegroundColor Green
Write-Host ""

# ─── 1. Check Log Files Existence ─────────────────────────────────
Write-Host "📁 LOG FILES" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    if [ -d storage/logs ]; then
        echo '✓ Log directory exists'
        echo ''
        echo 'Available log files:'
        ls -lh storage/logs/ | tail -n +2 | awk '{print \`"  \`" `$9 \`" (`" `$5 \`")\`"}'
    else
        echo '✗ Log directory not found'
    fi
"
Write-Host ""

# ─── 2. Laravel Log Analysis ──────────────────────────────────────
Write-Host "📊 LARAVEL LOG ANALYSIS" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    if [ -f storage/logs/laravel.log ]; then
        echo 'Last 20 lines of laravel.log:'
        echo ''
        tail -20 storage/logs/laravel.log
        echo ''
        echo '─────────────────────────────────────────────────────────'
        echo 'Error Summary:'
        ERROR_COUNT=`$(grep -c 'ERROR' storage/logs/laravel.log 2>/dev/null || echo '0')
        CRITICAL_COUNT=`$(grep -c 'CRITICAL' storage/logs/laravel.log 2>/dev/null || echo '0')
        WARNING_COUNT=`$(grep -c 'WARNING' storage/logs/laravel.log 2>/dev/null || echo '0')
        echo \`"  • Errors: `$ERROR_COUNT\`"
        echo \`"  • Critical: `$CRITICAL_COUNT\`"
        echo \`"  • Warnings: `$WARNING_COUNT\`"
    else
        echo '⚠️  laravel.log not found'
    fi
"
Write-Host ""

# ─── 3. Recent Errors ──────────────────────────────────────────────
Write-Host "🔥 RECENT ERRORS (Last 10)" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    if [ -f storage/logs/laravel.log ]; then
        grep 'ERROR' storage/logs/laravel.log | tail -10 || echo 'No errors found'
    else
        echo '⚠️  laravel.log not found'
    fi
"
Write-Host ""

# ─── 4. Disk Usage ─────────────────────────────────────────────────
Write-Host "💾 DISK USAGE" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    echo 'Log directory size:'
    du -sh storage/logs/ 2>/dev/null || echo 'Cannot calculate'
    echo ''
    echo 'Largest log files:'
    du -h storage/logs/* 2>/dev/null | sort -rh | head -5 || echo 'No files'
"
Write-Host ""

# ─── 5. Application Status ─────────────────────────────────────────
Write-Host "⚙️  APPLICATION STATUS" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────"
$env = docker exec $CONTAINER_NAME php artisan tinker --execute="echo config('app.env');" 2>$null
$debug = docker exec $CONTAINER_NAME php artisan tinker --execute="echo config('app.debug') ? 'ON' : 'OFF';" 2>$null
Write-Host "Environment: $env"
Write-Host "Debug Mode: $debug"
Write-Host ""

# ─── 6. Quick Commands ─────────────────────────────────────────────
Write-Host "🔧 QUICK COMMANDS" -ForegroundColor Yellow
Write-Host "────────────────────────────────────────────────────────────────"
Write-Host "View live logs:"
Write-Host "  docker exec -it $CONTAINER_NAME tail -f storage/logs/laravel.log" -ForegroundColor Gray
Write-Host ""
Write-Host "View last 50 lines:"
Write-Host "  docker exec $CONTAINER_NAME tail -50 storage/logs/laravel.log" -ForegroundColor Gray
Write-Host ""
Write-Host "Search for errors:"
Write-Host "  docker exec $CONTAINER_NAME grep 'ERROR' storage/logs/laravel.log" -ForegroundColor Gray
Write-Host ""

Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "✅ Analysis complete!" -ForegroundColor Green
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
