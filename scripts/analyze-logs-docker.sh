#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
# Docker Log Analysis Script for WHUSNET Admin Payment
# Run from host machine to analyze logs inside container
# ═══════════════════════════════════════════════════════════════════

CONTAINER_NAME="whusnet-app"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

echo "════════════════════════════════════════════════════════════════"
echo "Docker Log Analysis Report - $(date)"
echo "Container: $CONTAINER_NAME"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Check if container is running
if ! docker ps | grep -q "$CONTAINER_NAME"; then
    echo "❌ Container $CONTAINER_NAME is not running!"
    exit 1
fi

echo "✓ Container is running"
echo ""

# ─── 1. Check Log Files Existence ─────────────────────────────────
echo "📁 LOG FILES"
echo "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    if [ -d storage/logs ]; then
        echo '✓ Log directory exists'
        echo ''
        echo 'Available log files:'
        ls -lh storage/logs/ | tail -n +2 | awk '{print \"  \" \$9 \" (\" \$5 \")\"}'
    else
        echo '✗ Log directory not found'
    fi
"
echo ""

# ─── 2. Laravel Log Analysis ──────────────────────────────────────
echo "📊 LARAVEL LOG ANALYSIS"
echo "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    if [ -f storage/logs/laravel.log ]; then
        echo 'Last 20 lines of laravel.log:'
        echo ''
        tail -20 storage/logs/laravel.log
        echo ''
        echo '─────────────────────────────────────────────────────────'
        echo 'Error Summary:'
        ERROR_COUNT=\$(grep -c 'ERROR' storage/logs/laravel.log 2>/dev/null || echo '0')
        CRITICAL_COUNT=\$(grep -c 'CRITICAL' storage/logs/laravel.log 2>/dev/null || echo '0')
        WARNING_COUNT=\$(grep -c 'WARNING' storage/logs/laravel.log 2>/dev/null || echo '0')
        echo \"  • Errors: \$ERROR_COUNT\"
        echo \"  • Critical: \$CRITICAL_COUNT\"
        echo \"  • Warnings: \$WARNING_COUNT\"
    else
        echo '⚠️  laravel.log not found'
    fi
"
echo ""

# ─── 3. Recent Errors ──────────────────────────────────────────────
echo "🔥 RECENT ERRORS (Last 10)"
echo "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    if [ -f storage/logs/laravel.log ]; then
        grep 'ERROR' storage/logs/laravel.log | tail -10 || echo 'No errors found'
    else
        echo '⚠️  laravel.log not found'
    fi
"
echo ""

# ─── 4. Disk Usage ─────────────────────────────────────────────────
echo "💾 DISK USAGE"
echo "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    echo 'Log directory size:'
    du -sh storage/logs/ 2>/dev/null || echo 'Cannot calculate'
    echo ''
    echo 'Largest log files:'
    du -h storage/logs/* 2>/dev/null | sort -rh | head -5 || echo 'No files'
"
echo ""

# ─── 5. Application Status ─────────────────────────────────────────
echo "⚙️  APPLICATION STATUS"
echo "────────────────────────────────────────────────────────────────"
docker exec $CONTAINER_NAME bash -c "
    echo 'Environment: '\$(php artisan tinker --execute='echo config(\"app.env\");' 2>/dev/null)
    echo 'Debug Mode: '\$(php artisan tinker --execute='echo config(\"app.debug\") ? \"ON\" : \"OFF\";' 2>/dev/null)
    echo 'Log Level: '\$(php artisan tinker --execute='echo config(\"logging.level\");' 2>/dev/null)
"
echo ""

# ─── 6. Quick Commands ─────────────────────────────────────────────
echo "🔧 QUICK COMMANDS"
echo "────────────────────────────────────────────────────────────────"
echo "View live logs:"
echo "  docker exec -it $CONTAINER_NAME tail -f storage/logs/laravel.log"
echo ""
echo "Clear old logs:"
echo "  docker exec $CONTAINER_NAME php artisan log:clear"
echo ""
echo "View specific log file:"
echo "  docker exec $CONTAINER_NAME cat storage/logs/laravel.log"
echo ""

echo "════════════════════════════════════════════════════════════════"
echo "✅ Analysis complete!"
echo "════════════════════════════════════════════════════════════════"
