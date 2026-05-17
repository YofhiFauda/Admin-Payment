# Log Viewer 403 Error - Complete Fix Guide

## 🔍 Problem Analysis

### Symptoms
- Log Viewer page loads but shows "No log files were found"
- Browser console shows: `log-viewer/api/folders?direction=desc:1 Failed to load resource: the server responded with a status of 403 ()`
- API endpoint returns 403 Forbidden

### Root Causes
1. **Incorrect middleware configuration** - Package middleware classes don't exist
2. **Session configuration issues** - `SESSION_SAME_SITE=none` and `SESSION_ENCRYPT=true` causing cookie problems
3. **Authorization callback not working** - Package may not properly call the authorize callback
4. **CSRF token issues** - API routes may be blocked by CSRF protection

## ✅ Complete Solution

### 1. Custom Middleware (NEW)
**File:** `app/Http/Middleware/LogViewerAuth.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogViewerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Check if user is owner
        $user = auth()->user();
        if (!$user || $user->role !== 'owner') {
            return response()->json(['error' => 'Forbidden - Owner access only'], 403);
        }

        return $next($request);
    }
}
```

**Why:** Provides explicit, debuggable authorization logic that returns clear error messages.

### 2. Log Viewer Configuration
**File:** `config/log-viewer.php`

```php
'middleware' => ['web', 'auth', 'log-viewer.auth'],

'authorize' => function ($request) {
    // Authorization is handled by middleware
    return true;
},

'api_middleware' => ['web', 'auth', 'log-viewer.auth'],
```

**Changes:**
- Added custom middleware `log-viewer.auth` to both `middleware` and `api_middleware`
- Authorization callback returns `true` (handled by middleware)

### 3. Bootstrap Configuration
**File:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'broadcasting/auth',
        'log-viewer',
        'log-viewer/*',
        'log-viewer/api/*',  // NEW: Added API routes
    ]);
    $middleware->trustProxies(at: '*');
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
        'n8n.secret' => \App\Http\Middleware\N8nSecretMiddleware::class,
        'horizon.auth' => \App\Http\Middleware\HorizonBasicAuth::class,
        'log-viewer.auth' => \App\Http\Middleware\LogViewerAuth::class,  // NEW
    ]);
})
```

**Changes:**
- Added CSRF exception for `log-viewer/api/*`
- Registered middleware alias `log-viewer.auth`

### 4. Gate Definition
**File:** `app/Providers/AppServiceProvider.php`

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // ... existing code ...
    
    // Gate: Log Viewer Authorization
    Gate::define('viewLogViewer', function ($user) {
        return $user && $user->role === 'owner';
    });
    
    // ... existing code ...
}
```

**Why:** Provides alternative authorization method using Laravel Gates.

### 5. Session Configuration
**Files:** `.env` and `.env.production`

```env
# BEFORE (Wrong)
SESSION_ENCRYPT=true
SESSION_SAME_SITE=none

# AFTER (Correct)
SESSION_ENCRYPT=false
SESSION_SAME_SITE=lax
```

**Why:**
- `SESSION_ENCRYPT=false` - Prevents session validation issues
- `SESSION_SAME_SITE=lax` - Allows cookies to be sent with API requests

## 🚀 Deployment Steps

### For Local Development (Windows)
```powershell
# 1. Clear caches
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan cache:clear
docker exec admin-payment-app php artisan route:clear

# 2. Rebuild caches
docker exec admin-payment-app php artisan config:cache
docker exec admin-payment-app php artisan route:cache

# 3. Test
# Login as owner → Access /log-viewer
```

### For Production (Coolify/Docker)
```bash
# 1. Push changes
git add .
git commit -m "fix: resolve log-viewer 403 with custom middleware"
git push origin main

# 2. In production container
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan cache:clear
docker exec admin-payment-app php artisan route:clear
docker exec admin-payment-app php artisan config:cache
docker exec admin-payment-app php artisan route:cache

# 3. Optional: Restart container
docker-compose restart app
```

### Using Scripts
```bash
# Make scripts executable
chmod +x scripts/*.sh

# Deploy and test
./scripts/deploy-and-test.sh

# Run diagnostics
./scripts/test-log-viewer-auth.sh
```

## 🧪 Testing & Verification

### 1. Check Middleware Registration
```bash
docker exec admin-payment-app php artisan route:list --path=log-viewer
```

Expected output should show `log-viewer.auth` in middleware column.

### 2. Test Authentication
```bash
docker exec admin-payment-app php artisan tinker --execute="
    \$user = App\Models\User::where('role', 'owner')->first();
    echo 'User: ' . \$user->name . PHP_EOL;
    echo 'Role: ' . \$user->role . PHP_EOL;
    echo 'Is Owner: ' . (\$user->role === 'owner' ? 'Yes' : 'No') . PHP_EOL;
"
```

### 3. Test Session Configuration
```bash
docker exec admin-payment-app php artisan tinker --execute="
    echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
    echo 'SESSION_ENCRYPT: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
    echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
"
```

Expected:
- `SESSION_DRIVER: redis`
- `SESSION_ENCRYPT: false`
- `SESSION_SAME_SITE: lax`

### 4. Test Redis Connection
```bash
docker exec admin-payment-app php artisan redis:ping
```

Expected: `PONG`

### 5. Browser Testing
1. **Login** as owner user
2. **Open DevTools** (F12)
3. **Navigate** to `/log-viewer`
4. **Check Console** - No 403 errors
5. **Check Network** tab:
   - Request to `/log-viewer/api/folders` should return 200
   - Response should contain log file list

### 6. Check Cookies
In DevTools → Application → Cookies:
- Cookie name: `whusnet-admin-payment-session` (or similar)
- Secure: ✓ (if HTTPS)
- SameSite: Lax
- Cookie should be sent with API requests

## 🐛 Troubleshooting

### Still Getting 403?

#### Check 1: User Role
```bash
docker exec admin-payment-app php artisan tinker --execute="
    \$user = auth()->user();
    echo 'Authenticated: ' . (auth()->check() ? 'Yes' : 'No') . PHP_EOL;
    echo 'Role: ' . (\$user ? \$user->role : 'N/A') . PHP_EOL;
"
```

If role is not 'owner', login with correct user.

#### Check 2: Session Cookies
- Clear browser cookies
- Logout and login again
- Check if cookie is being sent in Network tab

#### Check 3: Redis Connection
```bash
docker exec admin-payment-app php artisan tinker --execute="
    Cache::store('redis')->put('test', 'value', 60);
    echo Cache::store('redis')->get('test');
"
```

If fails, check Redis container:
```bash
docker ps | grep redis
docker logs admin-payment-redis --tail=50
```

#### Check 4: Middleware Execution
Add temporary logging to `LogViewerAuth` middleware:
```php
public function handle(Request $request, Closure $next): Response
{
    \Log::info('LogViewerAuth: Checking authentication', [
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'user_role' => auth()->user()?->role,
    ]);
    
    // ... rest of code
}
```

Then check logs:
```bash
docker exec admin-payment-app tail -f storage/logs/laravel.log
```

#### Check 5: CSRF Token
In browser DevTools → Network → Request Headers:
- Should have `X-CSRF-TOKEN` header
- Or `X-XSRF-TOKEN` cookie

If missing, check if CSRF exception is working:
```bash
docker exec admin-payment-app php artisan route:list --path=log-viewer
```

### Session Not Persisting?

#### Solution 1: Clear Browser Data
1. Open DevTools (F12)
2. Application → Storage → Clear site data
3. Logout and login again

#### Solution 2: Check Session Store
```bash
docker exec admin-payment-app php artisan tinker --execute="
    session()->put('test', 'value');
    echo session()->get('test');
"
```

#### Solution 3: Check Redis Prefix
```bash
docker exec admin-payment-app php artisan tinker --execute="
    echo 'Redis Prefix: ' . config('database.redis.options.prefix') . PHP_EOL;
"
```

### API Returns Empty Response?

Check if log files exist:
```bash
docker exec admin-payment-app ls -la storage/logs/
```

If no logs, create test log:
```bash
docker exec admin-payment-app php artisan tinker --execute="
    \Log::info('Test log entry');
"
```

## 📊 Expected Results

### Success Indicators
- ✅ `/log-viewer` page loads
- ✅ Log files appear in sidebar
- ✅ Can click and view log contents
- ✅ No 403 errors in console
- ✅ API endpoint returns 200 with data

### API Response Example
```json
{
  "data": [
    {
      "name": "laravel.log",
      "path": "laravel.log",
      "size": 12345,
      "modified_at": "2024-01-15T10:30:00.000000Z"
    }
  ]
}
```

## 📁 Files Modified

### New Files
- ✅ `app/Http/Middleware/LogViewerAuth.php`
- ✅ `scripts/deploy-and-test.sh`
- ✅ `scripts/test-log-viewer-auth.sh`
- ✅ `DEPLOY_LOG_VIEWER_FIX_V2.md`
- ✅ `LOG_VIEWER_FIX_COMPLETE.md`

### Modified Files
- ✅ `config/log-viewer.php`
- ✅ `bootstrap/app.php`
- ✅ `app/Providers/AppServiceProvider.php`
- ✅ `.env`
- ✅ `.env.production`

## 🔄 Rollback Plan

If issues persist, rollback:

```bash
# Revert changes
git revert HEAD
git push origin main

# Or manual rollback
# 1. Remove LogViewerAuth middleware
# 2. Restore original config/log-viewer.php
# 3. Restore original bootstrap/app.php
# 4. Clear caches
```

## 📚 References

- [Laravel Log Viewer Documentation](https://log-viewer.opcodes.io/)
- [Laravel Middleware](https://laravel.com/docs/11.x/middleware)
- [Laravel Session Configuration](https://laravel.com/docs/11.x/session)
- [SameSite Cookie Attribute](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value)

## 💡 Key Learnings

1. **Custom middleware** provides better control and debugging than package callbacks
2. **Session configuration** is critical for API authentication
3. **CSRF exceptions** must include API routes
4. **Clear error messages** help debugging (401 vs 403)
5. **Redis session** is essential for multi-container environments

## 🎯 Success Criteria

- [x] Custom middleware created
- [x] Configuration updated
- [x] Session settings fixed
- [x] CSRF exceptions added
- [x] Gate definition added
- [x] Scripts created for deployment
- [x] Documentation complete
- [ ] Tested in production ← **YOU ARE HERE**
- [ ] Verified working
- [ ] Monitoring in place

## 📞 Support

If still experiencing issues:
1. Run `./scripts/test-log-viewer-auth.sh` for diagnostics
2. Check Laravel logs: `docker exec admin-payment-app tail -f storage/logs/laravel.log`
3. Check browser console and network tab
4. Verify user has owner role
5. Verify Redis is working
