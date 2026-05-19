# ═══════════════════════════════════════════════════════════════
#  Fix Stuck OCR Transactions (PowerShell)
#  Usage: .\scripts\fix-stuck-ocr.ps1 [-Auto]
# ═══════════════════════════════════════════════════════════════

param(
    [switch]$Auto
)

if ($Auto) {
    Write-Host "🤖 Running in AUTO mode (no confirmation)" -ForegroundColor Yellow
} else {
    Write-Host "🔧 Running in INTERACTIVE mode" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "🔍 Finding stuck OCR transactions..." -ForegroundColor Cyan
Write-Host ""

# Get count first
$stuckCountOutput = php artisan tinker --execute="
echo \App\Models\Transaction::whereIn('ai_status', ['queued', 'processing'])
    ->where('updated_at', '<=', now()->subMinutes(5))
    ->count();
"

$stuckCount = ($stuckCountOutput | Select-Object -Last 1).Trim()

if ($stuckCount -eq "0") {
    Write-Host "✅ No stuck transactions found!" -ForegroundColor Green
    exit 0
}

Write-Host "📊 Found $stuckCount stuck transaction(s)" -ForegroundColor Yellow
Write-Host ""

# Show details
php artisan ocr:reset-stuck

Write-Host ""

if (-not $Auto) {
    $confirmation = Read-Host "❓ Do you want to reset these transactions to 'error' status? (y/N)"
    if ($confirmation -ne 'y' -and $confirmation -ne 'Y') {
        Write-Host "❌ Cancelled by user" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "🔧 Resetting stuck transactions..." -ForegroundColor Cyan
Write-Host ""

# Execute fix
php artisan ocr:reset-stuck --fix

Write-Host ""
Write-Host "✅ Fix complete!" -ForegroundColor Green
Write-Host ""
Write-Host "💡 Users can now fill the forms manually from the transaction page." -ForegroundColor Cyan
Write-Host "💡 To bypass with existing data: php artisan ocr:reset-stuck --id=<ID> --complete" -ForegroundColor Cyan
