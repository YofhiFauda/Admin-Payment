#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Verify Pulse Setup - WHUSNET Admin Payment
#  Run this script on the server to verify Pulse configuration
# ═══════════════════════════════════════════════════════════════════

set -e

echo "🔍 Verifying Pulse Setup..."
echo ""

# ─── 1. Check Pulse Container ──────────────────────────────────────
echo "📋 Step 1: Checking Pulse container..."

if docker ps | grep -q "whusnet-pulse"; then
    echo "✅ Pulse container is running"
    docker ps | grep whusnet-pulse
else
    echo "❌ ERROR: Pulse container is NOT running!"
    echo "   Run: docker-compose restart pulse"
    exit 1
fi
echo ""

# ─── 2. Check Pulse Worker Logs ────────────────────────────────────
echo "📋 Step 2: Checking Pulse worker logs..."

PULSE_LOGS=$(docker logs whusnet-pulse --tail 20 2>&1)

if echo "$PULSE_LOGS" | grep -q "Processing\|Ingesting\|pulse:work"; then
    echo "✅ Pulse worker is processing entries"
    echo "   Last 5 lines:"
    docker logs whusnet-pulse --tail 5
else
    echo "⚠️  WARNING: No processing logs found"
    echo "   Last 10 lines:"
    docker logs whusnet-pulse --tail 10
fi
echo ""

# ─── 3. Check Redis Connection ─────────────────────────────────────
echo "📋 Step 3: Checking Pulse Redis connection..."

REDIS_TEST=$(docker exec whusnet-app php artisan tinker --execute="echo Redis::connection('pulse')->ping();" 2>&1)

if echo "$REDIS_TEST" | grep -q "PONG"; then
    echo "✅ Pulse Redis connection OK"
else
    echo "❌ ERROR: Pulse Redis connection FAILED!"
    echo "   Output: $REDIS_TEST"
    exit 1
fi
echo ""

# ─── 4. Check Database Connection ──────────────────────────────────
echo "📋 Step 4: Checking Pulse database connection..."

DB_TEST=$(docker exec whusnet-app php artisan tinker --execute="echo DB::connection('pulse')->getPdo() ? 'OK' : 'FAIL';" 2>&1)

if echo "$DB_TEST" | grep -q "OK"; then
    echo "✅ Pulse database connection OK"
else
    echo "❌ ERROR: Pulse database connection FAILED!"
    echo "   Output: $DB_TEST"
    exit 1
fi
echo ""

# ─── 5. Check Pulse Tables ─────────────────────────────────────────
echo "📋 Step 5: Checking Pulse database tables..."

TABLE_COUNT=$(docker exec whusnet-app php artisan tinker --execute="echo DB::connection('pulse')->table('pulse_entries')->count();" 2>&1 | grep -oE '[0-9]+' | head -1)

if [ -n "$TABLE_COUNT" ] && [ "$TABLE_COUNT" -ge 0 ]; then
    echo "✅ Pulse tables exist (entries: $TABLE_COUNT)"
else
    echo "❌ ERROR: Pulse tables not found!"
    echo "   Run: docker exec whusnet-app php artisan migrate --database=pulse"
    exit 1
fi
echo ""

# ─── 6. Check Session Configuration ────────────────────────────────
echo "📋 Step 6: Checking session configuration..."

SESSION_DOMAIN=$(docker exec whusnet-app php artisan tinker --execute="echo config('session.domain') ?? 'null';" 2>&1 | tail -1)

if [ "$SESSION_DOMAIN" = "null" ] || [ -z "$SESSION_DOMAIN" ]; then
    echo "✅ SESSION_DOMAIN is null (correct)"
else
    echo "⚠️  WARNING: SESSION_DOMAIN = $SESSION_DOMAIN"
    echo "   Recommended: SESSION_DOMAIN=null"
fi
echo ""

# ─── 7. Check User Authorization ───────────────────────────────────
echo "📋 Step 7: Checking user authorization..."

echo "   Note: You need to be logged in to test this"
echo "   Allowed roles: owner, atasan, admin"
echo ""

# ─── 8. Test Pulse Endpoint ────────────────────────────────────────
echo "📋 Step 8: Testing Pulse endpoint..."

PULSE_URL="https://admin-payment.whusnet.com/pulse"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$PULSE_URL" || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo "✅ Pulse endpoint accessible (HTTP $HTTP_CODE)"
elif [ "$HTTP_CODE" = "401" ] || [ "$HTTP_CODE" = "403" ]; then
    echo "⚠️  Pulse endpoint requires authentication (HTTP $HTTP_CODE)"
    echo "   This is normal - login first"
else
    echo "❌ ERROR: Pulse endpoint returned HTTP $HTTP_CODE"
fi
echo ""

# ─── Summary ────────────────────────────────────────────────────────
echo "═══════════════════════════════════════════════════════════════"
echo "📊 Summary"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "✅ Pulse container: Running"
echo "✅ Redis connection: OK"
echo "✅ Database connection: OK"
echo "✅ Database tables: Exist ($TABLE_COUNT entries)"
echo "✅ Session domain: $SESSION_DOMAIN"
echo ""
echo "🎯 Next Steps:"
echo "1. Login to: https://admin-payment.whusnet.com"
echo "2. Access: https://admin-payment.whusnet.com/pulse"
echo "3. Verify: No 419 error"
echo "4. Check: Metrics displayed"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "✅ Pulse setup verification completed!"
echo "═══════════════════════════════════════════════════════════════"
