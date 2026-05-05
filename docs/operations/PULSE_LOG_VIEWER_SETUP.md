# 🚀 Laravel Pulse & Log Viewer - Complete Setup Guide

## 📋 Overview

Setup lengkap untuk **Laravel Pulse** (real-time metrics) dan **Laravel Log Viewer** (GUI untuk logs).

---

## ✅ Files Created

### Configuration Files
- ✅ `config/pulse.php` - Pulse configuration
- ✅ `config/log-viewer.php` - Log Viewer configuration
- ✅ `app/Providers/PulseServiceProvider.php` - Pulse authorization
- ✅ `database/migrations/2026_05_04_000001_create_pulse_tables.php` - Pulse tables
- ✅ `INSTALL_PULSE_AND_LOG_VIEWER.sh` - Installation script

### Updated Files
- ✅ `bootstrap/app.php` - Registered PulseServiceProvider
- ✅ `.env.production.example` - Added Pulse & Log Viewer config

---

## 🚀 Installation (Production Server - Linux)

### Method 1: Using Installation Script (Recommended)

```bash
# 1. Make script executable
chmod +x INSTALL_PULSE_AND_LOG_VIEWER.sh

# 2. Run installation
./INSTALL_PULSE_AND_LOG_VIEWER.sh

# 3. Update .env
nano .env
# Add Pulse & Log Viewer configuration (see below)

# 4. Test access
# Visit: https://yourdomain.com/pulse
# Visit: https://yourdomain.com/log-viewer
```

### Method 2: Manual Installation

```bash
# 1. Install Laravel Pulse
composer require laravel/pulse

# 2. Run migrations
php artisan migrate

# 3. Install Laravel Log Viewer
composer require opcodesio/log-viewer

# 4. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🔧 Configuration

### 1. Environment Variables

Add to `.env`:

```env
# ─── Laravel Pulse (Monitoring) ────────────────────────────────────
PULSE_ENABLED=true
PULSE_PATH=pulse

# Pulse Thresholds (milliseconds)
PULSE_SLOW_JOBS_THRESHOLD=1000
PULSE_SLOW_QUERIES_THRESHOLD=1000
PULSE_SLOW_REQUESTS_THRESHOLD=1000
PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD=1000

# ─── Laravel Log Viewer (GUI for Logs) ─────────────────────────────
LOG_VIEWER_PATH=log-viewer
LOG_VIEWER_BACK_URL=/dashboard
LOG_VIEWER_TIMEZONE=Asia/Jakarta
```

### 2. Authentication (Already Configured)

**Pulse Authorization** (`app/Providers/PulseServiceProvider.php`):
```php
Gate::define('viewPulse', function ($user) {
    return in_array($user->role, ['owner', 'admin']);
});
```

**Log Viewer Authorization** (`config/log-viewer.php`):
```php
'authorize' => function ($request) {
    return $request->user() && 
           in_array($request->user()->role, ['owner', 'admin']);
},
```

### 3. Routes (Auto-registered)

Routes are automatically registered by the packages:

- **Pulse**: `https://yourdomain.com/pulse`
- **Log Viewer**: `https://yourdomain.com/log-viewer`

---

## 📊 Laravel Pulse Features

### What Pulse Monitors

1. **Slow Requests** - HTTP requests > 1 second
2. **Slow Queries** - Database queries > 1 second
3. **Slow Jobs** - Queue jobs > 1 second
4. **Slow Outgoing Requests** - External API calls > 1 second
5. **Exceptions** - Application errors
6. **Queues** - Queue metrics
7. **Cache** - Cache hit/miss rates
8. **Servers** - Server resources (CPU, memory, disk)
9. **User Requests** - Top users by request count
10. **User Jobs** - Top users by job count

### Dashboard Preview

```
┌─────────────────────────────────────────────────────────┐
│ Laravel Pulse - WHUSNET Admin Payment                  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Requests/min: 1,234  │  Exceptions: 3                  │
│ Slow Queries: 12     │  Queue Wait: 2.3s               │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ Slowest Endpoints                                       │
│ POST /api/ocr ............................ 2,345ms      │
│ GET /dashboard ........................... 1,234ms      │
│ POST /api/transactions ................... 987ms       │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ Slowest Queries                                         │
│ SELECT * FROM transactions WHERE... ...... 1,567ms      │
│ SELECT * FROM price_indexes WHERE... ..... 1,234ms      │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ Recent Exceptions                                       │
│ PaymentGatewayException .................. 3 times      │
│ DatabaseConnectionException .............. 1 time       │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ Queue Jobs                                              │
│ ocr: 45 pending, 234 processed                         │
│ default: 12 pending, 567 processed                     │
└─────────────────────────────────────────────────────────┘
```

### Customizing Thresholds

Edit `.env` to adjust what's considered "slow":

```env
# Default: 1000ms (1 second)
PULSE_SLOW_JOBS_THRESHOLD=2000        # 2 seconds
PULSE_SLOW_QUERIES_THRESHOLD=500      # 500ms
PULSE_SLOW_REQUESTS_THRESHOLD=1500    # 1.5 seconds
```

---

## 🖥️ Laravel Log Viewer Features

### What Log Viewer Shows

1. **All Log Files** - laravel.log, error.log, ocr.log, etc.
2. **Filter by Level** - ERROR, WARNING, INFO, DEBUG
3. **Filter by Date** - Today, yesterday, last 7 days, custom range
4. **Full-text Search** - Search across all logs
5. **Stack Traces** - Formatted exception traces
6. **Context Data** - JSON context data
7. **Download Logs** - Export log files
8. **Real-time Updates** - Auto-refresh option

### Dashboard Preview

```
┌─────────────────────────────────────────────────────────┐
│ Laravel Log Viewer                                      │
├─────────────────────────────────────────────────────────┤
│ File: [laravel.log ▼]  Level: [All ▼]  [Search...]    │
│ Date: [Today ▼]  [Auto-refresh: ON]  [Download]       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 🔴 ERROR   | 2026-05-04 10:23:45                        │
│   Payment failed: Gateway timeout                       │
│   Context:                                              │
│   {                                                     │
│     "transaction_id": "INV-20260504-00123",            │
│     "user_id": 45,                                     │
│     "amount": 1500000,                                 │
│     "gateway": "midtrans"                              │
│   }                                                     │
│   Stack Trace:                                          │
│   #0 app/Services/PaymentService.php(123)             │
│   #1 app/Http/Controllers/PaymentController.php(45)   │
│   [View Full Trace]                                    │
│                                                         │
│ ⚠️  WARNING | 2026-05-04 10:22:30                       │
│   Slow query detected: 2,345ms                         │
│   SELECT * FROM transactions WHERE...                   │
│   Context:                                              │
│   {                                                     │
│     "query": "SELECT * FROM transactions...",          │
│     "time": 2345.67,                                   │
│     "connection": "mysql"                              │
│   }                                                     │
│                                                         │
│ ℹ️  INFO    | 2026-05-04 10:21:15                       │
│   User logged in                                        │
│   Context:                                              │
│   {                                                     │
│     "user_id": 45,                                     │
│     "email": "user@example.com",                       │
│     "ip": "103.xxx.xxx.xxx"                            │
│   }                                                     │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Viewing Different Log Files

Log Viewer automatically detects all log files in `storage/logs/`:

- `laravel.log` - Main application log
- `error.log` - Errors only
- `ocr.log` - OCR processing
- `queue.log` - Queue jobs
- `security.log` - Security events
- `audit.log` - User actions
- `performance.log` - Slow operations

Switch between files using the dropdown menu.

---

## 🔒 Security

### Access Control

Both Pulse and Log Viewer are protected by:

1. **Authentication** - Must be logged in
2. **Authorization** - Must have `owner` or `admin` role

### Testing Access Control

```bash
# Test as owner
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://yourdomain.com/pulse

# Test as regular user (should be denied)
curl -H "Authorization: Bearer REGULAR_USER_TOKEN" \
     https://yourdomain.com/pulse
```

### IP Whitelist (Optional)

Add IP restriction to `config/pulse.php`:

```php
Gate::define('viewPulse', function ($user) {
    $allowedIps = ['103.xxx.xxx.xxx', '202.xxx.xxx.xxx'];
    
    return in_array($user->role, ['owner', 'admin']) &&
           in_array(request()->ip(), $allowedIps);
});
```

---

## 📊 Performance Impact

### Laravel Pulse

| Metric | Impact |
|--------|--------|
| CPU | < 1% |
| Memory | ~10MB |
| Database | ~1MB/day |
| Response Time | < 5ms |

**Verdict:** ✅ Production-safe

### Laravel Log Viewer

| Metric | Impact |
|--------|--------|
| CPU | < 0.5% |
| Memory | ~5MB |
| Disk I/O | Minimal (only when viewing) |
| Response Time | < 100ms |

**Verdict:** ✅ Production-safe

---

## 🎯 Usage Examples

### Scenario 1: Investigating Slow Performance

**Problem:** Users report slow page loads

**Steps:**
1. Open **Pulse**: `https://yourdomain.com/pulse`
2. Check **Slow Requests** card
3. Identify slowest endpoints
4. Check **Slow Queries** card
5. Identify problematic queries
6. Open **Log Viewer**: `https://yourdomain.com/log-viewer`
7. Filter `performance.log`
8. See detailed slow operation logs

**Result:** Found slow query, added index, performance improved

---

### Scenario 2: Debugging Production Error

**Problem:** Payment processing fails

**Steps:**
1. Check **Pulse** for exceptions
2. See `PaymentGatewayException` occurred 5 times
3. Open **Log Viewer**
4. Filter by ERROR level
5. Search for "PaymentGateway"
6. View full stack trace and context
7. Identify root cause: timeout issue
8. Fix and deploy

**Result:** Issue resolved, monitoring for recurrence

---

### Scenario 3: Monitoring Queue Health

**Problem:** Need to monitor OCR queue

**Steps:**
1. Open **Pulse**
2. Check **Queues** card
3. See `ocr` queue metrics:
   - Pending jobs: 45
   - Processed: 234
   - Failed: 2
4. Check **Slow Jobs** card
5. Identify jobs taking > 1 second
6. Open **Log Viewer**
7. Filter `queue.log`
8. See detailed job logs

**Result:** Identified slow OCR jobs, optimized processing

---

## 🛠️ Maintenance

### Data Retention

**Pulse** automatically trims old data:

```php
// config/pulse.php
'ingest' => [
    'trim' => [
        'keep' => '7 days', // Keep 7 days of data
    ],
],
```

**Log Viewer** reads from log files (managed by log rotation):

```bash
# Log rotation (already configured)
./scripts/rotate-logs.sh
```

### Database Cleanup

Pulse stores data in database. Monitor size:

```sql
-- Check Pulse table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = 'admin-payment'
    AND table_name LIKE 'pulse_%'
ORDER BY (data_length + index_length) DESC;
```

Manual cleanup (if needed):

```bash
# Trim Pulse data older than 7 days
php artisan pulse:clear
```

---

## 📋 Troubleshooting

### Issue: Can't access Pulse

**Solution:**
```bash
# Check if user is authenticated
# Check if user has owner/admin role
# Check logs
tail -f storage/logs/laravel.log | grep Pulse

# Clear caches
php artisan config:cache
php artisan route:cache
```

### Issue: Can't access Log Viewer

**Solution:**
```bash
# Check authentication
# Check file permissions
ls -la storage/logs/

# Fix permissions
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs

# Clear caches
php artisan config:cache
php artisan route:cache
```

### Issue: Pulse not recording data

**Solution:**
```bash
# Check if Pulse is enabled
grep PULSE_ENABLED .env

# Check database connection
php artisan pulse:check

# Check migrations
php artisan migrate:status | grep pulse

# Run migrations if needed
php artisan migrate
```

### Issue: Log Viewer shows "No logs found"

**Solution:**
```bash
# Check if log files exist
ls -la storage/logs/

# Generate test logs
php artisan tinker
>>> Log::info('Test log');
>>> exit

# Check file patterns in config/log-viewer.php
```

---

## 🎓 Best Practices

### ✅ DO

1. **Monitor regularly**
   - Check Pulse daily
   - Review slow queries
   - Monitor exceptions

2. **Set appropriate thresholds**
   - Adjust based on your app
   - Start with 1000ms
   - Lower for critical endpoints

3. **Use both tools together**
   - Pulse for overview
   - Log Viewer for details

4. **Secure access**
   - Require authentication
   - Limit to owner/admin
   - Consider IP whitelist

5. **Monitor disk space**
   - Pulse database size
   - Log file sizes
   - Setup rotation

### ❌ DON'T

1. **Don't expose publicly**
   - Always require auth
   - Don't disable authorization

2. **Don't ignore alerts**
   - Slow queries need attention
   - Exceptions need investigation

3. **Don't set thresholds too low**
   - Too many false positives
   - Start high, adjust down

4. **Don't forget to rotate logs**
   - Disk will fill up
   - Use rotation scripts

---

## 📚 Additional Resources

- [Laravel Pulse Documentation](https://laravel.com/docs/pulse)
- [Log Viewer Documentation](https://log-viewer.opcodes.io/)
- [Monolog Documentation](https://github.com/Seldaek/monolog)

---

## 🎉 Summary

### What You Have Now

✅ **Laravel Pulse** - Real-time metrics dashboard  
✅ **Laravel Log Viewer** - Beautiful GUI for logs  
✅ **Monolog** - Core logging system  
✅ **LogHelper** - Custom logging helper  
✅ **Security** - Authentication & authorization  
✅ **Documentation** - Complete guides  

### Access Points

```
📊 Log Viewer: https://yourdomain.com/log-viewer
📈 Pulse:      https://yourdomain.com/pulse
```

### Next Steps

1. ✅ Run installation script on production
2. ✅ Update .env with configuration
3. ✅ Test access to both dashboards
4. ✅ Train team on usage
5. ✅ Monitor regularly

---

**Congratulations! You now have a complete monitoring solution! 🚀**

---

**Last Updated**: May 4, 2026  
**Version**: 1.0  
**Maintainer**: DevOps Team
