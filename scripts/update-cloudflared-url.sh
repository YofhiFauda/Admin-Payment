#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Script untuk Update URL Cloudflared
# ═══════════════════════════════════════════════════════════════════
#
#  Usage: ./scripts/update-cloudflared-url.sh <new-app-url> <new-reverb-url>
#  Example: ./scripts/update-cloudflared-url.sh \
#           https://new-app.trycloudflare.com \
#           new-reverb.trycloudflare.com
#
#  NOTE: All php artisan commands run INSIDE the Docker container
#        to avoid Redis/MySQL connection errors on the host machine.
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
    echo "Example: $0 https://new-app.trycloudflare.com new-reverb.trycloudflare.com"
    exit 1
fi

NEW_APP_URL=$1
NEW_REVERB_URL=$2

# Extract host from URL (remove https:// or http://)
NEW_REVERB_HOST=$(echo $NEW_REVERB_URL | sed 's|https://||' | sed 's|http://||')

# Extract APP host for SERVICE_FQDN_NGINX
NEW_APP_HOST=$(echo $NEW_APP_URL | sed 's|https://||' | sed 's|http://||')

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         Update Cloudflared URL Configuration              ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}New APP_URL:${NC} $NEW_APP_URL"
echo -e "${YELLOW}New REVERB HOST:${NC} $NEW_REVERB_HOST"
echo ""

# ─── Helper: update env file ──────────────────────────────────────
update_env_file() {
    local ENV_FILE=$1
    local LABEL=$2

    if [ ! -f "$ENV_FILE" ]; then
        echo -e "${YELLOW}⊘${NC} $ENV_FILE not found, skipping"
        return
    fi

    sed -i "s|^APP_URL=.*|APP_URL=$NEW_APP_URL|" "$ENV_FILE"
    sed -i "s|^SERVICE_FQDN_NGINX=.*|SERVICE_FQDN_NGINX=$NEW_APP_HOST|" "$ENV_FILE"
    sed -i "s|^SERVICE_URL_NGINX=.*|SERVICE_URL_NGINX=$NEW_APP_URL|" "$ENV_FILE"
    sed -i "s|^VITE_REVERB_HOST=.*|VITE_REVERB_HOST=$NEW_REVERB_HOST|" "$ENV_FILE"

    echo -e "${GREEN}✓${NC} $LABEL updated"
}

# ─── Step 1: Update .env AND .env.production ───────────────────────
echo -e "${BLUE}[1/6]${NC} Updating env files..."

# Backup .env
cp .env ".env.backup.$(date +%Y%m%d_%H%M%S)"

update_env_file ".env" ".env"
update_env_file ".env.production" ".env.production"

# ─── Step 2: Clear all Laravel caches (inside Docker) ──────────────
echo -e "${BLUE}[2/6]${NC} Clearing Laravel caches (inside container)..."

docker compose exec -T app php artisan config:clear 2>/dev/null || echo -e "${YELLOW}⚠${NC} config:clear skipped (container not running?)"
docker compose exec -T app php artisan route:clear 2>/dev/null || echo -e "${YELLOW}⚠${NC} route:clear skipped"
docker compose exec -T app php artisan view:clear 2>/dev/null || echo -e "${YELLOW}⚠${NC} view:clear skipped"
docker compose exec -T app php artisan cache:clear 2>/dev/null || echo -e "${YELLOW}⚠${NC} cache:clear skipped"

echo -e "${GREEN}✓${NC} Caches cleared"

# ─── Step 3: Rebuild config cache (inside Docker) ──────────────────
echo -e "${BLUE}[3/6]${NC} Rebuilding config cache (inside container)..."

docker compose exec -T app php artisan config:cache 2>/dev/null || echo -e "${YELLOW}⚠${NC} config:cache skipped"

echo -e "${GREEN}✓${NC} Config cached"

# ─── Step 4: Restart Queue Workers (inside Docker) ─────────────────
echo -e "${BLUE}[4/6]${NC} Restarting queue workers..."

docker compose exec -T app php artisan queue:restart 2>/dev/null || echo -e "${YELLOW}⚠${NC} queue:restart skipped"

echo -e "${GREEN}✓${NC} Queue workers restarted"

# ─── Step 5: Restart Docker services ──────────────────────────────
echo -e "${BLUE}[5/6]${NC} Restarting Docker services..."

docker compose restart reverb horizon scheduler 2>/dev/null || echo -e "${YELLOW}⚠${NC} Some services failed to restart"
docker compose restart nginx 2>/dev/null || echo -e "${YELLOW}⚠${NC} Nginx restart skipped"

echo -e "${GREEN}✓${NC} Services restarted"

# ─── Step 6: Verify ────────────────────────────────────────────────
echo -e "${BLUE}[6/6]${NC} Verifying configuration..."

echo ""
echo -e "  ${YELLOW}Checking APP_URL in container:${NC}"
docker compose exec -T app php artisan tinker --execute="echo config('app.url');" 2>/dev/null || echo -e "  ${YELLOW}⚠${NC} Could not verify"

echo ""

# ─── Summary ────────────────────────────────────────────────────────
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                    Update Complete!                        ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Current Configuration:${NC}"
echo -e "  APP_URL:          ${GREEN}$NEW_APP_URL${NC}"
echo -e "  VITE_REVERB_HOST: ${GREEN}$NEW_REVERB_HOST${NC}"
echo ""
echo -e "${YELLOW}Files updated:${NC} .env, .env.production"
echo -e "${YELLOW}Backup saved to:${NC} .env.backup.$(date +%Y%m%d_%H%M%S)"
echo ""
echo -e "${BLUE}All services have been restarted automatically.${NC}"
