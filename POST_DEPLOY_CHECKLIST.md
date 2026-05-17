# Post-Deploy Checklist - Langkah Setelah Deploy Selesai

## 🎯 Overview

Panduan ini menjelaskan langkah-langkah yang harus dilakukan setelah deploy selesai agar website berfungsi dengan baik.

---

## 📋 Langkah-Langkah Wajib

### 1️⃣ **Clear All Caches**

Setelah deploy, cache lama bisa menyebabkan masalah. Clear semua cache:

```bash
# Masuk ke container app
docker exec -it admin-payment-app bash

# Atau jika menggunakan nama container lain
docker exec -it whusnet-app bash

# Clear semua cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Optional: Clear compiled classes
php artisan clear-compiled
```

**Expected Output:**
```
Configuration cache cleared successfully.
Application cache cleared successfully.
Route cache cleared successfully.
Compiled views cleared successfully.
Events cache cleared successfully.
```

---

### 2️⃣ **Rebuild Caches (Production)**

Setelah clear, rebuild cache untuk performa optimal:

```bash
# Masih di dalam container
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**Expected Output:**
```
Configuration cached successfully.
Routes cached successfully.
Blade templates cached successfully.
Events cached successfully.
```

**⚠️ PENTING:** Jika `config:cache` error dengan "not serializable", ada masalah di config file. Lihat troubleshooting di bawah.

---

### 3️⃣ **Run Database Migrations**

Pastikan database schema up-to-date:

```bash
# Check migration status
php artisan migrate:status

# Run migrations (jika ada yang pending)
php artisan migrate --force

# Jika ada seeder yang perlu dijalankan
php artisan db:seed --force
```

**Expected Output:**
```
Migration table created successfully.
Migrating: 2024_01_01_000000_create_xxx_table
Migrated:  2024_01_01_000000_create_xxx_table (123.45ms)
```

---

### 4️⃣ **Verify Environment Configuration**

Check apakah environment variables sudah benar:

```bash
# Check APP_URL
php artisan tinker --execute="echo config('app.url');"
# Expected: https://admin.whusnet.com

# Check database connection
php artisan tinker --execute="echo DB::connection()->getDatabaseName();"
# Expected: admin_payment

# Check Redis connection
php artisan redis:ping
# Expected: PONG

# Check session configuration
php artisan tinker --execute="
    echo 'Driver: ' . config('session.driver') . PHP_EOL;
    echo 'Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
    echo 'SameSite: ' . config('session.same_site') . PHP_EOL;
"
# Expected:
# Driver: redis
# Encrypt: false
# SameSite: lax
```

---

### 5️⃣ **Restart All Services**

Restart semua container untuk apply perubahan:

```bash
# Exit dari container dulu
exit

# Restart semua services
docker-compose restart

# Atau restart individual services
docker-compose restart app
docker-compose restart nginx
docker-compose restart horizon
docker-compose restart reverb
docker-compose restart scheduler
docker-compose restart pulse
```

**Expected Output:**
```
Restarting whusnet-app ... done
Restarting whusnet-nginx ... done
Restarting whusnet-horizon ... done
Restarting whusnet-reverb ... done
Restarting whusnet-scheduler ... done
Restarting whusnet-pulse ... done
```

---

### 6️⃣ **Verify All Containers Running**

Check apakah semua container berjalan dengan baik:

```bash
# Check container status
docker-compose ps

# Atau
docker ps --filter "name=whusnet"
```

**Expected Output:**
```
NAME                STATUS              PORTS
whusnet-app         Up 2 minutes        9000/tcp
whusnet-nginx       Up 2 minutes        0.0.0.0:8000->80/tcp
whusnet-horizon     Up 2 minutes        
whusnet-reverb      Up 2 minutes        0.0.0.0:8081->8081/tcp
whusnet-scheduler   Up 2 minutes        
whusnet-pulse       Up 2 minutes        
```

**⚠️ Jika ada container yang Exit/Restart:**
```bash
# Check logs untuk error
docker logs whusnet-app --tail=50
docker logs whusnet-horizon --tail=50
```

---

### 7️⃣ **Check Container Health**

Verify health checks passing:

```bash
# Check health status
docker inspect whusnet-app | grep -A 10 "Health"

# Atau check semua container
docker ps --format "table {{.Names}}\t{{.Status}}"
```

**Expected:**
```
whusnet-app         Up 5 minutes (healthy)
whusnet-nginx       Up 5 minutes (healthy)
whusnet-horizon     Up 5 minutes (healthy)
whusnet-reverb      Up 5 minutes (healthy)
```

---

### 8️⃣ **Test Application Endpoints**

Test apakah aplikasi merespon dengan benar:

```bash
# Test health check
curl -I http://localhost:8000/up
# Expected: HTTP/1.1 200 OK

# Test main page (dari server)
curl -I http://localhost:8000
# Expected: HTTP/1.1 200 OK atau 302 Found (redirect)

# Test dari luar (jika sudah ada domain)
curl -I https://admin.whusnet.com
# Expected: HTTP/2 200 OK
```

---

### 9️⃣ **Verify Background Workers**

Check apakah background workers berjalan:

```bash
# Check Horizon
docker exec whusnet-horizon ps aux | grep horizon
# Expected: php artisan horizon

# Check Scheduler
docker exec whusnet-scheduler ps aux | grep schedule
# Expected: php artisan schedule:work

# Check Pulse
docker exec whusnet-pulse ps aux | grep pulse
# Expected: php artisan pulse:work

# Check Reverb
docker exec whusnet-reverb ps aux | grep reverb
# Expected: php artisan reverb:start
```

---

### 🔟 **Test Queue Processing**

Test apakah queue berfungsi:

```bash
# Dispatch test job
docker exec whusnet-app php artisan tinker --execute="
    dispatch(function() {
        \Log::info('Test queue job executed');
    });
    echo 'Job dispatched' . PHP_EOL;
"

# Check logs
docker exec whusnet-app tail -f storage/logs/laravel.log | grep "Test queue"
# Expected: Test queue job executed

# Check Horizon dashboard
# Akses: https://admin.whusnet.com/horizon
```

---

## 🌐 Langkah-Langkah untuk Domain Baru

### 1️⃣ **Verify DNS Resolution**

```bash
# Check DNS
nslookup admin.whusnet.com
# Expected: IP server Anda

# Check dari berbagai DNS
dig admin.whusnet.com @8.8.8.8
dig admin.whusnet.com @1.1.1.1
```

---

### 2️⃣ **Verify SSL Certificate**

```bash
# Check SSL certificate
openssl s_client -connect admin.whusnet.com:443 -servername admin.whusnet.com

# Check expiry
echo | openssl s_client -connect admin.whusnet.com:443 2>/dev/null | openssl x509 -noout -dates

# Test SSL grade (online)
# https://www.ssllabs.com/ssltest/analyze.html?d=admin.whusnet.com
```

---

### 3️⃣ **Configure Reverse Proxy (Jika Belum)**

Jika menggunakan Nginx di host:

```bash
# Check Nginx config
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# Check Nginx status
sudo systemctl status nginx
```

---

### 4️⃣ **Test WebSocket Connection**

```bash
# Install wscat jika belum ada
npm install -g wscat

# Test WebSocket
wscat -c wss://admin.whusnet.com

# Expected: Connected
```

---

## 🧪 Testing di Browser

### 1️⃣ **Basic Functionality Test**

1. **Akses Website**
   - Buka: `https://admin.whusnet.com`
   - Expected: Halaman login muncul

2. **Login**
   - Masukkan credentials
   - Expected: Redirect ke dashboard

3. **Check Session**
   - Refresh page
   - Expected: Masih login (tidak logout)

4. **Check DevTools Console**
   - Buka DevTools (F12) → Console
   - Expected: No errors (no 403, 401, CORS errors)

---

### 2️⃣ **WebSocket Test**

1. **Check Connection**
   - Buka DevTools → Console
   - Look for: "WebSocket connection established" atau similar
   - Expected: WebSocket connected

2. **Test Real-time Features**
   - Trigger notification atau event
   - Expected: Update tanpa refresh

---

### 3️⃣ **Log Viewer Test**

1. **Akses Log Viewer**
   - Login sebagai owner
   - Akses: `https://admin.whusnet.com/log-viewer`
   - Expected: Log files muncul di sidebar

2. **Check API**
   - Buka DevTools → Network
   - Look for: `/log-viewer/api/folders`
   - Expected: Status 200 OK

3. **View Logs**
   - Click log file
   - Expected: Log contents muncul

---

### 4️⃣ **Pulse Test**

1. **Akses Pulse**
   - Login sebagai owner
   - Akses: `https://admin.whusnet.com/pulse`
   - Expected: Dashboard dengan metrics

2. **Check Metrics**
   - Expected: Server metrics, cache hits, slow queries, dll

---

### 5️⃣ **Horizon Test**

1. **Akses Horizon**
   - Akses: `https://admin.whusnet.com/horizon`
   - Expected: Dashboard dengan queue metrics

2. **Check Jobs**
   - Expected: Recent jobs, failed jobs, dll

---

## 🔍 Verification Checklist

### Infrastructure
- [ ] All containers running (docker ps)
- [ ] All containers healthy (health checks passing)
- [ ] DNS resolving correctly (nslookup)
- [ ] SSL certificate valid (openssl)
- [ ] Reverse proxy working (nginx -t)

### Application
- [ ] Config cache successful (no errors)
- [ ] Route cache successful
- [ ] Database connection working (redis:ping)
- [ ] Redis connection working (DB::connection())
- [ ] Session configuration correct

### Services
- [ ] Horizon running (queue worker)
- [ ] Scheduler running (cron jobs)
- [ ] Pulse running (monitoring)
- [ ] Reverb running (WebSocket)

### Endpoints
- [ ] Health check: /up (200 OK)
- [ ] Main page: / (200 OK or 302)
- [ ] Login page: /login (200 OK)
- [ ] Dashboard: /dashboard (200 OK after login)
- [ ] Log Viewer: /log-viewer (200 OK for owner)
- [ ] Pulse: /pulse (200 OK for owner)
- [ ] Horizon: /horizon (200 OK for owner)

### Browser Testing
- [ ] Website loads without errors
- [ ] Login successful
- [ ] Session persists after refresh
- [ ] No console errors (403, 401, CORS)
- [ ] WebSocket connected
- [ ] Real-time features working
- [ ] Log Viewer accessible
- [ ] Pulse showing metrics
- [ ] Horizon showing jobs

---

## 🐛 Troubleshooting

### Issue 1: Config Cache Error

**Symptom:**
```
Your configuration files are not serializable.
```

**Solution:**
```bash
# Check config files for closures
grep -r "function\|::" config/

# Fix: Replace closures with simple values
# Example in config/log-viewer.php:
'authorize' => fn($request) => true,
```

---

### Issue 2: Container Keeps Restarting

**Symptom:**
```
whusnet-app    Restarting (1) 10 seconds ago
```

**Solution:**
```bash
# Check logs
docker logs whusnet-app --tail=100

# Common issues:
# - Database connection failed
# - Redis connection failed
# - Permission issues
# - Missing .env file
```

---

### Issue 3: 502 Bad Gateway

**Symptom:** Nginx returns 502 Bad Gateway

**Solution:**
```bash
# Check app container
docker ps | grep whusnet-app
# Should be: Up (healthy)

# Check app logs
docker logs whusnet-app --tail=50

# Check nginx logs
docker logs whusnet-nginx --tail=50

# Restart app
docker-compose restart app nginx
```

---

### Issue 4: Session Not Persisting

**Symptom:** User logged out after refresh

**Solution:**
```bash
# Check Redis
docker exec whusnet-app php artisan redis:ping

# Check session config
docker exec whusnet-app php artisan tinker --execute="
    echo 'Driver: ' . config('session.driver') . PHP_EOL;
    echo 'Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
"

# Expected:
# Driver: redis
# Encrypt: false

# Clear session
docker exec whusnet-app php artisan cache:clear
```

---

### Issue 5: WebSocket Connection Failed

**Symptom:** Console error: "WebSocket connection failed"

**Solution:**
```bash
# Check Reverb container
docker logs whusnet-reverb --tail=50

# Check Reverb port
curl -I http://localhost:8081
# Expected: 200 OK or 101 Switching Protocols

# Check reverse proxy config
# Pastikan ada WebSocket upgrade headers

# Restart Reverb
docker-compose restart reverb
```

---

### Issue 6: Queue Not Processing

**Symptom:** Jobs stuck in queue

**Solution:**
```bash
# Check Horizon
docker logs whusnet-horizon --tail=50

# Check queue connection
docker exec whusnet-app php artisan tinker --execute="
    echo 'Queue: ' . config('queue.default') . PHP_EOL;
    echo 'Redis: ' . config('database.redis.default.host') . PHP_EOL;
"

# Restart Horizon
docker-compose restart horizon

# Clear failed jobs (if needed)
docker exec whusnet-app php artisan horizon:clear
```

---

### Issue 7: Log Viewer 403

**Symptom:** Log Viewer returns 403 Forbidden

**Solution:**
```bash
# Check user role
docker exec whusnet-app php artisan tinker --execute="
    \$user = auth()->user();
    echo 'Role: ' . (\$user ? \$user->role : 'Not authenticated') . PHP_EOL;
"

# Expected: Role: owner

# Check middleware
docker exec whusnet-app php artisan route:list --path=log-viewer

# Expected: Middleware includes 'log-viewer.auth'

# Clear config cache
docker exec whusnet-app php artisan config:clear
docker exec whusnet-app php artisan config:cache
```

---

## 📊 Monitoring (24 Jam Pertama)

### 1. Monitor Logs

```bash
# Laravel logs
docker exec whusnet-app tail -f storage/logs/laravel.log

# Nginx access logs
docker logs whusnet-nginx -f

# Horizon logs
docker logs whusnet-horizon -f
```

---

### 2. Monitor Resources

```bash
# Container resources
docker stats

# Expected:
# - App: < 512MB memory
# - Nginx: < 128MB memory
# - Horizon: < 512MB memory
# - Reverb: < 256MB memory
```

---

### 3. Monitor Pulse Dashboard

- Akses: `https://admin.whusnet.com/pulse`
- Check:
  - Server metrics (CPU, memory)
  - Slow queries
  - Slow requests
  - Exceptions
  - Cache hits/misses

---

### 4. Monitor Horizon Dashboard

- Akses: `https://admin.whusnet.com/horizon`
- Check:
  - Jobs processed
  - Failed jobs
  - Queue wait time
  - Throughput

---

## ✅ Success Criteria

Website dianggap berfungsi dengan baik jika:

### Infrastructure
- ✅ All containers running and healthy
- ✅ DNS resolving correctly
- ✅ SSL certificate valid
- ✅ No container restarts

### Application
- ✅ Config cache successful
- ✅ Database connection working
- ✅ Redis connection working
- ✅ Session persisting

### Services
- ✅ Horizon processing jobs
- ✅ Scheduler running cron jobs
- ✅ Pulse recording metrics
- ✅ Reverb handling WebSocket

### User Experience
- ✅ Website loads < 3 seconds
- ✅ Login successful
- ✅ Session stable
- ✅ No console errors
- ✅ Real-time features working
- ✅ All admin tools accessible (Log Viewer, Pulse, Horizon)

---

## 📞 Support

Jika masih ada masalah setelah mengikuti checklist ini:

1. **Check logs:**
```bash
docker logs whusnet-app --tail=100
docker logs whusnet-nginx --tail=100
docker logs whusnet-horizon --tail=100
```

2. **Run diagnostics:**
```bash
./scripts/test-before-production.sh
```

3. **Check documentation:**
- `LOG_VIEWER_FIX_COMPLETE.md`
- `MIGRATION_TO_CUSTOM_DOMAIN.md`
- `TROUBLESHOOTING.md`

---

**Last Updated:** 2024-01-15
**Estimated Time:** 30-45 menit untuk complete checklist
