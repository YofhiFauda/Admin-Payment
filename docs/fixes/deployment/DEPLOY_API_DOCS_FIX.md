# Deploy API Documentation Fix

## Status
✅ User dengan role 'owner' sudah ada: superadmin@whusnet.com
❌ File perubahan belum ter-deploy ke production

## Files yang Perlu Di-Deploy

1. ✅ `app/Http/Middleware/AuthorizeApiDocs.php` - Custom middleware (BARU)
2. ✅ `config/scramble.php` - Updated middleware config
3. ✅ `app/Providers/AppServiceProvider.php` - Gate definition
4. ✅ `bootstrap/app.php` - Middleware registration
5. ✅ `app/Console/Commands/CheckApiDocsAccess.php` - Debug command (BARU)

## Deployment Steps

### Step 1: Commit Changes
```bash
git add .
git commit -m "fix: add custom authorization for API documentation"
```

### Step 2: Push to Repository
```bash
git push origin main
```

### Step 3: Deploy to Production
Jika menggunakan Coolify atau auto-deploy, tunggu deployment selesai.

Jika manual:
```bash
# Pull latest code
cd /path/to/production
git pull origin main

# Install dependencies (if needed)
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
```

### Step 4: Verify Deployment
```bash
# Check if middleware file exists
docker exec whusnet-app test -f app/Http/Middleware/AuthorizeApiDocs.php && echo "✓ Middleware file deployed" || echo "✗ File missing"

# Check config
docker exec whusnet-app php artisan tinker --execute="print_r(config('scramble.middleware'));"
# Should show: AuthorizeApiDocs

# Check command
docker exec whusnet-app php artisan api-docs:check
# Should show user info and status
```

### Step 5: Test Access
1. Login ke aplikasi dengan: **superadmin@whusnet.com**
2. Akses: **https://layer-silver-armstrong-speech.trycloudflare.com/docs/api**
3. Should work! ✅

## Quick Deploy Script

Jika Anda di server production:

```bash
#!/bin/bash
echo "🚀 Deploying API Docs Fix..."

# Pull latest code
git pull origin main

# Clear caches
docker exec whusnet-app php artisan config:clear
docker exec whusnet-app php artisan route:clear  
docker exec whusnet-app php artisan cache:clear

# Verify
echo ""
echo "Verifying deployment..."
docker exec whusnet-app php artisan api-docs:check

echo ""
echo "✅ Deployment complete!"
echo "Now login as superadmin@whusnet.com and visit /docs/api"
```

## Troubleshooting After Deploy

### If Still Getting 403

1. **Clear browser cache and cookies**
   - Or use incognito/private window

2. **Logout and login again**
   - Session might be cached

3. **Verify config is updated**
   ```bash
   docker exec whusnet-app php artisan tinker --execute="print_r(config('scramble.middleware'));"
   ```
   Should show: `App\Http\Middleware\AuthorizeApiDocs`

4. **Check logs**
   ```bash
   docker exec whusnet-app tail -50 storage/logs/laravel.log
   ```

5. **Run debug command**
   ```bash
   docker exec whusnet-app php artisan api-docs:check
   ```

## Alternative: Quick Fix Without Deploy

Jika Anda ingin test dulu tanpa deploy semua file, bisa disable middleware sementara:

```bash
# Edit config di production
docker exec -it whusnet-app bash
nano config/scramble.php

# Change middleware to:
'middleware' => [
    'web',
],

# Save and clear cache
php artisan config:clear

# Test access - should work for everyone (not secure!)
```

⚠️ **WARNING**: Ini akan membuka dokumentasi API ke semua orang. Hanya untuk testing!

## Current User Info

✅ User yang bisa akses setelah deploy:
- **Name**: Super Admin
- **Email**: superadmin@whusnet.com  
- **Role**: owner

## Summary

**Problem**: File perubahan belum ter-deploy ke production container
**Solution**: Commit, push, dan deploy ulang
**After Deploy**: Login sebagai superadmin@whusnet.com → akses /docs/api

**Estimated Time**: 5-10 menit (tergantung deployment method)
