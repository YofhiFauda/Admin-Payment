#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Script untuk Update URL Cloudflared
# ═══════════════════════════════════════════════════════════════════
#
#  Usage: ./scripts/update-cloudflared-url.sh <new-app-url> <new-reverb-url>
#  Example: ./scripts/update-cloudflared-url.sh \
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

# Extract host from URL (remove https://)
NEW_REVERB_HOST=$(echo $NEW_REVERB_URL | sed 's|https://||' | sed 's|http://||')

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         Update Cloudflared URL Configuration              ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}New APP_URL:${NC} $NEW_APP_URL"
echo -e "${YELLOW}New REVERB URL:${NC} $NEW_REVERB_URL"
echo -e "${YELLOW}New REVERB HOST:${NC} $NEW_REVERB_HOST"
echo ""

# ─── Step 1: Update .env file ──────────────────────────────────────
echo -e "${BLUE}[1/6]${NC} Updating .env file..."

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update APP_URL
sed -i "s|^APP_URL=.*|APP_URL=$NEW_APP_URL|" .env
sed -i "s|^SERVICE_URL_NGINX=.*|SERVICE_URL_NGINX=$NEW_APP_URL|" .env

# Update REVERB
sed -i "s|^VITE_REVERB_HOST=.*|VITE_REVERB_HOST=$NEW_REVERB_HOST|" .env

echo -e "${GREEN}✓${NC} .env updated"

# ─── Step 2: Clear all Laravel caches ──────────────────────────────
echo -e "${BLUE}[2/6]${NC} Clearing Laravel caches..."

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo -e "${GREEN}✓${NC} Caches cleared"

# ─── Step 3: Rebuild config cache ──────────────────────────────────
echo -e "${BLUE}[3/6]${NC} Rebuilding config cache..."

php artisan config:cache

echo -e "${GREEN}✓${NC} Config cached"

# ─── Step 4: Restart Queue Workers ─────────────────────────────────
echo -e "${BLUE}[4/6]${NC} Restarting queue workers..."

php artisan queue:restart

echo -e "${GREEN}✓${NC} Queue workers restarted"

# ─── Step 5: Rebuild Vite assets (if needed) ───────────────────────
echo -e "${BLUE}[5/6]${NC} Checking if Vite rebuild is needed..."

if [ -f "package.json" ]; then
    echo -e "${YELLOW}→${NC} Rebuilding Vite assets with new REVERB_HOST..."
    npm run build
    echo -e "${GREEN}✓${NC} Vite assets rebuilt"
else
    echo -e "${YELLOW}⊘${NC} No package.json found, skipping Vite rebuild"
fi

# ─── Step 6: Show restart instructions ─────────────────────────────
echo ""
echo -e "${BLUE}[6/6]${NC} ${YELLOW}Manual restart required:${NC}"
echo ""
echo -e "  ${YELLOW}1. Restart Reverb server:${NC}"
echo -e "     docker-compose restart reverb"
echo -e "     ${GREEN}or${NC}"
echo -e "     php artisan reverb:restart"
echo ""
echo -e "  ${YELLOW}2. Restart Horizon (if using):${NC}"
echo -e "     php artisan horizon:terminate"
echo ""
echo -e "  ${YELLOW}3. Restart web server (if needed):${NC}"
echo -e "     docker-compose restart nginx app"
echo ""

# ─── Summary ────────────────────────────────────────────────────────
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
echo -e "${BLUE}Don't forget to restart services as shown above!${NC}"
