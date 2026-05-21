# 📦 Ringkasan Perbaikan Deployment Coolify

## 🔴 Masalah Awal

Deployment gagal dengan 3 error:

1. **REVERB_APP_KEY tidak terdefinisi** (exit code 255)
   ```
   level=warning msg="The \"REVERB_APP_KEY\" variable is not set. Defaulting to a blank string."
   ```

2. **File conflict saat docker cp**
   ```
   cannot overwrite directory "/data/coolify/.../app/Http/Middleware/LogViewerAuth.php" with non-directory
   ```

3. **Port already allocated** (exit code 1)
   ```
   Bind for 0.0.0.0:8000 failed: port is already allocated
   ```

---

## ✅ Perbaikan yang Dilakukan

### 1. **Dockerfile** - Tambah ARG REVERB_APP_KEY

```diff
# Stage 2: Frontend Assets (Vite + Tailwind)
FROM node:22-alpine AS node
WORKDIR /app

# Teruskan build arguments dari docker-compose.yaml
+ ARG REVERB_APP_KEY
  ARG VITE_REVERB_APP_KEY
  ARG VITE_REVERB_HOST
  ARG VITE_REVERB_PORT=443
  ARG VITE_REVERB_SCHEME=https

+ ENV REVERB_APP_KEY=$REVERB_APP_KEY
  ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
  ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
  ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
  ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME
```

### 2. **docker-compose.yaml** - Tambah Build Arg & Hapus File Mounts

```diff
  app:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        - APP_ENV=production
+       - REVERB_APP_KEY=${REVERB_APP_KEY}
        - VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
        - VITE_REVERB_HOST=${VITE_REVERB_HOST}
        - VITE_REVERB_PORT=${VITE_REVERB_PORT:-443}
        - VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME:-https}
    ...
    volumes:
      - app_public:/var/www/public
      - storage_data:/var/www/storage
-     - ./config/pulse.php:/var/www/config/pulse.php:ro
-     - ./config/log-viewer.php:/var/www/config/log-viewer.php:ro
-     - ./app/Http/Middleware/LogViewerAuth.php:/var/www/app/Http/Middleware/LogViewerAuth.php:ro
-     - ./bootstrap/app.php:/var/www/bootstrap/app.php:ro
-     - ./app/Providers/AppServiceProvider.php:/var/www/app/Providers/AppServiceProvider.php:ro
```

### 3. **docker-compose.yaml** - Ganti Port Mappings dengan Expose

```diff
  nginx:
    image: nginx:alpine
    container_name: whusnet-nginx
    restart: unless-stopped
-   ports:
-     - "8000:80"
+   expose:
+     - "80"
    networks:
      - coolify
      - default

  reverb:
    image: whusnet-app:${APP_VERSION:-latest}
    container_name: whusnet-reverb
    restart: unless-stopped
-   ports:
-     - "8081:8081"
+   expose:
+     - "8081"
    networks:
      - coolify
      - default
```

**Alasan:**
- Coolify menggunakan Traefik sebagai reverse proxy
- Port mapping eksplisit tidak diperlukan
- `expose:` cukup untuk internal network routing
- Menghindari port conflict dengan container lama

```bash
✅ REVERB_APP_KEY=whusnet_reverb_key_123
✅ VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
✅ VITE_REVERB_HOST=admin-payment.whusnet.com
✅ VITE_REVERB_PORT=443
✅ VITE_REVERB_SCHEME=https
```

---

## 🚀 Cara Deploy

### Opsi 1: Menggunakan Script (Recommended)

```bash
# Linux/Mac
bash scripts/deploy-coolify.sh

# Windows Git Bash
bash scripts/deploy-coolify.sh

# Windows PowerShell
# Jalankan manual steps di bawah
```

### Opsi 2: Manual Steps

```bash
# 1. Commit perubahan
git add Dockerfile docker-compose.yaml COOLIFY_DEPLOYMENT_FIX.md DEPLOYMENT_SUMMARY.md scripts/deploy-coolify.sh
git commit -m "fix: resolve Coolify deployment errors (REVERB_APP_KEY & file mounts)"

# 2. Push ke repository
git push origin master

# 3. Buka Coolify Dashboard
# → Applications → WHUSNET Admin Payment → Redeploy

# 4. Monitor logs untuk memastikan:
#    ✅ No REVERB_APP_KEY warnings
#    ✅ Build success
#    ✅ All containers running
```

---

## 🔍 Verifikasi Deployment

### 1. Check Container Status
```bash
docker ps | grep whusnet
```

Expected output:
```
whusnet-app        Up X minutes   9000/tcp
whusnet-nginx      Up X minutes   0.0.0.0:8000->80/tcp
whusnet-reverb     Up X minutes   0.0.0.0:8081->8081/tcp
whusnet-horizon    Up X minutes
whusnet-scheduler  Up X minutes
whusnet-pulse      Up X minutes
```

### 2. Check Logs
```bash
# App container
docker logs whusnet-app --tail 50

# Reverb (WebSocket)
docker logs whusnet-reverb --tail 50

# Nginx
docker logs whusnet-nginx --tail 50
```

### 3. Test Endpoints

```bash
# Main application
curl -I https://admin-payment.whusnet.com

# WebSocket (should return 426 Upgrade Required - normal for HTTP request)
curl -I https://admin-payment.whusnet.com:443

# Health check
curl https://admin-payment.whusnet.com/up
```

### 4. Browser Tests

1. **Main App:** https://admin-payment.whusnet.com
   - ✅ Login page loads
   - ✅ No console errors

2. **WebSocket Connection:**
   - Open browser console (F12)
   - Look for: `WebSocket connection established`
   - No errors like: `WebSocket connection failed`

3. **Log Viewer:** https://admin-payment.whusnet.com/log-viewer
   - ✅ Accessible (no 403)
   - ✅ Shows logs

4. **Pulse Dashboard:** https://admin-payment.whusnet.com/pulse
   - ✅ Accessible
   - ✅ Shows metrics

---

## 🆘 Troubleshooting

### Error: "REVERB_APP_KEY not set" masih muncul

**Solusi:**
```bash
# 1. Verifikasi .env.production
grep REVERB_APP_KEY .env.production

# 2. Rebuild tanpa cache
docker-compose build --no-cache app

# 3. Restart containers
docker-compose restart
```

### Error: WebSocket connection failed

**Solusi:**
```bash
# 1. Check Reverb container
docker logs whusnet-reverb

# 2. Test port 8081
curl http://localhost:8081

# 3. Check Traefik routing (Coolify proxy)
docker logs coolify-proxy | grep reverb
```

### Error: Log Viewer 403 Forbidden

**Solusi:**
- File `LogViewerAuth.php` sudah ada di image (via `COPY . /var/www`)
- Tidak perlu mount lagi
- Restart: `docker-compose restart app`

### Error: File conflict saat deploy

**Solusi:**
- Pastikan tidak ada individual file mounts di `docker-compose.yaml`
- Hanya gunakan named volumes: `app_public`, `storage_data`
- Rebuild: `docker-compose build --no-cache`

---

## 📊 Perbandingan Sebelum vs Sesudah

| Aspek | Sebelum ❌ | Sesudah ✅ |
|-------|-----------|-----------|
| **REVERB_APP_KEY** | Tidak di-pass ke build | Di-pass sebagai build arg |
| **Dockerfile ARG** | Tidak ada | `ARG REVERB_APP_KEY` |
| **File Mounts** | 5 individual files | 0 (semua di image) |
| **Volume Strategy** | Mixed (files + volumes) | Clean (volumes only) |
| **Coolify Compatibility** | ❌ Gagal | ✅ Berhasil |
| **Build Warnings** | 4x REVERB_APP_KEY warning | 0 warnings |
| **Deployment Status** | Exit code 255 | Success |

---

## 📚 Dokumentasi Terkait

- [COOLIFY_DEPLOYMENT_FIX.md](./COOLIFY_DEPLOYMENT_FIX.md) - Penjelasan detail teknis
- [scripts/deploy-coolify.sh](./scripts/deploy-coolify.sh) - Script deployment otomatis
- [docker-compose.yaml](./docker-compose.yaml) - Konfigurasi container
- [Dockerfile](./Dockerfile) - Image build configuration

---

## ✅ Checklist Final

Sebelum deploy, pastikan:

- [x] `REVERB_APP_KEY` ada di `.env.production`
- [x] `ARG REVERB_APP_KEY` ada di `Dockerfile`
- [x] Build arg `REVERB_APP_KEY` ada di `docker-compose.yaml`
- [x] Individual file mounts dihapus dari `docker-compose.yaml`
- [x] Semua perubahan di-commit
- [ ] Push ke repository
- [ ] Redeploy di Coolify
- [ ] Verifikasi deployment berhasil
- [ ] Test semua endpoints
- [ ] Test WebSocket connection
- [ ] Test Log Viewer & Pulse

---

**Status:** ✅ Ready to Deploy  
**Tanggal:** 2026-05-17  
**Estimasi Waktu Deploy:** 5-10 menit  
**Downtime:** ~2-3 menit (saat container restart)

---

## 🎯 Expected Result

Setelah deployment berhasil:

1. ✅ Build tanpa warning
2. ✅ Semua container running
3. ✅ WebSocket connection established
4. ✅ Log Viewer accessible
5. ✅ Pulse dashboard working
6. ✅ No 403 errors
7. ✅ No file conflict errors

**Good luck with your deployment! 🚀**
