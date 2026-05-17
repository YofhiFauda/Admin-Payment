#!/bin/bash

# Script untuk deploy dan test Log Viewer fix
# Usage: ./scripts/deploy-and-test.sh

set -e

echo "🚀 Deploying Log Viewer Fix..."
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
    print_info "Running inside Docker container"
else
    CMD_PREFIX="docker exec admin-payment-app"
    print_info "Running on host machine"
fi

echo ""
echo "Step 1: Clearing all caches..."
$CMD_PREFIX php artisan config:clear
$CMD_PREFIX php artisan cache:clear
$CMD_PREFIX php artisan route:clear
$CMD_PREFIX php artisan view:clear
print_success "All caches cleared"

echo ""
echo "Step 2: Rebuilding config cache..."
$CMD_PREFIX php artisan config:cache
print_success "Config cache rebuilt"

echo ""
echo "Step 3: Rebuilding route cache..."
$CMD_PREFIX php artisan route:cache
print_success "Route cache rebuilt"

echo ""
echo "Step 4: Testing configuration..."
echo ""

# Test session config
echo "Session Configuration:"
$CMD_PREFIX php artisan tinker --execute="
    echo '  Driver: ' . config('session.driver') . PHP_EOL;
    echo '  Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
    echo '  SameSite: ' . config('session.same_site') . PHP_EOL;
"

# Test log-viewer config
echo ""
echo "Log Viewer Configuration:"
$CMD_PREFIX php artisan tinker --execute="
    \$middleware = config('log-viewer.middleware');
    \$apiMiddleware = config('log-viewer.api_middleware');
    echo '  Middleware: ' . implode(', ', \$middleware) . PHP_EOL;
    echo '  API Middleware: ' . implode(', ', \$apiMiddleware) . PHP_EOL;
"

# Test Redis
echo ""
echo "Redis Connection:"
REDIS_TEST=$($CMD_PREFIX php artisan tinker --execute="echo Cache::store('redis')->ping() ? 'OK' : 'FAIL';" 2>&1 | grep -o "OK\|FAIL" || echo "ERROR")
if [ "$REDIS_TEST" = "OK" ]; then
    echo "  Status: ✓ Connected"
else
    echo "  Status: ✗ Failed"
fi

# Check owner users
echo ""
echo "Owner Users:"
$CMD_PREFIX php artisan tinker --execute="
    \$count = App\Models\User::where('role', 'owner')->count();
    echo '  Count: ' . \$count . PHP_EOL;
"

# Check routes
echo ""
echo "Log Viewer Routes:"
ROUTE_COUNT=$($CMD_PREFIX php artisan route:list --path=log-viewer 2>/dev/null | grep -c "log-viewer" || echo "0")
echo "  Registered: $ROUTE_COUNT routes"

echo ""
print_success "Deployment completed!"
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "                    NEXT STEPS"
echo "═══════════════════════════════════════════════════════════"
echo ""
print_info "1. Login to your application as owner user"
print_info "2. Navigate to /log-viewer"
print_info "3. Open browser DevTools (F12)"
print_info "4. Check Console tab for any errors"
print_info "5. Check Network tab for API calls"
echo ""
print_warning "If you see 403 errors:"
echo "  - Check that you're logged in as owner"
echo "  - Check that session cookies are being sent"
echo "  - Check browser console for detailed error messages"
echo "  - Run: ./scripts/test-log-viewer-auth.sh for detailed diagnostics"
echo ""
print_info "For troubleshooting, see: DEPLOY_LOG_VIEWER_FIX_V2.md"
echo ""
