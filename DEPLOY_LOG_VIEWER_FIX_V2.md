# Deploy Log Viewer Fix V2

## Perubahan Terbaru

### 1. Custom Middleware
Dibuat middleware khusus `LogViewerAuth` yang lebih eksplisit dalam menghandle authorization.

**File:** `app/Http/Middleware/LogViewerAuth.php`
- Cek authentication
- Cek role owner
- Return JSON error yang jelas

### 2. Konfigurasi Log Viewer
**File:** `config/log-viewer.php`
- Menggunakan middleware custom `log-viewer.auth`
- Authorization callback return `true` (handled by middleware)

### 3. Bootstrap App
**File:** `bootstrap/app.php`
- Register middleware alias `log-viewer.auth`
- Tambah CSRF exception untuk `log-viewer/api/*`

### 4. Gate Definition
**File:** `app/Providers/AppServiceProvider.php`
- Tambah Gate `viewLogViewer` untuk owner

## Langkah Deploy

### 1. Commit & Push
```bash
git add .
git commit -m "fix: add custom middleware for log-viewer authorization"
git push origin main
```

### 2. Di Server/Container Production
```bash
# Clear all caches
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan cache:clear
docker exec admin-payment-app php artisan route:clear
docker exec admin-payment-app php artisan view:clear

# Rebuild caches
docker exec admin-payment-app php artisan config:cache
docker exec admin-payment-app php artisan route:cache

# Optional: Restart container
docker-compose restart app
```

### 3. Test
1. Login sebagai owner
2. Akses `/log-viewer`
3. Cek browser console - tidak boleh ada error 403
4. Cek Network tab - API calls harus return 200

## Debug Commands

### Test Authentication
```bash
docker exec admin-payment-app php artisan tinker --execute="
    \$user = auth()->user();
    echo 'User: ' . (\$user ? \$user->name : 'Not authenticated') . PHP_EOL;
    echo 'Role: ' . (\$user ? \$user->role : 'N/A') . PHP_EOL;
    echo 'Is Owner: ' . (\$user && \$user->role === 'owner' ? 'Yes' : 'No') . PHP_EOL;
"
```

### Test Middleware
```bash
docker exec admin-payment-app php artisan route:list --path=log-viewer
```

### Check Session
```bash
docker exec admin-payment-app php artisan tinker --execute="
    echo 'Session Driver: ' . config('session.driver') . PHP_EOL;
    echo 'Session Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
    echo 'Session SameSite: ' . config('session.same_site') . PHP_EOL;
"
```

### Test Redis
```bash
docker exec admin-payment-app php artisan redis:ping
```

## Troubleshooting

### Jika masih 403:

1. **Cek user role di database:**
```bash
docker exec admin-payment-app php artisan tinker --execute="
    \$user = App\Models\User::where('email', 'YOUR_EMAIL')->first();
    echo 'Role: ' . \$user->role . PHP_EOL;
"
```

2. **Cek session cookies:**
- Buka DevTools (F12)
- Application → Cookies
- Cari cookie session
- Pastikan cookie terkirim di request

3. **Cek Laravel logs:**
```bash
docker exec admin-payment-app tail -f storage/logs/laravel.log
```

4. **Test manual authorization:**
```bash
docker exec admin-payment-app php artisan tinker --execute="
    \$request = request();
    \$user = auth()->user();
    echo 'Authenticated: ' . (auth()->check() ? 'Yes' : 'No') . PHP_EOL;
    echo 'User Role: ' . (\$user ? \$user->role : 'N/A') . PHP_EOL;
    echo 'Can Access: ' . (\$user && \$user->role === 'owner' ? 'Yes' : 'No') . PHP_EOL;
"
```

### Jika session tidak persist:

1. **Clear browser cookies:**
   - Logout
   - Clear all cookies untuk domain
   - Login kembali

2. **Test Redis connection:**
```bash
docker exec admin-payment-app php artisan tinker --execute="
    Cache::store('redis')->put('test', 'value', 60);
    echo Cache::store('redis')->get('test');
"
```

3. **Check Redis container:**
```bash
docker ps | grep redis
docker logs admin-payment-redis --tail=50
```

## Expected Behavior

### Success Response (200):
```json
{
  "data": [
    {
      "name": "laravel.log",
      "path": "/path/to/laravel.log",
      "size": 12345
    }
  ]
}
```

### Error Response (403):
```json
{
  "error": "Forbidden - Owner access only"
}
```

### Error Response (401):
```json
{
  "error": "Unauthenticated"
}
```

## Files Changed

- ✅ `app/Http/Middleware/LogViewerAuth.php` (NEW)
- ✅ `config/log-viewer.php`
- ✅ `bootstrap/app.php`
- ✅ `app/Providers/AppServiceProvider.php`
- ✅ `.env` (session config)
- ✅ `.env.production` (session config)

## Rollback Plan

Jika masih error, rollback dengan:
```bash
git revert HEAD
git push origin main
```

Atau manual:
1. Hapus middleware `LogViewerAuth`
2. Kembalikan config log-viewer ke default
3. Clear cache
