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

    # Tunggu DB & Redis siap (depends_on sudah handle ini, tapi double-check)
    sleep 2

    # Storage link (idempotent, --force aman jika sudah ada)
    echo "🔗 Creating storage link..."
    php artisan storage:link --force 2>/dev/null || echo "  storage:link skipped"

    # Jalankan migrasi (--force wajib di production)
    echo "🗄️  Running migrations..."
    php artisan migrate --force

    # Cache semua config (setelah migrate agar DB sudah ready)
    echo "⚡ Caching config, routes, views, events..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

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
