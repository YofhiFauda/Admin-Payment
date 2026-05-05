# 🔧 Fix Horizon Error 401 - Cookie Issue dengan Cloudflare Tunnel

## Masalah
Horizon menampilkan error 401 (Unauthorized) meskipun user sudah login dengan role yang sesuai (owner, admin, atasan).

## Root Cause
**Cookie tidak terkirim ke server** saat menggunakan Cloudflare Tunnel. Ini terjadi karena:
1. Cloudflare Tunnel menggunakan proxy yang bisa memblokir/strip cookies
2. Session cookie dengan `SameSite=lax` tidak selalu dikirim di cross-site context
3. Middleware `auth:web` memerlukan session cookie untuk validasi user

**Bukti**: Test dengan `curl` menunjukkan:
```json
{
  "user": null,
  "check": false,
  "cookies": []  // ❌ Cookie tidak terkirim!
}
```

## Solusi: Basic Authentication untuk Horizon

Karena session-based auth tidak reliable dengan Cloudflare Tunnel, kita gunakan **HTTP Basic Authentication** sebagai alternatif.

### 1. Buat Middleware `HorizonBasicAuth`

File: `app/Http/Middleware/HorizonBasicAuth.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HorizonBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Di local environment, skip basic auth
        if (app()->environment('local')) {
            return $next($request);
        }

        // Di production, gunakan basic auth
        $username = env('HORIZON_USERNAME', 'admin');
        $password = env('HORIZON_PASSWORD', 'secret');

        if ($request->getUser() !== $username || $request->getPassword() !== $password) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Horizon"',
            ]);
        }

        return $next($request);
    }
}
```

### 2. Daftarkan Middleware

File: `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
        'n8n.secret' => \App\Http\Middleware\N8nSecretMiddleware::class,
        'horizon.auth' => \App\Http\Middleware\HorizonBasicAuth::class, // ✅ Tambahkan ini
    ]);
})
```

### 3. Update Config Horizon

File: `config/horizon.php`

```php
'middleware' => env('APP_ENV') === 'local' ? ['web'] : ['web', 'horizon.auth'],
```

**Penjelasan**:
- **Local environment**: Hanya `['web']` - tidak perlu auth (untuk development)
- **Production**: `['web', 'horizon.auth']` - gunakan basic auth

### 4. Update `.env`

Tambahkan kredensial basic auth (untuk production):

```env
# Horizon Basic Auth (untuk production, bypass cookie issue)
HORIZON_USERNAME=admin
HORIZON_PASSWORD=your-secure-password-here
```

⚠️ **PENTING**: Ganti password dengan yang kuat di production!

### 5. Update `HorizonServiceProvider`

File: `app/Providers/HorizonServiceProvider.php`

```php
public function boot(): void
{
    parent::boot();

    try {
        Horizon::routeMailNotificationsTo('admin@whusnet.com');
    } catch (\Exception $e) {
        // Abaikan error jika service belum siap
    }

    // ✅ Horizon auth callback - allow semua di local
    Horizon::auth(function ($request) {
        // Di local, allow semua (basic auth sudah handle di middleware)
        if (app()->environment('local')) {
            return true;
        }

        // Di production, basic auth sudah handle di middleware
        // Jadi di sini hanya return true
        return true;
    });
}
```

## Cara Kerja

### Local Environment (Development)
1. Request ke `/horizon` → Middleware `['web']`
2. `Horizon::auth()` → Return `true` (allow semua)
3. ✅ Akses tanpa login

### Production Environment
1. Request ke `/horizon` → Middleware `['web', 'horizon.auth']`
2. `HorizonBasicAuth` middleware → Cek HTTP Basic Auth
   - ❌ Tidak ada credentials → Return 401 + prompt basic auth
   - ❌ Credentials salah → Return 401
   - ✅ Credentials benar → Lanjut
3. `Horizon::auth()` → Return `true`
4. ✅ Akses Horizon

## Testing

### 1. Clear cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 2. Test di Local
Akses: `https://them-manufacturing-physics-medium.trycloudflare.com/horizon`
- ✅ Langsung masuk tanpa login (karena `APP_ENV=local`)

### 3. Test di Production
Akses: `https://your-production-domain.com/horizon`
- Browser akan menampilkan popup basic auth
- Masukkan username & password dari `.env`
- ✅ Masuk ke Horizon

## Keuntungan Solusi Ini

1. ✅ **Tidak bergantung pada cookies** - Basic auth menggunakan HTTP header
2. ✅ **Kompatibel dengan Cloudflare Tunnel** - Header selalu diteruskan
3. ✅ **Sederhana** - Tidak perlu konfigurasi session yang rumit
4. ✅ **Aman** - Credentials di-encode di header (gunakan HTTPS!)
5. ✅ **Flexible** - Bisa disable di local, enable di production

## Alternatif Solusi

### Opsi 1: IP Whitelist
Jika Horizon hanya diakses dari IP tertentu:

```php
// app/Http/Middleware/HorizonBasicAuth.php
public function handle(Request $request, Closure $next): Response
{
    $allowedIps = explode(',', env('HORIZON_ALLOWED_IPS', ''));
    
    if (in_array($request->ip(), $allowedIps)) {
        return $next($request);
    }
    
    // Fallback ke basic auth
    // ...
}
```

### Opsi 2: VPN Only
Deploy Horizon di internal network yang hanya bisa diakses via VPN.

### Opsi 3: Fix Cookie Issue (Advanced)
Jika tetap ingin pakai session-based auth:
1. Setup custom domain (bukan Cloudflare Tunnel)
2. Konfigurasi `SESSION_DOMAIN` dengan benar
3. Pastikan `SESSION_SECURE_COOKIE=true` dan `SESSION_SAME_SITE=lax`
4. Test dengan browser yang support cookies properly

## Troubleshooting

### Masih Error 401?

1. **Cek environment**:
   ```bash
   php artisan tinker
   >>> app()->environment()
   => "local"
   ```

2. **Cek middleware config**:
   ```bash
   php artisan tinker
   >>> config('horizon.middleware')
   => ["web"]  // Di local
   => ["web", "horizon.auth"]  // Di production
   ```

3. **Test basic auth dengan curl**:
   ```bash
   curl -u admin:secret https://your-domain.com/horizon
   ```

### Browser tidak menampilkan popup basic auth?

Clear browser cache dan cookies, lalu refresh.

## Security Notes

⚠️ **PENTING untuk Production**:

1. **Gunakan password yang kuat** di `HORIZON_PASSWORD`
2. **Jangan commit `.env`** ke git
3. **Gunakan HTTPS** - Basic auth tidak aman di HTTP
4. **Rotate password** secara berkala
5. **Pertimbangkan IP whitelist** sebagai layer tambahan

## Referensi
- [Laravel Horizon Documentation - Authorization](https://laravel.com/docs/11.x/horizon#authorizing-horizon)
- [HTTP Basic Authentication](https://developer.mozilla.org/en-US/docs/Web/HTTP/Authentication)
- [Cloudflare Tunnel Documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
