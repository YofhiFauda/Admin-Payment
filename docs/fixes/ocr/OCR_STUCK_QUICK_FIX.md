# OCR Stuck - Quick Fix Guide

## 🚨 Immediate Actions (Production)

### 1. Check Current Status
```bash
# Run diagnostic script
./scripts/check-stuck-ocr.sh
```

Atau manual:
```bash
php artisan ocr:reset-stuck
```

### 2. Fix Stuck Transactions

**Option A: Interactive (Recommended)**
```bash
./scripts/fix-stuck-ocr.sh
```

**Option B: Automatic (Cron Job)**
```bash
./scripts/fix-stuck-ocr.sh --auto
```

**Option C: Manual Command**
```bash
# Dry-run first (see what will be reset)
php artisan ocr:reset-stuck

# Execute fix
php artisan ocr:reset-stuck --fix
```

### 3. Fix Specific Transaction

Jika ada transaksi tertentu yang stuck dan Anda ingin bypass dengan data yang ada:

```bash
# Lihat detail transaksi
php artisan ocr:reset-stuck --id=42

# Bypass ke completed dengan data di DB
php artisan ocr:reset-stuck --id=42 --complete

# Bypass dengan data dari Redis cache (jika n8n sudah callback)
php artisan ocr:reset-stuck --id=42 --complete --from-cache

# Bypass dengan data manual
php artisan ocr:reset-stuck --id=42 --complete \
  --vendor="Toko ABC" \
  --amount=150000 \
  --date=2026-05-19
```

## 🔄 Setup Auto-Fix (Recommended)

### Option 1: Laravel Scheduler (Recommended)

Edit `app/Console/Kernel.php`:

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

Pastikan Laravel scheduler berjalan:
```bash
# Check if scheduler is running
ps aux | grep "schedule:run"

# If not, add to crontab
crontab -e

# Add this line:
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Option 2: Direct Cron Job

```bash
crontab -e

# Add this line (runs every 10 minutes)
*/10 * * * * cd /path/to/app && php artisan ocr:reset-stuck --fix --minutes=10 >> /var/log/ocr-fix.log 2>&1
```

## 🔍 Root Causes & Prevention

### Common Causes

1. **N8N Callback Tidak Sampai**
   - Network timeout
   - N8N workflow error
   - Secret key mismatch
   
2. **Rate Limiter Blocking**
   - Gemini API 429 error
   - Queue penuh
   - Redis connection issue

3. **Job Timeout**
   - File nota tidak ditemukan
   - Image compression gagal
   - HTTP timeout ke n8n

### Prevention Steps

1. **Monitor N8N Logs**
   ```bash
   # Check n8n logs for errors
   docker logs n8n-container -f
   ```

2. **Monitor Rate Limiter**
   ```bash
   # Check rate limiter status
   php artisan tinker
   >>> app(\App\Services\OCR\GeminiRateLimiter::class)->getStatus()
   ```

3. **Monitor Redis**
   ```bash
   redis-cli
   > KEYS gemini:*
   > GET gemini:global:lock
   ```

4. **Check Queue Workers**
   ```bash
   # Check Horizon dashboard
   php artisan horizon:status
   
   # Check failed jobs
   php artisan queue:failed
   ```

## 📊 Monitoring Dashboard

### Health Check Endpoint (TODO)

Create endpoint untuk monitoring:

```php
// routes/api.php
Route::get('/ocr/health', [OcrHealthController::class, 'health']);
```

Response:
```json
{
  "ocr_healthy": true,
  "stuck_transactions": 0,
  "rate_limiter": {
    "current_rpm": 12,
    "rpm_limit": 60,
    "utilization_pct": 20,
    "queue_size": 3,
    "cooldown_active": false
  },
  "redis_connected": true
}
```

### Monitoring Commands

```bash
# Check stuck count
php artisan tinker --execute="
echo 'Stuck: ' . \App\Models\Transaction::whereIn('ai_status', ['queued', 'processing'])
    ->where('updated_at', '<=', now()->subMinutes(5))
    ->count();
"

# Check rate limiter
php artisan tinker --execute="
print_r(app(\App\Services\OCR\GeminiRateLimiter::class)->getStatus());
"

# Check recent OCR logs
tail -f storage/logs/ocr.log | grep "STUCK\|ERROR\|TIMEOUT"
```

## 🎯 Success Metrics

After implementing fixes, you should see:

- ✅ Stuck rate < 1% of total OCR requests
- ✅ Auto-recovery within 10 minutes for 90% of stuck transactions
- ✅ Clear error messages for users
- ✅ Manual fallback option always available

## 📞 Escalation

If stuck transactions persist after auto-fix:

1. **Check N8N Workflow**
   - Login to n8n dashboard
   - Check workflow execution logs
   - Verify webhook URL is correct
   - Test webhook manually

2. **Check Gemini API**
   - Verify API key is valid
   - Check quota/billing
   - Review rate limits

3. **Check Infrastructure**
   - Redis memory usage
   - Queue worker status
   - Network connectivity
   - Disk space

4. **Manual Intervention**
   ```bash
   # Reset all stuck transactions
   php artisan ocr:reset-stuck --fix
   
   # Restart queue workers
   php artisan horizon:terminate
   php artisan horizon
   
   # Clear Redis cache
   php artisan cache:clear
   redis-cli FLUSHDB
   ```

## 📝 Logging

All OCR operations are logged to:
- `storage/logs/ocr.log` - Job execution logs
- `storage/logs/ai_autofill.log` - Callback logs
- Horizon dashboard - Queue metrics

Search for these patterns:
- `[OCR JOB] JOB STARTED` - Job dispatched
- `[OCR JOB] STATUS UPDATED TO PROCESSING` - Job running
- `[AI CALLBACK] CALLBACK COMPLETE` - Success
- `[OCR JOB] EXCEPTION` - Job failed
- `STUCK` - Stuck detection
