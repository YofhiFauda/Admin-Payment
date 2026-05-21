# OCR Stuck di Processing - Summary & Action Plan

## 📋 Executive Summary

OCR transactions dapat stuck di status "processing" karena beberapa alasan:
1. N8N callback tidak sampai ke Laravel
2. Rate limiter blocking
3. Job timeout/failure
4. Race condition
5. Broadcast failure

## 🎯 Immediate Actions (Do This Now)

### 1. Check Current Stuck Transactions
```bash
php artisan ocr:reset-stuck
```

### 2. Fix Stuck Transactions
```bash
# Interactive mode (recommended)
./scripts/fix-stuck-ocr.sh

# Or direct command
php artisan ocr:reset-stuck --fix
```

### 3. Setup Auto-Fix (Prevent Future Issues)

**Edit `app/Console/Kernel.php`:**
```php
protected function schedule(Schedule $schedule)
{
    // Auto-reset stuck OCR every 10 minutes
    $schedule->command('ocr:reset-stuck --fix --minutes=10')
        ->everyTenMinutes()
        ->withoutOverlapping();
}
```

**Ensure scheduler is running:**
```bash
# Add to crontab if not already
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## 📁 Files Created

1. **OCR_STUCK_DIAGNOSIS_AND_FIX.md** - Comprehensive diagnosis & solutions
2. **OCR_STUCK_QUICK_FIX.md** - Quick reference guide
3. **scripts/check-stuck-ocr.sh** - Diagnostic script
4. **scripts/fix-stuck-ocr.sh** - Auto-fix script

## 🔧 Available Commands

### Check Status
```bash
# Dry-run (see stuck transactions)
php artisan ocr:reset-stuck

# Detailed diagnostics
./scripts/check-stuck-ocr.sh
```

### Fix Stuck Transactions
```bash
# Reset to error (users fill manually)
php artisan ocr:reset-stuck --fix

# Bypass specific transaction to completed
php artisan ocr:reset-stuck --id=42 --complete

# Bypass with cache data
php artisan ocr:reset-stuck --id=42 --complete --from-cache

# Bypass with manual data
php artisan ocr:reset-stuck --id=42 --complete --vendor="Toko ABC" --amount=150000
```

### Monitoring
```bash
# Check rate limiter status
php artisan tinker
>>> app(\App\Services\OCR\GeminiRateLimiter::class)->getStatus()

# Check stuck count
>>> Transaction::whereIn('ai_status', ['queued', 'processing'])->where('updated_at', '<=', now()->subMinutes(5))->count()

# View logs
tail -f storage/logs/ocr.log
tail -f storage/logs/ai_autofill.log
```

## 🚀 Recommended Implementation Order

### Phase 1: Immediate (Today)
- [x] ✅ Create diagnostic scripts
- [ ] Run `php artisan ocr:reset-stuck --fix` to clear current stuck transactions
- [ ] Setup auto-fix scheduler in `app/Console/Kernel.php`
- [ ] Verify scheduler is running

### Phase 2: Short-term (This Week)
- [ ] Add job retry logic to `OcrProcessingJob.php`
- [ ] Improve error handling in job
- [ ] Add timeout detection in frontend
- [ ] Add manual retry button in UI

### Phase 3: Long-term (This Month)
- [ ] Create health check endpoint
- [ ] Setup monitoring/alerting (Sentry/Bugsnag)
- [ ] Add Prometheus metrics
- [ ] Review n8n workflow reliability
- [ ] Optimize rate limiter settings

## 📊 Expected Results

After implementing Phase 1:
- ✅ Stuck transactions auto-reset every 10 minutes
- ✅ Users can fill forms manually after reset
- ✅ No manual intervention needed for most cases

After implementing Phase 2:
- ✅ Jobs auto-retry on failure
- ✅ Better error messages for users
- ✅ Frontend timeout handling

After implementing Phase 3:
- ✅ Proactive monitoring
- ✅ Alerts before issues become critical
- ✅ < 1% stuck rate

## 🔍 Debugging Workflow

When OCR is stuck:

1. **Check if it's actually stuck**
   ```bash
   php artisan ocr:reset-stuck
   ```

2. **Check rate limiter**
   ```bash
   php artisan tinker
   >>> app(\App\Services\OCR\GeminiRateLimiter::class)->getStatus()
   ```

3. **Check logs**
   ```bash
   tail -f storage/logs/ocr.log | grep "upload_id_here"
   ```

4. **Check n8n**
   - Login to n8n dashboard
   - Check workflow executions
   - Look for failed webhooks

5. **Fix it**
   ```bash
   # Reset to error
   php artisan ocr:reset-stuck --id=42 --fix
   
   # Or bypass to completed
   php artisan ocr:reset-stuck --id=42 --complete --from-cache
   ```

## 📞 Support

If issues persist:

1. Check `OCR_STUCK_DIAGNOSIS_AND_FIX.md` for detailed troubleshooting
2. Check `OCR_STUCK_QUICK_FIX.md` for quick reference
3. Run `./scripts/check-stuck-ocr.sh` for full diagnostics
4. Review logs in `storage/logs/ocr.log` and `storage/logs/ai_autofill.log`

## 🎓 Key Learnings

1. **OCR Flow**: Upload → Job → N8N → Gemini → N8N Callback → Laravel → Broadcast
2. **Failure Points**: Any step can fail, causing stuck status
3. **Solution**: Auto-reset + manual fallback + monitoring
4. **Prevention**: Retry logic + timeout detection + health checks

## ✅ Next Steps

1. **Immediate**: Run `php artisan ocr:reset-stuck --fix` now
2. **Today**: Setup auto-fix scheduler
3. **This Week**: Implement Phase 2 improvements
4. **This Month**: Setup monitoring & alerting

---

**Created**: 2026-05-19
**Status**: Ready for implementation
**Priority**: High (affects user experience)
