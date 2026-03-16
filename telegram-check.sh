#!/bin/bash

# ═══════════════════════════════════════════════════════════════
#  TELEGRAM BOT DIAGNOSTIC SCRIPT
#  Run: bash telegram-check.sh
# ═══════════════════════════════════════════════════════════════

echo "════════════════════════════════════════════════════════════"
echo "  TELEGRAM BOT DIAGNOSTIC CHECK"
echo "════════════════════════════════════════════════════════════"
echo ""

# ─── Check 1: Bot Token Exists ───────────────────────────────
echo "📍 Check 1: Bot Token di .env"
if grep -q "TELEGRAM_BOT_TOKEN=" .env; then
    TOKEN=$(grep "TELEGRAM_BOT_TOKEN=" .env | cut -d '=' -f2)
    if [ -z "$TOKEN" ]; then
        echo "❌ TELEGRAM_BOT_TOKEN ada tapi KOSONG"
        echo "   Fix: Tambahkan token dari @BotFather"
    else
        echo "✅ TELEGRAM_BOT_TOKEN ada: ${TOKEN:0:20}..."
        
        # Test token valid
        echo ""
        echo "📍 Testing bot token validity..."
        RESPONSE=$(curl -s "https://api.telegram.org/bot${TOKEN}/getMe")
        
        if echo "$RESPONSE" | grep -q '"ok":true'; then
            BOT_NAME=$(echo "$RESPONSE" | grep -o '"first_name":"[^"]*"' | cut -d '"' -f4)
            BOT_USERNAME=$(echo "$RESPONSE" | grep -o '"username":"[^"]*"' | cut -d '"' -f4)
            echo "✅ Bot Token VALID"
            echo "   Bot Name: $BOT_NAME"
            echo "   Username: @$BOT_USERNAME"
        else
            echo "❌ Bot Token INVALID atau bot tidak aktif"
            echo "   Response: $RESPONSE"
            echo "   Fix: Minta token baru dari @BotFather"
        fi
    fi
else
    echo "❌ TELEGRAM_BOT_TOKEN TIDAK ADA di .env"
    echo "   Fix: Tambahkan: TELEGRAM_BOT_TOKEN=your_token_here"
fi

echo ""
echo "─────────────────────────────────────────────────────────"

# ─── Check 2: APP_URL (harus HTTPS) ──────────────────────────
echo ""
echo "📍 Check 2: APP_URL (harus HTTPS untuk webhook)"
if grep -q "APP_URL=" .env; then
    APP_URL=$(grep "APP_URL=" .env | cut -d '=' -f2)
    if [ -z "$APP_URL" ]; then
        echo "❌ APP_URL ada tapi KOSONG"
    else
        echo "   APP_URL: $APP_URL"
        
        if [[ "$APP_URL" == https://* ]]; then
            echo "✅ APP_URL menggunakan HTTPS"
        else
            echo "❌ APP_URL BUKAN HTTPS (Telegram webhook wajib HTTPS!)"
            echo "   Fix: Gunakan Cloudflare Tunnel atau ngrok"
        fi
    fi
else
    echo "❌ APP_URL TIDAK ADA di .env"
fi

echo ""
echo "─────────────────────────────────────────────────────────"

# ─── Check 3: Cloudflare Tunnel Running ──────────────────────
echo ""
echo "📍 Check 3: Cloudflare Tunnel"
if pgrep -f cloudflared > /dev/null; then
    echo "✅ Cloudflare Tunnel sedang berjalan"
else
    echo "❌ Cloudflare Tunnel TIDAK berjalan"
    echo "   Fix: Jalankan: cloudflared tunnel --url http://localhost:8000"
fi

echo ""
echo "─────────────────────────────────────────────────────────"

# ─── Check 4: Route Exists ───────────────────────────────────
echo ""
echo "📍 Check 4: Route /api/telegram/webhook"
if php artisan route:list 2>/dev/null | grep -q "telegram/webhook"; then
    echo "✅ Route telegram webhook ADA"
else
    echo "❌ Route telegram webhook TIDAK ADA"
    echo "   Fix: Tambahkan di routes/api.php:"
    echo "   Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);"
fi

echo ""
echo "─────────────────────────────────────────────────────────"

# ─── Check 5: Controller Exists ──────────────────────────────
echo ""
echo "📍 Check 5: TelegramWebhookController"
if [ -f "app/Http/Controllers/Api/TelegramWebhookController.php" ]; then
    echo "✅ TelegramWebhookController.php ADA"
else
    echo "❌ TelegramWebhookController.php TIDAK ADA"
    echo "   Fix: Copy dari file output yang sudah dibuat"
fi

echo ""
echo "─────────────────────────────────────────────────────────"

# ─── Check 6: TelegramBotService Exists ──────────────────────
echo ""
echo "📍 Check 6: TelegramBotService"
if [ -f "app/Services/Telegram/TelegramBotService.php" ]; then
    echo "✅ TelegramBotService.php ADA"
else
    echo "❌ TelegramBotService.php TIDAK ADA"
    echo "   Fix: Copy dari file output yang sudah dibuat"
fi

echo ""
echo "─────────────────────────────────────────────────────────"

# ─── Check 7: Webhook Status ─────────────────────────────────
echo ""
echo "📍 Check 7: Webhook Registration Status"
if grep -q "TELEGRAM_BOT_TOKEN=" .env; then
    TOKEN=$(grep "TELEGRAM_BOT_TOKEN=" .env | cut -d '=' -f2)
    if [ ! -z "$TOKEN" ]; then
        echo "   Checking webhook info..."
        WEBHOOK_INFO=$(curl -s "https://api.telegram.org/bot${TOKEN}/getWebhookInfo")
        
        WEBHOOK_URL=$(echo "$WEBHOOK_INFO" | grep -o '"url":"[^"]*"' | cut -d '"' -f4)
        
        if [ -z "$WEBHOOK_URL" ]; then
            echo "❌ Webhook BELUM TERDAFTAR"
            echo "   Fix: Jalankan: php artisan telegram:setup-webhook"
        else
            echo "✅ Webhook SUDAH TERDAFTAR"
            echo "   URL: $WEBHOOK_URL"
            
            # Check for errors
            if echo "$WEBHOOK_INFO" | grep -q '"last_error_message"'; then
                ERROR_MSG=$(echo "$WEBHOOK_INFO" | grep -o '"last_error_message":"[^"]*"' | cut -d '"' -f4)
                echo "⚠️  Last Error: $ERROR_MSG"
            fi
        fi
    fi
fi

echo ""
echo "════════════════════════════════════════════════════════════"
echo "  DIAGNOSTIC COMPLETE"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "📌 NEXT STEPS:"
echo ""
echo "Jika ada ❌, perbaiki issue tersebut lalu jalankan:"
echo "  php artisan telegram:setup-webhook"
echo ""
echo "Lalu test dengan mengetik /start di Telegram bot Anda."
echo ""
