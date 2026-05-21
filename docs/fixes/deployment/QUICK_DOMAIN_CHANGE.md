# Quick Guide: Ganti Domain dari Cloudflare Tunnel ke Domain Sendiri

## 🎯 TL;DR - Yang Perlu Diubah

Jika domain baru Anda: `admin.whusnet.com`

### 1. Edit `.env` dan `.env.production`

```bash
# Cari dan ganti semua URL Cloudflare dengan domain Anda
# Contoh: layer-silver-armstrong-speech.trycloudflare.com → admin.whusnet.com
```

**3 Variable Wajib:**
```env
APP_URL=https://admin.whusnet.com
SERVICE_URL_NGINX=https://admin.whusnet.com
VITE_REVERB_HOST=admin.whusnet.com
```

**2 Variable Optional:**
```env
APP_DOMAIN=admin.whusnet.com
SESSION_DOMAIN=null
```

---

## 📝 Langkah Cepat

### Step 1: Backup
```bash
cp .env.production .env.production.backup
```

### Step 2: Find & Replace
```bash
# Ganti semua Cloudflare URL dengan domain Anda
sed -i 's/aus-relatively-mouth-factor.trycloudflare.com/admin.whusnet.com/g' .env.production
sed -i 's/requiring-barriers-merry-every.trycloudflare.com/admin.whusnet.com/g' .env.production
```

### Step 3: Rebuild Frontend
```bash
# Karena VITE_REVERB_HOST berubah
npm run build

# Atau jika Docker
docker-compose build app --no-cache
```

### Step 4: Clear & Rebuild Cache
```bash
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan cache:clear
docker exec admin-payment-app php artisan config:cache
docker exec admin-payment-app php artisan route:cache
```

### Step 5: Restart
```bash
docker-compose restart app nginx reverb
```

### Step 6: Test
```bash
# Test main app
curl -I https://admin.whusnet.com

# Test WebSocket
wscat -c wss://admin.whusnet.com

# Test di browser
# - Login
# - Cek DevTools Console (no errors)
# - Test real-time features
```

---

## 🔍 Detail Perubahan

### File: `.env` dan `.env.production`

#### Section: Coolify / Service Discovery
```env
# SEBELUM
SERVICE_URL_NGINX=https://aus-relatively-mouth-factor.trycloudflare.com

# SESUDAH
SERVICE_URL_NGINX=https://admin.whusnet.com
```

#### Section: Application
```env
# SEBELUM
APP_URL=https://aus-relatively-mouth-factor.trycloudflare.com
APP_DOMAIN=

# SESUDAH
APP_URL=https://admin.whusnet.com
APP_DOMAIN=admin.whusnet.com  # Optional
```

#### Section: Laravel Reverb
```env
# SEBELUM
VITE_REVERB_HOST=requiring-barriers-merry-every.trycloudflare.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# SESUDAH - Opsi 1: Subdomain terpisah
VITE_REVERB_HOST=ws.admin.whusnet.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# SESUDAH - Opsi 2: Domain yang sama (Recommended)
VITE_REVERB_HOST=admin.whusnet.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

#### Section: Session (Optional)
```env
# SEBELUM
SESSION_DOMAIN=null

# SESUDAH - Opsi 1: Tidak restrict (Recommended)
SESSION_DOMAIN=null

# SESUDAH - Opsi 2: Restrict ke domain
SESSION_DOMAIN=admin.whusnet.com

# SESUDAH - Opsi 3: Allow semua subdomain
SESSION_DOMAIN=.whusnet.com
```

---

## ⚠️ Yang TIDAK Perlu Diubah

```env
# Internal container connections (JANGAN DIUBAH)
REVERB_HOST=reverb
REVERB_PORT=8081
REVERB_SCHEME=http

# Database (JANGAN DIUBAH kecuali pindah server)
DB_HOST=s4g9fygoajcwzuphriodko8z
DB_PORT=3306
DB_DATABASE=admin_payment

# Redis (JANGAN DIUBAH kecuali pindah server)
REDIS_HOST=tn59dithi7858uejuz5pr8g1
REDIS_PORT=6379

# Session settings (SUDAH BENAR)
SESSION_DRIVER=redis
SESSION_ENCRYPT=false
SESSION_SAME_SITE=lax
SESSION_SECURE_COOKIE=true

# Pulse, Log Viewer, dll (TIDAK PERLU DIUBAH)
```

---

## 🌐 Setup DNS

```
# A Record
admin.whusnet.com.     A     <IP_SERVER_ANDA>

# Jika WebSocket di subdomain terpisah
ws.admin.whusnet.com.  A     <IP_SERVER_ANDA>
```

**Test DNS:**
```bash
nslookup admin.whusnet.com
# Expected: IP server Anda
```

---

## 🔧 Setup Reverse Proxy (Jika Belum)

### Nginx Config (Minimal)
```nginx
server {
    listen 443 ssl http2;
    server_name admin.whusnet.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # WebSocket
    location /ws {
        proxy_pass http://localhost:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }
    
    # Main app
    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### SSL Certificate (Let's Encrypt)
```bash
sudo certbot --nginx -d admin.whusnet.com
```

---

## ✅ Checklist

### Pre-Migration
- [ ] Domain sudah terdaftar
- [ ] DNS A record sudah dikonfigurasi
- [ ] SSL certificate sudah ready
- [ ] Reverse proxy sudah dikonfigurasi
- [ ] Backup `.env.production`

### Migration
- [ ] Update `APP_URL` di `.env.production`
- [ ] Update `SERVICE_URL_NGINX` di `.env.production`
- [ ] Update `VITE_REVERB_HOST` di `.env.production`
- [ ] Rebuild frontend: `npm run build`
- [ ] Clear caches
- [ ] Restart containers

### Post-Migration
- [ ] Test main app: `curl -I https://admin.whusnet.com`
- [ ] Test WebSocket: `wscat -c wss://admin.whusnet.com`
- [ ] Login di browser
- [ ] Session persist setelah refresh
- [ ] WebSocket connected (cek DevTools)
- [ ] Real-time features berfungsi
- [ ] No errors di console

---

## 🐛 Troubleshooting

### WebSocket Connection Failed
```bash
# Check Reverb container
docker logs whusnet-reverb --tail=50

# Check reverse proxy
curl -I http://localhost:8081

# Check firewall
sudo ufw status
```

### Session Not Persisting
```bash
# Check Redis
docker exec admin-payment-app php artisan redis:ping

# Check session config
docker exec admin-payment-app php artisan tinker --execute="
    echo 'Driver: ' . config('session.driver') . PHP_EOL;
    echo 'Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
    echo 'SameSite: ' . config('session.same_site') . PHP_EOL;
"
```

### Mixed Content Warning
```env
# Pastikan semua URL HTTPS
APP_URL=https://admin.whusnet.com
VITE_REVERB_SCHEME=https
```

---

## 📞 Rollback

Jika ada masalah:
```bash
# Restore backup
cp .env.production.backup .env.production

# Clear caches
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan cache:clear

# Restart
docker-compose restart
```

---

## 📚 Dokumentasi Lengkap

Untuk panduan detail, lihat: `MIGRATION_TO_CUSTOM_DOMAIN.md`

---

**Last Updated:** 2024-01-15
**Estimated Time:** 15-30 menit
