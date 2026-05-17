#!/bin/bash

# Script untuk memperbaiki Log Viewer 403 Error
# Usage: ./scripts/fix-log-viewer.sh

set -e

echo "🔧 Fixing Log Viewer 403 Error..."
echo ""

# Warna untuk output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
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

# Cek apakah di dalam container atau host
if [ -f "/.dockerenv" ]; then
    echo "Running inside Docker container"
    IN_CONTAINER=true
else
    echo "Running on host machine"
    IN_CONTAINER=false
fi

# Step 1: Clear all caches
echo ""
echo "Step 1: Clearing all caches..."
if [ "$IN_CONTAINER" = true ]; then
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
else
    docker exec admin-payment-app php artisan config:clear
    docker exec admin-payment-app php artisan cache:clear
    docker exec admin-payment-app php artisan route:clear
    docker exec admin-payment-app php artisan view:clear
fi
print_success "All caches cleared"

# Step 2: Rebuild config cache
echo ""
echo "Step 2: Rebuilding config cache..."
if [ "$IN_CONTAINER" = true ]; then
    php artisan config:cache
else
    docker exec admin-payment-app php artisan config:cache
fi
print_success "Config cache rebuilt"

# Step 3: Test Redis connection
echo ""
echo "Step 3: Testing Redis connection..."
if [ "$IN_CONTAINER" = true ]; then
    php artisan tinker --execute="echo Cache::store('redis')->ping() ? 'Redis OK' : 'Redis FAIL';"
else
    docker exec admin-payment-app php artisan tinker --execute="echo Cache::store('redis')->ping() ? 'Redis OK' : 'Redis FAIL';"
fi

# Step 4: Check log-viewer routes
echo ""
echo "Step 4: Checking log-viewer routes..."
if [ "$IN_CONTAINER" = true ]; then
    php artisan route:list --path=log-viewer | head -n 20
else
    docker exec admin-payment-app php artisan route:list --path=log-viewer | head -n 20
fi

# Step 5: Verify session configuration
echo ""
echo "Step 5: Verifying session configuration..."
if [ "$IN_CONTAINER" = true ]; then
    php artisan tinker --execute="
        echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
        echo 'SESSION_ENCRYPT: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
        echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
        echo 'SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
    "
else
    docker exec admin-payment-app php artisan tinker --execute="
        echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
        echo 'SESSION_ENCRYPT: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
        echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
        echo 'SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
    "
fi

echo ""
print_success "Fix completed!"
echo ""
echo "📝 Next steps:"
echo "1. Login as owner user"
echo "2. Access /log-viewer"
echo "3. Check browser console for any 403 errors"
echo ""
echo "If still having issues, check FIX_LOG_VIEWER_403.md for troubleshooting"
