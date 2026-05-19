#!/bin/bash

# ═══════════════════════════════════════════════════════════════
#  Fix Stuck OCR Transactions
#  Usage: ./scripts/fix-stuck-ocr.sh [--auto]
# ═══════════════════════════════════════════════════════════════

AUTO_MODE=false

if [ "$1" == "--auto" ]; then
    AUTO_MODE=true
    echo "🤖 Running in AUTO mode (no confirmation)"
else
    echo "🔧 Running in INTERACTIVE mode"
fi

echo ""
echo "🔍 Finding stuck OCR transactions..."
echo ""

# Get count first
STUCK_COUNT=$(php artisan tinker --execute="
echo \App\Models\Transaction::whereIn('ai_status', ['queued', 'processing'])
    ->where('updated_at', '<=', now()->subMinutes(5))
    ->count();
" | tail -n 1)

if [ "$STUCK_COUNT" -eq "0" ]; then
    echo "✅ No stuck transactions found!"
    exit 0
fi

echo "📊 Found $STUCK_COUNT stuck transaction(s)"
echo ""

# Show details
php artisan ocr:reset-stuck

echo ""

if [ "$AUTO_MODE" = false ]; then
    read -p "❓ Do you want to reset these transactions to 'error' status? (y/N): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Cancelled by user"
        exit 1
    fi
fi

echo ""
echo "🔧 Resetting stuck transactions..."
echo ""

# Execute fix
php artisan ocr:reset-stuck --fix

echo ""
echo "✅ Fix complete!"
echo ""
echo "💡 Users can now fill the forms manually from the transaction page."
echo "💡 To bypass with existing data: php artisan ocr:reset-stuck --id=<ID> --complete"
