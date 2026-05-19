# ═══════════════════════════════════════════════════════════════
#  Check Stuck OCR Transactions (PowerShell)
#  Usage: .\scripts\check-stuck-ocr.ps1
# ═══════════════════════════════════════════════════════════════

Write-Host "🔍 Checking for stuck OCR transactions..." -ForegroundColor Cyan
Write-Host ""

# Check stuck transactions
Write-Host "📊 Stuck Transactions:" -ForegroundColor Yellow
php artisan tinker --execute="
`$stuck = \App\Models\Transaction::whereIn('ai_status', ['queued', 'processing'])
    ->where('updated_at', '<=', now()->subMinutes(5))
    ->get(['id', 'invoice_number', 'ai_status', 'status', 'updated_at']);

echo 'Count: ' . `$stuck->count() . PHP_EOL;
echo '' . PHP_EOL;

if (`$stuck->count() > 0) {
    echo 'ID | Invoice | AI Status | Status | Stuck Since' . PHP_EOL;
    echo '---|---------|-----------|--------|-------------' . PHP_EOL;
    foreach (`$stuck as `$t) {
        echo `$t->id . ' | ' . 
             (`$t->invoice_number ?? 'N/A') . ' | ' . 
             `$t->ai_status . ' | ' . 
             `$t->status . ' | ' . 
             `$t->updated_at->diffForHumans() . PHP_EOL;
    }
    echo '' . PHP_EOL;
    echo '💡 To fix: php artisan ocr:reset-stuck --fix' . PHP_EOL;
} else {
    echo '✅ No stuck transactions found!' . PHP_EOL;
}
"

Write-Host ""
Write-Host "🔧 Checking Rate Limiter Status..." -ForegroundColor Yellow
Write-Host ""

# Check rate limiter
php artisan tinker --execute="
`$limiter = app(\App\Services\OCR\GeminiRateLimiter::class);
`$status = `$limiter->getStatus();

echo '📈 Rate Limiter Status:' . PHP_EOL;
echo '  Current RPM: ' . `$status['current_rpm'] . '/' . `$status['rpm_limit'] . PHP_EOL;
echo '  Utilization: ' . `$status['utilization_pct'] . '%' . PHP_EOL;
echo '  Queue Size: ' . `$status['queue_size'] . '/' . `$status['max_queue_size'] . PHP_EOL;
echo '  Cooldown Active: ' . (`$status['cooldown_active'] ? 'YES ⚠️' : 'NO ✅') . PHP_EOL;
if (`$status['cooldown_active']) {
    echo '  Cooldown Remaining: ' . `$status['cooldown_remaining'] . 's' . PHP_EOL;
}
if (`$status['last_429']) {
    echo '  Last 429 Error: ' . `$status['last_429']['at'] . PHP_EOL;
}
"

Write-Host ""
Write-Host "🔍 Checking Redis Connection..." -ForegroundColor Yellow
Write-Host ""

# Check Redis
php artisan tinker --execute="
try {
    `$ping = \Illuminate\Support\Facades\Redis::ping();
    echo '✅ Redis: Connected' . PHP_EOL;
    
    // Check OCR-related keys
    `$keys = \Illuminate\Support\Facades\Redis::keys('gemini:*');
    echo '  Gemini Keys: ' . count(`$keys) . PHP_EOL;
    
    `$lockKeys = \Illuminate\Support\Facades\Redis::keys('lock:ai_callback:*');
    echo '  Callback Locks: ' . count(`$lockKeys) . PHP_EOL;
    
} catch (\Exception `$e) {
    echo '❌ Redis: ' . `$e->getMessage() . PHP_EOL;
}
"

Write-Host ""
Write-Host "📋 Recent OCR Logs (last 20 lines)..." -ForegroundColor Yellow
Write-Host ""

if (Test-Path "storage/logs/ocr.log") {
    Get-Content "storage/logs/ocr.log" -Tail 20
} else {
    Write-Host "⚠️ OCR log file not found" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "✅ Check complete!" -ForegroundColor Green
