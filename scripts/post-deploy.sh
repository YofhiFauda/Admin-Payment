#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
# Post-Deploy Script
# Automated steps after deployment
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
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "═══════════════════════════════════════════════════════════════════"
echo "  POST-DEPLOY AUTOMATION"
echo "═══════════════════════════════════════════════════════════════════"
echo ""

# ─── Step 1: Clear All Caches ──────────────────────────────────────
echo -e "${BLUE}[Step 1/8]${NC} Clearing all caches..."
docker exec $CONTAINER_NAME php artisan config:clear > /dev/null 2>&1
docker exec $CONTAINER_NAME php artisan cache:clear > /dev/null 2>&1
docker exec $CONTAINER_NAME php artisan route:clear > /dev/null 2>&1
docker exec $CONTAINER_NAME php artisan view:clear > /dev/null 2>&1
docker exec $CONTAINER_NAME php artisan event:clear > /dev/null 2>&1
echo -e "${GREEN}✓ All caches cleared${NC}"
echo ""

# ─── Step 2: Rebuild Caches ────────────────────────────────────────
echo -e "${BLUE}[Step 2/8]${NC} Rebuilding caches..."
if docker exec $CONTAINER_NAME php artisan config:cache > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Config cached${NC}"
else
    echo -e "${RED}✗ Config cache failed (check for serialization issues)${NC}"
    exit 1
fi

docker exec $CONTAINER_NAME php artisan route:cache > /dev/null 2>&1
echo -e "${GREEN}✓ Routes cached${NC}"

docker exec $CONTAINER_NAME php artisan view:cache > /dev/null 2>&1
echo -e "${GREEN}✓ Views cached${NC}"

docker exec $CONTAINER_NAME php artisan event:cache > /dev/null 2>&1
echo -e "${GREEN}✓ Events cached${NC}"
echo ""

# ─── Step 3: Check Database Connection ─────────────────────────────
echo -e "${BLUE}[Step 3/8]${NC} Checking database connection..."
DB_NAME=$(docker exec $CONTAINER_NAME php artisan tinker --execute="echo DB::connection()->getDatabaseName();" 2>/dev/null | tail -1)
if [ -n "$DB_NAME" ]; then
    echo -e "${GREEN}✓ Database connected: $DB_NAME${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
    exit 1
fi
echo ""

# ─── Step 4: Check Redis Connection ────────────────────────────────
echo -e "${BLUE}[Step 4/8]${NC} Checking Redis connection..."
REDIS_PING=$(docker exec $CONTAINER_NAME php artisan redis:ping 2>/dev/null | tail -1)
if [[ $REDIS_PING == *"PONG"* ]]; then
    echo -e "${GREEN}✓ Redis connected${NC}"
else
    echo -e "${RED}✗ Redis connection failed${NC}"
    exit 1
fi
echo ""

# ─── Step 5: Run Migrations ────────────────────────────────────────
echo -e "${BLUE}[Step 5/8]${NC} Running database migrations..."
MIGRATION_OUTPUT=$(docker exec $CONTAINER_NAME php artisan migrate --force 2>&1)
if [[ $MIGRATION_OUTPUT == *"Nothing to migrate"* ]]; then
    echo -e "${GREEN}✓ Database up-to-date${NC}"
elif [[ $MIGRATION_OUTPUT == *"Migrated"* ]]; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
    echo "$MIGRATION_OUTPUT" | grep "Migrated:"
else
    echo -e "${YELLOW}⚠ Migration output:${NC}"
    echo "$MIGRATION_OUTPUT"
fi
echo ""

# ─── Step 6: Verify Configuration ──────────────────────────────────
echo -e "${BLUE}[Step 6/8]${NC} Verifying configuration..."

# Check APP_URL
APP_URL=$(docker exec $CONTAINER_NAME php artisan tinker --execute="echo config('app.url');" 2>/dev/null | tail -1)
echo "  APP_URL: $APP_URL"

# Check Session config
SESSION_INFO=$(docker exec $CONTAINER_NAME php artisan tinker --execute="
    echo config('session.driver') . '|';
    echo (config('session.encrypt') ? 'true' : 'false') . '|';
    echo config('session.same_site');
" 2>/dev/null | tail -1)

IFS='|' read -r SESSION_DRIVER SESSION_ENCRYPT SESSION_SAME_SITE <<< "$SESSION_INFO"
echo "  Session: driver=$SESSION_DRIVER, encrypt=$SESSION_ENCRYPT, same_site=$SESSION_SAME_SITE"

if [ "$SESSION_DRIVER" = "redis" ] && [ "$SESSION_ENCRYPT" = "false" ] && [ "$SESSION_SAME_SITE" = "lax" ]; then
    echo -e "${GREEN}✓ Session configuration correct${NC}"
else
    echo -e "${YELLOW}⚠ Session configuration may need review${NC}"
fi
echo ""

# ─── Step 7: Ensure Traefik WebSocket Config ───────────────────────
echo -e "${BLUE}[Step 7/9]${NC} Ensuring Traefik WebSocket config..."
TRAEFIK_DYNAMIC_DIR="/data/coolify/proxy/dynamic"
WEBSOCKET_CONFIG="$TRAEFIK_DYNAMIC_DIR/websocket.yml"
WEBSOCKET_SOURCE="$(dirname "$0")/../docker/traefik/websocket.yml"

if [ -f "$WEBSOCKET_SOURCE" ] && [ -d "$TRAEFIK_DYNAMIC_DIR" ]; then
    cp "$WEBSOCKET_SOURCE" "$WEBSOCKET_CONFIG"
    echo -e "${GREEN}✓ Traefik WebSocket config deployed${NC}"
else
    echo -e "${YELLOW}⚠ Traefik WebSocket config skipped (dir not found or source missing)${NC}"
fi
echo ""

# ─── Step 8: Restart Services ──────────────────────────────────────
echo -e "${BLUE}[Step 8/9]${NC} Restarting services..."
docker-compose restart app nginx horizon reverb scheduler pulse > /dev/null 2>&1
echo -e "${GREEN}✓ Services restarted${NC}"
echo ""

# Wait for services to be ready
echo "  Waiting for services to be ready..."
sleep 10

# ─── Step 8: Verify Services ───────────────────────────────────────
echo -e "${BLUE}[Step 9/9]${NC} Verifying services..."

# Check container status
CONTAINERS=("app" "nginx" "horizon" "reverb" "scheduler" "pulse")
ALL_RUNNING=true

for container in "${CONTAINERS[@]}"; do
    STATUS=$(docker ps --filter "name=whusnet-$container" --format "{{.Status}}" 2>/dev/null)
    if [[ $STATUS == *"Up"* ]]; then
        echo -e "  ${GREEN}✓${NC} $container: Running"
    else
        echo -e "  ${RED}✗${NC} $container: Not running"
        ALL_RUNNING=false
    fi
done

if [ "$ALL_RUNNING" = false ]; then
    echo ""
    echo -e "${RED}Some services are not running. Check logs:${NC}"
    echo "  docker logs whusnet-app --tail=50"
    exit 1
fi
echo ""

# ─── Test Application ──────────────────────────────────────────────
echo -e "${BLUE}Testing application...${NC}"

# Test health endpoint
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/up 2>/dev/null || echo "000")
if [ "$HEALTH_STATUS" = "200" ]; then
    echo -e "${GREEN}✓ Health check: OK${NC}"
else
    echo -e "${YELLOW}⚠ Health check: $HEALTH_STATUS${NC}"
fi

# Test main page
MAIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000 2>/dev/null || echo "000")
if [ "$MAIN_STATUS" = "200" ] || [ "$MAIN_STATUS" = "302" ]; then
    echo -e "${GREEN}✓ Main page: OK${NC}"
else
    echo -e "${YELLOW}⚠ Main page: $MAIN_STATUS${NC}"
fi
echo ""

# ═══════════════════════════════════════════════════════════════════
echo -e "${GREEN}═══════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ POST-DEPLOY COMPLETED${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════════════${NC}"
echo ""
echo "Next steps:"
echo "1. Test website di browser:"
echo "   - Akses: $APP_URL"
echo "   - Login dan verify session persists"
echo "   - Check DevTools Console (no errors)"
echo ""
echo "2. Test admin tools:"
echo "   - Log Viewer: $APP_URL/log-viewer"
echo "   - Pulse: $APP_URL/pulse"
echo "   - Horizon: $APP_URL/horizon"
echo ""
echo "3. Monitor logs:"
echo "   docker logs whusnet-app -f"
echo ""
echo "4. Check Pulse dashboard for metrics"
echo ""
echo "If any issues, check:"
echo "  - POST_DEPLOY_CHECKLIST.md"
echo "  - docker logs whusnet-app --tail=100"
echo ""
