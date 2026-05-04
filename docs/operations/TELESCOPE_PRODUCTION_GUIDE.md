# Telescope di Production - Panduan Lengkap

## ⚠️ Rekomendasi: NONAKTIFKAN di Production

Telescope **TIDAK disarankan** untuk production karena:

1. **Performance Impact**: Merekam setiap request, query, job → overhead signifikan
2. **Storage**: Database membengkak dengan data monitoring
3. **Security Risk**: Bisa mengekspos data sensitif (queries, headers, env vars)
4. **Memory Usage**: Konsumsi memory yang tidak perlu

---

## ✅ Cara Menonaktifkan Telescope (RECOMMENDED)

### Opsi 1: Via Environment Variable (Paling Mudah)

Di `.env` production:
```env
TELESCOPE_ENABLED=false
```

### Opsi 2: Uninstall Package (Paling Bersih)

```bash
# Hapus dari composer
composer remove laravel/telescope --dev

# Hapus service provider dari config/app.php jika ada
# Hapus file TelescopeServiceProvider.php
```

### Opsi 3: Conditional Loading (Flexible)

Edit `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    if ($this->app->environment('local', 'staging')) {
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->app->register(TelescopeServiceProvider::class);
    }
}
```

---

## 🔧 Jika HARUS Menggunakan Telescope di Production

### Konfigurasi Minimal & Aman

#### 1. Filter Hanya Error & Exception

File: `app/Providers/TelescopeServiceProvider.php`

```php
public function register(): void
{
    $this->hideSensitiveRequestDetails();

    // Hanya rekam error, exception, dan failed jobs
    Telescope::filter(function (IncomingEntry $entry) {
        if ($this->app->environment('local')) {
            return true; // Rekam semua di local
        }

        // Di production: hanya rekam yang penting
        return $entry->isReportableException() ||
               $entry->isFailedRequest() ||
               $entry->isFailedJob();
    });
}
```

#### 2. Nonaktifkan Watcher yang Tidak Perlu

File: `.env` production:

```env
TELESCOPE_ENABLED=true

# Nonaktifkan watcher yang berat
TELESCOPE_BATCH_WATCHER=false
TELESCOPE_CACHE_WATCHER=false
TELESCOPE_CLIENT_REQUEST_WATCHER=false
TELESCOPE_COMMAND_WATCHER=false
TELESCOPE_DUMP_WATCHER=false
TELESCOPE_EVENT_WATCHER=false
TELESCOPE_GATE_WATCHER=false
TELESCOPE_MODEL_WATCHER=false
TELESCOPE_NOTIFICATION_WATCHER=false
TELESCOPE_QUERY_WATCHER=false
TELESCOPE_REDIS_WATCHER=false
TELESCOPE_REQUEST_WATCHER=false
TELESCOPE_SCHEDULE_WATCHER=false
TELESCOPE_VIEW_WATCHER=false

# Aktifkan hanya untuk error tracking
TELESCOPE_EXCEPTION_WATCHER=true
TELESCOPE_JOB_WATCHER=true
TELESCOPE_LOG_WATCHER=true
```

#### 3. Batasi Akses dengan IP Whitelist

File: `app/Providers/TelescopeServiceProvider.php`

```php
protected function gate(): void
{
    Gate::define('viewTelescope', function ($user) {
        // Hanya owner + IP whitelist
        $allowedIps = ['103.xxx.xxx.xxx', '202.xxx.xxx.xxx'];
        $userIp = request()->ip();

        return $user->role === 'owner' && 
               in_array($userIp, $allowedIps);
    });
}
```

#### 4. Auto-Prune Data Lama

File: `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    // Hapus data Telescope lebih dari 7 hari
    $schedule->command('telescope:prune --hours=168')->daily();
}
```

#### 5. Gunakan Queue untuk Async Recording

File: `config/telescope.php`

```php
'queue' => [
    'connection' => env('TELESCOPE_QUEUE_CONNECTION', 'redis'),
    'queue' => env('TELESCOPE_QUEUE', 'telescope'),
    'delay' => env('TELESCOPE_QUEUE_DELAY', 10),
],
```

---

## 🎯 Alternatif yang Lebih Baik untuk Production

### 1. **Laravel Pulse** (Recommended)
- Lebih ringan dari Telescope
- Fokus pada metrics & performance
- Built-in untuk production

```bash
composer require laravel/pulse
php artisan pulse:install
```

### 2. **Sentry** (Error Tracking)
- Khusus untuk error monitoring
- Tidak merekam semua request
- Dashboard yang powerful

```bash
composer require sentry/sentry-laravel
```

### 3. **New Relic / DataDog** (APM)
- Application Performance Monitoring
- Minimal overhead
- Production-grade

### 4. **CloudWatch / Stackdriver** (Cloud Native)
- Terintegrasi dengan infrastructure
- Auto-scaling friendly

---

## 📋 Checklist Production Readiness

- [ ] `TELESCOPE_ENABLED=false` di `.env` production
- [ ] Atau install Laravel Pulse sebagai alternatif
- [ ] Setup Sentry untuk error tracking
- [ ] Configure proper logging (`LOG_LEVEL=warning`)
- [ ] Setup log rotation (daily/weekly)
- [ ] Monitor disk space untuk logs
- [ ] Setup alerting untuk critical errors

---

## 🚀 Deployment Script

Tambahkan ke `deploy.sh`:

```bash
# Disable Telescope di production
if [ "$APP_ENV" = "production" ]; then
    echo "🔒 Disabling Telescope for production..."
    php artisan config:cache
    
    # Optional: Clear Telescope data
    php artisan telescope:clear
fi
```

---

## 📊 Perbandingan

| Feature | Telescope | Pulse | Sentry | New Relic |
|---------|-----------|-------|--------|-----------|
| Performance Impact | ⚠️ High | ✅ Low | ✅ Low | ✅ Low |
| Storage Usage | ⚠️ High | ✅ Medium | ✅ None | ✅ None |
| Production Ready | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes |
| Cost | Free | Free | Paid | Paid |
| Setup Complexity | Easy | Easy | Easy | Medium |

---

## 🎓 Kesimpulan

**Untuk Production:**
1. ✅ **DISABLE Telescope** (`TELESCOPE_ENABLED=false`)
2. ✅ Install **Laravel Pulse** untuk monitoring
3. ✅ Setup **Sentry** untuk error tracking
4. ✅ Use proper **logging** dengan rotation

**Untuk Staging/Development:**
- Telescope boleh diaktifkan dengan filter yang ketat
- Gunakan IP whitelist
- Auto-prune data secara berkala
