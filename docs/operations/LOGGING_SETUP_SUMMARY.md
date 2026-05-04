# 📝 Logging Setup Summary - WHUSNET Admin Payment

## ✅ What Has Been Configured

### 1. **Files Created**

```
✅ MONOLOG_PRODUCTION_GUIDE.md      # Panduan lengkap Monolog
✅ LOGGING_QUICK_REFERENCE.md       # Quick reference untuk daily use
✅ app/Helpers/LogHelper.php        # Custom logging helper class
✅ scripts/rotate-logs.sh           # Log rotation script
✅ scripts/analyze-logs.sh          # Log analysis script
```

### 2. **Files Updated**

```
✅ config/logging.php               # Production-ready logging config
✅ composer.json                    # Autoload LogHelper
✅ .env.production.example          # Logging environment variables
✅ PRODUCTION_READINESS_CHECKLIST.md # Added logging section
```

---

## 🚀 Quick Setup (5 Minutes)

### Step 1: Autoload Helper

```bash
composer dump-autoload
```

### Step 2: Update .env

```env
# Production settings
LOG_CHANNEL=stack
LOG_STACK=daily,error,slack
LOG_LEVEL=warning
LOG_LEVEL_OCR=info
LOG_LEVEL_QUEUE=info
LOG_DAILY_DAYS=30

# Slack webhook (optional)
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK
LOG_SLACK_USERNAME="WHUSNET Production Alert"
```

### Step 3: Setup Log Rotation

```bash
# Make scripts executable
chmod +x scripts/rotate-logs.sh
chmod +x scripts/analyze-logs.sh

# Add to crontab
crontab -e

# Add this line:
0 3 * * * /var/www/scripts/rotate-logs.sh >> /var/www/storage/logs/rotation.log 2>&1
```

### Step 4: Test Logging

```php
use App\Helpers\LogHelper;

// Test basic logging
Log::info('Testing logging setup');

// Test custom helper
LogHelper::ocr('info', 'Test OCR log', ['test' => true]);
LogHelper::security('Test security log');
LogHelper::audit('test', 'TestModel', 1, ['field' => 'value']);
```

---

## 📊 Log Structure

```
storage/logs/
├── laravel.log              # Main application log (30 days)
├── laravel-2026-05-04.log   # Daily rotated logs
├── error.log                # Errors only (90 days)
├── ocr.log                  # OCR processing (14 days)
├── queue.log                # Queue jobs (14 days)
├── security.log             # Security events (90 days)
├── audit.log                # User actions (365 days)
└── performance.log          # Slow operations (7 days)
```

---

## 🎯 Usage Examples

### In Controllers

```php
namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;

class PembelianController extends Controller
{
    public function store(Request $request)
    {
        $start = microtime(true);
        
        try {
            Log::info('Creating transaction', [
                'user_id' => auth()->id(),
                'branch_id' => $request->branch_id,
            ]);
            
            $transaction = Transaction::create($request->validated());
            
            // Audit log
            LogHelper::audit('created', 'Transaction', $transaction->id, [
                'amount' => $transaction->amount,
            ]);
            
            // Performance log
            $duration = (microtime(true) - $start) * 1000;
            LogHelper::performance('Transaction Creation', $duration);
            
            return response()->json($transaction);
            
        } catch (\Exception $e) {
            LogHelper::exception($e, [
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);
            
            throw $e;
        }
    }
}
```

### In Jobs

```php
namespace App\Jobs;

use App\Helpers\LogHelper;

class OcrProcessingJob implements ShouldQueue
{
    public function handle()
    {
        $start = microtime(true);
        
        LogHelper::queue('info', 'OcrProcessingJob', [
            'status' => 'started',
            'transaction_id' => $this->transaction->id,
        ]);
        
        try {
            $result = $this->processOcr();
            
            $duration = (microtime(true) - $start) * 1000;
            
            LogHelper::ocr('info', 'OCR completed', [
                'transaction_id' => $this->transaction->id,
                'duration_ms' => $duration,
                'items_found' => count($result['items']),
            ]);
            
        } catch (\Exception $e) {
            LogHelper::queue('error', 'OcrProcessingJob', [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception)
    {
        LogHelper::queue('critical', 'OcrProcessingJob', [
            'status' => 'failed_permanently',
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Security Logging

```php
// In AuthController
public function login(Request $request)
{
    if (!Auth::attempt($request->only('email', 'password'))) {
        LogHelper::security('Failed login attempt', [
            'email' => $request->email,
        ]);
        
        return response()->json(['error' => 'Invalid credentials'], 401);
    }
    
    LogHelper::security('Successful login', [
        'user_id' => auth()->id(),
    ]);
    
    return response()->json(['token' => $token]);
}
```

---

## 🔍 Monitoring

### Real-time Monitoring

```bash
# Watch all logs
tail -f storage/logs/laravel.log

# Watch errors only
tail -f storage/logs/error.log

# Watch OCR processing
tail -f storage/logs/ocr.log | grep "duration_ms"

# Watch security events
tail -f storage/logs/security.log

# Multi-tail (install: apt-get install multitail)
multitail storage/logs/laravel.log storage/logs/error.log
```

### Analysis Commands

```bash
# Run full analysis
./scripts/analyze-logs.sh

# Count errors
grep -c "ERROR" storage/logs/laravel.log

# Top 10 errors
grep "ERROR" storage/logs/error.log | \
  cut -d' ' -f6- | \
  sort | uniq -c | sort -rn | head -10

# Failed login attempts
grep "Failed login" storage/logs/security.log | wc -l

# OCR average time
grep "duration_ms" storage/logs/ocr.log | \
  grep -oP 'duration_ms":\K[0-9.]+' | \
  awk '{sum+=$1; count++} END {print sum/count}'
```

---

## 📋 Production Checklist

### Before Deployment

- [ ] Set `LOG_LEVEL=warning` in `.env`
- [ ] Configure `LOG_STACK=daily,error,slack`
- [ ] Setup Slack webhook (optional)
- [ ] Run `composer dump-autoload`
- [ ] Make scripts executable (`chmod +x scripts/*.sh`)
- [ ] Setup cron for log rotation
- [ ] Test logging in staging

### After Deployment

- [ ] Verify logs are being written
- [ ] Check log rotation is working
- [ ] Monitor disk space usage
- [ ] Test Slack notifications (if configured)
- [ ] Review log retention policies
- [ ] Setup monitoring alerts

---

## 🎓 Best Practices

### ✅ DO

1. **Use appropriate log levels**
   - Production: `warning` and above
   - Staging: `info` and above
   - Development: `debug` (all)

2. **Include context**
   ```php
   Log::error('Payment failed', [
       'transaction_id' => $transaction->id,
       'user_id' => $user->id,
       'error' => $e->getMessage(),
   ]);
   ```

3. **Sanitize sensitive data**
   ```php
   $data = LogHelper::sanitize($request->all());
   Log::info('Request data', $data);
   ```

4. **Use separate channels**
   ```php
   Log::channel('ocr')->info('Processing');
   Log::channel('security')->warning('Failed login');
   ```

### ❌ DON'T

1. **Don't log in loops**
   ```php
   // ❌ BAD
   foreach ($items as $item) {
       Log::info('Processing', ['id' => $item->id]);
   }
   
   // ✅ GOOD
   Log::info('Processing items', ['count' => count($items)]);
   ```

2. **Don't log sensitive data**
   ```php
   // ❌ BAD
   Log::info('Login', ['password' => $password]);
   
   // ✅ GOOD
   Log::info('Login', ['email' => $email]);
   ```

3. **Don't use debug level in production**
   - Too verbose
   - Performance impact
   - Storage waste

---

## 🔗 Documentation

| Document | Purpose |
|----------|---------|
| `MONOLOG_PRODUCTION_GUIDE.md` | Complete Monolog guide |
| `LOGGING_QUICK_REFERENCE.md` | Quick reference for daily use |
| `PRODUCTION_READINESS_CHECKLIST.md` | Production deployment checklist |
| `TELESCOPE_PRODUCTION_GUIDE.md` | Telescope vs Monolog comparison |

---

## 🆘 Troubleshooting

### Logs not being written

```bash
# Check permissions
ls -la storage/logs/

# Fix permissions
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs
```

### Disk space full

```bash
# Check disk usage
df -h

# Check log sizes
du -sh storage/logs/*

# Compress old logs
find storage/logs -name "*.log" -mtime +7 -exec gzip {} \;

# Delete old compressed logs
find storage/logs -name "*.log.gz" -mtime +30 -delete
```

### Logs too verbose

```env
# Increase log level
LOG_LEVEL=error  # Only errors and critical
```

### Missing log entries

```bash
# Check log channel configuration
php artisan config:cache

# Verify .env settings
grep LOG_ .env
```

---

## 📊 Performance Impact

| Configuration | Disk Usage | Performance | Recommended |
|---------------|------------|-------------|-------------|
| `LOG_LEVEL=debug` | Very High | High | ❌ Dev only |
| `LOG_LEVEL=info` | High | Medium | ⚠️ Staging |
| `LOG_LEVEL=warning` | Low | Low | ✅ Production |
| `LOG_LEVEL=error` | Very Low | Very Low | ✅ High-traffic |

---

## 🎉 Summary

You now have:

✅ **Production-ready logging** with Monolog  
✅ **Custom helper class** for easy logging  
✅ **Automatic log rotation** with retention policies  
✅ **Log analysis tools** for monitoring  
✅ **Separate channels** for different concerns  
✅ **Security & audit logging** built-in  
✅ **Performance tracking** for slow operations  
✅ **Slack integration** for critical alerts  

**Next Steps:**
1. Run `composer dump-autoload`
2. Update `.env` with logging settings
3. Setup cron for log rotation
4. Test logging in staging
5. Deploy to production

---

**Questions?** Check `MONOLOG_PRODUCTION_GUIDE.md` for detailed documentation.

**Last Updated**: May 4, 2026
