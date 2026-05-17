# 🔧 Fix: Pulse Error 419

## 🔴 Masalah

**Error 419 di Pulse Dashboard** (`/pulse`)

```
419 | Page Expired
The page has expired due to inactivity.
Please refresh and try again.
```

---

## 🎯 Penyebab

### ✅ Ya, `SESSION_DOMAIN` yang salah **JUGA menyebabkan Pulse error 419**!

**Alasan yang sama dengan logout error:**

1. **SESSION_DOMAIN mismatch**
   ```env
   SESSION_DOMAIN=.whusnet.com  # ❌ Mismatch
   ```

2. **Pulse menggunakan session yang sama** dengan aplikasi utama
   - Pulse middleware: `['web', Authorize::class]`
   - Middleware `web` include session & CSRF protection
   - CSRF token validation gagal karena session domain salah

3. **Pulse dashboard interactive**
   - Pulse dashboard melakukan AJAX requests
   - Setiap request butuh CSRF token valid
   - Token tidak valid → 419 error

---

## ✅ Solusi

### 1. **Fix SESSION_DOMAIN** (Sudah Dilakukan)

```diff
# .env.production
- SESSION_DOMAIN=.whusnet.com
+ SESSION_DOMAIN=null
+ SESSION_HTTP_ONLY=true
```

**Ini sudah cukup untuk fix Pulse 419!** ✅

---

## 🔍 Verifikasi Setup Pulse

### 1. **Check Konfigurasi Pulse** ✅

File `config/pulse.php` sudah benar:

```php
'middleware' => [
    'web',  // ✅ Include session & CSRF
    Authorize::class,  // ✅ Authorization
],

'enabled' => env('PULSE_ENABLED', false),  // ✅ Controlled by env
'path' => env('PULSE_PATH', 'pulse'),  // ✅ /pulse
```

### 2. **Check Authorization** ✅

File `app/Providers/PulseServiceProvider.php` sudah benar:

```php
Gate::define('viewPulse', function ($user) {
    return in_array($user->role, ['owner', 'atasan', 'admin']);
});
```

### 3. **Check Environment Variables** ✅

File `.env.production` sudah benar:

```env
PULSE_ENABLED=true
PULSE_PATH=pulse
PULSE_STORAGE_DRIVER=database
PULSE_INGEST_DRIVER=redis
PULSE_REDIS_CONNECTION=pulse
PULSE_DB_CONNECTION=pulse
```

### 4. **Check Pulse Container** ⚠️ PERLU DICEK!

```bash
# Check apakah pulse container running
docker ps | grep pulse

# Expected output:
# whusnet-pulse   Up X minutes
```

**Jika tidak running:**
```bash
# Check logs
docker logs whusnet-pulse

# Restart
docker-compose restart pulse
```

---

## 🚀 Setup yang Perlu Dilakukan di Server

### 1. **Redeploy dengan SESSION_DOMAIN Fix**

```bash
# Commit perubahan
git add .env.production
git commit -m "fix: set SESSION_DOMAIN=null for Pulse & logout"
git push origin master

# Redeploy di Coolify
# → Applications → WHUSNET Admin Payment → Redeploy
```

### 2. **Verify Pulse Container Running**

```bash
# SSH ke server
ssh user@your-server

# Check containers
docker ps | grep whusnet

# Should see:
# whusnet-app        Up
# whusnet-nginx      Up
# whusnet-reverb     Up
# whusnet-horizon    Up
# whusnet-scheduler  Up
# whusnet-pulse      Up  ← PENTING!
```

### 3. **Check Pulse Worker Logs**

```bash
# Check pulse container logs
docker logs whusnet-pulse --tail 50

# Should see:
# [2026-05-17 15:00:00] Processing...
# [2026-05-17 15:00:01] Ingesting entries...
```

**Jika error:**
```bash
# Common errors:
# - Redis connection failed
# - Database connection failed
# - Permission denied

# Fix: Restart pulse container
docker-compose restart pulse
```

### 4. **Clear Cache & Config**

```bash
# Clear application cache
docker exec whusnet-app php artisan config:clear
docker exec whusnet-app php artisan cache:clear
docker exec whusnet-app php artisan view:clear

# Restart app container
docker-compose restart app
```

### 5. **Test Pulse Dashboard**

```bash
# Via browser
https://admin-payment.whusnet.com/pulse

# Should see:
# ✅ Dashboard loads
# ✅ No 419 error
# ✅ Metrics displayed
# ✅ Real-time updates working
```

---

## 🔧 Additional Setup (Jika Masih Error)

### 1. **Check Pulse Redis Connection**

```bash
# Connect to Redis
docker exec -it <redis-container> redis-cli -a <password>

# Switch to Pulse Redis DB (index 2)
SELECT 2

# Check keys
KEYS *

# Should see pulse entries:
# laravel_pulse:*
```

**Jika tidak ada keys:**
```bash
# Check Pulse Redis config
docker exec whusnet-app php artisan tinker
>>> config('database.redis.pulse')

# Should return:
# [
#   'host' => 'jcai2jxhrhoei39qkmyf89k6',
#   'port' => 6379,
#   'database' => 2,
#   ...
# ]
```

### 2. **Check Pulse Database Tables**

```bash
# Connect to database
docker exec whusnet-app php artisan tinker
>>> DB::connection('pulse')->table('pulse_entries')->count()

# Should return number > 0
```

**Jika table tidak ada:**
```bash
# Run migrations
docker exec whusnet-app php artisan migrate --database=pulse
```

### 3. **Restart Pulse Worker**

```bash
# Restart pulse container
docker-compose restart pulse

# Check logs
docker logs whusnet-pulse --tail 50 -f

# Should see:
# INFO  Processing entries...
```

---

## 📊 Checklist Setup Pulse

### Environment Variables ✅
- [x] `PULSE_ENABLED=true`
- [x] `PULSE_PATH=pulse`
- [x] `PULSE_STORAGE_DRIVER=database`
- [x] `PULSE_INGEST_DRIVER=redis`
- [x] `PULSE_REDIS_CONNECTION=pulse`
- [x] `PULSE_DB_CONNECTION=pulse`
- [x] `SESSION_DOMAIN=null` ← **PENTING!**

### Configuration Files ✅
- [x] `config/pulse.php` configured
- [x] `app/Providers/PulseServiceProvider.php` with Gate
- [x] `config/database.php` has pulse connections

### Containers ⚠️ PERLU DICEK
- [ ] `whusnet-pulse` container running
- [ ] Pulse worker processing entries
- [ ] No errors in logs

### Database & Redis ⚠️ PERLU DICEK
- [ ] Pulse tables exist
- [ ] Pulse Redis connection working
- [ ] Entries being stored

### Access & Authorization ⚠️ PERLU DICEK
- [ ] User has role: owner/atasan/admin
- [ ] Can access `/pulse` without 403
- [ ] No 419 error on dashboard

---

## 🆘 Troubleshooting

### Error 419 masih terjadi setelah fix SESSION_DOMAIN

**Solusi 1: Clear browser cookies**
```
1. F12 → Application → Cookies
2. Delete all cookies
3. Refresh page
4. Login ulang
5. Access /pulse
```

**Solusi 2: Check user role**
```bash
docker exec whusnet-app php artisan tinker
>>> $user = auth()->user()
>>> $user->role
# Should return: 'owner', 'atasan', or 'admin'
```

**Solusi 3: Restart all containers**
```bash
docker-compose restart
```

### Pulse dashboard blank (no data)

**Solusi 1: Check pulse worker**
```bash
docker logs whusnet-pulse --tail 50

# Should see processing logs
# If not, restart:
docker-compose restart pulse
```

**Solusi 2: Generate some traffic**
```bash
# Visit some pages to generate metrics
curl https://admin-payment.whusnet.com/dashboard
curl https://admin-payment.whusnet.com/transactions

# Wait 10-30 seconds
# Refresh /pulse dashboard
```

**Solusi 3: Check Redis connection**
```bash
docker exec whusnet-app php artisan tinker
>>> Redis::connection('pulse')->ping()
# Should return: "+PONG"
```

### Error 403 Forbidden on /pulse

**Solusi: Check user role**
```bash
# Update user role
docker exec whusnet-app php artisan tinker
>>> $user = User::find(1)
>>> $user->role = 'owner'
>>> $user->save()
```

---

## 📝 Kesimpulan

### ❓ Apakah SESSION_DOMAIN menyebabkan Pulse error 419?

**Jawaban: YA** ✅

**Alasan:**
- Pulse menggunakan middleware `web` (include session & CSRF)
- CSRF token validation gagal karena SESSION_DOMAIN mismatch
- Fix SESSION_DOMAIN → Fix Pulse 419

### ❓ Apakah perlu setup lain di container?

**Jawaban: Perlu verifikasi** ⚠️

**Yang perlu dicek:**
1. ✅ Pulse container running
2. ✅ Pulse worker processing entries
3. ✅ Redis connection working
4. ✅ Database tables exist
5. ✅ User has correct role

**Cara cek:**
```bash
# 1. Check containers
docker ps | grep pulse

# 2. Check logs
docker logs whusnet-pulse --tail 50

# 3. Test Redis
docker exec whusnet-app php artisan tinker
>>> Redis::connection('pulse')->ping()

# 4. Test database
>>> DB::connection('pulse')->table('pulse_entries')->count()

# 5. Check user role
>>> auth()->user()->role
```

---

## 🎯 Action Items

### Immediate (Sudah Dilakukan)
- [x] Fix `SESSION_DOMAIN=null`
- [x] Add `SESSION_HTTP_ONLY=true`

### Deploy
- [ ] Commit & push perubahan
- [ ] Redeploy di Coolify
- [ ] Clear cache: `php artisan config:clear`

### Verify
- [ ] Check pulse container: `docker ps | grep pulse`
- [ ] Check pulse logs: `docker logs whusnet-pulse`
- [ ] Test Pulse dashboard: `/pulse`
- [ ] Verify no 419 error
- [ ] Verify metrics displayed

---

**Status:** ✅ Fix ready (SESSION_DOMAIN)  
**Additional Setup:** ⚠️ Perlu verifikasi container & connections  
**Tanggal:** 2026-05-17
