# OCR Stuck di Processing - Diagnosis & Solusi

## 🔍 Root Cause Analysis

Berdasarkan analisis kode, OCR bisa stuck di status "processing" karena beberapa alasan:

### 1. **N8N Callback Tidak Sampai ke Laravel**
   - **Lokasi**: `OcrProcessingJob.php` → n8n → `AiAutoFillController.php`
   - **Penyebab**:
     - N8N webhook gagal mengirim callback ke `/api/ai/auto-fill`
     - Network timeout antara n8n dan Laravel
     - N8N workflow error/crash sebelum callback
     - Secret key mismatch (unauthorized)
   
### 2. **Rate Limiter Blocking**
   - **Lokasi**: `GeminiRateLimiter.php`
   - **Penyebab**:
     - Job acquire slot tapi tidak pernah release (exception sebelum `finally`)
     - Global cooldown aktif terlalu lama (429 dari Gemini)
     - Queue penuh (>200 jobs)
     - Redis connection issue

### 3. **Job Timeout/Failure**
   - **Lokasi**: `OcrProcessingJob.php`
   - **Penyebab**:
     - HTTP timeout ke n8n (120 detik)
     - File nota tidak ditemukan
     - Image compression gagal
     - Job di-release tapi tidak di-retry

### 4. **Race Condition**
   - **Lokasi**: `AiAutoFillController.php`
   - **Penyebab**:
     - Duplicate callback dari n8n
     - Lock Redis tidak berfungsi
     - Cache expired sebelum callback sampai

### 5. **Status Update Tidak Broadcast**
   - **Lokasi**: `OcrProcessingJob.php` line 90-100
   - **Penyebab**:
     - Broadcast gagal (Reverb/Pusher down)
     - Frontend tidak listen ke channel yang benar
     - WebSocket connection terputus

## 🛠️ Solusi yang Sudah Ada

### 1. **Command Reset Manual**
```bash
# Dry-run (lihat transaksi stuck)
php artisan ocr:reset-stuck

# Reset ke error (user isi manual)
php artisan ocr:reset-stuck --fix

# Bypass ke completed (gunakan data existing)
php artisan ocr:reset-stuck --id=42 --complete

# Bypass dengan data dari cache
php artisan ocr:reset-stuck --id=42 --complete --from-cache

# Bypass dengan data manual
php artisan ocr:reset-stuck --id=42 --complete --vendor="Toko ABC" --amount=150000
```

### 2. **Timeout Detection di Polling**
- **Lokasi**: `AiAutoFillController.php` line 641-656
- **Mekanisme**: Jika processing > 3 menit, otomatis mark as error
- **Catatan**: Hanya berfungsi jika frontend masih polling

## 🚀 Rekomendasi Perbaikan

### Priority 1: Monitoring & Alerting

#### A. Tambah Health Check Endpoint
```php
// app/Http/Controllers/Api/OcrHealthController.php
public function health()
{
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
}
```

#### B. Scheduled Auto-Reset
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Auto-reset stuck OCR setiap 10 menit
    $schedule->command('ocr:reset-stuck --fix --minutes=10')
        ->everyTenMinutes()
        ->withoutOverlapping();
}
```

### Priority 2: Improve Job Reliability

#### A. Add Job Retry Logic
```php
// OcrProcessingJob.php
public $tries = 3;
public $backoff = [30, 60, 120]; // Exponential backoff
public $timeout = 180; // 3 minutes max

public function failed(\Throwable $exception)
{
    Log::channel('ocr')->error('❌ [OCR JOB] FINAL FAILURE', [
        'upload_id' => $this->uploadId,
        'error' => $exception->getMessage(),
    ]);
    
    // Mark as error in DB
    Transaction::where('upload_id', $this->uploadId)
        ->update(['ai_status' => 'error']);
    
    // Update cache
    Cache::put("ai_autofill:{$this->uploadId}", [
        'status' => 'error',
        'message' => 'OCR gagal setelah 3 percobaan. Silakan isi manual.',
    ], now()->addMinutes(30));
    
    // Broadcast error
    $transaction = Transaction::where('upload_id', $this->uploadId)->first();
    if ($transaction) {
        broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));
    }
}
```

#### B. Improve Error Handling in Job
```php
// OcrProcessingJob.php - dalam handle()
try {
    // ... existing code ...
    
    if (!$response->successful()) {
        // Jangan throw exception, langsung mark as error
        $this->markAsError($transaction, 'N8N returned error: ' . $response->status());
        return; // Exit gracefully
    }
    
} catch (\Throwable $e) {
    $this->markAsError($transaction, $e->getMessage());
    // Jangan throw lagi, biar job tidak retry
    return;
}

private function markAsError($transaction, $message)
{
    Cache::put("ai_autofill:{$this->uploadId}", [
        'status' => 'error',
        'message' => $message,
    ], now()->addMinutes(30));
    
    if ($transaction) {
        $transaction->update(['ai_status' => 'error']);
        broadcast(new \App\Events\TransactionUpdated($transaction->fresh()));
    }
}
```

### Priority 3: Better Callback Handling

#### A. Add Webhook Retry Mechanism di N8N
- Configure n8n webhook node dengan retry: 3x dengan backoff
- Tambah error handling node untuk log failure

#### B. Add Callback Verification
```php
// AiAutoFillController.php - di akhir store()
// Send confirmation back to n8n
try {
    Http::timeout(5)->post(config('services.n8n.webhook_url') . '/webhook/callback-confirm', [
        'upload_id' => $uploadId,
        'status' => 'received',
        'timestamp' => now()->toIso8601String(),
    ]);
} catch (\Exception $e) {
    // Non-critical, just log
    Log::channel('ai_autofill')->warning('Failed to send callback confirmation', [
        'upload_id' => $uploadId,
        'error' => $e->getMessage(),
    ]);
}
```

### Priority 4: Frontend Improvements

#### A. Add Timeout UI
```javascript
// resources/js/transactions/realtime.js
const OCR_TIMEOUT_MS = 180000; // 3 minutes

let ocrStartTime = Date.now();

function checkOcrTimeout() {
    if (Date.now() - ocrStartTime > OCR_TIMEOUT_MS) {
        // Show error UI
        showOcrTimeoutError();
        // Stop polling
        clearInterval(pollingInterval);
    }
}
```

#### B. Add Manual Retry Button
```blade
{{-- resources/views/transactions/loading.blade.php --}}
<div id="ocr-timeout-error" style="display: none;">
    <p>OCR memakan waktu terlalu lama. Silakan:</p>
    <button onclick="retryOcr()">Coba Lagi</button>
    <button onclick="fillManually()">Isi Manual</button>
</div>
```

## 📊 Monitoring Checklist

### Harian
- [ ] Check Horizon dashboard untuk failed jobs
- [ ] Check log channel `ocr` untuk error patterns
- [ ] Monitor rate limiter status via `/api/ocr/health`

### Mingguan
- [ ] Review stuck transaction trends
- [ ] Check n8n workflow execution logs
- [ ] Verify Redis memory usage

### Bulanan
- [ ] Analyze OCR success rate
- [ ] Review Gemini API quota usage
- [ ] Optimize rate limiter settings

## 🔧 Quick Fixes untuk Production

### 1. Immediate: Reset Stuck Transactions
```bash
# SSH ke server
cd /path/to/app
php artisan ocr:reset-stuck --fix --minutes=5
```

### 2. Short-term: Add Cron Job
```bash
# Edit crontab
crontab -e

# Add line:
*/10 * * * * cd /path/to/app && php artisan ocr:reset-stuck --fix --minutes=10 >> /dev/null 2>&1
```

### 3. Long-term: Implement Monitoring
- Setup Sentry/Bugsnag untuk track OCR failures
- Add Prometheus metrics untuk rate limiter
- Setup alerting untuk stuck count > 10

## 📝 Debugging Commands

```bash
# Check stuck transactions
php artisan tinker
>>> Transaction::whereIn('ai_status', ['queued', 'processing'])->where('updated_at', '<=', now()->subMinutes(5))->get(['id', 'invoice_number', 'ai_status', 'updated_at'])

# Check rate limiter status
>>> app(\App\Services\OCR\GeminiRateLimiter::class)->getStatus()

# Check Redis keys
redis-cli
> KEYS gemini:*
> GET gemini:global:lock
> ZCARD gemini:rpm:window

# Check queue jobs
php artisan queue:failed
php artisan horizon:list

# Check logs
tail -f storage/logs/ocr.log
tail -f storage/logs/ai_autofill.log
```

## 🎯 Expected Outcomes

Setelah implementasi fixes:
- **Stuck rate**: < 1% dari total OCR requests
- **Auto-recovery**: 90% stuck transactions auto-reset dalam 10 menit
- **Manual intervention**: Hanya untuk edge cases
- **User experience**: Clear error messages + manual fallback option
