#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Test API Documentation Access
# ═══════════════════════════════════════════════════════════════════

echo "🔍 Testing API Documentation Access..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ─── 1. Check Environment ──────────────────────────────────────────
echo "1️⃣  Checking environment..."
APP_ENV=$(php artisan tinker --execute="echo config('app.env');")
echo "   Environment: $APP_ENV"
echo ""

# ─── 2. Check if Scramble is installed ────────────────────────────
echo "2️⃣  Checking Scramble installation..."
if php artisan list | grep -q "scramble"; then
    echo -e "   ${GREEN}✓${NC} Scramble is installed"
else
    echo -e "   ${RED}✗${NC} Scramble is NOT installed"
    exit 1
fi
echo ""

# ─── 3. Check Routes ───────────────────────────────────────────────
echo "3️⃣  Checking routes..."
echo "   Looking for /docs/api route..."
php artisan route:list --path=docs 2>/dev/null || echo "   Could not list routes (check Horizon error)"
echo ""

# ─── 4. Check Middleware ───────────────────────────────────────────
echo "4️⃣  Checking middleware configuration..."
if grep -q "AuthorizeApiDocs" config/scramble.php; then
    echo -e "   ${GREEN}✓${NC} Custom middleware is configured"
else
    echo -e "   ${YELLOW}⚠${NC}  Custom middleware not found in config"
fi
echo ""

# ─── 5. Check Gate Definition ──────────────────────────────────────
echo "5️⃣  Checking Gate definition..."
if grep -q "viewApiDocs" app/Providers/AppServiceProvider.php; then
    echo -e "   ${GREEN}✓${NC} Gate 'viewApiDocs' is defined"
else
    echo -e "   ${RED}✗${NC} Gate 'viewApiDocs' is NOT defined"
fi
echo ""

# ─── 6. Test Access (if authenticated) ─────────────────────────────
echo "6️⃣  Testing access..."
echo "   To test access, you need to:"
echo "   1. Login to the application"
echo "   2. Make sure your user has role 'owner'"
echo "   3. Visit: $(php artisan tinker --execute="echo config('app.url');")/docs/api"
echo ""

# ─── 7. Check User Roles ───────────────────────────────────────────
echo "7️⃣  Checking users with 'owner' role..."
php artisan tinker --execute="
    \$owners = \App\Models\User::where('role', 'owner')->get(['id', 'name', 'email', 'role']);
    if (\$owners->isEmpty()) {
        echo '   No users with owner role found!';
    } else {
        echo '   Users with owner role:';
        foreach (\$owners as \$owner) {
            echo PHP_EOL . '   - ' . \$owner->name . ' (' . \$owner->email . ')';
        }
    }
"
echo ""
echo ""

# ─── 8. Recommendations ────────────────────────────────────────────
echo "📋 Recommendations:"
echo ""
echo "If still getting 403:"
echo "1. Clear cache:"
echo "   php artisan config:clear"
echo "   php artisan route:clear"
echo "   php artisan cache:clear"
echo ""
echo "2. Make sure you're logged in as owner"
echo ""
echo "3. Check logs:"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "4. Test in local environment first (APP_ENV=local)"
echo ""

echo "✅ Test completed!"
