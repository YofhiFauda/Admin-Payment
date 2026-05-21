# 🔧 OCR Stuck Fix - Complete Solution

## 📖 Overview

This solution addresses the issue of OCR transactions getting stuck in "processing" status. The fix includes diagnostic tools, auto-fix scripts, and comprehensive documentation.

## 🎯 Quick Start

### 1. Check for Stuck Transactions

**Windows (PowerShell):**
```powershell
.\scripts\check-stuck-ocr.ps1
```

**Linux/Mac (Bash):**
```bash
./scripts/check-stuck-ocr.sh
```

**Manual:**
```bash
php artisan ocr:reset-stuck
```

### 2. Fix Stuck Transactions

**Windows (PowerShell):**
```powershell
# Interactive mode
.\scripts\fix-stuck-ocr.ps1

# Auto mode (no confirmation)
.\scripts\fix-stuck-ocr.ps1 -Auto
```

**Linux/Mac (Bash):**
```bash
# Interactive mode
./scripts/fix-stuck-ocr.sh

# Auto mode (no confirmation)
./scripts/fix-stuck-ocr.sh --auto
```

**Manual:**
```bash
# Dry-run (see what will be fixed)
php artisan ocr:reset-stuck

# Execute fix
php artisan ocr:reset-stuck --fix
```

## 📁 Documentation Files

| File | Purpose |
|------|---------|
| `OCR_STUCK_SUMMARY.md` | Executive summary & action plan |
| `OCR_STUCK_QUICK_FIX.md` | Quick reference guide |
| `OCR_STUCK_DIAGNOSIS_AND_FIX.md` | Comprehensive diagnosis & solutions |
| `README_OCR_STUCK_FIX.md` | This file |

## 🛠️ Scripts

| Script | Platform | Purpose |
|--------|----------|---------|
| `scripts/check-stuck-ocr.sh` | Linux/Mac | Diagnostic script |
| `scripts/fix-stuck-ocr.sh` | Linux/Mac | Auto-fix script |
| `scripts/check-stuck-ocr.ps1` | Windows | Diagnostic script |
| `scripts/fix-stuck-ocr.ps1` | Windows | Auto-fix script |

## 🚀 Setup Auto-Fix (Recommended)

### Option 1: Laravel Scheduler (Recommended)

1. Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Auto-reset stuck OCR every 10 minutes
    $schedule->command('ocr:reset-stuck --fix --minutes=10')
        ->everyTenMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}
```

2. Ensure Laravel scheduler is running:

**Linux/Mac:**
```bash
# Check if running
ps aux | grep "schedule:run"

# Add to crontab if not running
crontab -e

# Add this line:
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

**Windows (Task Scheduler):**
- Open Task Scheduler
- Create new task
- Trigger: Every 1 minute
- Action: `php artisan schedule:run`
- Start in: Your project directory

### Option 2: Direct Cron Job

**Linux/Mac:**
```bash
crontab -e

# Add this line (runs every 10 minutes):
*/10 * * * * cd /path/to/app && php artisan ocr:reset-stuck --fix --minutes=10 >> /var/log/ocr-fix.log 2>&1
```

**Windows (Task Scheduler):**
- Open Task Scheduler
- Create new task
- Trigger: Every 10 minutes
- Action: `php artisan ocr:reset-stuck --fix --minutes=10`
- Start in: Your project directory

## 📊 Available Commands

### Diagnostic Commands

```bash
# Check stuck transactions (dry-run)
php artisan ocr:reset-stuck

# Check with specific time threshold
php artisan ocr:reset-stuck --minutes=5

# Check specific status
php artisan ocr:reset-stuck --status=processing

# Check specific transaction
php artisan ocr:reset-stuck --id=42
```

### Fix Commands

```bash
# Reset stuck transactions to error
php artisan ocr:reset-stuck --fix

# Reset with custom threshold
php artisan ocr:reset-stuck --fix --minutes=5

# Bypass specific transaction to completed
php artisan ocr:reset-stuck --id=42 --complete

# Bypass with cache data
php artisan ocr:reset-stuck --id=42 --complete --from-cache

# Bypass with manual data
php artisan ocr:reset-stuck --id=42 --complete \
  --vendor="Toko ABC" \
  --amount=150000 \
  --date=2026-05-19
```

### Monitoring Commands

```bash
# Check rate limiter status
php artisan tinker
>>> app(\App\Services\OCR\GeminiRateLimiter::class)->getStatus()

# Check stuck count
>>> Transaction::whereIn('ai_status', ['queued', 'processing'])
    ->where('updated_at', '<=', now()->subMinutes(5))
    ->count()

# View recent logs
tail -f storage/logs/ocr.log
tail -f storage/logs/ai_autofill.log

# Search logs for specific upload_id
grep "nota-1234567890" storage/logs/ocr.log
```

## 🔍 Understanding the OCR Flow

```
User Upload
    ↓
OcrProcessingJob (Laravel Queue)
    ↓
N8N Webhook
    ↓
Gemini API (OCR Processing)
    ↓
N8N Callback
    ↓
AiAutoFillController (Laravel)
    ↓
Broadcast to Frontend
    ↓
User Sees Result
```

### Potential Failure Points

1. **Job Dispatch** → Queue not running
2. **N8N Webhook** → Network timeout, n8n down
3. **Gemini API** → Rate limit, API error
4. **N8N Callback** → Network timeout, wrong URL
5. **Laravel Callback** → Secret mismatch, validation error
6. **Broadcast** → Reverb/Pusher down, WebSocket error

## 🚨 Troubleshooting

### Issue: Transactions Still Stuck After Fix

**Check:**
1. Is Laravel scheduler running?
   ```bash
   ps aux | grep "schedule:run"
   ```

2. Is queue worker running?
   ```bash
   php artisan horizon:status
   # or
   php artisan queue:work --once
   ```

3. Is Redis connected?
   ```bash
   php artisan tinker
   >>> Redis::ping()
   ```

4. Check logs:
   ```bash
   tail -f storage/logs/ocr.log
   tail -f storage/logs/ai_autofill.log
   ```

### Issue: N8N Not Sending Callbacks

**Check:**
1. N8N workflow is active
2. Webhook URL is correct in n8n
3. Secret key matches between Laravel and n8n
4. Network connectivity between n8n and Laravel

**Test manually:**
```bash
curl -X POST http://your-app.com/api/ai/auto-fill \
  -H "X-SECRET: your-secret" \
  -H "Content-Type: application/json" \
  -d '{
    "upload_id": "nota-1234567890",
    "status": "success",
    "vendor": "Test Vendor",
    "amount": 100000,
    "confidence": 85
  }'
```

### Issue: Rate Limiter Blocking

**Check status:**
```bash
php artisan tinker
>>> app(\App\Services\OCR\GeminiRateLimiter::class)->getStatus()
```

**Clear cooldown:**
```bash
php artisan tinker
>>> Redis::del('gemini:global:lock')
>>> Redis::del('gemini:last429')
```

**Adjust limits in `.env`:**
```env
GEMINI_RPM_LIMIT=60
GEMINI_COOLDOWN_SECONDS=5
OCR_MAX_QUEUE_SIZE=200
```

## 📈 Monitoring & Alerting

### Health Check

Create a monitoring endpoint:

```php
// routes/api.php
Route::get('/ocr/health', function() {
    $rateLimiter = app(\App\Services\OCR\GeminiRateLimiter::class);
    $status = $rateLimiter->getStatus();
    
    $stuckCount = Transaction::whereIn('ai_status', ['queued', 'processing'])
        ->where('updated_at', '<=', now()->subMinutes(5))
        ->count();
    
    return response()->json([
        'ocr_healthy' => $stuckCount < 5 && !$status['cooldown_active'],
        'stuck_transactions' => $stuckCount,
        'rate_limiter' => $status,
        'redis_connected' => Redis::ping(),
    ]);
});
```

### Monitoring Script

```bash
# Add to crontab (check every 5 minutes)
*/5 * * * * curl -s http://your-app.com/api/ocr/health | jq '.stuck_transactions' | xargs -I {} sh -c 'if [ {} -gt 10 ]; then echo "ALERT: {} stuck OCR transactions" | mail -s "OCR Alert" admin@example.com; fi'
```

## 🎯 Success Metrics

After implementing this solution:

- ✅ **Stuck Rate**: < 1% of total OCR requests
- ✅ **Auto-Recovery**: 90% of stuck transactions auto-reset within 10 minutes
- ✅ **Manual Intervention**: Only needed for edge cases
- ✅ **User Experience**: Clear error messages + manual fallback option

## 📞 Support

For issues or questions:

1. Check `OCR_STUCK_DIAGNOSIS_AND_FIX.md` for detailed troubleshooting
2. Check `OCR_STUCK_QUICK_FIX.md` for quick reference
3. Run diagnostic scripts: `./scripts/check-stuck-ocr.sh` or `.\scripts\check-stuck-ocr.ps1`
4. Review logs: `storage/logs/ocr.log` and `storage/logs/ai_autofill.log`

## 📝 Change Log

### 2026-05-19 - Initial Release
- Created diagnostic scripts (bash & PowerShell)
- Created auto-fix scripts (bash & PowerShell)
- Created comprehensive documentation
- Added Laravel command for manual intervention
- Documented auto-fix setup with Laravel scheduler

## 🔐 Security Notes

- Scripts require proper file permissions on Linux/Mac
- Ensure `.env` secrets are not exposed in logs
- Rate limiter prevents API abuse
- Redis locks prevent race conditions

## 📚 Additional Resources

- Laravel Queue Documentation: https://laravel.com/docs/queues
- Laravel Task Scheduling: https://laravel.com/docs/scheduling
- Redis Documentation: https://redis.io/docs
- N8N Documentation: https://docs.n8n.io

---

**Created**: 2026-05-19  
**Version**: 1.0.0  
**Status**: Production Ready  
**Priority**: High
