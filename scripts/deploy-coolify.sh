#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Deploy ke Coolify - WHUSNET Admin Payment
# ═══════════════════════════════════════════════════════════════════

set -e

echo "🚀 Starting Coolify Deployment Process..."
echo ""

# ─── 1. Verifikasi Environment Variables ───────────────────────────
echo "📋 Step 1: Verifying environment variables..."

if ! grep -q "^REVERB_APP_KEY=" .env.production; then
    echo "❌ ERROR: REVERB_APP_KEY not found in .env.production"
    exit 1
fi

REVERB_KEY=$(grep "^REVERB_APP_KEY=" .env.production | cut -d'=' -f2)
if [ -z "$REVERB_KEY" ]; then
    echo "❌ ERROR: REVERB_APP_KEY is empty in .env.production"
    exit 1
fi

echo "✅ REVERB_APP_KEY: $REVERB_KEY"
echo ""

# ─── 2. Verifikasi Docker Files ────────────────────────────────────
echo "📋 Step 2: Verifying Docker configuration..."

if ! grep -q "ARG REVERB_APP_KEY" Dockerfile; then
    echo "❌ ERROR: ARG REVERB_APP_KEY not found in Dockerfile"
    exit 1
fi

if ! grep -q "REVERB_APP_KEY=\${REVERB_APP_KEY}" docker-compose.yaml; then
    echo "❌ ERROR: REVERB_APP_KEY build arg not found in docker-compose.yaml"
    exit 1
fi

echo "✅ Dockerfile configuration OK"
echo "✅ docker-compose.yaml configuration OK"
echo ""

# ─── 3. Check Git Status ───────────────────────────────────────────
echo "📋 Step 3: Checking git status..."

if [[ -n $(git status -s) ]]; then
    echo "⚠️  WARNING: You have uncommitted changes:"
    git status -s
    echo ""
    read -p "Do you want to commit these changes? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "Enter commit message: " commit_msg
        git add .
        git commit -m "$commit_msg"
        echo "✅ Changes committed"
    else
        echo "⚠️  Proceeding without committing changes"
    fi
else
    echo "✅ No uncommitted changes"
fi
echo ""

# ─── 4. Push to Repository ─────────────────────────────────────────
echo "📋 Step 4: Pushing to repository..."

CURRENT_BRANCH=$(git branch --show-current)
echo "Current branch: $CURRENT_BRANCH"

read -p "Push to origin/$CURRENT_BRANCH? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    git push origin "$CURRENT_BRANCH"
    echo "✅ Pushed to origin/$CURRENT_BRANCH"
else
    echo "⚠️  Skipping git push"
fi
echo ""

# ─── 5. Deployment Instructions ────────────────────────────────────
echo "═══════════════════════════════════════════════════════════════"
echo "📝 NEXT STEPS - Manual Actions Required:"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "1. Open Coolify Dashboard:"
echo "   https://coolify.whusnet.com (or your Coolify URL)"
echo ""
echo "2. Navigate to your application:"
echo "   → Applications → WHUSNET Admin Payment"
echo ""
echo "3. Click 'Redeploy' button"
echo ""
echo "4. Monitor deployment logs for:"
echo "   ✅ No 'REVERB_APP_KEY not set' warnings"
echo "   ✅ Build completes successfully"
echo "   ✅ All containers start (app, nginx, reverb, horizon, etc.)"
echo ""
echo "5. Verify deployment:"
echo "   → Visit: https://admin-payment.whusnet.com"
echo "   → Check: WebSocket connection (browser console)"
echo "   → Test: Log Viewer at /log-viewer"
echo "   → Test: Pulse dashboard at /pulse"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "🔍 Troubleshooting Commands:"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "# Check container status"
echo "docker ps | grep whusnet"
echo ""
echo "# View logs"
echo "docker logs whusnet-app"
echo "docker logs whusnet-reverb"
echo ""
echo "# Test WebSocket"
echo "curl -I https://admin-payment.whusnet.com"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "✅ Pre-deployment checks completed!"
echo "═══════════════════════════════════════════════════════════════"
