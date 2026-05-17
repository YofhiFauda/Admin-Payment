#!/bin/bash

# Script untuk testing Log Viewer di Production (Coolify)
# Usage: ./scripts/test-log-viewer-production.sh

set -e

echo "🧪 Testing Log Viewer in Production..."
echo ""

# Warna untuk output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fungsi untuk print dengan warna
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Test 1: Check if app is running
echo "Test 1: Checking if app is running..."
if docker ps | grep -q "admin-payment-app"; then
    print_success "App container is running"
else
    print_error "App container is not running"
    exit 1
fi

# Test 2: Check Redis connection
echo ""
echo "Test 2: Checking Redis connection..."
REDIS_TEST=$(docker exec admin-payment-app php artisan tinker --execute="echo Cache::store('redis')->ping() ? 'OK' : 'FAIL';" 2>&1 | grep -o "OK\|FAIL" || echo "ERROR")
if [ "$REDIS_TEST" = "OK" ]; then
    print_success "Redis connection OK"
else
    print_error "Redis connection FAILED"
fi

# Test 3: Check session configuration
echo ""
echo "Test 3: Checking session configuration..."
docker exec admin-payment-app php artisan tinker --execute="
    \$driver = config('session.driver');
    \$encrypt = config('session.encrypt') ? 'true' : 'false';
    \$sameSite = config('session.same_site');
    \$secure = config('session.secure') ? 'true' : 'false';
    
    echo 'SESSION_DRIVER: ' . \$driver . PHP_EOL;
    echo 'SESSION_ENCRYPT: ' . \$encrypt . PHP_EOL;
    echo 'SESSION_SAME_SITE: ' . \$sameSite . PHP_EOL;
    echo 'SESSION_SECURE: ' . \$secure . PHP_EOL;
    
    if (\$driver === 'redis' && \$encrypt === 'false' && \$sameSite === 'lax') {
        echo PHP_EOL . '✓ Session config is correct' . PHP_EOL;
    } else {
        echo PHP_EOL . '✗ Session config needs fixing' . PHP_EOL;
    }
"

# Test 4: Check log-viewer routes
echo ""
echo "Test 4: Checking log-viewer routes..."
ROUTE_COUNT=$(docker exec admin-payment-app php artisan route:list --path=log-viewer 2>/dev/null | grep -c "log-viewer" || echo "0")
if [ "$ROUTE_COUNT" -gt "0" ]; then
    print_success "Log-viewer routes registered ($ROUTE_COUNT routes)"
else
    print_error "Log-viewer routes not found"
fi

# Test 5: Check log-viewer config
echo ""
echo "Test 5: Checking log-viewer configuration..."
docker exec admin-payment-app php artisan tinker --execute="
    \$middleware = config('log-viewer.middleware');
    \$apiMiddleware = config('log-viewer.api_middleware');
    
    echo 'Middleware: ' . implode(', ', \$middleware) . PHP_EOL;
    echo 'API Middleware: ' . implode(', ', \$apiMiddleware) . PHP_EOL;
    
    if (in_array('web', \$apiMiddleware) && in_array('auth', \$apiMiddleware)) {
        echo PHP_EOL . '✓ Log-viewer middleware is correct' . PHP_EOL;
    } else {
        echo PHP_EOL . '✗ Log-viewer middleware needs fixing' . PHP_EOL;
    }
"

# Test 6: Check owner user exists
echo ""
echo "Test 6: Checking owner user..."
OWNER_COUNT=$(docker exec admin-payment-app php artisan tinker --execute="echo App\Models\User::where('role', 'owner')->count();" 2>&1 | grep -o "[0-9]" | head -1 || echo "0")
if [ "$OWNER_COUNT" -gt "0" ]; then
    print_success "Owner user exists ($OWNER_COUNT users)"
else
    print_warning "No owner user found"
fi

# Test 7: Check log files
echo ""
echo "Test 7: Checking log files..."
LOG_COUNT=$(docker exec admin-payment-app find storage/logs -name "*.log" -type f 2>/dev/null | wc -l || echo "0")
if [ "$LOG_COUNT" -gt "0" ]; then
    print_success "Log files found ($LOG_COUNT files)"
    docker exec admin-payment-app ls -lh storage/logs/*.log 2>/dev/null | tail -5
else
    print_warning "No log files found"
fi

# Test 8: Test HTTP endpoint (if URL provided)
echo ""
echo "Test 8: Testing HTTP endpoint..."
APP_URL=$(docker exec admin-payment-app php artisan tinker --execute="echo config('app.url');" 2>&1 | grep -o "https://[^[:space:]]*" || echo "")
if [ -n "$APP_URL" ]; then
    print_info "Testing: $APP_URL/log-viewer"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL/log-viewer" || echo "000")
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
        print_success "HTTP endpoint accessible (HTTP $HTTP_CODE)"
    else
        print_warning "HTTP endpoint returned $HTTP_CODE (might need authentication)"
    fi
else
    print_warning "APP_URL not configured, skipping HTTP test"
fi

# Summary
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "                    TEST SUMMARY"
echo "═══════════════════════════════════════════════════════════"
echo ""
print_info "If all tests passed, log-viewer should work correctly."
print_info "To access log-viewer:"
echo "  1. Login as owner user"
echo "  2. Navigate to /log-viewer"
echo "  3. Check browser console for any errors"
echo ""
print_warning "If you see 403 errors, check:"
echo "  - User is logged in as owner"
echo "  - Session cookies are being sent"
echo "  - Redis is working properly"
echo ""
