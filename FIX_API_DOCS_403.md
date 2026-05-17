# Fix API Documentation 403 Forbidden

## Problem
Ketika mengakses `/docs/api` di production, mendapat error **403 Forbidden** karena middleware `RestrictedDocsAccess` memblokir akses.

## Solution
Membuat custom middleware yang hanya mengizinkan user dengan role `owner` untuk mengakses dokumentasi API di production, tapi tetap terbuka di local environment.

## Changes Made

### 1. Created Custom Middleware
**File**: `app/Http/Middleware/AuthorizeApiDocs.php`
- Mengizinkan akses penuh di environment `local`
- Di production, memerlukan authentication dan role `owner`
- Redirect ke login jika belum authenticated
- Memberikan pesan error yang jelas dengan informasi role user

### 2. Updated AppServiceProvider
**File**: `app/Providers/AppServiceProvider.php`
- Menambahkan Gate `viewApiDocs` untuk authorization (optional, untuk konsistensi)

### 3. Updated Scramble Config
**File**: `config/scramble.php`
- Menggunakan middleware custom `AuthorizeApiDocs`
- Middleware stack: `['web', AuthorizeApiDocs::class]`

### 4. Registered Middleware Alias
**File**: `bootstrap/app.php`
- Mendaftarkan alias `api-docs.auth` untuk middleware baru

## How to Access API Documentation

### Local Development
```
http://localhost:8000/docs/api
```
✅ Akses terbuka untuk semua (tidak perlu login)

### Production
```
https://layer-silver-armstrong-speech.trycloudflare.com/docs/api
```
⚠️ **Requirements**:
1. Login terlebih dahulu ke aplikasi
2. User harus memiliki role `owner`
3. Kemudian akses URL di atas

## Debugging Steps

### 1. Run Debug Script
```bash
php scripts/debug-api-docs.php
```

Script ini akan mengecek:
- Environment configuration
- Scramble configuration
- Middleware existence
- Users with owner role
- Routes registration
- Middleware logic

### 2. Check Your User Role
```bash
php artisan tinker
```

```php
// Check your user
$user = User::where('email', 'your@email.com')->first();
echo "Role: " . $user->role;

// Update to owner if needed
$user->role = 'owner';
$user->save();
```

Atau via SQL:
```sql
-- Check role
SELECT id, name, email, role FROM users WHERE email = 'your@email.com';

-- Update to owner
UPDATE users SET role = 'owner' WHERE email = 'your@email.com';
```

### 3. Clear All Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

### 4. Check Logs
```bash
# Linux/Mac
tail -f storage/logs/laravel.log

# Windows
Get-Content storage/logs/laravel.log -Tail 50 -Wait
```

## Common Issues & Solutions

### Issue 1: "Hanya owner yang dapat mengakses dokumentasi API"
**Cause**: User role bukan `owner`

**Solution**:
```bash
php artisan tinker
```
```php
$user = User::where('email', 'your@email.com')->first();
$user->role = 'owner';
$user->save();
```

### Issue 2: Redirect ke login terus-menerus
**Cause**: Session tidak tersimpan atau middleware auth tidak bekerja

**Solution**:
1. Check `.env.production`:
   ```env
   SESSION_DRIVER=redis
   SESSION_SECURE_COOKIE=true
   SESSION_SAME_SITE=lax
   ```

2. Clear session:
   ```bash
   php artisan session:clear
   redis-cli FLUSHDB
   ```

3. Login ulang

### Issue 3: Route tidak ditemukan (404)
**Cause**: Scramble tidak terinstall atau routes tidak terdaftar

**Solution**:
```bash
# Check if Scramble is installed
composer show | grep scramble

# If not installed
composer require dedoc/scramble

# Clear cache
php artisan config:clear
php artisan route:clear

# Check routes
php artisan route:list --path=docs
```

### Issue 4: Middleware tidak berjalan
**Cause**: Middleware tidak terdaftar atau cache belum di-clear

**Solution**:
```bash
# Clear all caches
php artisan optimize:clear

# Verify middleware is registered
php artisan route:list --path=docs

# Should show: web, App\Http\Middleware\AuthorizeApiDocs
```

## Testing

### Test 1: Local Environment
```bash
# Should return 200 OK (no authentication required)
curl http://localhost:8000/docs/api
```

### Test 2: Production (Not Logged In)
```bash
# Should redirect to login
curl -I https://layer-silver-armstrong-speech.trycloudflare.com/docs/api
```

### Test 3: Production (Logged In as Owner)
1. Login ke aplikasi dengan user role `owner`
2. Akses: `https://layer-silver-armstrong-speech.trycloudflare.com/docs/api`
3. Should display API documentation

### Test 4: Production (Logged In as Non-Owner)
1. Login dengan user role `admin` atau `user`
2. Akses: `https://layer-silver-armstrong-speech.trycloudflare.com/docs/api`
3. Should return 403 with message showing your current role

## Deployment Checklist

- [ ] Commit all changes
- [ ] Push to production
- [ ] Run cache clear commands
- [ ] Verify at least one user has role `owner`
- [ ] Test login with owner account
- [ ] Test access to `/docs/api`
- [ ] Check logs for any errors

```bash
# Deployment commands
git add .
git commit -m "fix: add custom authorization for API documentation"
git push origin main

# On production server
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Verify owner users exist
php artisan tinker --execute="User::where('role', 'owner')->get(['name', 'email', 'role']);"
```

## Alternative Configurations

### Option 1: Allow All Authenticated Users
Edit `app/Http/Middleware/AuthorizeApiDocs.php`:
```php
public function handle(Request $request, Closure $next): Response
{
    if (app()->environment('local')) {
        return $next($request);
    }

    if (!auth()->check()) {
        return redirect()->route('login')
            ->with('error', 'Silakan login terlebih dahulu.');
    }

    // Remove role check - allow all authenticated users
    return $next($request);
}
```

### Option 2: Allow Multiple Roles
Edit `app/Http/Middleware/AuthorizeApiDocs.php`:
```php
public function handle(Request $request, Closure $next): Response
{
    if (app()->environment('local')) {
        return $next($request);
    }

    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();
    $allowedRoles = ['owner', 'admin']; // Add more roles
    
    if (!in_array($user->role, $allowedRoles)) {
        abort(403, 'Akses ditolak. Role yang diizinkan: ' . implode(', ', $allowedRoles));
    }

    return $next($request);
}
```

### Option 3: Public Access (NOT RECOMMENDED)
⚠️ **Tidak disarankan untuk production**

Edit `config/scramble.php`:
```php
'middleware' => [
    'web',
    // Remove AuthorizeApiDocs::class
],
```

## Export OpenAPI Specification

Alternative untuk membagikan dokumentasi tanpa membuka akses web:

```bash
# Export to api.json
php artisan scramble:export

# File location: api.json (root directory)
```

Bisa dibuka dengan:
- Swagger UI: https://editor.swagger.io/
- Postman: Import → OpenAPI
- Insomnia: Import → OpenAPI
- Stoplight: https://stoplight.io/

## Security Best Practices

✅ **Recommended**:
- API documentation hanya untuk owner/admin di production
- Menggunakan middleware untuk authorization
- Pesan error yang informatif untuk debugging
- Akses terbuka di local untuk development
- Regular audit user roles

⚠️ **Avoid**:
- Membuka dokumentasi API ke publik di production
- Hardcoding credentials
- Menonaktifkan authentication
- Menggunakan role check tanpa authentication check

## Related Files
- `app/Http/Middleware/AuthorizeApiDocs.php` - Custom middleware
- `app/Providers/AppServiceProvider.php` - Gate definition
- `config/scramble.php` - Scramble configuration
- `bootstrap/app.php` - Middleware registration
- `scripts/debug-api-docs.php` - Debug script
- `scripts/test-api-docs-access.sh` - Test script

## Support

Jika masih mengalami masalah:
1. Run debug script: `php scripts/debug-api-docs.php`
2. Check logs: `tail -f storage/logs/laravel.log`
3. Verify user role in database
4. Clear all caches
5. Test in local environment first
