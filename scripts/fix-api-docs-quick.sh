#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Quick Fix for API Documentation 403 Error
# ═══════════════════════════════════════════════════════════════════

echo "🔧 Quick Fix for API Documentation Access..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ─── Step 1: Clear All Caches ──────────────────────────────────────
echo -e "${BLUE}Step 1:${NC} Clearing all caches..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
echo -e "${GREEN}✓${NC} Caches cleared"
echo ""

# ─── Step 2: Check User Role ───────────────────────────────────────
echo -e "${BLUE}Step 2:${NC} Checking users with owner role..."
php artisan tinker --execute="
    \$owners = \App\Models\User::where('role', 'owner')->get(['id', 'name', 'email']);
    if (\$owners->isEmpty()) {
        echo '${RED}✗${NC} No owner users found!';
        echo PHP_EOL;
        echo 'Please update a user to owner role:';
        echo PHP_EOL;
        echo 'php artisan tinker';
        echo PHP_EOL;
        echo '\$user = User::where(\"email\", \"your@email.com\")->first();';
        echo PHP_EOL;
        echo '\$user->role = \"owner\";';
        echo PHP_EOL;
        echo '\$user->save();';
    } else {
        echo '${GREEN}✓${NC} Owner users found:';
        foreach (\$owners as \$owner) {
            echo PHP_EOL . '  - ' . \$owner->name . ' (' . \$owner->email . ')';
        }
    }
"
echo ""
echo ""

# ─── Step 3: Verify Middleware ─────────────────────────────────────
echo -e "${BLUE}Step 3:${NC} Verifying middleware configuration..."
if grep -q "AuthorizeApiDocs" config/scramble.php; then
    echo -e "${GREEN}✓${NC} Middleware configured correctly"
else
    echo -e "${RED}✗${NC} Middleware not found in config"
fi
echo ""

# ─── Step 4: Test Access ───────────────────────────────────────────
echo -e "${BLUE}Step 4:${NC} Next steps to test access..."
echo ""
echo "1. Login to your application with an owner account"
echo "2. Visit: $(php artisan tinker --execute="echo config('app.url');")/docs/api"
echo ""
echo "If you still get 403 error:"
echo "  - Make sure you're logged in"
echo "  - Check your user role is 'owner'"
echo "  - Try in incognito/private window"
echo "  - Check browser console for errors"
echo ""

# ─── Step 5: Run Debug Script ──────────────────────────────────────
echo -e "${BLUE}Step 5:${NC} For detailed debugging, run:"
echo "  php scripts/debug-api-docs.php"
echo ""

echo -e "${GREEN}✅ Quick fix completed!${NC}"
