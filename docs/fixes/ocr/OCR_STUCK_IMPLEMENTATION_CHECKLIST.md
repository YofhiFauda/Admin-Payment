# ✅ OCR Stuck Fix - Implementation Checklist

## 📋 Pre-Implementation

- [ ] Read `OCR_STUCK_SUMMARY.md` for overview
- [ ] Read `README_OCR_STUCK_FIX.md` for detailed instructions
- [ ] Backup database before making changes
- [ ] Ensure you have access to:
  - [ ] Server SSH/terminal
  - [ ] Laravel application
  - [ ] N8N dashboard
  - [ ] Redis CLI
  - [ ] Crontab/Task Scheduler

## 🚀 Phase 1: Immediate Fix (Do Now)

### Step 1: Check Current Stuck Transactions

**Windows:**
```powershell
.\scripts\check-stuck-ocr.ps1
```

**Linux/Mac:**
```bash
./scripts/check-stuck-ocr.sh
```

**Manual:**
```bash
php artisan ocr:reset-stuck
```

- [ ] Executed diagnostic script
- [ ] Noted number of stuck transactions: _______
- [ ] Reviewed stuck transaction details

### Step 2: Fix Stuck Transactions

**Windows:**
```powershell
.\scripts\fix-stuck-ocr.ps1
```

**Linux/Mac:**
```bash
./scripts/fix-stuck-ocr.sh
```

**Manual:**
```bash
php artisan ocr:reset-stuck --fix
```

- [ ] Executed fix script
- [ ] Verified transactions are now in 'error' status
- [ ] Confirmed users can now fill forms manually

### Step 3: Verify Fix

- [ ] Check transaction status in database
- [ ] Test manual form submission
- [ ] Verify no new stuck transactions appear

**Verification Command:**
```bash
php artisan tinker
>>> Transaction::whereIn('ai_status', ['queued', 'processing'])->where('updated_at', '<=', now()->subMinutes(5))->count()
```

Expected result: `0`

## 🔧 Phase 2: Setup Auto-Fix (Do Today)

### Step 1: Update Laravel Scheduler

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

- [ ] Added scheduler code to `app/Console/Kernel.php`
- [ ] Saved file
- [ ] Committed changes to git

### Step 2: Verify Scheduler is Running

**Linux/Mac:**
```bash
# Check if scheduler is running
ps aux | grep "schedule:run"

# If not running, add to crontab
crontab -e

# Add this line:
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

**Windows:**
- [ ] Open Task Scheduler
- [ ] Create new task: "Laravel Scheduler"
- [ ] Trigger: Every 1 minute
- [ ] Action: `php artisan schedule:run`
- [ ] Start in: Your project directory
- [ ] Test task runs successfully

- [ ] Verified scheduler is running
- [ ] Tested scheduler executes commands

### Step 3: Test Auto-Fix

1. Create a test stuck transaction (optional):
```bash
php artisan tinker
>>> $tx = Transaction::first()
>>> $tx->update(['ai_status' => 'processing', 'updated_at' => now()->subMinutes(15)])
```

2. Wait 10 minutes or manually trigger:
```bash
php artisan schedule:run
```

3. Verify transaction is auto-fixed:
```bash
php artisan tinker
>>> Transaction::find($tx->id)->ai_status
```

Expected result: `'error'`

- [ ] Created test stuck transaction
- [ ] Waited for auto-fix or manually triggered
- [ ] Verified transaction was auto-fixed
- [ ] Removed test transaction

## 📊 Phase 3: Monitoring Setup (Do This Week)

### Step 1: Setup Log Monitoring

**Option A: Tail logs in separate terminal**
```bash
# Terminal 1: OCR logs
tail -f storage/logs/ocr.log

# Terminal 2: AI Autofill logs
tail -f storage/logs/ai_autofill.log
```

**Option B: Setup log aggregation (e.g., Papertrail, Loggly)**
- [ ] Signed up for log aggregation service
- [ ] Configured Laravel to send logs
- [ ] Created alerts for OCR errors

**Option C: Use Laravel Log Viewer**
- [ ] Installed Laravel Log Viewer (if not already)
- [ ] Configured access permissions
- [ ] Created bookmark for quick access

- [ ] Setup log monitoring solution
- [ ] Tested log viewing
- [ ] Created alerts for critical errors

### Step 2: Setup Health Check Monitoring

Create health check endpoint in `routes/api.php`:

```php
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

- [ ] Added health check endpoint
- [ ] Tested endpoint: `curl http://your-app.com/api/ocr/health`
- [ ] Verified response is correct

### Step 3: Setup Uptime Monitoring

**Option A: Use UptimeRobot or similar**
- [ ] Created account
- [ ] Added health check URL
- [ ] Configured alerts (email/SMS)
- [ ] Set check interval: 5 minutes

**Option B: Use cron + curl**
```bash
crontab -e

# Add this line (check every 5 minutes):
*/5 * * * * curl -s http://your-app.com/api/ocr/health | jq '.stuck_transactions' | xargs -I {} sh -c 'if [ {} -gt 10 ]; then echo "ALERT: {} stuck OCR transactions" | mail -s "OCR Alert" admin@example.com; fi'
```

- [ ] Setup uptime monitoring
- [ ] Configured alerts
- [ ] Tested alert delivery

## 🔬 Phase 4: Advanced Improvements (Do This Month)

### Step 1: Add Job Retry Logic

Edit `app/Jobs/OcrProcessingJob.php`:

```php
public $tries = 3;
public $backoff = [30, 60, 120];
public $timeout = 180;

public function failed(\Throwable $exception)
{
    Log::channel('ocr')->error('❌ [OCR JOB] FINAL FAILURE', [
        'upload_id' => $this->uploadId,
        'error' => $exception->getMessage(),
    ]);
    
    Transaction::where('upload_id', $this->uploadId)
        ->update(['ai_status' => 'error']);
    
    Cache::put("ai_autofill:{$this->uploadId}", [
        'status' => 'error',
        'message' => 'OCR gagal setelah 3 percobaan. Silakan isi manual.',
    ], now()->addMinutes(30));
    
    $transaction = Transaction::where('upload_id', $this->uploadId)->first();
    if ($transaction) {
        broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));
    }
}
```

- [ ] Added retry logic to job
- [ ] Added failed() method
- [ ] Tested job failure handling
- [ ] Verified error broadcast works

### Step 2: Improve Frontend Timeout Handling

Edit `resources/js/transactions/realtime.js`:

```javascript
const OCR_TIMEOUT_MS = 180000; // 3 minutes
let ocrStartTime = Date.now();

function checkOcrTimeout() {
    if (Date.now() - ocrStartTime > OCR_TIMEOUT_MS) {
        showOcrTimeoutError();
        clearInterval(pollingInterval);
    }
}

function showOcrTimeoutError() {
    // Show error UI with manual fallback
    document.getElementById('ocr-loading').style.display = 'none';
    document.getElementById('ocr-timeout-error').style.display = 'block';
}
```

- [ ] Added timeout detection to frontend
- [ ] Added error UI for timeout
- [ ] Added manual retry button
- [ ] Tested timeout handling

### Step 3: Setup Error Tracking (Sentry/Bugsnag)

**Option A: Sentry**
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn
```

**Option B: Bugsnag**
```bash
composer require bugsnag/bugsnag-laravel
php artisan vendor:publish --provider="Bugsnag\BugsnagLaravel\BugsnagServiceProvider"
```

- [ ] Installed error tracking service
- [ ] Configured DSN/API key
- [ ] Tested error reporting
- [ ] Created alerts for OCR errors

### Step 4: Optimize Rate Limiter Settings

Review and adjust in `.env`:

```env
# Free tier: 15 RPM
GEMINI_RPM_LIMIT=15
GEMINI_COOLDOWN_SECONDS=5
OCR_MAX_QUEUE_SIZE=200

# Pro tier: 60 RPM
# GEMINI_RPM_LIMIT=60
# GEMINI_COOLDOWN_SECONDS=3
# OCR_MAX_QUEUE_SIZE=500
```

- [ ] Reviewed current Gemini API tier
- [ ] Adjusted rate limiter settings
- [ ] Tested with production load
- [ ] Monitored for 429 errors

## 📈 Phase 5: Performance Optimization (Optional)

### Step 1: Add Database Indexes

```sql
-- Add index for stuck transaction queries
ALTER TABLE transactions 
ADD INDEX idx_ai_status_updated_at (ai_status, updated_at);

-- Add index for upload_id lookups
ALTER TABLE transactions 
ADD INDEX idx_upload_id (upload_id);
```

- [ ] Added database indexes
- [ ] Verified query performance improved
- [ ] Monitored database load

### Step 2: Optimize Image Compression

Review `app/Services/ImageCompressionService.php`:

- [ ] Reviewed compression settings
- [ ] Tested compression quality vs file size
- [ ] Adjusted settings if needed
- [ ] Verified OCR accuracy not affected

### Step 3: Add Caching for Rate Limiter Status

```php
// Cache rate limiter status for 30 seconds
$status = Cache::remember('rate_limiter_status', 30, function() {
    return app(\App\Services\OCR\GeminiRateLimiter::class)->getStatus();
});
```

- [ ] Added caching for rate limiter status
- [ ] Tested performance improvement
- [ ] Verified cache invalidation works

## ✅ Final Verification

### Functional Tests

- [ ] Upload new nota → OCR completes successfully
- [ ] Upload nota → Simulate n8n failure → Auto-reset after 10 min
- [ ] Upload nota → Manual reset works
- [ ] Upload nota → Bypass with cache works
- [ ] Upload nota → Bypass with manual data works

### Performance Tests

- [ ] Upload 10 notas simultaneously → All process correctly
- [ ] Check rate limiter doesn't block legitimate requests
- [ ] Verify queue workers handle load
- [ ] Check Redis memory usage is acceptable

### Monitoring Tests

- [ ] Health check endpoint returns correct data
- [ ] Alerts trigger when stuck count > threshold
- [ ] Logs are readable and useful
- [ ] Error tracking captures OCR failures

### User Experience Tests

- [ ] Loading screen shows progress
- [ ] Timeout shows error message
- [ ] Manual fallback is easy to use
- [ ] Success shows auto-filled form

## 📊 Success Metrics

After implementation, track these metrics:

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Stuck Rate | < 1% | ___% | ⬜ |
| Auto-Recovery Time | < 10 min | ___ min | ⬜ |
| Manual Intervention | < 5% | ___% | ⬜ |
| User Satisfaction | > 90% | ___% | ⬜ |
| OCR Success Rate | > 95% | ___% | ⬜ |

## 📝 Documentation

- [ ] Updated team wiki with OCR troubleshooting guide
- [ ] Created runbook for on-call engineers
- [ ] Documented common issues and solutions
- [ ] Trained team on new monitoring tools

## 🎉 Completion

- [ ] All phases completed
- [ ] Metrics meet targets
- [ ] Team trained
- [ ] Documentation updated
- [ ] Monitoring in place
- [ ] Auto-fix working reliably

**Completion Date:** _______________

**Signed off by:** _______________

---

**Notes:**
- Check off items as you complete them
- Add notes for any issues encountered
- Update metrics weekly
- Review and adjust thresholds as needed
