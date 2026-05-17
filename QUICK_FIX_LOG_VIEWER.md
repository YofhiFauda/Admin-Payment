# Quick Fix: Log Viewer 403 Error

## 🚀 Quick Deploy (Production/Staging)

### Option 1: Manual Commands
```bash
# 1. Clear cache
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan cache:clear

# 2. Rebuild cache
docker exec admin-payment-app php artisan config:cache

# 3. Test
# Login as owner → Access /log-viewer
```

### Option 2: Using Script (Linux/Mac)
```bash
chmod +x scripts/fix-log-viewer.sh
./scripts/fix-log-viewer.sh
```

### Option 3: Using Script (Windows PowerShell)
```powershell
.\scripts\fix-log-viewer.ps1
```

## 🔍 Quick Test
```bash
# Test Redis
docker exec admin-payment-app php artisan tinker --execute="echo Cache::store('redis')->ping() ? 'OK' : 'FAIL';"

# Check routes
docker exec admin-payment-app php artisan route:list --path=log-viewer

# Check session config
docker exec admin-payment-app php artisan tinker --execute="echo config('session.same_site');"
```

## ✅ What Was Fixed

### 1. Config File: `config/log-viewer.php`
```php
// BEFORE (❌ Wrong)
'api_middleware' => [
    'web',
    'auth',
    \Opcodes\LogViewer\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    \Opcodes\LogViewer\Http\Middleware\AuthorizeLogViewer::class,
],

// AFTER (✅ Correct)
'api_middleware' => [
    'web',
    'auth',
],
```

### 2. Environment Files: `.env` & `.env.production`
```env
# BEFORE (❌ Wrong)
SESSION_ENCRYPT=true
SESSION_SAME_SITE=none

# AFTER (✅ Correct)
SESSION_ENCRYPT=false
SESSION_SAME_SITE=lax
```

## 🐛 Still Having Issues?

### Check 1: User Role
```bash
docker exec admin-payment-app php artisan tinker --execute="
    \$user = App\Models\User::find(YOUR_USER_ID);
    echo \$user->role . PHP_EOL;
    echo \$user->isOwner() ? 'Is Owner' : 'Not Owner';
"
```

### Check 2: Session Cookies
1. Open browser DevTools (F12)
2. Go to Application → Cookies
3. Look for cookie with name like `whusnet-admin-payment-session`
4. Check if it's being sent with requests

### Check 3: Redis Connection
```bash
docker exec admin-payment-app php artisan redis:ping
```

### Check 4: Laravel Logs
```bash
docker exec admin-payment-app tail -f storage/logs/laravel.log
```

## 📋 Checklist

- [ ] Config cache cleared
- [ ] Session config updated (SAME_SITE=lax, ENCRYPT=false)
- [ ] Log-viewer middleware fixed
- [ ] Redis connection working
- [ ] User has owner role
- [ ] Session cookies being sent
- [ ] No 403 errors in browser console

## 🔗 Related Files
- `config/log-viewer.php` - Log viewer configuration
- `.env` - Environment variables (staging)
- `.env.production` - Environment variables (production)
- `app/Http/Middleware/AuthorizeLogViewer.php` - Authorization logic
- `FIX_LOG_VIEWER_403.md` - Detailed documentation

## 💡 Tips

1. **Always clear cache after config changes**
   ```bash
   php artisan config:clear && php artisan config:cache
   ```

2. **Test in incognito mode** to avoid cached cookies

3. **Check container logs** if still having issues:
   ```bash
   docker logs admin-payment-app --tail=100 -f
   ```

4. **Verify environment** is using correct .env file:
   ```bash
   docker exec admin-payment-app php artisan about
   ```

## 🎯 Expected Result

After fix:
- ✅ `/log-viewer` loads without 403 error
- ✅ Log files appear in sidebar
- ✅ Can view log contents
- ✅ No console errors

## 📞 Support

If still having issues after following this guide:
1. Check `FIX_LOG_VIEWER_403.md` for detailed troubleshooting
2. Run `./scripts/test-log-viewer-production.sh` for comprehensive testing
3. Check Laravel logs for specific error messages
