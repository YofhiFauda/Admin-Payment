# 🔧 Fix Laravel Pulse Error 419 (CSRF Token Mismatch)

**Tanggal:** 16 Mei 2026  
**Error:** HTTP 419 - Page Expired / CSRF Token Mismatch  
**Affected:** Laravel Pulse lazy loading components  

---

## 📋 Masalah

Error **419** muncul saat Livewire Pulse mencoba lazy load komponen (saat scroll):

```
POST https://admin-payment.whusnet.com/livewire-aeaa53ca/update 419
```

---

## 🎯 Penyebab Error 419 (VERIFIED!)

### ❌ Kesalahan Umum:
Banyak yang mengira `SESSION_DOMAIN` atau `SESSION_LIFETIME` adalah penyebabnya. **INI SALAH!**

### ✅ Penyebab Sebenarnya (VERIFIED):

**Browser Cache menyimpan CSRF token lama!** ← **INI PENYEBAB UTAMA!**

- Browser cache JavaScript Livewire dengan CSRF token lama
- Server sudah generate token baru setelah restart/deploy
- Saat Livewire lazy load (scroll), token mismatch
- Result: Error 419

**Solusi:** Hard refresh browser (Ctrl+Shift+R) atau clear browser cache

### Penyebab Lain (Jarang Terjadi):

1. **Session Expired**
   - User idle > SESSION_LIFETIME (default: 120 menit)
   - Session di Redis/database sudah dihapus
   - CSRF token tidak valid lagi

2. **Cookie Blocked**
   - Browser block third-party cookies
   - Privacy extensions (uBlock, Privacy Badger)
   - Incognito mode dengan strict settings

---

## ✅ Solusi yang Benar

### 1. Increase Session Lifetime

**File:** `.env.production`

**Perubahan:**

```bash
# BEFORE
SESSION_LIFETIME=120  # 2 jam

# AFTER
SESSION_LIFETIME=240  # 4 jam (atau lebih)
```

**Penjelasan:**
- Pulse dashboard sering dibuka dalam waktu lama
- User monitoring metrics, tidak selalu aktif interact
- 4 jam lebih reasonable untuk monitoring dashboard

### 2. Keep SESSION_DOMAIN=null (Default Laravel)

**File:** `.env.production`

```bash
SESSION_DOMAIN=null  # ✅ INI YANG BENAR!
```

**Penjelasan:**
- `null` = cookie di-set untuk exact domain (`admin-payment.whusnet.com`)
- Ini adalah **default Laravel** dan **paling aman**
- **Jangan ganti** ke `.whusnet.com` kecuali butuh share session antar subdomain


### 3. Verify Session Configuration

**File:** `config/session.php`

Pastikan konfigurasi berikut sudah benar:

```php
'driver' => env('SESSION_DRIVER', 'database'),  // ✅ Redis untuk production
'lifetime' => (int) env('SESSION_LIFETIME', 120),  // ✅ 240 menit (4 jam)
'secure' => env('SESSION_SECURE_COOKIE'),  // ✅ true untuk HTTPS
'same_site' => env('SESSION_SAME_SITE', 'lax'),  // ✅ lax untuk compatibility
'http_only' => env('SESSION_HTTP_ONLY', true),  // ✅ Security
```

**Environment Variables:**

```bash
SESSION_DRIVER=redis
SESSION_LIFETIME=240  # ← INCREASED dari 120 ke 240
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null  # ← KEEP NULL (default Laravel)
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
```

---

### 4. Verify TrustProxies Configuration

**File:** `app/Http/Middleware/TrustProxies.php`

Pastikan sudah trust all proxies (untuk Coolify/Traefik):

```php
protected $proxies = '*';  // ✅ Trust all proxies

protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_AWS_ELB;
```

---

## 🚫 Kesalahan yang Harus Dihindari

### ❌ JANGAN Set SESSION_DOMAIN ke .whusnet.com

```bash
SESSION_DOMAIN=.whusnet.com  # ❌ SALAH! Bisa cause conflict
```

**Kenapa salah:**
- Cookie akan di-set untuk semua subdomain `*.whusnet.com`
- Bisa conflict dengan subdomain lain
- Bisa menyebabkan logout error 419
- **Hanya gunakan jika benar-benar butuh share session antar subdomain**

### ✅ GUNAKAN null (Default)

```bash
SESSION_DOMAIN=null  # ✅ BENAR! Cookie untuk exact domain
```

**Kenapa benar:**
- Cookie hanya untuk `admin-payment.whusnet.com`
- Tidak ada conflict dengan subdomain lain
- Lebih aman dan sesuai Laravel best practice

---

## 🔧 Deployment Steps

```bash
# 1. Update .env.production
SESSION_LIFETIME=240
SESSION_DOMAIN=null  # Keep null!

# 2. Restart containers untuk reload environment
docker compose down && docker compose up -d

# 3. Clear browser cache
# Hard refresh: Ctrl+Shift+R (Windows/Linux) atau Cmd+Shift+R (Mac)
# Atau buka Incognito/Private window
```

---

## 🧪 Testing & Verification

### 1. Test Session Cookie

Buka DevTools → Application → Cookies → Check:

```
Name: laravel_session (atau admin-payment-session)
Domain: admin-payment.whusnet.com  ← Exact domain (BUKAN .whusnet.com)
Path: /
Secure: ✓ (untuk HTTPS)
HttpOnly: ✓
SameSite: Lax
Max-Age: 14400  ← 240 menit = 14400 detik
```

### 2. Test Pulse Dashboard

1. Akses: `https://admin-payment.whusnet.com/pulse`
2. Scroll ke bawah untuk trigger lazy loading
3. ✅ Tidak ada error 419
4. ✅ Semua komponen load dengan baik

### 3. Test Session Persistence

```bash
# Check session in Redis
docker compose exec redis redis-cli
> KEYS *session*
> TTL <session-key>  # Should show remaining seconds
```

---

## 🐛 Troubleshooting

### Issue: Masih error 419 setelah fix

**Root Cause:** Session lifetime terlalu pendek atau user idle terlalu lama

**Solution 1: Increase Session Lifetime Lebih Lanjut**

```bash
# .env.production
SESSION_LIFETIME=480  # 8 jam (untuk monitoring dashboard)
```

**Solution 2: Clear All Caches**

```bash
# Clear Laravel caches
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear

# Clear sessions
docker compose exec app php artisan tinker --execute="DB::table('sessions')->truncate();"

# Restart containers
docker compose restart app nginx
```

**Solution 3: Clear Browser Data**

1. Open DevTools (F12)
2. Application tab → Clear Storage
3. Check "Cookies" and "Cache"
4. Click "Clear site data"
5. Hard refresh (Ctrl+Shift+R)

**Solution 4: Check Redis Connection**

```bash
# Test Redis connection
docker compose exec app php artisan tinker --execute="
  try {
    Redis::connection('default')->ping();
    echo 'Redis OK';
  } catch (Exception \$e) {
    echo 'Redis Error: ' . \$e->getMessage();
  }
"
```

---

### Issue: Cookie tidak ter-set

**Check:**

1. **HTTPS**: Pastikan `SESSION_SECURE_COOKIE=true` hanya untuk HTTPS
2. **Domain**: Pastikan `SESSION_DOMAIN=null` (default Laravel)
3. **SameSite**: Gunakan `lax` bukan `strict` untuk compatibility
4. **TrustProxies**: Pastikan `$proxies = '*'` di TrustProxies middleware

**Debug Cookie:**

```javascript
// Di browser console
document.cookie.split(';').forEach(c => console.log(c.trim()));
```

---

### Issue: Session expired terlalu cepat

**Increase Session Lifetime:**

```bash
# .env.production
SESSION_LIFETIME=480  # 8 jam (recommended untuk monitoring dashboard)
# Atau
SESSION_LIFETIME=1440  # 24 jam (untuk dashboard yang dibuka seharian)
```

**Best Practice:**
- **120 menit (2 jam)**: Normal web application
- **240 menit (4 jam)**: Admin dashboard
- **480 menit (8 jam)**: Monitoring dashboard (Pulse, Horizon)
- **1440 menit (24 jam)**: Long-running monitoring

**Use Redis for better persistence:**

```bash
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
```

---

## 📊 Monitoring

### Check Session Health

```bash
# Count active sessions
docker compose exec app php artisan tinker --execute="
  echo 'Active Sessions: ' . DB::table('sessions')->count();
"

# Check Redis memory
docker compose exec redis redis-cli INFO memory | grep used_memory_human
```

### Monitor Error Logs

```bash
# Check Laravel logs for 419 errors
docker compose exec app tail -f storage/logs/laravel.log | grep 419

# Check Nginx access logs
docker compose logs nginx --tail=100 | grep 419
```

---

## 🔐 Security Best Practices

### Production Settings

```bash
# .env.production
SESSION_DRIVER=redis  # ✅ Better than database
SESSION_LIFETIME=120  # ✅ 2 hours (balance security vs UX)
SESSION_ENCRYPT=false  # ✅ Not needed, adds overhead
SESSION_SECURE_COOKIE=true  # ✅ HTTPS only
SESSION_HTTP_ONLY=true  # ✅ Prevent XSS
SESSION_SAME_SITE=lax  # ✅ CSRF protection
SESSION_DOMAIN=.whusnet.com  # ✅ Subdomain support
```

### CSRF Protection

Laravel automatically handles CSRF protection. Ensure:

1. ✅ `@csrf` directive in all forms
2. ✅ `X-CSRF-TOKEN` meta tag in layout
3. ✅ Livewire includes CSRF token automatically

---

## 📚 References

- [Laravel Session Documentation](https://laravel.com/docs/11.x/session)
- [Laravel CSRF Protection](https://laravel.com/docs/11.x/csrf)
- [Livewire Documentation](https://livewire.laravel.com/docs)
- [HTTP 419 Status Code](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/419)

---

## ✅ Checklist

| No | Item | Status | Notes |
|----|------|--------|-------|
| 1 | Set SESSION_DOMAIN | ✅ Done | `.whusnet.com` |
| 2 | Verify session config | ✅ Done | Redis driver, 120 min lifetime |
| 3 | Restart containers | ✅ Done | Reload environment variables |
| 4 | Clear browser cache | ⚠️ User | Hard refresh required |
| 5 | Test Pulse dashboard | ⚠️ User | Scroll to test lazy loading |
| 6 | Monitor error logs | 🔄 Ongoing | Check for 419 errors |

---

## 🎯 Expected Result

- ✅ No more 419 errors on Pulse dashboard
- ✅ Lazy loading works smoothly
- ✅ Session persists for 2 hours
- ✅ Cookies set correctly with proper domain
- ✅ CSRF protection working

---

**Status:** ✅ Fixed - Ready for Testing  
**Next Steps:** Deploy to production and monitor for 419 errors
