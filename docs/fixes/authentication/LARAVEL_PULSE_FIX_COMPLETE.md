# 🔧 Laravel Pulse - Dokumentasi Perbaikan Lengkap

**Tanggal:** 16 Mei 2026  
**Project:** WHUSNET Admin Payment  
**Environment:** Docker + Coolify Production  

---

## 📋 Ringkasan Masalah

Laravel Pulse mengalami beberapa error yang mencegah dashboard berfungsi dengan baik:

1. ❌ Error `Undefined array key 'groups'` saat lazy loading komponen
2. ❌ Database schema outdated (missing `count` column dan `key_hash` virtual column)
3. ❌ Livewire masih menggunakan URL Cloudflare lama
4. ❌ Pulse worker crash dengan error database

---

## 🔍 Root Cause Analysis

### 1. Missing `groups` Configuration

**Error Message:**
```
ErrorException
Undefined array key "groups"
vendor/laravel/pulse/src/Livewire/Concerns/HasPeriod.php:20
```

**Root Cause:**
- Trait `HasPeriod` di Laravel Pulse mengharapkan semua recorder memiliki key `groups` dalam konfigurasi
- Beberapa recorder di `config/pulse.php` tidak memiliki key ini
- Error muncul saat komponen di-lazy load (scroll ke bawah)

**Affected Recorders:**
- `SlowOutgoingRequests`
- `SlowQueries`
- `SlowRequests`
- `SlowJobs`
- `UserJobs`
- `UserRequests`

---

### 2. Outdated Database Schema

**Error Messages:**
```
SQLSTATE[HY000]: General error: 1364 Field 'key_hash' doesn't have a default value
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'count' in 'field list'
```

**Root Cause:**
- Migration Pulse yang digunakan tidak sesuai dengan versi terbaru Laravel Pulse
- Kolom `key_hash` seharusnya menggunakan **virtual column** (generated column)
- Kolom `count` hilang di tabel `pulse_aggregates`

**Schema Comparison:**

| Column | Old Migration | New Migration (Official) |
|--------|--------------|--------------------------|
| `key_hash` | `char(16)->nullable()` | `char(16)->virtualAs('unhex(md5(`key`))')` |
| `count` | ❌ Missing | ✅ `unsignedInteger()->nullable()` |

---

### 3. Stale Configuration Cache

**Issue:**
- Container menggunakan `.env.production` bukan `.env`
- Config cache masih menyimpan URL Cloudflare lama
- Container perlu restart untuk reload environment variables

---

## ✅ Solusi yang Diterapkan

### 1. Fix: Menambahkan `groups` Configuration

**File:** `config/pulse.php`

**Perubahan:**

```php
// BEFORE (Missing groups)
\Laravel\Pulse\Recorders\SlowOutgoingRequests::class => [
    'enabled' => env('PULSE_SLOW_OUTGOING_REQUESTS_ENABLED', true),
    'sample_rate' => env('PULSE_SLOW_OUTGOING_REQUESTS_SAMPLE_RATE', 1),
    'threshold' => env('PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD', 1000),
    'ignore' => [
        // '#^http://127\.0\.0\.1:13714#',
    ],
],

// AFTER (With groups)
\Laravel\Pulse\Recorders\SlowOutgoingRequests::class => [
    'enabled' => env('PULSE_SLOW_OUTGOING_REQUESTS_ENABLED', true),
    'sample_rate' => env('PULSE_SLOW_OUTGOING_REQUESTS_SAMPLE_RATE', 1),
    'threshold' => env('PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD', 1000),
    'ignore' => [
        // '#^http://127\.0\.0\.1:13714#',
    ],
    'groups' => [
        // Add URL grouping patterns here if needed
    ],
],
```

**Diterapkan ke semua recorder:**
- ✅ `SlowOutgoingRequests`
- ✅ `SlowQueries`
- ✅ `SlowRequests`
- ✅ `SlowJobs`
- ✅ `UserJobs`
- ✅ `UserRequests`

---

### 2. Fix: Update Database Schema

**File:** `database/migrations/2026_05_04_000001_create_pulse_tables.php`

**Langkah Perbaikan:**

```bash
# 1. Rollback migration lama
docker compose exec app php artisan migrate:rollback --step=1

# 2. Hapus migration lama
rm database/migrations/2026_05_04_000001_create_pulse_tables.php

# 3. Copy migration resmi dari vendor
docker compose exec app cp \
  /var/www/vendor/laravel/pulse/database/migrations/2023_06_07_000001_create_pulse_tables.php \
  /var/www/database/migrations/2026_05_04_000001_create_pulse_tables.php

# 4. Jalankan migration baru
docker compose exec app php artisan migrate

# 5. Clear Pulse data
docker compose exec app php artisan pulse:clear
```

**Schema Baru (Correct):**

```php
Schema::create('pulse_aggregates', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('bucket');
    $table->unsignedMediumInteger('period');
    $table->string('type');
    $table->mediumText('key');
    
    // Virtual column - auto-generated from key
    match ($this->driver()) {
        'mariadb', 'mysql' => $table->char('key_hash', 16)
            ->charset('binary')
            ->virtualAs('unhex(md5(`key`))'),
        'pgsql' => $table->uuid('key_hash')->storedAs('md5("key")::uuid'),
        'sqlite' => $table->string('key_hash'),
    };
    
    $table->string('aggregate');
    $table->decimal('value', 20, 2);
    $table->unsignedInteger('count')->nullable(); // ✅ ADDED
    
    $table->unique(['bucket', 'period', 'type', 'aggregate', 'key_hash']);
    $table->index(['period', 'bucket']);
    $table->index('type');
    $table->index(['period', 'type', 'aggregate', 'bucket']);
});
```

---

### 3. Fix: Update APP_URL Configuration

**File:** `.env.production`

**Perubahan:**

```bash
# BEFORE
APP_URL=https://constitution-behalf-stamps-surgical.trycloudflare.com

# AFTER
APP_URL=https://aus-relatively-mouth-factor.trycloudflare.com
```

**Clear Cache & Restart:**

```bash
# Clear all caches
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan optimize:clear

# Clear sessions
docker compose exec app php artisan tinker --execute="DB::table('sessions')->truncate();"

# Restart containers to reload environment
docker compose down && docker compose up -d
```

---

### 4. Fix: Sync Config to Running Container (Temporary)

**Issue:** Perubahan `config/pulse.php` tidak masuk ke container karena file di-build ke dalam Docker image.

**Temporary Solution:**

```bash
# Copy updated config to running container
docker compose cp config/pulse.php app:/var/www/config/pulse.php

# Clear config cache
docker compose exec app php artisan config:clear

# Restart services
docker compose restart app nginx
```

**Permanent Solution:** Akan otomatis teratasi saat rebuild image di Coolify.

---

## 🧪 Verifikasi Perbaikan

### 1. Verifikasi APP_URL

```bash
docker compose exec app php artisan tinker --execute="echo config('app.url');"
# Output: https://aus-relatively-mouth-factor.trycloudflare.com
```

### 2. Verifikasi Database Schema

```bash
docker compose exec app php artisan tinker --execute="
  echo json_encode(
    DB::connection('pulse')->select('DESCRIBE pulse_aggregates'),
    JSON_PRETTY_PRINT
  );
"
```

**Expected Output:**
```json
[
    {"Field": "id", "Type": "bigint unsigned", ...},
    {"Field": "bucket", "Type": "int unsigned", ...},
    {"Field": "period", "Type": "mediumint unsigned", ...},
    {"Field": "type", "Type": "varchar(255)", ...},
    {"Field": "key", "Type": "mediumtext", ...},
    {"Field": "key_hash", "Type": "char(16)", ...},
    {"Field": "aggregate", "Type": "varchar(255)", ...},
    {"Field": "value", "Type": "decimal(20,2)", ...},
    {"Field": "count", "Type": "int unsigned", ...}  // ✅ Present
]
```

### 3. Verifikasi Pulse Configuration

```bash
docker compose exec app php -r "
  echo substr(file_get_contents('/var/www/config/pulse.php'), -2000);
"
```

**Expected:** Semua recorder memiliki `'groups' => []`

### 4. Test Pulse Dashboard

1. Akses: `https://aus-relatively-mouth-factor.trycloudflare.com/pulse`
2. Scroll ke bawah untuk lazy load komponen
3. ✅ Tidak ada error "Undefined array key 'groups'"
4. ✅ Semua widget (Exceptions, Slow Queries, Slow Requests, dll) berfungsi

---

## 📦 Deployment ke Coolify

### Pre-Deployment Checklist

- ✅ `config/pulse.php` - Updated dengan `groups` configuration
- ✅ `database/migrations/2026_05_04_000001_create_pulse_tables.php` - Schema terbaru
- ✅ `.env.production` - APP_URL sudah benar
- ✅ All changes committed to Git

### Deployment Steps

```bash
# 1. Commit semua perubahan
git add config/pulse.php
git add database/migrations/2026_05_04_000001_create_pulse_tables.php
git add .env.production
git commit -m "fix: Laravel Pulse configuration and database schema

- Add groups configuration to all Pulse recorders
- Update Pulse migration to use official schema with virtual columns
- Fix APP_URL configuration for Cloudflare tunnel
- Resolve 'Undefined array key groups' error on lazy loading"

# 2. Push ke repository
git push origin main

# 3. Deploy di Coolify
# Coolify akan otomatis:
# - Pull code terbaru
# - Build Docker image baru (dengan config yang sudah diperbaiki)
# - Run migrations
# - Deploy containers
```

### Post-Deployment Verification

```bash
# 1. Check Pulse worker status
docker compose ps pulse

# 2. Check Pulse worker logs
docker compose logs pulse --tail=50

# 3. Verify no errors
# Expected: "📊 Starting Laravel Pulse worker (timeout=0)..."
# No error messages about missing columns or undefined keys
```

---

## 🔐 Environment Variables

Pastikan environment variables berikut sudah di-set di Coolify:

```bash
# Pulse Configuration
PULSE_ENABLED=true
PULSE_INGEST_DRIVER=redis
PULSE_STORAGE_DRIVER=database

# Pulse Database Connection
PULSE_DB_CONNECTION=pulse
PULSE_DB_HOST=your-database-host
PULSE_DB_PORT=3306
PULSE_DB_DATABASE=admin_payment
PULSE_DB_USERNAME=your-username
PULSE_DB_PASSWORD=your-password

# Pulse Redis Connection
PULSE_REDIS_CONNECTION=pulse
PULSE_REDIS_HOST=your-redis-host
PULSE_REDIS_PORT=6379
PULSE_REDIS_DB=2

# Pulse Recorders (Optional - defaults to true)
PULSE_CACHE_INTERACTIONS_ENABLED=true
PULSE_EXCEPTIONS_ENABLED=true
PULSE_QUEUES_ENABLED=true
PULSE_SERVERS_ENABLED=true
PULSE_SLOW_JOBS_ENABLED=true
PULSE_SLOW_OUTGOING_REQUESTS_ENABLED=true
PULSE_SLOW_QUERIES_ENABLED=true
PULSE_SLOW_REQUESTS_ENABLED=true
PULSE_USER_JOBS_ENABLED=true
PULSE_USER_REQUESTS_ENABLED=true
```

---

## 🐛 Troubleshooting

### Issue: Error masih muncul setelah deploy

**Solution:**
```bash
# Clear all caches
docker compose exec app php artisan optimize:clear

# Restart all containers
docker compose restart
```

### Issue: Pulse worker terus restart

**Check logs:**
```bash
docker compose logs pulse --tail=100 --follow
```

**Common causes:**
- Database connection error → Check `PULSE_DB_*` env vars
- Redis connection error → Check `PULSE_REDIS_*` env vars
- Migration not run → Run `php artisan migrate`

### Issue: Pulse dashboard kosong (no data)

**Verify Pulse worker is running:**
```bash
docker compose ps pulse
# Status should be "Up" and "healthy"
```

**Check if data is being ingested:**
```bash
docker compose exec app php artisan tinker --execute="
  echo 'Pulse Entries: ' . DB::connection('pulse')->table('pulse_entries')->count() . PHP_EOL;
  echo 'Pulse Aggregates: ' . DB::connection('pulse')->table('pulse_aggregates')->count() . PHP_EOL;
"
```

### Issue: URL masih menggunakan Cloudflare lama

**Solution:**
```bash
# 1. Update .env.production dengan URL baru
# 2. Restart containers
docker compose down && docker compose up -d

# 3. Hard refresh browser (Ctrl+Shift+R)
# 4. Or open in Incognito mode
```

---

## 📊 Monitoring & Maintenance

### Check Pulse Health

```bash
# Check all Pulse-related containers
docker compose ps app horizon pulse scheduler

# Check Pulse worker logs
docker compose logs pulse --tail=50

# Check database size
docker compose exec app php artisan tinker --execute="
  echo 'Pulse Entries: ' . DB::connection('pulse')->table('pulse_entries')->count() . PHP_EOL;
  echo 'Pulse Aggregates: ' . DB::connection('pulse')->table('pulse_aggregates')->count() . PHP_EOL;
  echo 'Pulse Values: ' . DB::connection('pulse')->table('pulse_values')->count() . PHP_EOL;
"
```

### Clear Old Pulse Data

```bash
# Clear all Pulse data (use with caution)
docker compose exec app php artisan pulse:clear

# Pulse automatically trims old data based on config
# Default: keeps 7 days of data
# See config/pulse.php -> ingest.trim.keep
```

---

## 📚 References

- [Laravel Pulse Documentation](https://laravel.com/docs/11.x/pulse)
- [Laravel Pulse GitHub](https://github.com/laravel/pulse)
- [Pulse Migration Schema](https://github.com/laravel/pulse/blob/main/database/migrations/2023_06_07_000001_create_pulse_tables.php)

---

## ✅ Checklist Perbaikan

| No | Item | Status | Notes |
|----|------|--------|-------|
| 1 | Add `groups` to all recorders | ✅ Done | 6 recorders updated |
| 2 | Update Pulse migration schema | ✅ Done | Using official migration |
| 3 | Fix `key_hash` virtual column | ✅ Done | Auto-generated from `key` |
| 4 | Add missing `count` column | ✅ Done | Added to `pulse_aggregates` |
| 5 | Update APP_URL configuration | ✅ Done | Using new Cloudflare URL |
| 6 | Clear all caches | ✅ Done | Config, view, route, optimize |
| 7 | Restart containers | ✅ Done | All services restarted |
| 8 | Verify Pulse dashboard works | ✅ Done | No errors on lazy load |
| 9 | Document all changes | ✅ Done | This document |
| 10 | Ready for Coolify deployment | ✅ Ready | All changes in Git |

---

## 🎉 Hasil Akhir

- ✅ Laravel Pulse dashboard berfungsi dengan baik
- ✅ Tidak ada error "Undefined array key 'groups'"
- ✅ Semua komponen (Cache, Exceptions, Slow Queries, dll) dapat di-lazy load
- ✅ Pulse worker berjalan stabil tanpa crash
- ✅ Database schema sesuai dengan versi terbaru Laravel Pulse
- ✅ Livewire menggunakan URL yang benar
- ✅ Siap untuk production deployment di Coolify

---

**Dokumentasi dibuat oleh:** Kiro AI Assistant  
**Tanggal:** 16 Mei 2026  
**Status:** ✅ Complete & Verified
