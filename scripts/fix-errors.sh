#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# Script untuk memperbaiki error WebSocket, Permission, dan 500
# ═══════════════════════════════════════════════════════════════

echo "🔧 Memulai perbaikan error..."

# ─── 1. Fix Permission untuk Log Files ─────────────────────────
echo ""
echo "📝 [1/4] Memperbaiki permission log files..."

# Pastikan direktori log ada
mkdir -p storage/logs

# Set permission yang benar untuk storage
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Set ownership (sesuaikan dengan user web server Anda)
# Untuk Docker: www-data
# Untuk local development: current user
if [ -n "$SUDO_USER" ]; then
    chown -R $SUDO_USER:$SUDO_USER storage bootstrap/cache
elif command -v docker &> /dev/null && docker ps | grep -q "php"; then
    # Jika running di Docker
    docker exec $(docker ps -q -f name=php) chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
else
    # Local development
    chown -R $(whoami):$(whoami) storage bootstrap/cache
fi

echo "✅ Permission log files diperbaiki"

# ─── 2. Clear Cache ─────────────────────────────────────────────
echo ""
echo "🗑️ [2/4] Membersihkan cache..."

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "✅ Cache dibersihkan"

# ─── 3. Restart Queue Workers ──────────────────────────────────
echo ""
echo "🔄 [3/4] Restart queue workers..."

php artisan queue:restart

echo "✅ Queue workers direstart"

# ─── 4. Informasi WebSocket Fix ────────────────────────────────
echo ""
echo "🌐 [4/4] Informasi WebSocket Fix..."
echo ""
echo "⚠️  PERHATIAN: Ditemukan typo di .env:"
echo "    VITE_REVERB_HOST=graphs-deborah-wide-gray.trycloudflare.com.com"
echo "                                                              ^^^^"
echo "    Ada '.com' ganda!"
echo ""
echo "📋 Langkah manual yang perlu dilakukan:"
echo ""
echo "1. Edit file .env, ubah baris:"
echo "   VITE_REVERB_HOST=graphs-deborah-wide-gray.trycloudflare.com.com"
echo "   Menjadi:"
echo "   VITE_REVERB_HOST=graphs-deborah-wide-gray.trycloudflare.com"
echo ""
echo "2. Rebuild frontend assets:"
echo "   npm run build"
echo ""
echo "3. Restart Reverb server:"
echo "   php artisan reverb:restart"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "✅ Script selesai!"
echo ""
echo "📌 Ringkasan masalah yang ditemukan:"
echo ""
echo "1. ❌ WebSocket Failed:"
echo "   - URL salah: '.com.com' (ganda)"
echo "   - Fix: Edit .env → VITE_REVERB_HOST"
echo ""
echo "2. ❌ POST /transactions/17/override 500:"
echo "   - Permission denied saat menulis log"
echo "   - Fix: ✅ Sudah diperbaiki (chmod 775)"
echo ""
echo "3. ❌ Log Permission Denied:"
echo "   - /var/www/storage/logs/ai-autofill-2026-05-07.log"
echo "   - Fix: ✅ Sudah diperbaiki (chmod 775)"
echo ""
echo "═══════════════════════════════════════════════════════════════"
