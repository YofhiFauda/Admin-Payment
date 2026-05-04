# 🚀 Install Laravel Log Viewer - Step by Step

## Overview

Laravel Log Viewer memberikan GUI yang beautiful untuk melihat Monolog logs, mirip dengan Telescope tapi production-safe.

---

## 📦 Installation

### Step 1: Install Package

```bash
# Install via Composer
composer require opcodesio/log-viewer

# Publish configuration
php artisan log-viewer:publish
```

### Step 2: Configure Security

Edit `config/log-viewer.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Path
    |--------------------------------------------------------------------------
    */
    'route_path' => 'log-viewer',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web', 'auth', 'role:owner'],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */
    'authorize' => function ($request) {
        // Only allow owner and admin
        return $request->user() && 
               in_array($request->user()->role, ['owner', 'admin']);
    },

    /*
    |--------------------------------------------------------------------------
    | Back to System URL
    |--------------------------------------------------------------------------
    */
    'back_to_system_url' => '/dashboard',

    /*
    |--------------------------------------------------------------------------
    | Max Log Size
    |--------------------------------------------------------------------------
    */
    'max_log_size_to_display' => 104857600, // 100MB

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => 'Asia/Jakarta',

    /*
    |--------------------------------------------------------------------------
    | Include Files Pattern
    |--------------------------------------------------------------------------
    */
    'include_files' => [
        'laravel*.log',
        'error*.log',
        'ocr*.log',
        'queue*.log',
        'security*.log',
        'audit*.log',
        'performance*.log',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude Files Pattern
    |--------------------------------------------------------------------------
    */
    'exclude_files' => [
        'horizon*.log',
    ],
];
```

### Step 3: Add Route (Optional - if not auto-registered)

```php
// routes/web.php
use Opcodes\LogViewer\Facades\LogViewer;

Route::middleware(['auth', 'role:owner'])->group(function () {
    LogViewer::auth();
});
```

### Step 4: Test

```bash
# Generate test logs
php artisan tinker

>>> use Illuminate\Support\Facades\Log;
>>> Log::info('Test info log from Log Viewer installation');
>>> Log::warning('Test warning log');
>>> Log::error('Test error log');
>>> exit

# Visit log viewer
# https://yourdomain.com/log-viewer
```

---

## 🎨 Features

### 1. **Multiple Log Files**
- View all log files in one place
- Switch between files easily
- laravel.log, error.log, ocr.log, etc.

### 2. **Advanced Filtering**
- Filter by log level (debug, info, warning, error, critical)
- Filter by date range
- Full-text search

### 3. **Beautiful UI**
- Syntax highlighting
- Color-coded log levels
- Dark mode support
- Responsive design

### 4. **Real-time Updates**
- Auto-refresh option
- Live log streaming
- No page reload needed

### 5. **Download & Export**
- Download individual log files
- Export filtered results
- Share specific log entries

### 6. **Performance**
- Lazy loading
- Pagination
- Efficient file reading
- Production-safe

---

## 🔒 Security Best Practices

### 1. **Always Require Authentication**

```php
// config/log-viewer.php
'middleware' => ['web', 'auth', 'role:owner'],
```

### 2. **Use Authorization Gate**

```php
'authorize' => function ($request) {
    return $request->user() && 
           $request->user()->role === 'owner';
},
```

### 3. **Restrict by IP (Optional)**

```php
'authorize' => function ($request) {
    $allowedIps = ['103.xxx.xxx.xxx', '202.xxx.xxx.xxx'];
    
    return $request->user() && 
           $request->user()->role === 'owner' &&
           in_array($request->ip(), $allowedIps);
},
```

### 4. **Disable in Production (if needed)**

```php
// .env
LOG_VIEWER_ENABLED=false

// config/log-viewer.php
'enabled' => env('LOG_VIEWER_ENABLED', true),
```

---

## 📊 Usage Examples

### Viewing Logs

1. **Access Log Viewer**
   ```
   https://yourdomain.com/log-viewer
   ```

2. **Select Log File**
   - Click on file name in sidebar
   - laravel.log, error.log, ocr.log, etc.

3. **Filter by Level**
   - Click on level badges
   - ERROR, WARNING, INFO, DEBUG

4. **Search Logs**
   - Use search box
   - Full-text search across all logs

5. **Filter by Date**
   - Select date range
   - View logs from specific period

### Keyboard Shortcuts

- `Ctrl/Cmd + K` - Focus search
- `Ctrl/Cmd + R` - Refresh logs
- `Esc` - Clear search
- `↑/↓` - Navigate log entries

---

## 🎯 Alternative: rap2hpoutre/laravel-log-viewer

If you prefer a simpler alternative:

```bash
# Install
composer require rap2hpoutre/laravel-log-viewer

# Add route
Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerController::class, 'index']);
});

# Access
# https://yourdomain.com/logs
```

**Pros:**
- Simpler
- Lighter
- Single file view

**Cons:**
- Less features
- No real-time updates
- Basic UI

---

## 🔄 Comparison: Log Viewer vs Telescope

| Feature | Log Viewer | Telescope |
|---------|-----------|-----------|
| **Production Ready** | ✅ Yes | ❌ No |
| **Performance** | ✅ Low overhead | ❌ High overhead |
| **Storage** | ✅ File-based | ❌ Database bloat |
| **Security** | ✅ Safe | ⚠️ Risk |
| **Log Viewing** | ✅ Excellent | ✅ Excellent |
| **Queries** | ❌ No | ✅ Yes |
| **Requests** | ❌ No | ✅ Yes |
| **Jobs** | ❌ No | ✅ Yes |
| **Events** | ❌ No | ✅ Yes |
| **Cost** | FREE | FREE |

**Verdict:** 
- **Log Viewer** for production log viewing
- **Telescope** for development debugging only

---

## 🚀 Complete Setup: Log Viewer + Pulse + Sentry

### The Perfect Combo (All FREE)

```bash
# 1. Log Viewer - For viewing logs
composer require opcodesio/log-viewer
php artisan log-viewer:publish

# 2. Laravel Pulse - For metrics
composer require laravel/pulse
php artisan pulse:install
php artisan migrate

# 3. Sentry - For error tracking
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN
```

### Access Points

```
📊 Logs:    https://yourdomain.com/log-viewer
📈 Metrics: https://yourdomain.com/pulse
🐛 Errors:  https://sentry.io
```

### What Each Does

| Tool | Purpose | Use Case |
|------|---------|----------|
| **Log Viewer** | View detailed logs | Debugging, investigation |
| **Pulse** | Real-time metrics | Performance monitoring |
| **Sentry** | Error tracking | Production errors |

---

## 📋 Production Checklist

### Before Deployment

- [ ] Install Log Viewer
- [ ] Configure authentication
- [ ] Test access control
- [ ] Set up authorization gate
- [ ] Configure file patterns
- [ ] Test in staging

### After Deployment

- [ ] Verify authentication works
- [ ] Test log viewing
- [ ] Check performance impact
- [ ] Monitor disk usage
- [ ] Train team on usage

---

## 🆘 Troubleshooting

### Issue: Can't access log viewer

**Solution:**
```bash
# Clear cache
php artisan config:cache
php artisan route:cache

# Check authentication
# Make sure user is logged in and has correct role
```

### Issue: Logs not showing

**Solution:**
```bash
# Check file permissions
ls -la storage/logs/

# Fix permissions
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs

# Check log files exist
ls storage/logs/*.log
```

### Issue: Performance slow

**Solution:**
```php
// config/log-viewer.php
'max_log_size_to_display' => 52428800, // Reduce to 50MB

// Or exclude large files
'exclude_files' => [
    'horizon*.log',
    'very-large*.log',
],
```

### Issue: 403 Forbidden

**Solution:**
```php
// Check authorization in config/log-viewer.php
'authorize' => function ($request) {
    \Log::info('Log Viewer Auth Check', [
        'user_id' => $request->user()?->id,
        'role' => $request->user()?->role,
    ]);
    
    return $request->user() && 
           $request->user()->role === 'owner';
},
```

---

## 📚 Resources

- [Log Viewer Documentation](https://log-viewer.opcodes.io/)
- [GitHub Repository](https://github.com/opcodesio/log-viewer)
- [Laravel Pulse](https://laravel.com/docs/pulse)
- [Sentry Laravel](https://docs.sentry.io/platforms/php/guides/laravel/)

---

## 🎉 Summary

**You now have:**

✅ **Beautiful GUI** for viewing Monolog logs  
✅ **Production-safe** with low overhead  
✅ **Secure** with authentication & authorization  
✅ **Feature-rich** with filtering, search, real-time  
✅ **FREE** - No cost  

**Next Steps:**
1. Install on production server (Linux)
2. Configure authentication
3. Test access
4. Train team

**Access:** `https://yourdomain.com/log-viewer`

---

**Last Updated**: May 4, 2026
**Version**: 1.0
