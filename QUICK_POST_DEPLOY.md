# Quick Post-Deploy Guide

## 🚀 Setelah Deploy Selesai - Langkah Cepat

### ⚡ Automated (Recommended)

```bash
# Jalankan script otomatis
chmod +x scripts/post-deploy.sh
./scripts/post-deploy.sh
```

**Script akan otomatis:**
- ✅ Clear all caches
- ✅ Rebuild caches
- ✅ Check database & Redis
- ✅ Run migrations
- ✅ Restart services
- ✅ Verify all containers

**Estimasi waktu:** 2-3 menit

---

### 🔧 Manual (Jika Script Gagal)

```bash
# 1. Masuk ke container
docker exec -it admin-payment-app bash

# 2. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migrations
php artisan migrate --force

# 5. Exit container
exit

# 6. Restart services
docker-compose restart
```

**Estimasi waktu:** 5 menit

---

## ✅ Verification Checklist

### 1. Check Containers
```bash
docker ps --filter "name=whusnet"
```
**Expected:** All containers "Up" and "healthy"

---

### 2. Test Endpoints
```bash
# Health check
curl -I http://localhost:8000/up
# Expected: 200 OK

# Main page
curl -I http://localhost:8000
# Expected: 200 OK or 302 Found
```

---

### 3. Test di Browser

**Basic Test:**
- [ ] Akses website (https://admin.whusnet.com)
- [ ] Login berhasil
- [ ] Refresh page → masih login
- [ ] No errors di DevTools Console

**Admin Tools:**
- [ ] Log Viewer: `/log-viewer` (owner only)
- [ ] Pulse: `/pulse` (owner only)
- [ ] Horizon: `/horizon` (owner only)

---

## 🐛 Quick Troubleshooting

### Container Restarting?
```bash
docker logs whusnet-app --tail=50
```

### Config Cache Error?
```bash
docker exec admin-payment-app php artisan config:clear
# Check config files for closures
```

### Session Not Persisting?
```bash
docker exec admin-payment-app php artisan redis:ping
# Expected: PONG
```

### WebSocket Failed?
```bash
docker logs whusnet-reverb --tail=50
docker-compose restart reverb
```

---

## 📊 Success Criteria

Website berfungsi dengan baik jika:

- ✅ All containers running
- ✅ Health check returns 200
- ✅ Login successful
- ✅ Session persists
- ✅ No console errors
- ✅ WebSocket connected
- ✅ Admin tools accessible

---

## 📞 Need Help?

**Check documentation:**
- `POST_DEPLOY_CHECKLIST.md` - Detailed checklist
- `LOG_VIEWER_FIX_COMPLETE.md` - Log Viewer troubleshooting
- `MIGRATION_TO_CUSTOM_DOMAIN.md` - Domain migration guide

**Check logs:**
```bash
docker logs whusnet-app --tail=100
docker logs whusnet-nginx --tail=100
docker logs whusnet-horizon --tail=100
```

---

**Estimated Total Time:** 5-10 menit
**Last Updated:** 2024-01-15
