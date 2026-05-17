#!/bin/bash

# Script untuk test Log Viewer Authentication
# Usage: ./scripts/test-log-viewer-auth.sh

set -e

echo "🧪 Testing Log Viewer Authentication..."
echo ""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ $1${NC}"; }

# Check if running in container or host
if [ -f "/.dockerenv" ]; then
    CMD_PREFIX=""
else
    CMD_PREFIX="docker exec admin-payment-app"
fi

echo "═══════════════════════════════════════════════════════════"
echo "Test 1: Check Middleware Registration"
echo "═══════════════════════════════════════════════════════════"
$CMD_PREFIX php artisan route:list --path=log-viewer | grep -i "middleware" || print_warning "Could not check middleware"

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "Test 2: Check Log Viewer Config"
echo "═══════════════════════════════════════════════════════════"
$CMD_PREFIX php artisan tinker --execute="
    \$middleware = config('log-viewer.middleware');
    \$apiMiddleware = config('log-viewer.api_middleware');
    echo 'Middleware: ' . implode(', ', \$middleware) . PHP_EOL;
    echo 'API Middleware: ' . implode(', ', \$apiMiddleware) . PHP_EOL;
"

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "Test 3: Check Session Config"
echo "═══════════════════════════════════════════════════════════"
$CMD_PREFIX php artisan tinker --execute="
    echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
    echo 'SESSION_ENCRYPT: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
    echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
    echo 'SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
"

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "Test 4: Check Owner Users"
echo "═══════════════════════════════════════════════════════════"
$CMD_PREFIX php artisan tinker --execute="
    \$owners = App\Models\User::where('role', 'owner')->get(['id', 'name', 'email', 'role']);
    echo 'Owner Users: ' . \$owners->count() . PHP_EOL;
    foreach (\$owners as \$owner) {
        echo '  - ' . \$owner->name . ' (' . \$owner->email . ')' . PHP_EOL;
    }
"

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "Test 5: Check Redis Connection"
echo "═══════════════════════════════════════════════════════════"
REDIS_TEST=$($CMD_PREFIX php artisan tinker --execute="echo Cache::store('redis')->ping() ? 'OK' : 'FAIL';" 2>&1 | grep -o "OK\|FAIL" || echo "ERROR")
if [ "$REDIS_TEST" = "OK" ]; then
    print_success "Redis connection OK"
else
    print_error "Redis connection FAILED"
fi

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "Test 6: Check Gate Definition"
echo "═══════════════════════════════════════════════════════════"
$CMD_PREFIX php artisan tinker --execute="
    \$user = App\Models\User::where('role', 'owner')->first();
    if (\$user) {
        echo 'Testing with user: ' . \$user->name . PHP_EOL;
        echo 'Can view log viewer: ' . (Gate::forUser(\$user)->allows('viewLogViewer') ? 'Yes' : 'No') . PHP_EOL;
    } else {
        echo 'No owner user found' . PHP_EOL;
    }
"

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "Test 7: Check Log Files"
echo "═══════════════════════════════════════════════════════════"
LOG_COUNT=$($CMD_PREFIX find storage/logs -name "*.log" -type f 2>/dev/null | wc -l || echo "0")
if [ "$LOG_COUNT" -gt "0" ]; then
    print_success "Log files found ($LOG_COUNT files)"
    $CMD_PREFIX ls -lh storage/logs/*.log 2>/dev/null | tail -5
else
    print_warning "No log files found"
fi

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "Test 8: Check CSRF Exceptions"
echo "═══════════════════════════════════════════════════════════"
print_info "CSRF exceptions should include: log-viewer, log-viewer/*, log-viewer/api/*"

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "                    SUMMARY"
echo "═══════════════════════════════════════════════════════════"
echo ""
print_info "Next Steps:"
echo "1. Clear all caches: php artisan config:clear && php artisan cache:clear"
echo "2. Rebuild caches: php artisan config:cache"
echo "3. Login as owner user"
echo "4. Access /log-viewer"
echo "5. Check browser console for errors"
echo ""
print_warning "If still getting 403:"
echo "- Check browser DevTools → Network tab"
echo "- Look for the failing request"
echo "- Check request headers (Cookie, X-CSRF-TOKEN)"
echo "- Check response body for error message"
echo ""
