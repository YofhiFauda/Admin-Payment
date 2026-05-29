#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  Create Testing Database
#  Script untuk membuat database testing di MySQL Docker
# ═══════════════════════════════════════════════════════════════════

echo "🔧 Creating testing database..."

# Ambil credentials dari .env secara tepat (menghindari duplikasi match seperti PULSE_DB_HOST)
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2)
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)

# Nama database testing
DB_TEST="admin_payment_testing"

echo "📦 Database Host: $DB_HOST"
echo "📦 Database Port: $DB_PORT"
echo "📦 Creating database: $DB_TEST"

# Coba buat database testing menggunakan beberapa alternatif cara:
success=false

# Cara 1: Menggunakan mysql client lokal (jika terinstall)
if command -v mysql >/dev/null 2>&1; then
    echo "🔄 Mencoba via local mysql client..."
    if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_TEST CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >/dev/null 2>&1; then
        success=true
    fi
fi

# Cara 2: Menggunakan docker exec ke database container (jika docker ada di host)
if [ "$success" = false ] && command -v docker >/dev/null 2>&1; then
    echo "🔄 mysql client lokal tidak ditemukan atau gagal. Mencoba via Docker container '$DB_HOST'..."
    if docker exec -i "$DB_HOST" mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_TEST CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >/dev/null 2>&1; then
        success=true
    fi
fi

# Cara 3: Menggunakan docker exec ke app container (jika ada php di sana)
if [ "$success" = false ] && command -v docker >/dev/null 2>&1; then
    echo "🔄 Mencoba via PHP PDO di dalam container 'admin-payment-app-1'..."
    if docker exec -i admin-payment-app-1 php -r "
        try {
            \$pdo = new PDO('mysql:host=$DB_HOST;port=$DB_PORT', '$DB_USERNAME', '$DB_PASSWORD');
            \$pdo->exec('CREATE DATABASE IF NOT EXISTS $DB_TEST CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " >/dev/null 2>&1; then
        success=true
    fi
fi

if [ "$success" = true ]; then
    echo "✅ Database testing berhasil dibuat!"
    echo ""
    echo "📝 Sekarang jalankan migration (di dalam container app):"
    echo "   docker exec -it admin-payment-app-1 php artisan migrate --env=testing"
    echo ""
    echo "🧪 Kemudian jalankan tests (di dalam container app):"
    echo "   docker exec -it admin-payment-app-1 php artisan test"
else
    echo "❌ Gagal membuat database testing! Silakan pastikan container database '$DB_HOST' sedang berjalan."
    exit 1
fi
