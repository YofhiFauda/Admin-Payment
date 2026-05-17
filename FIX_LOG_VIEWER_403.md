# Fix Log Viewer 403 Error

## Masalah
Log Viewer menampilkan error 403 saat mengakses `/log-viewer` dengan error di console:
```
log-viewer/api/folders?direction=desc:1  Failed to load resource: the server responded with a status of 403 ()
```

## Penyebab
1. **Middleware API yang salah** di `config/log-viewer.php` menggunakan middleware dari package yang tidak ada:
   - `\Opcodes\LogViewer\Http\Middleware\EnsureFrontendRequestsAreStateful::class`
   - `\Opcodes\LogViewer\Http\Middleware\AuthorizeLogViewer::class`

2. **Session configuration** yang tidak kompatibel dengan API routes:
   - `SESSION_SAME_SITE=none` menyebabkan masalah dengan cookie session di API routes
   - `SESSION_ENCRYPT=true` bisa menyebabkan masalah dengan session validation

## Solusi

### 1. Perbaiki `config/log-viewer.php`
Ubah `api_middleware` dari:
```php
'api_middleware' => [
    'web',
    'auth',
    \Opcodes\LogViewer\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    \Opcodes\LogViewer\Http\Middleware\AuthorizeLogViewer::class,
],
```

Menjadi:
```php
'api_middleware' => [
    'web',
    'auth',
],
```

### 2. Perbaiki Session Configuration di `.env` dan `.env.production`
Ubah dari:
```env
SESSION_ENCRYPT=true
SESSION_SAME_SITE=none
```

Menjadi:
```env
SESSION_ENCRYPT=false
SESSION_SAME_SITE=lax
```

## Langkah Deploy ke Production

### 1. Clear Cache di Container
```bash
# Masuk ke container app
docker exec -it <container_name> bash

# Clear semua cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
```

### 2. Restart Container (Opsional)
Jika masih ada masalah, restart container:
```bash
docker-compose restart app
```

### 3. Test Log Viewer
1. Login sebagai user dengan role `owner`
2. Akses `/log-viewer`
3. Pastikan tidak ada error 403 di console
4. Pastikan file log muncul di sidebar

## Verifikasi

### Cek Authorization
Authorization log-viewer menggunakan `AuthorizeLogViewer` middleware yang memeriksa:
- User sudah login (`auth` middleware)
- User memiliki role `owner` (method `isOwner()`)

### Cek Session
Pastikan session berfungsi dengan baik:
```bash
# Di container
php artisan tinker

# Test session
>>> session()->put('test', 'value');
>>> session()->get('test');
=> "value"

# Test Redis connection
>>> Cache::store('redis')->get('test');
```

### Cek User Authentication
```bash
# Di container
php artisan tinker

# Test user
>>> $user = User::where('role', 'owner')->first();
>>> $user->isOwner();
=> true
```

## Catatan Penting

1. **Session Driver**: Menggunakan Redis untuk session agar stabil di multi-container environment
2. **SESSION_SECURE_COOKIE**: Tetap `true` karena menggunakan HTTPS (Cloudflare Tunnel)
3. **SESSION_SAME_SITE**: Menggunakan `lax` untuk kompatibilitas dengan API routes
4. **TRUSTED_PROXIES**: Sudah dikonfigurasi `*` untuk trust semua proxy (Cloudflare Tunnel)

## Troubleshooting

### Jika masih error 403:
1. Pastikan user yang login memiliki role `owner`
2. Cek Redis connection: `php artisan redis:ping`
3. Cek session di browser (Developer Tools > Application > Cookies)
4. Cek log Laravel: `tail -f storage/logs/laravel.log`

### Jika session tidak persist:
1. Pastikan Redis berjalan: `docker ps | grep redis`
2. Cek konfigurasi Redis di `.env`
3. Test Redis connection: `php artisan tinker` → `Cache::store('redis')->ping()`

### Jika cookie tidak terkirim:
1. Pastikan `SESSION_SECURE_COOKIE=true` untuk HTTPS
2. Pastikan `SESSION_SAME_SITE=lax` (bukan `none` atau `strict`)
3. Cek cookie di browser Developer Tools

## File yang Diubah
- ✅ `config/log-viewer.php` - Perbaiki api_middleware
- ✅ `.env` - Perbaiki session configuration
- ✅ `.env.production` - Perbaiki session configuration

## Referensi
- [Laravel Log Viewer Documentation](https://log-viewer.opcodes.io/)
- [Laravel Session Configuration](https://laravel.com/docs/11.x/session)
- [SameSite Cookie Attribute](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value)
