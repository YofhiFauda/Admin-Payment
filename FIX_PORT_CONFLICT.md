# 🔧 Fix: Port Already Allocated Error

## 🔴 Error yang Terjadi

```
Error response from daemon: driver failed programming external connectivity 
on endpoint nginx-cd7rtyyg5s44on8cwrg0abht-144712300417: 
Bind for 0.0.0.0:8000 failed: port is already allocated
```

---

## 🎯 Penyebab

### 1. **Container Lama Masih Running**
- Deployment sebelumnya gagal
- Container lama tidak di-stop
- Port 8000 dan 8081 masih digunakan

### 2. **Port Mapping Tidak Diperlukan di Coolify**
- Coolify menggunakan **Traefik** sebagai reverse proxy
- Traefik yang handle routing dari domain ke container
- Port mapping eksplisit (`8000:80`) tidak diperlukan
- Cukup gunakan `expose:` untuk internal network

---

## ✅ Solusi yang Diterapkan

### 1. **Ganti `ports:` dengan `expose:`**

#### Sebelum (❌ Bermasalah):
```yaml
nginx:
  ports:
    - "8000:80"  # Bind ke host port 8000

reverb:
  ports:
    - "8081:8081"  # Bind ke host port 8081
```

#### Sesudah (✅ Fixed):
```yaml
nginx:
  expose:
    - "80"  # Expose ke internal network saja
  # ports:
  #   - "8000:80"  # Comment untuk Coolify

reverb:
  expose:
    - "8081"  # Expose ke internal network saja
  # ports:
  #   - "8081:8081"  # Comment untuk Coolify
```

---

## 🔍 Penjelasan Teknis

### Coolify Architecture

```
Internet
   ↓
Traefik (Coolify Proxy)
   ↓
Docker Network: coolify
   ↓
┌─────────────────────────────────┐
│ nginx:80 (internal)             │
│   ↓                             │
│ app:9000 (PHP-FPM)              │
│                                 │
│ reverb:8081 (WebSocket)         │
└─────────────────────────────────┘
```

**Flow:**
1. User → `https://admin-payment.whusnet.com`
2. Traefik → route ke `nginx:80` (internal)
3. Nginx → proxy ke `app:9000` (PHP-FPM)

**WebSocket:**
1. User → `wss://admin-payment.whusnet.com`
2. Traefik → route ke `reverb:8081` (internal)

**Tidak perlu expose port ke host!**

---

## 🚀 Cara Deploy Ulang

### 1. **Commit Perubahan**
```bash
git add docker-compose.yaml
git commit -m "fix: remove port mappings for Coolify compatibility"
git push origin master
```

### 2. **Stop Container Lama (Jika Perlu)**

Jika masih error, stop container lama dulu:

```bash
# Via Coolify Dashboard
# → Applications → WHUSNET Admin Payment → Stop

# Atau via SSH ke server
docker ps | grep whusnet
docker stop whusnet-nginx whusnet-reverb
docker rm whusnet-nginx whusnet-reverb
```

### 3. **Redeploy di Coolify**
- Buka Coolify Dashboard
- Pilih aplikasi "WHUSNET Admin Payment"
- Klik **"Redeploy"**

---

## 🔍 Verifikasi

### 1. Check Container Status
```bash
docker ps | grep whusnet
```

Expected output (tanpa port mapping ke host):
```
whusnet-nginx      Up X minutes   80/tcp
whusnet-reverb     Up X minutes   8081/tcp
```

**Perhatikan:** Tidak ada `0.0.0.0:8000->80` atau `0.0.0.0:8081->8081`

### 2. Check Traefik Routing
```bash
# Check Traefik logs
docker logs coolify-proxy | grep admin-payment

# Should show routing rules
```

### 3. Test Endpoints
```bash
# Main app (via Traefik)
curl -I https://admin-payment.whusnet.com

# WebSocket (via Traefik)
curl -I https://admin-payment.whusnet.com
# Browser console should show WebSocket connection
```

---

## 🛠️ Local Development

Untuk testing lokal (bukan Coolify), uncomment port mappings:

```yaml
# docker-compose.yaml (local testing)
nginx:
  ports:
    - "8000:80"  # Uncomment untuk local

reverb:
  ports:
    - "8081:8081"  # Uncomment untuk local
```

Atau gunakan `docker-compose.dev.yaml`:
```bash
docker-compose -f docker-compose.yaml -f docker-compose.dev.yaml up
```

---

## 📊 Perbandingan

| Aspek | Dengan `ports:` | Dengan `expose:` |
|-------|----------------|------------------|
| **Coolify Compatible** | ❌ Port conflict | ✅ No conflict |
| **Traefik Routing** | ⚠️ Works but redundant | ✅ Optimal |
| **Security** | ⚠️ Port exposed to host | ✅ Internal only |
| **Local Testing** | ✅ Easy (localhost:8000) | ⚠️ Need Traefik |
| **Production** | ❌ Not recommended | ✅ Best practice |

---

## 🆘 Troubleshooting

### Error: Port masih allocated setelah fix

**Solusi 1: Stop container lama**
```bash
# List all containers
docker ps -a | grep whusnet

# Stop & remove
docker stop $(docker ps -a | grep whusnet | awk '{print $1}')
docker rm $(docker ps -a | grep whusnet | awk '{print $1}')

# Redeploy
```

**Solusi 2: Gunakan port berbeda (temporary)**
```yaml
# Temporary workaround
nginx:
  ports:
    - "8001:80"  # Gunakan port berbeda

reverb:
  ports:
    - "8082:8081"  # Gunakan port berbeda
```

### Error: Cannot access via domain

**Solusi: Check Traefik configuration**
```bash
# Check Traefik logs
docker logs coolify-proxy

# Check container network
docker network inspect coolify

# Verify container in coolify network
docker inspect whusnet-nginx | grep coolify
```

---

## ✅ Checklist

- [x] Ganti `ports:` dengan `expose:` di nginx
- [x] Ganti `ports:` dengan `expose:` di reverb
- [x] Tambah komentar penjelasan
- [x] Commit & push perubahan
- [ ] Stop container lama (jika perlu)
- [ ] Redeploy di Coolify
- [ ] Verifikasi deployment berhasil
- [ ] Test akses via domain
- [ ] Test WebSocket connection

---

## 📚 Referensi

- [Docker Compose Networking](https://docs.docker.com/compose/networking/)
- [Coolify Traefik Documentation](https://coolify.io/docs/knowledge-base/traefik)
- [Docker Expose vs Ports](https://docs.docker.com/compose/compose-file/compose-file-v3/#expose)

---

**Status:** ✅ Fixed  
**Tanggal:** 2026-05-17  
**Next:** Redeploy di Coolify
