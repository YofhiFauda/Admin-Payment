# ⚡ Quick Fix - Coolify Deployment Error

## 🔴 Error yang Terjadi

```
Error: The "REVERB_APP_KEY" variable is not set. Defaulting to a blank string.
Error: cannot overwrite directory with non-directory
Deployment failed (exit code 255)
```

---

## ✅ Solusi Cepat (3 Langkah)

### 1️⃣ Commit & Push Perubahan

```bash
git add Dockerfile docker-compose.yaml
git commit -m "fix: Coolify deployment errors"
git push origin master
```

### 2️⃣ Redeploy di Coolify

1. Buka Coolify Dashboard
2. Pilih aplikasi "WHUSNET Admin Payment"
3. Klik **"Redeploy"**

### 3️⃣ Verifikasi

```bash
# Check containers
docker ps | grep whusnet

# Check logs
docker logs whusnet-app --tail 20
```

---

## 🔧 Apa yang Diperbaiki?

### File: `Dockerfile`
```dockerfile
# ✅ DITAMBAHKAN
ARG REVERB_APP_KEY
ENV REVERB_APP_KEY=$REVERB_APP_KEY
```

### File: `docker-compose.yaml`
```yaml
# ✅ DITAMBAHKAN build arg
build:
  args:
    - REVERB_APP_KEY=${REVERB_APP_KEY}

# ✅ DIHAPUS file mounts yang bermasalah
volumes:
  - app_public:/var/www/public
  - storage_data:/var/www/storage
  # ❌ REMOVED: ./config/pulse.php:/var/www/config/pulse.php:ro
  # ❌ REMOVED: ./app/Http/Middleware/LogViewerAuth.php:...
```

---

## 🎯 Expected Result

✅ Build tanpa warning  
✅ Deployment success  
✅ WebSocket working  
✅ Log Viewer accessible  

---

## 📖 Dokumentasi Lengkap

- [DEPLOYMENT_SUMMARY.md](./DEPLOYMENT_SUMMARY.md) - Ringkasan lengkap
- [COOLIFY_DEPLOYMENT_FIX.md](./COOLIFY_DEPLOYMENT_FIX.md) - Penjelasan teknis detail

---

**Waktu Deploy:** ~5 menit  
**Downtime:** ~2 menit
