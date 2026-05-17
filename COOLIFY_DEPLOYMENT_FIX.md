# 🔧 Perbaikan Deployment Error di Coolify

## 📋 Masalah yang Ditemukan

### 1. **REVERB_APP_KEY tidak terdefinisi**
```
time="2026-05-17T14:23:44Z" level=warning msg="The \"REVERB_APP_KEY\" variable is not set. Defaulting to a blank string."
```

**Penyebab:**
- `docker-compose.yaml` tidak meneruskan `REVERB_APP_KEY` sebagai build argument
- `Dockerfile` tidak mendefinisikan `ARG REVERB_APP_KEY`
- Vite build membutuhkan variabel ini untuk embed WebSocket config

### 2. **File Conflict Error**
```
cannot overwrite directory "/data/coolify/applications/.../app/Http/Middleware/LogViewerAuth.php" with non-directory
```

**Penyebab:**
- Volume mounting individual files (`./config/pulse.php:/var/www/config/pulse.php:ro`) menyebabkan konflik di Coolify
- Coolify menggunakan `docker cp` untuk copy artifacts yang tidak kompatibel dengan file-level mounts
- Best practice: hanya mount directories atau named volumes, bukan individual files

---

## ✅ Solusi yang Diterapkan

### 1. **Tambahkan REVERB_APP_KEY ke Build Process**

#### `docker-compose.yaml`
```yaml
app:
  build:
    args:
      - APP_ENV=production
      - REVERB_APP_KEY=${REVERB_APP_KEY}           # ✅ DITAMBAHKAN
      - VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
      - VITE_REVERB_HOST=${VITE_REVERB_HOST}
      - VITE_REVERB_PORT=${VITE_REVERB_PORT:-443}
      - VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME:-https}
```

#### `Dockerfile` (Stage 2: Frontend Assets)
```dockerfile
# Teruskan build arguments dari docker-compose.yaml
ARG REVERB_APP_KEY                    # ✅ DITAMBAHKAN
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https

ENV REVERB_APP_KEY=$REVERB_APP_KEY   # ✅ DITAMBAHKAN
ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME
```

### 2. **Hapus Individual File Mounts**

#### Sebelum (❌ Bermasalah):
```yaml
volumes:
  - app_public:/var/www/public
  - storage_data:/var/www/storage
  - ./config/pulse.php:/var/www/config/pulse.php:ro
  - ./config/log-viewer.php:/var/www/config/log-viewer.php:ro
  - ./app/Http/Middleware/LogViewerAuth.php:/var/www/app/Http/Middleware/LogViewerAuth.php:ro
  - ./bootstrap/app.php:/var/www/bootstrap/app.php:ro
  - ./app/Providers/AppServiceProvider.php:/var/www/app/Providers/AppServiceProvider.php:ro
```

#### Sesudah (✅ Fixed):
```yaml
volumes:
  - app_public:/var/www/public
  - storage_data:/var/www/storage
```

**Alasan:**
- File-level mounts tidak diperlukan karena semua file sudah di-copy ke image saat build
- Coolify deployment process tidak kompatibel dengan file-level mounts
- Named volumes (`app_public`, `storage_data`) sudah cukup untuk data persistence

---

## 🚀 Cara Deploy Ulang

### 1. **Commit Perubahan**
```bash
git add docker-compose.yaml Dockerfile
git commit -m "fix: resolve Coolify deployment errors (REVERB_APP_KEY & file mounts)"
git push origin master
```

### 2. **Trigger Redeploy di Coolify**
- Buka Coolify dashboard
- Pilih aplikasi "WHUSNET Admin Payment"
- Klik tombol **"Redeploy"**
- Monitor logs untuk memastikan tidak ada error

### 3. **Verifikasi Deployment**
```bash
# Cek container status
docker ps | grep whusnet

# Cek logs
docker logs whusnet-app
docker logs whusnet-nginx
docker logs whusnet-reverb

# Test WebSocket connection
curl -I https://admin-payment.whusnet.com
```

---

## 🔍 Penjelasan Teknis

### Mengapa REVERB_APP_KEY Penting?

1. **Build-time Configuration:**
   - Vite embed WebSocket config ke dalam compiled JavaScript
   - Tanpa `REVERB_APP_KEY`, frontend tidak bisa authenticate ke Reverb server
   - Warning "defaulting to blank string" menyebabkan WebSocket connection gagal

2. **Environment Variable Flow:**
   ```
   .env.production → docker-compose.yaml (build args) → Dockerfile (ARG/ENV) → Vite build → public/build/*.js
   ```

### Mengapa File Mounts Bermasalah di Coolify?

1. **Coolify Deployment Process:**
   ```
   Build → docker cp artifacts → Start containers
   ```

2. **Conflict Scenario:**
   - Coolify: `docker cp /artifacts/app/Http/Middleware/LogViewerAuth.php /data/...`
   - Docker Compose: Mount `./app/Http/Middleware/LogViewerAuth.php` as file
   - Result: "cannot overwrite directory with non-directory"

3. **Best Practice:**
   - ✅ Copy files ke image saat build (via `COPY` di Dockerfile)
   - ✅ Mount directories atau named volumes untuk data persistence
   - ❌ Jangan mount individual files di production

---

## 📝 Checklist Verifikasi

- [x] `REVERB_APP_KEY` ditambahkan ke `docker-compose.yaml` build args
- [x] `ARG REVERB_APP_KEY` ditambahkan ke `Dockerfile`
- [x] Individual file mounts dihapus dari `docker-compose.yaml`
- [x] `.env.production` sudah memiliki `REVERB_APP_KEY=whusnet_reverb_key_123`
- [ ] Commit & push ke repository
- [ ] Redeploy di Coolify
- [ ] Verifikasi deployment berhasil
- [ ] Test WebSocket connection
- [ ] Test Log Viewer & Pulse dashboard

---

## 🆘 Troubleshooting

### Jika masih error "REVERB_APP_KEY not set":
```bash
# Pastikan .env.production memiliki nilai yang benar
grep REVERB_APP_KEY .env.production

# Rebuild tanpa cache
docker-compose build --no-cache app
```

### Jika WebSocket tidak connect:
```bash
# Cek Reverb container logs
docker logs whusnet-reverb

# Test port 8081
curl http://localhost:8081

# Cek Traefik routing (Coolify)
docker logs coolify-proxy
```

### Jika Log Viewer 403:
- File `LogViewerAuth.php` sudah ada di image (via `COPY . /var/www`)
- Tidak perlu mount lagi
- Restart container: `docker-compose restart app`

---

## 📚 Referensi

- [Coolify Docker Compose Documentation](https://coolify.io/docs/knowledge-base/docker/compose)
- [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb)
- [Docker Volume Best Practices](https://docs.docker.com/storage/volumes/)

---

**Status:** ✅ Fixed  
**Tanggal:** 2026-05-17  
**Tested:** Pending redeploy
