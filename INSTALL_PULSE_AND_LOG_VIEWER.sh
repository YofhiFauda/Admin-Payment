#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
# Install Laravel Pulse & Log Viewer
# Run this script on your production/staging server (Linux)
# ═══════════════════════════════════════════════════════════════════

set -e

echo "════════════════════════════════════════════════════════════════"
echo "Installing Laravel Pulse & Log Viewer"
echo "════════════════════════════════════════════════════════════════"
echo ""

# ─── 1. Install Laravel Pulse ──────────────────────────────────────
echo "→ Installing Laravel Pulse..."
composer require laravel/pulse

echo "→ Publishing Pulse configuration..."
php artisan pulse:install

echo "→ Running migrations..."
php artisan migrate

echo "✓ Laravel Pulse installed successfully!"
echo ""

# ─── 2. Install Laravel Log Viewer ─────────────────────────────────
echo "→ Installing Laravel Log Viewer..."
composer require opcodesio/log-viewer

echo "→ Publishing Log Viewer configuration..."
php artisan log-viewer:publish

echo "✓ Laravel Log Viewer installed successfully!"
echo ""

# ─── 3. Clear caches ───────────────────────────────────────────────
echo "→ Clearing caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✓ Caches cleared!"
echo ""

# ─── 4. Set permissions ────────────────────────────────────────────
echo "→ Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✓ Permissions set!"
echo ""

echo "════════════════════════════════════════════════════════════════"
echo "Installation Complete!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Access Points:"
echo "  📊 Log Viewer: https://yourdomain.com/log-viewer"
echo "  📈 Pulse:      https://yourdomain.com/pulse"
echo ""
echo "Next Steps:"
echo "  1. Configure authentication in config/log-viewer.php"
echo "  2. Configure authentication in config/pulse.php"
echo "  3. Update .env with PULSE_ENABLED=true"
echo "  4. Test access to both dashboards"
echo ""
echo "Documentation:"
echo "  - PULSE_LOG_VIEWER_SETUP.md"
echo "  - LOGGING_COMPLETE_SOLUTION.md"
echo ""
