#!/bin/bash

# Real-Time Testing Script
# Usage: bash test-realtime.sh

echo "🧪 Testing Real-Time Configuration..."
echo ""

# Test 1: Broadcasting Driver
echo "1️⃣ Checking Broadcasting Driver..."
DRIVER=$(php artisan tinker --execute="echo config('broadcasting.default');")
if [ "$DRIVER" = "reverb" ]; then
    echo "   ✅ Broadcasting Driver: reverb"
else
    echo "   ❌ Broadcasting Driver: $DRIVER (should be 'reverb')"
    echo "   Fix: Set BROADCAST_CONNECTION=reverb in .env"
    exit 1
fi
echo ""

# Test 2: Reverb Server
echo "2️⃣ Checking Reverb Server..."
if docker ps | grep -q "whusnet-reverb"; then
    echo "   ✅ Reverb Server: Running"
else
    echo "   ❌ Reverb Server: Not Running"
    echo "   Fix: docker restart whusnet-reverb"
    exit 1
fi
echo ""

# Test 3: Config Cache
echo "3️⃣ Clearing Config Cache..."
php artisan config:clear > /dev/null 2>&1
php artisan config:cache > /dev/null 2>&1
echo "   ✅ Config Cache: Cleared and Rebuilt"
echo ""

# Test 4: Queue
echo "4️⃣ Restarting Queue..."
php artisan queue:restart > /dev/null 2>&1
echo "   ✅ Queue: Restarted"
echo ""

# Test 5: Assets
echo "5️⃣ Checking Assets..."
if [ -f "public/build/manifest.json" ]; then
    echo "   ✅ Assets: Built"
else
    echo "   ⚠️  Assets: Not Built"
    echo "   Run: npm run build"
fi
echo ""

# Test 6: Event Files
echo "6️⃣ Checking Event Files..."
if [ -f "app/Events/TransactionCreated.php" ]; then
    echo "   ✅ TransactionCreated.php: Exists"
else
    echo "   ❌ TransactionCreated.php: Missing"
    exit 1
fi

if [ -f "app/Events/TransactionUpdated.php" ]; then
    echo "   ✅ TransactionUpdated.php: Exists"
else
    echo "   ❌ TransactionUpdated.php: Missing"
    exit 1
fi

if [ -f "app/Events/TransactionDeleted.php" ]; then
    echo "   ✅ TransactionDeleted.php: Exists"
else
    echo "   ❌ TransactionDeleted.php: Missing"
    exit 1
fi
echo ""

# Test 7: Frontend Files
echo "7️⃣ Checking Frontend Files..."
if [ -f "resources/js/transactions/realtime.js" ]; then
    echo "   ✅ realtime.js: Exists"
else
    echo "   ❌ realtime.js: Missing"
    exit 1
fi
echo ""

# Summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 All Checks Passed!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 Next Steps:"
echo "   1. Open browser as Owner"
echo "   2. Go to /transactions"
echo "   3. Open Developer Tools → Console"
echo "   4. Look for: 📡 [REALTIME] Echo listener initialized"
echo "   5. Create transaction from another browser"
echo "   6. Check console for: 🆕 [REALTIME] Transaction Created"
echo "   7. Verify grid auto-refresh"
echo ""
echo "📚 Documentation:"
echo "   - REALTIME_TROUBLESHOOTING.md"
echo "   - REALTIME_DELETE_IMPLEMENTATION.md"
echo "   - IMPLEMENTATION_SUMMARY.md"
echo ""
