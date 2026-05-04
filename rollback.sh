#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Rollback Script - WHUSNET Admin Payment
#  Rollback to previous Docker image version
# ═══════════════════════════════════════════════════════════════════

set -e

DEPLOY_PATH="/var/www/admin-payment"
REGISTRY="ghcr.io"
IMAGE_NAME="${REGISTRY}/$(basename $(git config --get remote.origin.url) .git)"

cd "${DEPLOY_PATH}"

echo "⏪ Starting rollback process..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Load environment variables
set -a
source .env
set +a

# Get current running image
CURRENT_IMAGE=$(docker inspect whusnet-app --format='{{.Config.Image}}' 2>/dev/null || echo "none")
echo "📦 Current image: ${CURRENT_IMAGE}"

# Get list of available images (excluding latest)
echo ""
echo "Available versions:"
docker images "${IMAGE_NAME}" --format "table {{.Tag}}\t{{.CreatedAt}}" | grep -v latest | head -n 5

# Get previous image (second in list, first is current)
PREVIOUS_TAG=$(docker images "${IMAGE_NAME}" --format "{{.Tag}}" | grep -v latest | head -n 2 | tail -n 1)

if [ -z "${PREVIOUS_TAG}" ]; then
  echo ""
  echo "❌ No previous image found for rollback"
  echo "Available images:"
  docker images "${IMAGE_NAME}"
  exit 1
fi

echo ""
echo "🎯 Target rollback version: ${PREVIOUS_TAG}"
echo ""
read -p "Continue with rollback? (yes/no): " -r
echo

if [[ ! $REPLY =~ ^[Yy]es$ ]]; then
  echo "❌ Rollback cancelled"
  exit 1
fi

# Set version to rollback to
export APP_VERSION="${PREVIOUS_TAG}"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 Pulling previous image..."
docker-compose pull app horizon reverb scheduler

echo ""
echo "🔄 Restarting services with previous version..."
docker-compose up -d --force-recreate app horizon reverb scheduler nginx

echo ""
echo "⏳ Waiting for services to be ready..."
sleep 15

echo ""
echo "🏥 Running health checks..."
if docker-compose exec -T app php artisan list > /dev/null 2>&1; then
  echo "✅ Health check passed"
else
  echo "❌ Health check failed"
  echo ""
  echo "📋 Recent logs:"
  docker-compose logs --tail 50 app
  exit 1
fi

echo ""
echo "✅ Rollback completed successfully!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Current status:"
docker-compose ps

echo ""
echo "📦 Current image:"
docker inspect whusnet-app --format='{{.Config.Image}}'
