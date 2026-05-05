---
title: Development Tools Usage Guide
inclusion: manual
tags: [development, monitoring, debugging]
---

# 🛠️ Development Tools Usage Guide

## Overview

Project ini menggunakan beberapa development tools yang **HANYA** tersedia di local development environment:

- **Laravel Pulse** - Real-time application monitoring
- **Laravel ** - Debug assistant
- **Log Viewer** - Web-based log viewer

⚠️ **PENTING:** Tools ini **TIDAK** akan terinstall di production untuk alasan security dan performance.

---

## 🔍 Laravel Pulse

### Akses
```
http://localhost:8000/pulse
```

### Fitur
- Real-time request monitoring
- Queue job tracking
- Cache hit/miss rates
- Slow query detection
- Exception tracking
- User request tracking

### Setup (Sudah Dikonfigurasi)
```bash
# Publish config (jika belum)
php artisan vendor:publish --tag=pulse-config

# Publish dashboard
php artisan vendor:publish --tag=pulse-dashboard

# Run migration
php artisan migrate
```

### Proteksi Route
Di `routes/web.php` atau `config/pulse.php`:
```php
'middleware' => ['web', 'auth', 'role:owner'],
```

---

## 🔭 Laravel 

### Akses
```
http://localhost:8000/
```

### Fitur
- Request inspection
- Exception tracking
- Database queries
- Model events
- Mail preview
- Cache operations
- Redis commands
- Schedule monitoring

### Setup
```bash
# Install (sudah di require-dev)
composer require laravel/ --dev

# Publish assets
php artisan :install

# Run migration
php artisan migrate
```

### Proteksi
Di `app/Providers/ServiceProvider.php`:
```php
protected function gate()
{
    Gate::define('view', function ($user) {
        return in_array($user->email, [
            'admin@whusnet.com',
        ]) || $user->role === 'owner';
    });
}
```

---

## 📋 Log Viewer

### Akses
```
http://localhost:8000/log-viewer
```

### Fitur
- Browse log files
- Filter by level (error, warning, info, debug)
- Search logs
- Download logs
- Real-time log streaming

### Setup
```bash
# Publish config
php artisan vendor:publish --tag=log-viewer-config

# Publish assets
php artisan vendor:publish --tag=log-viewer-assets
```

### Proteksi Route
Di `config/log-viewer.php`:
```php
'middleware' => ['web', 'auth', 'role:owner'],
```

---

## 🚀 Local Development Workflow

### 1. Start Development Environment
```bash
# Dengan Docker
docker-compose up -d

# Atau dengan composer dev script
composer dev
```

### 2. Access Tools
- **App:** http://localhost:8000
- **Pulse:** http://localhost:8000/pulse
- **:** http://localhost:8000/
- **Log Viewer:** http://localhost:8000/log-viewer
- **Horizon:** http://localhost:8000/horizon

### 3. Monitoring Workflow
1. **Pulse** - Monitor real-time performance
2. **** - Debug specific requests
3. **Log Viewer** - Check error logs
4. **Horizon** - Monitor queue jobs

---

## 🔒 Security Best Practices

### 1. Environment Check
Pastikan tools hanya aktif di local:
```php
// config/pulse.php
'enabled' => env('PULSE_ENABLED', false),

// .env.example
PULSE_ENABLED=false

// .env (local only)
PULSE_ENABLED=true
```

### 2. Middleware Protection
Selalu gunakan middleware auth + role:
```php
Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::get('/pulse', ...);
    Route::get('/log-viewer', ...);
});
```

### 3. Production Check
Di production, tools ini **TIDAK** akan terinstall karena:
```bash
# Dockerfile.prod menggunakan
composer install --no-dev
```

---

## 📊 Monitoring Checklist

### Daily Monitoring
- [ ] Check Pulse untuk slow queries
- [ ] Review error logs di Log Viewer
- [ ] Monitor queue jobs di Horizon
- [ ] Check exception rate di 

### Before Deployment
- [ ] Review  exceptions
- [ ] Check slow query logs
- [ ] Verify queue job success rate
- [ ] Review cache hit rates

### Performance Optimization
- [ ] Identify N+1 queries di 
- [ ] Check slow endpoints di Pulse
- [ ] Monitor memory usage
- [ ] Review cache effectiveness

---

## 🐛 Debugging Tips

### 1. Slow Requests
```
Pulse → Slow Requests → Click request → View in 
```

### 2. Database Issues
```
 → Queries → Sort by duration → Optimize slow queries
```

### 3. Queue Failures
```
Horizon → Failed Jobs → View exception → Check logs
```

### 4. Application Errors
```
Log Viewer → Filter: error → Search by date/message
```

---

## 🔄 Updating Tools

```bash
# Update all dev dependencies
composer update --dev

# Update specific tool
composer update laravel/pulse --dev
composer update opcodesio/log-viewer --dev
```

---

## ⚠️ Common Issues

### Issue: Pulse dashboard blank
**Solution:**
```bash
php artisan pulse:clear
php artisan config:clear
php artisan cache:clear
```

### Issue:  not recording
**Solution:**
```bash
# Check .env
_ENABLED=true

# Clear config
php artisan config:clear
```

### Issue: Log Viewer 403
**Solution:**
Check middleware di `config/log-viewer.php` dan pastikan user punya role yang sesuai.

---

## 📚 Resources

- [Laravel Pulse Docs](https://laravel.com/docs/pulse)
- [Laravel  Docs](https://laravel.com/docs/)
- [Log Viewer Docs](https://log-viewer.opcodes.io/)
- [Laravel Horizon Docs](https://laravel.com/docs/horizon)

---

## 💡 Pro Tips

1. **Use Pulse for real-time monitoring** during development
2. **Use  for debugging** specific issues
3. **Use Log Viewer for historical analysis** of errors
4. **Use Horizon for queue management** and monitoring
5. **Never expose these tools in production** without proper authentication
6. **Regularly check for slow queries** and optimize them
7. **Monitor exception rates** to catch issues early

---

## 🎯 Quick Commands

```bash
# Clear all caches
php artisan optimize:clear

# View logs in terminal
php artisan pail

# Monitor queue
php artisan horizon

# Run tests
php artisan test

# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```
