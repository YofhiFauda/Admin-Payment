# Pre-Production Checklist - Log Viewer & Pulse

## 🎯 Tujuan
Memastikan perubahan Log Viewer dan Pulse aman untuk production.

---

## ✅ Perubahan yang Sudah Dilakukan

### 1. Log Viewer
- ✅ Custom middleware `LogViewerAuth` dibuat
- ✅ Session configuration diperbaiki (`SESSION_SAME_SITE=lax`, `SESSION_ENCRYPT=false`)
- ✅ CSRF exception untuk API routes
- ✅ Middleware alias registered
- ✅ Gate definition di AppServiceProvider
- ✅ **FIXED**: Authorize callback menggunakan closure (config:cache compatible)

### 2. Pulse
- ✅ Redis connection terpisah (DB index 2)
- ✅ Ingest driver menggunakan Redis
- ✅ Storage driver menggunakan database
- ✅ Recorders dikonfigurasi dengan grouping patterns
- ✅ Environment variables lengkap di `.env.production`

---

## 🧪 Testing Wajib di Local (Sebelum Push)

### Test 1: Config Cache Compatibility
```bash
# CRITICAL: Test ini HARUS berhasil sebelum push
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan config:cache

# Jika error "not serializable", JANGAN push!
# Jika sukses, lanjut ke test berikutnya
```

**Expected:** ✅ No errors, config cached successfully

**Jika Error:**
- Cek `config/log-viewer.php` - pastikan tidak ada array dengan class reference di callback
- Cek `config/pulse.php` - pastikan tidak ada closure yang tidak bisa di-serialize

---

### Test 2: Route Cache Compatibility
```bash
docker exec admin-payment-app php artisan route:clear
docker exec admin-payment-app php artisan route:cache

# Cek apakah log-viewer routes terdaftar
docker exec admin-payment-app php artisan route:list --path=log-viewer
```

**Expected:**
```
GET|HEAD  log-viewer .................. log-viewer.index › Opcodes\LogViewer › LogViewerController@index
GET|HEAD  log-viewer/api/folders ..... log-viewer.api.folders › Opcodes\LogViewer › FoldersController
```

**Middleware harus include:** `web`, `auth`, `log-viewer.auth`

---

### Test 3: Log Viewer Access
```bash
# 1. Login sebagai owner
# 2. Akses http://localhost/log-viewer
# 3. Buka DevTools (F12) → Console
# 4. Cek Network tab
```

**Expected:**
- ✅ No 403 errors in console
- ✅ API request to `/log-viewer/api/folders` returns 200
- ✅ Log files appear in sidebar
- ✅ Can view log contents

**Jika 403:**
- Cek session cookie di DevTools → Application → Cookies
- Cek user role: `docker exec admin-payment-app php artisan tinker --execute="echo auth()->user()->role;"`
- Cek middleware execution di logs

---

### Test 4: Pulse Access
```bash
# 1. Pastikan PULSE_ENABLED=true di .env
# 2. Start pulse:work
docker exec admin-payment-app php artisan pulse:work

# 3. Di terminal lain, generate traffic
docker exec admin-payment-app php artisan tinker --execute="
    \Log::info('Test pulse');
    cache()->put('test', 'value', 60);
    cache()->get('test');
"

# 4. Akses http://localhost/pulse
```

**Expected:**
- ✅ Pulse dashboard loads
- ✅ Shows server metrics
- ✅ Shows cache interactions
- ✅ No errors in logs

**Jika Error:**
- Cek Redis Pulse connection: `docker exec admin-payment-app php artisan redis:ping`
- Cek Pulse database: `docker exec admin-payment-app php artisan pulse:check`
- Cek logs: `docker logs admin-payment-app --tail=50`

---

### Test 5: Redis Connections
```bash
# Test Redis utama (cache, queue, session)
docker exec admin-payment-app php artisan tinker --execute="
    echo 'Main Redis: ';
    Cache::store('redis')->put('test', 'main', 60);
    echo Cache::store('redis')->get('test') . PHP_EOL;
"

# Test Redis Pulse
docker exec admin-payment-app php artisan tinker --execute="
    echo 'Pulse Redis: ';
    \Illuminate\Support\Facades\Redis::connection('pulse')->set('test', 'pulse');
    echo \Illuminate\Support\Facades\Redis::connection('pulse')->get('test') . PHP_EOL;
"
```

**Expected:**
```
Main Redis: main
Pulse Redis: pulse
```

**Jika Error:**
- Cek `config/database.php` - pastikan ada connection `pulse`
- Cek `.env` - pastikan `PULSE_REDIS_HOST` dan credentials benar
- Cek container: `docker ps | grep redis`

---

### Test 6: Session Persistence
```bash
# 1. Login ke aplikasi
# 2. Buka DevTools → Application → Cookies
# 3. Cek cookie session
```

**Expected Cookie Properties:**
- Name: `laravel_session` atau sesuai `SESSION_COOKIE`
- Secure: ✓ (jika HTTPS)
- SameSite: Lax
- HttpOnly: ✓

**Test Session:**
```bash
docker exec admin-payment-app php artisan tinker --execute="
    session()->put('test_key', 'test_value');
    echo session()->get('test_key') . PHP_EOL;
    echo 'Session ID: ' . session()->getId() . PHP_EOL;
"
```

---

### Test 7: Authorization
```bash
# Test Gate
docker exec admin-payment-app php artisan tinker --execute="
    \$owner = App\Models\User::where('role', 'owner')->first();
    echo 'Owner can view: ' . (\Gate::forUser(\$owner)->allows('viewLogViewer') ? 'Yes' : 'No') . PHP_EOL;
    
    \$staff = App\Models\User::where('role', 'staff')->first();
    echo 'Staff can view: ' . (\Gate::forUser(\$staff)->allows('viewLogViewer') ? 'Yes' : 'No') . PHP_EOL;
"
```

**Expected:**
```
Owner can view: Yes
Staff can view: No
```

---

## 🚀 Deployment ke Production

### Jika Semua Test Lokal PASSED ✅

#### Step 1: Backup Production
```bash
# Backup database
docker exec admin-payment-db mysqldump -u digitalconnexa -p admin_payment > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup .env
docker exec admin-payment-app cat .env > .env.production.backup
```

#### Step 2: Push Changes
```bash
git add .
git commit -m "fix: log-viewer 403 & pulse configuration

- Add custom LogViewerAuth middleware
- Fix session configuration (SESSION_SAME_SITE=lax, SESSION_ENCRYPT=false)
- Add CSRF exception for log-viewer API
- Configure Pulse with separate Redis connection
- Fix config:cache compatibility for authorize callback"

git push origin main
```

#### Step 3: Deploy di Production
```bash
# SSH ke server atau exec ke container

# 1. Pull latest code (jika manual)
git pull origin main

# 2. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Rebuild caches
php artisan config:cache
php artisan route:cache

# 4. Restart services
# Jika menggunakan Supervisor
supervisorctl restart all

# Atau restart container
docker-compose restart app queue
```

#### Step 4: Verify Production
```bash
# 1. Test config cache
php artisan config:cache
# Expected: No errors

# 2. Test routes
php artisan route:list --path=log-viewer
# Expected: Routes listed with correct middleware

# 3. Test Redis connections
php artisan redis:ping
# Expected: PONG

# 4. Test Pulse
php artisan pulse:check
# Expected: All checks passed

# 5. Check logs
tail -f storage/logs/laravel.log
```

#### Step 5: Browser Testing
1. Login sebagai owner
2. Akses `/log-viewer` - harus bisa lihat logs
3. Akses `/pulse` - harus bisa lihat metrics
4. Cek DevTools Console - tidak ada error 403
5. Cek Network tab - semua API requests return 200

---

## ⚠️ Rollback Plan (Jika Ada Masalah)

### Quick Rollback
```bash
# 1. Revert git commit
git revert HEAD
git push origin main

# 2. Restore .env
cat .env.production.backup > .env

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 4. Restart
docker-compose restart app
```

### Manual Rollback
Jika git revert tidak bisa:

1. **Restore config/log-viewer.php:**
```php
'middleware' => ['web', 'auth'],
'api_middleware' => ['web', 'auth'],
'authorize' => fn($request) => $request->user()?->role === 'owner',
```

2. **Restore .env session config:**
```env
SESSION_ENCRYPT=true
SESSION_SAME_SITE=none
```

3. **Remove middleware alias dari bootstrap/app.php:**
```php
// Remove this line
'log-viewer.auth' => \App\Http\Middleware\LogViewerAuth::class,
```

4. **Clear caches dan restart**

---

## 🐛 Troubleshooting Production

### Issue: Config Cache Error
**Symptom:** `php artisan config:cache` fails with "not serializable"

**Solution:**
```bash
# 1. Clear config
php artisan config:clear

# 2. Check config files for closures or class arrays
grep -r "function\|::" config/

# 3. Fix problematic configs
# Replace closures with simple values or use string references
```

---

### Issue: 403 on Log Viewer
**Symptom:** API returns 403 Forbidden

**Debug Steps:**
```bash
# 1. Check user authentication
php artisan tinker --execute="
    echo 'Auth: ' . (auth()->check() ? 'Yes' : 'No') . PHP_EOL;
    echo 'Role: ' . (auth()->user()?->role ?? 'N/A') . PHP_EOL;
"

# 2. Check middleware registration
php artisan route:list --path=log-viewer | grep middleware

# 3. Check session
php artisan tinker --execute="
    echo 'Session Driver: ' . config('session.driver') . PHP_EOL;
    echo 'Session Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
"

# 4. Check logs
tail -f storage/logs/laravel.log | grep -i "log.*viewer\|403"
```

---

### Issue: Pulse Not Recording
**Symptom:** Pulse dashboard empty or not updating

**Debug Steps:**
```bash
# 1. Check Pulse enabled
php artisan tinker --execute="echo config('pulse.enabled') ? 'Enabled' : 'Disabled';"

# 2. Check Redis Pulse connection
php artisan tinker --execute="
    \Illuminate\Support\Facades\Redis::connection('pulse')->ping();
"

# 3. Check pulse:work is running
ps aux | grep "pulse:work"

# 4. Manually trigger pulse:work
php artisan pulse:work --once

# 5. Check Pulse tables
php artisan tinker --execute="
    echo 'Entries: ' . \DB::connection('pulse')->table('pulse_entries')->count() . PHP_EOL;
"
```

---

### Issue: Session Not Persisting
**Symptom:** User logged out after page refresh

**Debug Steps:**
```bash
# 1. Check Redis session connection
php artisan tinker --execute="
    session()->put('test', 'value');
    echo session()->get('test') . PHP_EOL;
"

# 2. Check session cookie in browser
# DevTools → Application → Cookies
# Look for: SameSite=Lax, Secure=true (if HTTPS)

# 3. Check Redis container
docker ps | grep redis
docker logs admin-payment-redis --tail=50

# 4. Check session configuration
php artisan tinker --execute="
    echo 'Driver: ' . config('session.driver') . PHP_EOL;
    echo 'Connection: ' . config('session.connection') . PHP_EOL;
    echo 'Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
    echo 'SameSite: ' . config('session.same_site') . PHP_EOL;
"
```

---

## 📊 Success Metrics

### Log Viewer
- ✅ No 403 errors in browser console
- ✅ API endpoint `/log-viewer/api/folders` returns 200
- ✅ Log files visible in sidebar
- ✅ Can view log contents
- ✅ Only owner role can access

### Pulse
- ✅ Dashboard loads without errors
- ✅ Shows real-time metrics
- ✅ Recorders capturing data
- ✅ Redis Pulse connection stable
- ✅ No performance impact on main app

### Session
- ✅ Session persists across requests
- ✅ Cookies sent with API requests
- ✅ Redis session storage working
- ✅ No session encryption errors

---

## 📝 Post-Deployment Monitoring

### First 24 Hours
Monitor these metrics:

1. **Error Logs**
```bash
tail -f storage/logs/laravel.log | grep -i "error\|exception\|403\|401"
```

2. **Redis Memory**
```bash
docker stats admin-payment-redis
```

3. **Pulse Performance**
```bash
# Check pulse:work process
ps aux | grep pulse:work

# Check Pulse Redis memory
docker exec admin-payment-redis redis-cli INFO memory
```

4. **Session Issues**
```bash
# Count active sessions
docker exec admin-payment-redis redis-cli KEYS "whusnet_prod:*session*" | wc -l
```

### Weekly Review
- Check Pulse dashboard for anomalies
- Review slow queries and requests
- Check Redis memory usage trend
- Review log-viewer access logs

---

## ✅ Final Checklist

Sebelum menandai deployment sebagai sukses:

- [ ] Config cache berhasil di production
- [ ] Route cache berhasil di production
- [ ] Log Viewer accessible oleh owner
- [ ] Log Viewer blocked untuk non-owner
- [ ] Pulse dashboard menampilkan metrics
- [ ] pulse:work berjalan di background
- [ ] Session persists across requests
- [ ] Redis connections stable
- [ ] No errors in Laravel logs
- [ ] No 403/401 errors in browser console
- [ ] Monitoring setup dan berjalan

---

## 🎯 Kesimpulan

**REKOMENDASI:** 

### ✅ AMAN untuk Push JIKA:
1. ✅ Semua test lokal PASSED
2. ✅ Config cache berhasil tanpa error
3. ✅ Log Viewer berfungsi di local
4. ✅ Pulse berfungsi di local
5. ✅ Session stable di local

### ⚠️ JANGAN Push JIKA:
1. ❌ Config cache error "not serializable"
2. ❌ Log Viewer masih 403 di local
3. ❌ Pulse tidak recording data
4. ❌ Session tidak persist
5. ❌ Redis connection error

### 🔧 Perbaikan yang Sudah Dilakukan:
- ✅ **FIXED**: `config/log-viewer.php` authorize callback sekarang menggunakan closure sederhana
- ✅ Session configuration sudah benar
- ✅ Middleware sudah registered
- ✅ CSRF exception sudah ditambahkan

### 📋 Action Items:
1. **Jalankan semua test di checklist ini di LOCAL**
2. **Jika semua PASSED, lanjut push ke production**
3. **Jika ada yang FAILED, perbaiki dulu sebelum push**
4. **Setelah push, monitor logs selama 24 jam pertama**

---

**Last Updated:** 2024-01-15
**Status:** Ready for Testing → Pending Local Verification
