#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Script untuk Update URL Cloudflared (Docker Environment)
# ═══════════════════════════════════════════════════════════════════
#
#  Usage: ./scripts/update-cloudflared-docker.sh <new-app-url> <new-reverb-url>
#  Example: ./scripts/update-cloudflared-docker.sh \
#           https://new-app.trycloudflare.com \
#           https://new-reverb.trycloudflare.com
#
# ═══════════════════════════════════════════════════════════════════

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check arguments
if [ "$#" -ne 2 ]; then
    echo -e "${RED}Error: Missing arguments${NC}"
    echo "Usage: $0 <new-app-url> <new-reverb-url>"
    echo "Example: $0 https://new-app.trycloudflare.com https://new-reverb.trycloudflare.com"
    exit 1
fi

NEW_APP_URL=$1
NEW_REVERB_URL=$2

# Extract host from URL
NEW_REVERB_HOST=$(echo $NEW_REVERB_URL | sed 's|https://||' | sed 's|http://||')

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║    Update Cloudflared URL (Docker Environment)            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}New APP_URL:${NC} $NEW_APP_URL"
echo -e "${YELLOW}New REVERB URL:${NC} $NEW_REVERB_URL"
echo -e "${YELLOW}New REVERB HOST:${NC} $NEW_REVERB_HOST"
echo ""

# ─── Step 1: Update .env file ──────────────────────────────────────
echo -e "${BLUE}[1/7]${NC} Updating .env file..."

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update APP_URL
sed -i "s|^APP_URL=.*|APP_URL=$NEW_APP_URL|" .env
sed -i "s|^SERVICE_URL_NGINX=.*|SERVICE_URL_NGINX=$NEW_APP_URL|" .env

# Update REVERB
sed -i "s|^VITE_REVERB_HOST=.*|VITE_REVERB_HOST=$NEW_REVERB_HOST|" .env

echo -e "${GREEN}✓${NC} .env updated"

# ─── Step 2: Clear Laravel caches in container ─────────────────────
echo -e "${BLUE}[2/7]${NC} Clearing Laravel caches..."

docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan cache:clear

echo -e "${GREEN}✓${NC} Caches cleared"

# ─── Step 3: Rebuild config cache ──────────────────────────────────
echo -e "${BLUE}[3/7]${NC} Rebuilding config cache..."

docker-compose exec app php artisan config:cache

echo -e "${GREEN}✓${NC} Config cached"

# ─── Step 4: Restart Queue Workers ─────────────────────────────────
echo -e "${BLUE}[4/7]${NC} Restarting queue workers..."

docker-compose exec app php artisan queue:restart

echo -e "${GREEN}✓${NC} Queue workers restarted"

# ─── Step 5: Rebuild Vite assets ───────────────────────────────────
echo -e "${BLUE}[5/7]${NC} Rebuilding Vite assets..."

if [ -f "package.json" ]; then
    npm run build
    echo -e "${GREEN}✓${NC} Vite assets rebuilt"
else
    echo -e "${YELLOW}⊘${NC} No package.json found, skipping"
fi

# ─── Step 6: Restart Docker services ───────────────────────────────
echo -e "${BLUE}[6/7]${NC} Restarting Docker services..."

echo -e "${YELLOW}→${NC} Restarting reverb..."
docker-compose restart reverb

echo -e "${YELLOW}→${NC} Restarting app..."
docker-compose restart app

echo -e "${YELLOW}→${NC} Restarting nginx..."
docker-compose restart nginx

echo -e "${GREEN}✓${NC} Docker services restarted"

# ─── Step 7: Verify services ───────────────────────────────────────
echo -e "${BLUE}[7/7]${NC} Verifying services..."

sleep 3

echo ""
echo -e "${YELLOW}Service Status:${NC}"
docker-compose ps

# ─── Summary ────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                    Update Complete!                        ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Current Configuration:${NC}"
echo -e "  APP_URL: ${GREEN}$NEW_APP_URL${NC}"
echo -e "  VITE_REVERB_HOST: ${GREEN}$NEW_REVERB_HOST${NC}"
echo ""
echo -e "${YELLOW}Backup saved to:${NC} .env.backup.$(date +%Y%m%d_%H%M%S)"
echo ""
echo -e "${BLUE}Test your application:${NC}"
echo -e "  ${GREEN}→${NC} Open: $NEW_APP_URL"
echo -e "  ${GREEN}→${NC} Check WebSocket: Browser Console"
echo ""
