#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
# Pre-Production Testing Script
# Test semua perubahan sebelum push ke production
# ═══════════════════════════════════════════════════════════════════

set -e

# Auto-detect container name
CONTAINER_NAME=$(docker ps --format "{{.Names}}" | grep -E "(admin-payment-app|whusnet-app)" | head -1)

if [ -z "$CONTAINER_NAME" ]; then
    echo "Error: Container not found. Looking for 'admin-payment-app' or 'whusnet-app'"
    exit 1
fi

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "═══════════════════════════════════════════════════════════════════"
echo "  PRE-PRODUCTION TESTING - LOG VIEWER & PULSE"
echo "═══════════════════════════════════════════════════════════════════"
echo ""

# ─── Test 1: Config Cache Compatibility ────────────────────────────
echo -e "${YELLOW}[Test 1/7]${NC} Testing config:cache compatibility..."
if docker exec $CONTAINER_NAME php artisan config:clear > /dev/null 2>&1 && \
   docker exec $CONTAINER_NAME php artisan config:cache > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PASSED${NC} - Config cache berhasil"
else
    echo -e "${RED}✗ FAILED${NC} - Config cache error (not serializable)"
    echo "  Fix: Cek config/log-viewer.php dan config/pulse.php"
    exit 1
fi
echo ""

# ─── Test 2: Route Cache Compatibility ─────────────────────────────
echo -e "${YELLOW}[Test 2/7]${NC} Testing route:cache compatibility..."
if docker exec $CONTAINER_NAME php artisan route:clear > /dev/null 2>&1 && \
   docker exec $CONTAINER_NAME php artisan route:cache > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PASSED${NC} - Route cache berhasil"
    
    # Check log-viewer routes
    echo "  Checking log-viewer routes..."
    docker exec $CONTAINER_NAME php artisan route:list --path=log-viewer | head -5
else
    echo -e "${RED}✗ FAILED${NC} - Route cache error"
    exit 1
fi
echo ""

# ─── Test 3: Redis Connections ─────────────────────────────────────
echo -e "${YELLOW}[Test 3/7]${NC} Testing Redis connections..."

# Main Redis
echo "  Testing main Redis..."
MAIN_REDIS=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
    try {
        Cache::store('redis')->put('test_main', 'ok', 60);
        echo Cache::store('redis')->get('test_main');
    } catch (Exception \$e) {
        echo 'ERROR';
    }
" 2>/dev/null | tail -1)

if [ "$MAIN_REDIS" = "ok" ]; then
    echo -e "  ${GREEN}✓${NC} Main Redis: Connected"
else
    echo -e "  ${RED}✗${NC} Main Redis: Failed"
    exit 1
fi

# Pulse Redis
echo "  Testing Pulse Redis..."
PULSE_REDIS=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
    try {
        \Illuminate\Support\Facades\Redis::connection('pulse')->set('test_pulse', 'ok');
        echo \Illuminate\Support\Facades\Redis::connection('pulse')->get('test_pulse');
    } catch (Exception \$e) {
        echo 'ERROR';
    }
" 2>/dev/null | tail -1)

if [ "$PULSE_REDIS" = "ok" ]; then
    echo -e "  ${GREEN}✓${NC} Pulse Redis: Connected"
else
    echo -e "  ${RED}✗${NC} Pulse Redis: Failed"
    echo "  Fix: Cek PULSE_REDIS_HOST dan credentials di .env"
    exit 1
fi
echo ""

# ─── Test 4: Session Configuration ─────────────────────────────────
echo -e "${YELLOW}[Test 4/7]${NC} Testing session configuration..."
SESSION_INFO=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
    echo 'DRIVER=' . config('session.driver') . '|';
    echo 'ENCRYPT=' . (config('session.encrypt') ? 'true' : 'false') . '|';
    echo 'SAME_SITE=' . config('session.same_site');
" 2>/dev/null | tail -1)

echo "  $SESSION_INFO"

if [[ $SESSION_INFO == *"DRIVER=redis"* ]] && \
   [[ $SESSION_INFO == *"ENCRYPT=false"* ]] && \
   [[ $SESSION_INFO == *"SAME_SITE=lax"* ]]; then
    echo -e "${GREEN}✓ PASSED${NC} - Session configuration correct"
else
    echo -e "${RED}✗ FAILED${NC} - Session configuration incorrect"
    echo "  Expected: DRIVER=redis, ENCRYPT=false, SAME_SITE=lax"
    exit 1
fi

# Test session persistence
echo "  Testing session persistence..."
SESSION_TEST=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
    session()->put('test_session', 'value123');
    echo session()->get('test_session');
" 2>/dev/null | tail -1)

if [ "$SESSION_TEST" = "value123" ]; then
    echo -e "  ${GREEN}✓${NC} Session persistence: Working"
else
    echo -e "  ${RED}✗${NC} Session persistence: Failed"
    exit 1
fi
echo ""

# ─── Test 5: Authorization ──────────────────────────────────────────
echo -e "${YELLOW}[Test 5/7]${NC} Testing authorization..."

# Check if owner exists
OWNER_EXISTS=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
    echo App\Models\User::where('role', 'owner')->exists() ? 'yes' : 'no';
" 2>/dev/null | tail -1)

if [ "$OWNER_EXISTS" = "yes" ]; then
    echo -e "  ${GREEN}✓${NC} Owner user exists"
    
    # Test Gate
    GATE_TEST=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
        \$owner = App\Models\User::where('role', 'owner')->first();
        echo \Gate::forUser(\$owner)->allows('viewLogViewer') ? 'allowed' : 'denied';
    " 2>/dev/null | tail -1)
    
    if [ "$GATE_TEST" = "allowed" ]; then
        echo -e "  ${GREEN}✓${NC} Gate authorization: Working"
    else
        echo -e "  ${RED}✗${NC} Gate authorization: Failed"
        exit 1
    fi
else
    echo -e "  ${YELLOW}⚠${NC} No owner user found (create one for testing)"
fi
echo ""

# ─── Test 6: Pulse Configuration ───────────────────────────────────
echo -e "${YELLOW}[Test 6/7]${NC} Testing Pulse configuration..."

PULSE_ENABLED=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
    echo config('pulse.enabled') ? 'true' : 'false';
" 2>/dev/null | tail -1)

if [ "$PULSE_ENABLED" = "true" ]; then
    echo -e "  ${GREEN}✓${NC} Pulse: Enabled"
    
    # Check Pulse storage
    PULSE_STORAGE=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
        echo config('pulse.storage.driver');
    " 2>/dev/null | tail -1)
    echo "  Storage driver: $PULSE_STORAGE"
    
    # Check Pulse ingest
    PULSE_INGEST=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
        echo config('pulse.ingest.driver');
    " 2>/dev/null | tail -1)
    echo "  Ingest driver: $PULSE_INGEST"
    
    if [ "$PULSE_STORAGE" = "database" ] && [ "$PULSE_INGEST" = "redis" ]; then
        echo -e "${GREEN}✓ PASSED${NC} - Pulse configuration correct"
    else
        echo -e "${RED}✗ FAILED${NC} - Pulse configuration incorrect"
        echo "  Expected: storage=database, ingest=redis"
        exit 1
    fi
else
    echo -e "  ${YELLOW}⚠${NC} Pulse disabled (set PULSE_ENABLED=true for testing)"
fi
echo ""

# ─── Test 7: Middleware Registration ───────────────────────────────
echo -e "${YELLOW}[Test 7/7]${NC} Testing middleware registration..."

# Check if log-viewer.auth middleware exists
MIDDLEWARE_CHECK=$(docker exec $CONTAINER_NAME php artisan route:list --path=log-viewer 2>/dev/null | grep -c "log-viewer.auth" || echo "0")

if [ "$MIDDLEWARE_CHECK" -gt "0" ]; then
    echo -e "${GREEN}✓ PASSED${NC} - log-viewer.auth middleware registered"
else
    echo -e "${RED}✗ FAILED${NC} - log-viewer.auth middleware not found"
    echo "  Fix: Cek bootstrap/app.php middleware alias"
    exit 1
fi
echo ""

# ═══════════════════════════════════════════════════════════════════
echo -e "${GREEN}═══════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ ALL TESTS PASSED${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════════════${NC}"
echo ""
echo "Next steps:"
echo "1. Test Log Viewer manually:"
echo "   - Login sebagai owner"
echo "   - Akses http://localhost/log-viewer"
echo "   - Cek DevTools Console (no 403 errors)"
echo ""
echo "2. Test Pulse manually:"
echo "   - Start: docker exec $CONTAINER_NAME php artisan pulse:work"
echo "   - Akses http://localhost/pulse"
echo "   - Verify metrics appear"
echo ""
echo "3. Jika semua manual test OK, ready untuk push ke production!"
echo ""
