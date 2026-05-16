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

    # ⚠️ SYNC PUBLIC DIRECTORY KE SHARED VOLUME
    # Memastikan nginx mendapatkan file build terbaru yang mungkin ter-mask oleh volume lama
    if [ -d "/var/www/public_source" ]; then
        echo "📁 Syncing public files (including Vite build) to shared volume..."
        cp -a /var/www/public_source/. /var/www/public/
    fi

    # Storage link (idempotent, --force aman jika sudah ada)
    echo "🔗 Creating storage link..."
    php artisan storage:link --force 2>/dev/null || echo "  storage:link skipped"

    # ─────────────────────────────────────────
    # Tunggu DB benar-benar ready dengan retry aktif.
    # Pakai PHP PDO langsung — TIDAK bootstrap full Laravel,
    # TIDAK butuh .env, langsung baca env var container.
    # Jauh lebih ringan dari 'artisan tinker' atau 'artisan db:show'.
    # ─────────────────────────────────────────
    echo "⏳ Waiting for database to be ready..."
    MAX_TRIES=12
    TRIES=0
    DB_DSN="mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-admin_payment}"
    until php -r "
        try {
            new PDO('${DB_DSN}', getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null || [ "$TRIES" -ge "$MAX_TRIES" ]; do
        TRIES=$((TRIES + 1))
        echo "  Database not ready (attempt $TRIES/$MAX_TRIES), retrying in 5s..."
        sleep 5
    done

    if [ "$TRIES" -ge "$MAX_TRIES" ]; then
        echo "⚠️  WARNING: Database did not respond after $MAX_TRIES attempts. Proceeding anyway..."
    fi

    # Jalankan migrasi (--force wajib di production)
    echo "🗄️  Running migrations..."
    php artisan migrate --force || {
        echo "❌ WARNING: Migration failed. Please check your database connection / DB_HOST."
        echo "   (Continuing boot process to allow debugging...)"
    }

    # ─────────────────────────────────────────
    # Runtime config cache — WAJIB di sini, BUKAN di Dockerfile.
    # config:cache di build stage akan bake nilai .env.example secara permanen,
    # sehingga runtime ENV dari docker-compose diabaikan oleh Laravel.
    # ─────────────────────────────────────────
    echo "⚡ Building runtime config cache (using actual container ENV)..."
    php artisan config:clear 2>/dev/null || true
    php artisan config:cache  || echo "  ⚠️ config:cache failed (app will use uncached config)"
    php artisan route:clear  2>/dev/null || true
    php artisan route:cache   || echo "  ⚠️ route:cache failed (non-fatal)"

    # ─────────────────────────────────────────
    # Wajib clear view cache saat startup.
    # storage/framework/views/ ada di persistent volume (storage_data),
    # sehingga compiled Blade lama dari deploy sebelumnya bisa survive restart.
    # Ini menyebabkan error seperti "Undefined array key 'groups'" saat
    # vendor package update struktur view-nya (misal: laravel/pulse).
    # view:cache di Dockerfile build stage tidak efektif karena di-override volume.
    # ─────────────────────────────────────────
    echo "🗂️  Clearing stale compiled views from persistent storage volume..."
    php artisan view:clear 2>/dev/null || true

    echo "✅ App setup complete. Starting PHP-FPM..."
    exec php-fpm

# ─────────────────────────────────────────
#  HORIZON — Queue Worker Manager
#  horizon:terminate dulu agar worker lama tidak pakai code lama
# ─────────────────────────────────────────
elif [ "$ROLE" = "horizon" ]; then
    echo "🔄 Terminating any stale Horizon workers before start..."
    php artisan horizon:terminate || true
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
#  schedule:work lebih stabil dari manual while-loop
#  Healthcheck cukup via pgrep karena ini adalah long-running process
# ─────────────────────────────────────────
elif [ "$ROLE" = "scheduler" ]; then
    echo "⏰ Starting Laravel Scheduler (schedule:work)..."
    exec php artisan schedule:work

# ─────────────────────────────────────────
#  PULSE — Performance Monitoring Worker
#  --timeout=0 mencegah PHP timeout (default 60s) membunuh proses
#  Loop restart memastikan pulse:work bangkit kembali jika crash
# ─────────────────────────────────────────
# ─────────────────────────────────────────
#  PULSE — Performance Monitoring Worker
# ─────────────────────────────────────────
elif [ "$ROLE" = "pulse" ]; then
    echo "📊 Starting Laravel Pulse worker (timeout=0)..."
    
    # Matikan strict mode sementara agar error tidak membunuh container
    set +e 
    
    while true; do
        php artisan pulse:work --timeout=0
        EXIT_CODE=$?
        if [ $EXIT_CODE -ne 0 ]; then
            echo "⚠️  pulse:work exited with code $EXIT_CODE. Restarting in 3s..."
            sleep 3
        else
            echo "ℹ️  pulse:work stopped cleanly. Restarting in 1s..."
            sleep 1
        fi
    done

else
    echo "❌ ERROR: Unknown CONTAINER_ROLE '$ROLE'"
    echo "   Valid values: app, horizon, reverb, scheduler, pulse"
    exit 1
fi
