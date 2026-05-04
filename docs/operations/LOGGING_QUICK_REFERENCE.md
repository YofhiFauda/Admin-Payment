# 📝 Logging Quick Reference

## 🚀 Quick Start

### 1. Setup (One-time)

```bash
# Autoload helper
composer dump-autoload

# Make scripts executable
chmod +x scripts/rotate-logs.sh
chmod +x scripts/analyze-logs.sh

# Setup cron
crontab -e
# Add: 0 3 * * * /var/www/scripts/rotate-logs.sh
```

### 2. Environment Variables

```env
# .env production
LOG_CHANNEL=stack
LOG_STACK=daily,error,slack
LOG_LEVEL=warning
LOG_DAILY_DAYS=30
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK
```

---

## 📚 Usage Examples

### Basic Logging

```php
use Illuminate\Support\Facades\Log;

Log::info('User logged in', ['user_id' => $user->id]);
Log::warning('High memory usage', ['memory' => memory_get_usage()]);
Log::error('Payment failed', ['transaction_id' => $transaction->id]);
Log::critical('Database connection lost');
```

### Custom Helper (Recommended)

```php
use App\Helpers\LogHelper;

// OCR logging
LogHelper::ocr('info', 'Processing invoice', [
    'file' => $filename,
    'size' => $filesize,
]);

// Security logging
LogHelper::security('Failed login attempt', [
    'email' => $email,
    'ip' => request()->ip(),
]);

// Audit logging
LogHelper::audit('created', 'Transaction', $transaction->id, [
    'amount' => $transaction->amount,
    'status' => $transaction->status,
]);

// Performance logging (auto-logs if > 1 second)
$start = microtime(true);
// ... expensive operation ...
$duration = (microtime(true) - $start) * 1000;
LogHelper::performance('OCR Processing', $duration);

// Queue logging
LogHelper::queue('info', 'OcrProcessingJob', [
    'status' => 'completed',
    'duration_ms' => $duration,
]);

// Exception logging
try {
    // code
} catch (\Exception $e) {
    LogHelper::exception($e, ['context' => 'additional info']);
}

// Sanitize sensitive data
$data = LogHelper::sanitize([
    'email' => 'user@example.com',
    'password' => 'secret123', // Will be ***REDACTED***
]);
Log::info('User data', $data);
```

---

## 🔍 Monitoring Commands

```bash
# Watch logs real-time
tail -f storage/logs/laravel.log
tail -f storage/logs/error.log
tail -f storage/logs/ocr.log

# Count errors
grep -c "ERROR" storage/logs/laravel.log

# Find slow queries
grep "Slow query" storage/logs/performance.log

# Failed login attempts
grep "Failed login" storage/logs/security.log | wc -l

# OCR average processing time
grep "duration_ms" storage/logs/ocr.log | \
  grep -oP 'duration_ms":\K[0-9.]+' | \
  awk '{sum+=$1; count++} END {print sum/count}'

# Analyze logs
./scripts/analyze-logs.sh
```

---

## 📊 Log Channels

| Channel | Purpose | Retention | Level |
|---------|---------|-----------|-------|
| `daily` | All application logs | 30 days | warning |
| `error` | Errors & critical only | 90 days | error |
| `ocr` | OCR processing | 14 days | info |
| `queue` | Queue jobs | 14 days | info |
| `security` | Security events | 90 days | notice |
| `audit` | User actions | 365 days | info |
| `performance` | Slow operations | 7 days | warning |
| `slack` | Critical alerts | N/A | critical |

---

## 🎯 Log Levels

| Level | When to Use | Production |
|-------|-------------|------------|
| `debug` | Development debugging | ❌ Never |
| `info` | Informational messages | ⚠️ Selective |
| `notice` | Normal but significant | ✅ Yes |
| `warning` | Warning conditions | ✅ Yes |
| `error` | Error conditions | ✅ Yes |
| `critical` | Critical conditions | ✅ Yes |
| `alert` | Action must be taken | ✅ Yes |
| `emergency` | System unusable | ✅ Yes |

---

## 🛠️ Maintenance

```bash
# Rotate logs manually
./scripts/rotate-logs.sh

# Analyze logs
./scripts/analyze-logs.sh

# Check disk usage
du -sh storage/logs

# Compress old logs
find storage/logs -name "*.log" -mtime +7 -exec gzip {} \;

# Delete old compressed logs
find storage/logs -name "*.log.gz" -mtime +30 -delete
```

---

## 📖 Full Documentation

- **Complete Guide**: `MONOLOG_PRODUCTION_GUIDE.md`
- **Production Checklist**: `PRODUCTION_READINESS_CHECKLIST.md`
- **Telescope Guide**: `TELESCOPE_PRODUCTION_GUIDE.md`

---

**Last Updated**: May 4, 2026
