# ⚡ Quick Fix: Port Already Allocated

## 🔴 Error

```
Bind for 0.0.0.0:8000 failed: port is already allocated
```

---

## ✅ Solusi (2 Langkah)

### 1️⃣ Update docker-compose.yaml

**Ganti `ports:` dengan `expose:`:**

```yaml
# nginx
expose:
  - "80"
# ports:
#   - "8000:80"  # Comment ini

# reverb  
expose:
  - "8081"
# ports:
#   - "8081:8081"  # Comment ini
```

### 2️⃣ Commit & Redeploy

```bash
git add docker-compose.yaml
git commit -m "fix: use expose instead of ports for Coolify"
git push origin master

# Redeploy di Coolify Dashboard
```

---

## 🤔 Mengapa?

**Coolify menggunakan Traefik** sebagai reverse proxy:
- ✅ Traefik route traffic dari domain ke container
- ✅ Tidak perlu expose port ke host
- ✅ Cukup `expose:` untuk internal network
- ❌ `ports:` menyebabkan port conflict

---

## 📖 Dokumentasi Lengkap

- [FIX_PORT_CONFLICT.md](./FIX_PORT_CONFLICT.md) - Penjelasan detail
- [DEPLOYMENT_SUMMARY.md](./DEPLOYMENT_SUMMARY.md) - Ringkasan lengkap

---

**Waktu Fix:** ~2 menit  
**Status:** ✅ Ready to deploy
