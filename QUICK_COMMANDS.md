# Quick Commands - Log Viewer Fix

## 🚀 Deploy Now (Copy & Paste)

### Windows PowerShell
```powershell
# Clear caches
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan cache:clear
docker exec admin-payment-app php artisan route:clear
docker exec admin-payment-app php artisan view:clear

# Rebuild caches
docker exec admin-payment-app php artisan config:cache
docker exec admin-payment-app php artisan route:cache

# Test
Write-Host "✓ Caches cleared and rebuilt" -ForegroundColor Green
Write-Host "Now login as owner and access /log-viewer" -ForegroundColor Yellow
```

### Linux/Mac Bash
```bash
# Clear caches
docker exec admin-payment-app php artisan config:clear && \
docker exec admin-payment-app php artisan cache:clear && \
docker exec admin-payment-app php artisan route:clear && \
docker exec admin-payment-app php artisan view:clear && \
docker exec admin-payment-app php artisan config:cache && \
docker exec admin-payment-app php artisan route:cache && \
echo "✓ Caches cleared and rebuilt" && \
echo "Now login as owner and access /log-viewer"
```

## 🧪 Quick Test (Copy & Paste)

### Test 1: Check Configuration
```bash
docker exec admin-payment-app php artisan tinker --execute="
echo '=== Session Config ===' . PHP_EOL;
echo 'Driver: ' . config('session.driver') . PHP_EOL;
echo 'Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
echo 'SameSite: ' . config('session.same_site') . PHP_EOL;
echo PHP_EOL;
echo '=== Log Viewer Config ===' . PHP_EOL;
echo 'Middleware: ' . implode(', ', config('log-viewer.middleware')) . PHP_EOL;
echo 'API Middleware: ' . implode(', ', config('log-viewer.api_middleware')) . PHP_EOL;
"
```

### Test 2: Check Owner User
```bash
docker exec admin-payment-app php artisan tinker --execute="
\$owners = App\Models\User::where('role', 'owner')->get(['name', 'email', 'role']);
echo 'Owner Users: ' . \$owners->count() . PHP_EOL;
foreach (\$owners as \$owner) {
    echo '  - ' . \$owner->name . ' (' . \$owner->email . ')' . PHP_EOL;
}
"
```

### Test 3: Check Redis
```bash
docker exec admin-payment-app php artisan redis:ping
```

### Test 4: Check Routes
```bash
docker exec admin-payment-app php artisan route:list --path=log-viewer
```

### Test 5: Check Log Files
```bash
docker exec admin-payment-app ls -lh storage/logs/
```

## 🐛 Debug Commands

### If Still 403 - Check Authentication
```bash
docker exec admin-payment-app php artisan tinker --execute="
echo 'Checking authentication...' . PHP_EOL;
\$user = App\Models\User::where('role', 'owner')->first();
if (\$user) {
    echo 'Owner user found: ' . \$user->name . PHP_EOL;
    echo 'Email: ' . \$user->email . PHP_EOL;
    echo 'Role: ' . \$user->role . PHP_EOL;
} else {
    echo 'ERROR: No owner user found!' . PHP_EOL;
}
"
```

### Check Laravel Logs (Real-time)
```bash
docker exec admin-payment-app tail -f storage/logs/laravel.log
```

### Check Container Status
```bash
docker ps | grep admin-payment
```

### Check Redis Container
```bash
docker ps | grep redis
docker logs admin-payment-redis --tail=20
```

## 🔄 Restart Container (If Needed)
```bash
docker-compose restart app
# Or
docker restart admin-payment-app
```

## ✅ Verification Checklist

After running deploy commands:

- [ ] Caches cleared successfully
- [ ] Config cache rebuilt
- [ ] Route cache rebuilt
- [ ] Session config shows: `encrypt=false`, `same_site=lax`
- [ ] Log viewer middleware includes: `log-viewer.auth`
- [ ] Owner user exists in database
- [ ] Redis connection working (PONG response)
- [ ] Log files exist in storage/logs/
- [ ] Routes registered (check with route:list)

Then test in browser:

- [ ] Login as owner user
- [ ] Navigate to /log-viewer
- [ ] Page loads without errors
- [ ] Log files appear in sidebar
- [ ] Can view log contents
- [ ] No 403 errors in browser console

## 📋 What Changed?

### New Files
1. `app/Http/Middleware/LogViewerAuth.php` - Custom auth middleware
2. Scripts for deployment and testing

### Modified Files
1. `config/log-viewer.php` - Added custom middleware
2. `bootstrap/app.php` - Registered middleware, added CSRF exception
3. `app/Providers/AppServiceProvider.php` - Added Gate definition
4. `.env` & `.env.production` - Fixed session config

## 🎯 Expected Result

**Before Fix:**
```
Console: log-viewer/api/folders 403 (Forbidden)
Page: "No log files were found"
```

**After Fix:**
```
Console: log-viewer/api/folders 200 (OK)
Page: Shows list of log files in sidebar
```

## 💡 Quick Tips

1. **Always clear cache** after config changes
2. **Test in incognito** to avoid cached cookies
3. **Check browser DevTools** for detailed errors
4. **Verify user role** in database if still 403
5. **Check Redis** if session not persisting

## 📞 Still Not Working?

Run comprehensive diagnostics:
```bash
chmod +x scripts/test-log-viewer-auth.sh
./scripts/test-log-viewer-auth.sh
```

Or check detailed guide:
- `LOG_VIEWER_FIX_COMPLETE.md` - Complete troubleshooting guide
- `DEPLOY_LOG_VIEWER_FIX_V2.md` - Deployment guide
