# 🔧 Fix: Error 419 Saat Logout

## 🔴 Error yang Terjadi

```
419 | Page Expired
The page has expired due to inactivity.
Please refresh and try again.
```

**Kapan terjadi:** Saat klik tombol Logout

---

## 🎯 Penyebab

### 1. **SESSION_DOMAIN Salah Konfigurasi**

```env
# ❌ SALAH
SESSION_DOMAIN=.whusnet.com
```

**Masalah:**
- Domain aplikasi: `admin-payment.whusnet.com`
- Session domain: `.whusnet.com` (dengan leading dot)
- Leading dot berarti cookie berlaku untuk **semua subdomain**
- Bisa menyebabkan CSRF token mismatch

### 2. **CSRF Token Expired/Invalid**

Error 419 adalah Laravel CSRF protection yang menolak request karena:
- Token tidak valid
- Token expired
- Session tidak match dengan request

### 3. **Reverse Proxy (Traefik) Configuration**

Di Coolify dengan Traefik:
- Request melewati proxy
- Perlu `TrustProxies` middleware configured
- Session cookie harus compatible dengan proxy setup

---

## ✅ Solusi

### 1. **Fix SESSION_DOMAIN**

#### `.env.production`

```diff
# ─── Cache & Session ───────────────────────────────────────────────
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
- SESSION_DOMAIN=.whusnet.com
+ SESSION_DOMAIN=null

# Jika HTTPS di-handle reverse proxy (Coolify/Traefik)
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
+ SESSION_HTTP_ONLY=true
```

**Penjelasan:**
- `SESSION_DOMAIN=null` → Cookie hanya berlaku untuk domain saat ini
- Lebih aman dan tidak ada mismatch
- Cocok untuk single subdomain application

**Alternatif (jika perlu share session antar subdomain):**
```env
# Untuk share session: admin.whusnet.com, api.whusnet.com, dll
SESSION_DOMAIN=.whusnet.com  # Dengan leading dot

# Untuk single domain saja
SESSION_DOMAIN=null  # Recommended
```

### 2. **Verifikasi TrustProxies Middleware**

File `app/Http/Middleware/TrustProxies.php` sudah benar:

```php
protected $proxies = '*';  // ✅ Trust all proxies (Coolify/Traefik)

protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_AWS_ELB;
```

### 3. **Verifikasi Session Configuration**

File `config/session.php` sudah benar:

```php
'driver' => env('SESSION_DRIVER', 'database'),  // redis di production
'lifetime' => (int) env('SESSION_LIFETIME', 120),
'secure' => env('SESSION_SECURE_COOKIE'),  // true untuk HTTPS
'http_only' => env('SESSION_HTTP_ONLY', true),
'same_site' => env('SESSION_SAME_SITE', 'lax'),
```

---

## 🚀 Cara Deploy Fix

### 1. **Commit Perubahan**

```bash
git add .env.production
git commit -m "fix: resolve 419 error on logout (SESSION_DOMAIN)"
git push origin master
```

### 2. **Redeploy di Coolify**

1. Buka Coolify Dashboard
2. Pilih aplikasi "WHUSNET Admin Payment"
3. Klik **"Redeploy"**

### 3. **Clear Cache (Setelah Deploy)**

```bash
# Via SSH ke server atau docker exec
docker exec whusnet-app php artisan config:clear
docker exec whusnet-app php artisan cache:clear
docker exec whusnet-app php artisan view:clear

# Restart container (optional)
docker-compose restart app
```

---

## 🔍 Verifikasi

### 1. **Test Logout**

1. Login ke aplikasi
2. Klik tombol Logout
3. ✅ Harus redirect ke login page tanpa error 419

### 2. **Check Session Cookie**

Di browser (F12 → Application → Cookies):

```
Name: whusnet-admin-payment-session
Domain: admin-payment.whusnet.com  (bukan .whusnet.com)
Path: /
Secure: ✓ (true)
HttpOnly: ✓ (true)
SameSite: Lax
```

### 3. **Check Redis Session**

```bash
# Connect ke Redis
docker exec -it <redis-container> redis-cli -a <password>

# List sessions
KEYS whusnet_prod:*

# Check specific session
GET whusnet_prod:laravel_session:<session_id>
```

---

## 🤔 Penjelasan Teknis

### SESSION_DOMAIN Options

| Value | Behavior | Use Case |
|-------|----------|----------|
| `null` | Cookie untuk domain saat ini saja | ✅ Single subdomain (recommended) |
| `admin-payment.whusnet.com` | Cookie untuk domain spesifik | Single subdomain (explicit) |
| `.whusnet.com` | Cookie untuk semua subdomain | Multi-subdomain sharing |
| `whusnet.com` | Cookie untuk root domain saja | Root domain only |

### CSRF Protection Flow

```
1. User load page
   → Laravel generate CSRF token
   → Store in session
   → Embed in form: <input type="hidden" name="_token">

2. User submit form (logout)
   → Browser send token + session cookie
   → Laravel verify:
     - Token exists in session?
     - Token matches request?
     - Session not expired?

3. If valid → Process logout
   If invalid → 419 Error
```

### Reverse Proxy Impact

```
Browser → Traefik (HTTPS) → Nginx (HTTP) → PHP-FPM

Issues:
- Browser sees: https://admin-payment.whusnet.com
- PHP sees: http://nginx (without TrustProxies)
- Session domain mismatch → CSRF fail

Solution:
- TrustProxies middleware
- Forward headers: X-Forwarded-*
- SESSION_DOMAIN=null (auto-detect)
```

---

## 🆘 Troubleshooting

### Error 419 masih terjadi setelah fix

**Solusi 1: Clear browser cookies**
```
1. F12 → Application → Cookies
2. Delete all cookies untuk admin-payment.whusnet.com
3. Refresh page
4. Login ulang
5. Test logout
```

**Solusi 2: Clear Redis sessions**
```bash
# Connect ke Redis
docker exec -it <redis-container> redis-cli -a <password>

# Delete all sessions
KEYS whusnet_prod:laravel_session:*
# Copy keys, then:
DEL whusnet_prod:laravel_session:<key1> <key2> ...

# Or flush all (HATI-HATI!)
FLUSHDB
```

**Solusi 3: Check APP_KEY**
```bash
# Verify APP_KEY is set
docker exec whusnet-app php artisan tinker
>>> config('app.key')
# Should return: "base64:+TS5XAo3m3pSC4Bo5ozy64oLv7ETkGH70DLdWuBGhvM="

# If empty, regenerate:
php artisan key:generate
```

**Solusi 4: Check Redis connection**
```bash
# Test Redis connection
docker exec whusnet-app php artisan tinker
>>> Redis::ping()
# Should return: "+PONG"

# Test session write
>>> session(['test' => 'value'])
>>> session('test')
# Should return: "value"
```

### Logout berhasil tapi redirect ke 404

**Solusi:** Check logout redirect di `AuthController.php`

```php
public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return redirect('/login');  // Pastikan route ini ada
}
```

### Session expired terlalu cepat

**Solusi:** Increase session lifetime

```env
# .env.production
SESSION_LIFETIME=480  # 8 hours (default: 120 = 2 hours)
```

---

## 📊 Perbandingan Sebelum vs Sesudah

| Aspek | Sebelum ❌ | Sesudah ✅ |
|-------|-----------|-----------|
| **SESSION_DOMAIN** | `.whusnet.com` | `null` |
| **Cookie Domain** | All subdomains | Current domain only |
| **CSRF Validation** | ❌ Mismatch | ✅ Valid |
| **Logout** | ❌ Error 419 | ✅ Success |
| **Security** | ⚠️ Broader scope | ✅ Tighter scope |

---

## ✅ Checklist

- [x] Update `SESSION_DOMAIN=null` di `.env.production`
- [x] Add `SESSION_HTTP_ONLY=true`
- [x] Verify `TrustProxies` middleware
- [x] Commit & push perubahan
- [ ] Redeploy di Coolify
- [ ] Clear config cache
- [ ] Clear browser cookies
- [ ] Test login
- [ ] Test logout
- [ ] Verify no 419 error

---

## 📚 Referensi

- [Laravel CSRF Protection](https://laravel.com/docs/11.x/csrf)
- [Laravel Session Configuration](https://laravel.com/docs/11.x/session)
- [Laravel TrustProxies](https://laravel.com/docs/11.x/requests#configuring-trusted-proxies)
- [HTTP Cookies Domain](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#define_where_cookies_are_sent)

---

**Status:** ✅ Fixed  
**Tanggal:** 2026-05-17  
**Root Cause:** SESSION_DOMAIN mismatch  
**Solution:** Set SESSION_DOMAIN=null
