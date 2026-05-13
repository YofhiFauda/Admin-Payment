#!/bin/sh
# ═══════════════════════════════════════════════════════════════════
#  docker/entrypoint.sh — WHUSNET Admin Payment
#  Container entrypoint: routes logic berdasarkan CONTAINER_ROLE
# ═══════════════════════════════════════════════════════════════════

set -e

ROLE=${CONTAINER_ROLE:-app}

echo "🚀 Container starting as role: $ROLE"

# ─────────────────────────────────────────
#  APP — PHP-FPM + First-run setup
# ─────────────────────────────────────────
if [ "$ROLE" = "app" ]; then

    echo "⏳ Waiting for services..."
    sleep 2

    # ⚠️ SYNC PUBLIC DIRECTORY KE SHARED VOLUME
    # Memastikan nginx mendapatkan file build terbaru yang mungkin ter-mask oleh volume lama
    if [ -d "/var/www/public_source" ]; then
        echo "📁 Syncing public files (including Vite build) to shared volume..."
        cp -a /var/www/public_source/. /var/www/public/
    fi

    # Storage link (idempotent, --force aman jika sudah ada)
    echo "🔗 Creating storage link..."
    php artisan storage:link --force 2>/dev/null || echo "  storage:link skipped"

    # Jalankan migrasi (--force wajib di production)
    echo "🗄️  Running migrations..."
    php artisan migrate --force || {
        echo "❌ WARNING: Migration failed. Please check your database connection / DB_HOST."
        echo "   (Continuing boot process to allow debugging...)"
    }

    # Bersihkan stale cache dari volume sebelumnya (penting untuk fresh deploy)
    echo "🧹 Clearing stale bootstrap cache..."
    rm -f /var/www/bootstrap/cache/packages.php \
          /var/www/bootstrap/cache/services.php \
          /var/www/bootstrap/cache/config.php \
          /var/www/bootstrap/cache/routes*.php \
          /var/www/bootstrap/cache/events.php \
          /var/www/bootstrap/cache/compiled.php

    # Discover packages (wajib karena Dockerfile pakai --no-scripts saat dump-autoload)
    echo "🔍 Discovering packages..."
    php artisan package:discover --ansi 2>/dev/null || echo "  package:discover skipped"

    # Cache semua config — non-fatal agar php-fpm selalu start meskipun ada error
    echo "⚡ Caching config, routes, views, events..."
    php artisan config:cache || echo "  ⚠️ config:cache failed (app will run without cache)"
    php artisan route:cache  || echo "  ⚠️ route:cache failed (non-fatal)"
    php artisan view:cache   || echo "  ⚠️ view:cache failed (non-fatal)"
    php artisan event:cache  || echo "  ⚠️ event:cache failed (non-fatal)"

    # Publish assets untuk Pulse & Log-Viewer dashboard
    echo "📦 Publishing Pulse & Log-Viewer assets..."
    php artisan vendor:publish --tag=pulse-assets --force 2>/dev/null || echo "  pulse assets skipped"
    php artisan vendor:publish --tag=log-viewer-assets --force 2>/dev/null || echo "  log-viewer assets skipped"

    echo "✅ App setup complete. Starting PHP-FPM..."
    exec php-fpm

# ─────────────────────────────────────────
#  HORIZON — Queue Worker Manager
# ─────────────────────────────────────────
elif [ "$ROLE" = "horizon" ]; then
    echo "🔄 Starting Laravel Horizon..."
    exec php artisan horizon

# ─────────────────────────────────────────
#  REVERB — WebSocket Server
# ─────────────────────────────────────────
elif [ "$ROLE" = "reverb" ]; then
    echo "📡 Starting Laravel Reverb on 0.0.0.0:8081..."
    exec php artisan reverb:start --host=0.0.0.0 --port=8081

# ─────────────────────────────────────────
#  SCHEDULER — Laravel Cron
# ─────────────────────────────────────────
elif [ "$ROLE" = "scheduler" ]; then
    echo "⏰ Starting Laravel Scheduler loop..."
    while true; do
        php artisan schedule:run --verbose --no-interaction
        sleep 60
    done

else
    echo "❌ ERROR: Unknown CONTAINER_ROLE '$ROLE'"
    echo "   Valid values: app, horizon, reverb, scheduler"
    exit 1
fi
