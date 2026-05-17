# Migrasi dari Cloudflare Tunnel ke Domain Sendiri

## 📋 Overview

Panduan ini menjelaskan perubahan yang diperlukan di `.env` dan `.env.production` saat migrasi dari Cloudflare Tunnel ke domain sendiri.

---

## 🔄 Perubahan yang Diperlukan

### Asumsi Domain Baru
Misalnya domain Anda: `admin.whusnet.com`

---

## 📝 File: `.env` (Local Development)

### 1. **Application URLs**

```env
# ─── SEBELUM (Cloudflare Tunnel) ───────────────────────────────────
SERVICE_URL_NGINX=https://layer-silver-armstrong-speech.trycloudflare.com
APP_URL=https://layer-silver-armstrong-speech.trycloudflare.com

# ─── SESUDAH (Domain Sendiri) ──────────────────────────────────────
SERVICE_URL_NGINX=https://admin.whusnet.com
APP_URL=https://admin.whusnet.com
```

**Catatan:**
- `APP_URL` digunakan untuk generate URL di aplikasi (email, notifications, dll)
- Harus menggunakan HTTPS jika production

---

### 2. **APP_DOMAIN (Optional)**

```env
# ─── SEBELUM ───────────────────────────────────────────────────────
APP_DOMAIN=

# ─── SESUDAH ───────────────────────────────────────────────────────
APP_DOMAIN=admin.whusnet.com
```

**Catatan:**
- Digunakan untuk session domain restriction
- Biarkan kosong jika tidak perlu restrict subdomain

---

### 3. **Laravel Reverb (WebSocket)**

```env
# ─── SEBELUM (Cloudflare Tunnel) ───────────────────────────────────
VITE_REVERB_HOST=requiring-barriers-merry-every.trycloudflare.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# ─── SESUDAH (Domain Sendiri) ──────────────────────────────────────
# Opsi 1: WebSocket di subdomain terpisah
VITE_REVERB_HOST=ws.admin.whusnet.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# Opsi 2: WebSocket di path yang sama (dengan reverse proxy)
VITE_REVERB_HOST=admin.whusnet.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

**Catatan:**
- `VITE_REVERB_HOST` adalah hostname yang diakses dari **browser client**
- Jika menggunakan subdomain, pastikan DNS sudah dikonfigurasi
- Port 443 untuk HTTPS, port 80 untuk HTTP

---

### 4. **Session Configuration**

```env
# ─── SEBELUM ───────────────────────────────────────────────────────
SESSION_DOMAIN=null

# ─── SESUDAH (Jika ingin restrict ke domain) ──────────────────────
# Opsi 1: Restrict ke domain utama saja
SESSION_DOMAIN=admin.whusnet.com

# Opsi 2: Allow semua subdomain (dengan leading dot)
SESSION_DOMAIN=.whusnet.com

# Opsi 3: Tidak restrict (recommended untuk simplicity)
SESSION_DOMAIN=null
```

**Rekomendasi:** Biarkan `null` kecuali ada kebutuhan khusus

---

### 5. **Horizon Domain (Optional)**

```env
# ─── SEBELUM ───────────────────────────────────────────────────────
HORIZON_DOMAIN=null

# ─── SESUDAH (Jika ingin subdomain terpisah) ──────────────────────
# Opsi 1: Subdomain terpisah
HORIZON_DOMAIN=horizon.admin.whusnet.com

# Opsi 2: Path di domain utama (recommended)
HORIZON_DOMAIN=null
```

**Rekomendasi:** Biarkan `null` dan akses via `/horizon`

---

## 📝 File: `.env.production` (Production)

Perubahan yang sama seperti `.env`, tapi dengan domain production:

```env
# ═══════════════════════════════════════════════════════════════════
#  PERUBAHAN UNTUK DOMAIN SENDIRI
# ═══════════════════════════════════════════════════════════════════

# ─── Coolify / Service Discovery ──────────────────────────────────
SERVICE_FQDN_APP=admin.whusnet.com
SERVICE_URL_APP=https://admin.whusnet.com
SERVICE_FQDN_NGINX=admin.whusnet.com
SERVICE_URL_NGINX=https://admin.whusnet.com
SERVICE_FQDN_REVERB=ws.admin.whusnet.com
SERVICE_URL_REVERB=https://ws.admin.whusnet.com

# ─── Application ───────────────────────────────────────────────────
APP_URL=https://admin.whusnet.com
APP_DOMAIN=admin.whusnet.com

# ─── Laravel Reverb (WebSocket) ────────────────────────────────────
# Internal container connection (TIDAK BERUBAH)
REVERB_HOST=reverb
REVERB_PORT=8081
REVERB_SCHEME=http

# Frontend/browser connection (BERUBAH)
VITE_REVERB_HOST=ws.admin.whusnet.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# ─── Session ───────────────────────────────────────────────────────
SESSION_DOMAIN=null
# Atau jika ingin restrict:
# SESSION_DOMAIN=.whusnet.com

# ─── Horizon ───────────────────────────────────────────────────────
HORIZON_DOMAIN=null
```

---

## 🌐 Konfigurasi DNS

### Setup DNS Records

```
# A Record untuk aplikasi utama
admin.whusnet.com.     A     <IP_SERVER_ANDA>

# A Record untuk WebSocket (jika subdomain terpisah)
ws.admin.whusnet.com.  A     <IP_SERVER_ANDA>

# Atau CNAME jika menggunakan subdomain
ws.admin.whusnet.com.  CNAME admin.whusnet.com.
```

---

## 🔧 Konfigurasi Reverse Proxy

### Jika Menggunakan Nginx/Traefik/Caddy

#### Opsi 1: WebSocket di Subdomain Terpisah

**Nginx Config:**
```nginx
# Main app
server {
    listen 443 ssl http2;
    server_name admin.whusnet.com;
    
    # SSL certificates
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# WebSocket
server {
    listen 443 ssl http2;
    server_name ws.admin.whusnet.com;
    
    # SSL certificates
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        proxy_pass http://localhost:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

#### Opsi 2: WebSocket di Path `/ws` (Recommended)

**Nginx Config:**
```nginx
server {
    listen 443 ssl http2;
    server_name admin.whusnet.com;
    
    # SSL certificates
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # WebSocket endpoint
    location /ws {
        proxy_pass http://localhost:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
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

**Update .env untuk Opsi 2:**
```env
VITE_REVERB_HOST=admin.whusnet.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
VITE_REVERB_PATH=/ws  # Tambahkan ini
```

---

### Jika Menggunakan Coolify

Coolify biasanya handle reverse proxy otomatis. Yang perlu dilakukan:

1. **Set Domain di Coolify Dashboard:**
   - Service → Settings → Domains
   - Add domain: `admin.whusnet.com`
   - Add domain untuk Reverb: `ws.admin.whusnet.com` (jika subdomain terpisah)

2. **Enable SSL:**
   - Coolify akan otomatis generate Let's Encrypt certificate
   - Atau upload custom certificate

3. **Update Environment Variables:**
   - Update `APP_URL` dan `VITE_REVERB_HOST` di Coolify dashboard
   - Atau update `.env.production` dan redeploy

---

## 🔐 SSL Certificate

### Opsi 1: Let's Encrypt (Recommended)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d admin.whusnet.com -d ws.admin.whusnet.com

# Auto-renewal
sudo certbot renew --dry-run
```

---

### Opsi 2: Cloudflare SSL (Jika menggunakan Cloudflare DNS)

1. **Cloudflare Dashboard:**
   - SSL/TLS → Overview → Full (strict)
   - SSL/TLS → Origin Server → Create Certificate
   - Download certificate dan key

2. **Install di Server:**
```bash
# Copy certificate
sudo cp origin-cert.pem /etc/ssl/certs/admin.whusnet.com.pem
sudo cp origin-key.pem /etc/ssl/private/admin.whusnet.com.key

# Update nginx config
ssl_certificate /etc/ssl/certs/admin.whusnet.com.pem;
ssl_certificate_key /etc/ssl/private/admin.whusnet.com.key;
```

---

## 📋 Checklist Migrasi

### Pre-Migration
- [ ] Domain sudah terdaftar dan DNS sudah dikonfigurasi
- [ ] SSL certificate sudah ready
- [ ] Reverse proxy sudah dikonfigurasi
- [ ] Backup database dan .env production
- [ ] Test DNS resolution: `nslookup admin.whusnet.com`
- [ ] Test SSL: `curl -I https://admin.whusnet.com`

---

### Migration Steps

#### 1. Update .env.production
```bash
# Backup dulu
cp .env.production .env.production.backup

# Edit .env.production
nano .env.production

# Update:
# - APP_URL
# - SERVICE_URL_NGINX
# - VITE_REVERB_HOST
# - APP_DOMAIN (optional)
# - SESSION_DOMAIN (optional)
```

---

#### 2. Rebuild Frontend Assets
```bash
# Karena VITE_REVERB_HOST berubah, perlu rebuild
npm run build

# Atau jika menggunakan Docker
docker-compose build app --no-cache
```

---

#### 3. Clear Caches
```bash
docker exec admin-payment-app php artisan config:clear
docker exec admin-payment-app php artisan cache:clear
docker exec admin-payment-app php artisan route:clear
docker exec admin-payment-app php artisan view:clear
```

---

#### 4. Rebuild Caches
```bash
docker exec admin-payment-app php artisan config:cache
docker exec admin-payment-app php artisan route:cache
docker exec admin-payment-app php artisan view:cache
```

---

#### 5. Restart Services
```bash
docker-compose restart app nginx reverb
```

---

### Post-Migration Testing

#### 1. Test Main App
```bash
# Test HTTP response
curl -I https://admin.whusnet.com

# Expected: 200 OK
```

#### 2. Test WebSocket
```bash
# Test WebSocket connection
wscat -c wss://ws.admin.whusnet.com

# Atau jika di path /ws
wscat -c wss://admin.whusnet.com/ws

# Expected: Connected
```

#### 3. Test di Browser
- [ ] Akses `https://admin.whusnet.com`
- [ ] Login berhasil
- [ ] Session persist setelah refresh
- [ ] WebSocket connected (cek DevTools Console)
- [ ] Real-time features berfungsi (notifications, live updates)
- [ ] No mixed content warnings
- [ ] No CORS errors

#### 4. Test SSL
```bash
# Test SSL certificate
openssl s_client -connect admin.whusnet.com:443 -servername admin.whusnet.com

# Check SSL grade
# https://www.ssllabs.com/ssltest/analyze.html?d=admin.whusnet.com
```

---

## 🐛 Troubleshooting

### Issue 1: WebSocket Connection Failed

**Symptom:** Console error: `WebSocket connection to 'wss://...' failed`

**Solutions:**

1. **Check Reverse Proxy Config:**
```bash
# Test WebSocket endpoint
curl -I http://localhost:8081

# Expected: 101 Switching Protocols atau 200 OK
```

2. **Check Reverb Container:**
```bash
docker logs whusnet-reverb --tail=50

# Expected: "Reverb server started"
```

3. **Check Firewall:**
```bash
# Allow WebSocket port
sudo ufw allow 8081/tcp
```

---

### Issue 2: Mixed Content Warning

**Symptom:** Browser console: `Mixed Content: The page at 'https://...' was loaded over HTTPS, but requested an insecure resource`

**Solution:**
```env
# Pastikan semua URL menggunakan HTTPS
APP_URL=https://admin.whusnet.com
VITE_REVERB_SCHEME=https
```

---

### Issue 3: Session Not Persisting

**Symptom:** User logged out setelah refresh

**Solutions:**

1. **Check Session Domain:**
```env
# Jika menggunakan subdomain, pastikan SESSION_DOMAIN benar
SESSION_DOMAIN=.whusnet.com

# Atau biarkan null
SESSION_DOMAIN=null
```

2. **Check Cookie Settings:**
```env
SESSION_SECURE_COOKIE=true  # Untuk HTTPS
SESSION_SAME_SITE=lax
```

3. **Check Redis:**
```bash
docker exec admin-payment-app php artisan redis:ping
# Expected: PONG
```

---

### Issue 4: CORS Error

**Symptom:** Console error: `Access to XMLHttpRequest at '...' from origin '...' has been blocked by CORS policy`

**Solution:**

1. **Check APP_URL:**
```env
# Pastikan APP_URL match dengan domain yang diakses
APP_URL=https://admin.whusnet.com
```

2. **Check Reverb Config:**
```env
# Pastikan VITE_REVERB_HOST match dengan domain
VITE_REVERB_HOST=ws.admin.whusnet.com
```

---

### Issue 5: SSL Certificate Error

**Symptom:** Browser warning: "Your connection is not private"

**Solutions:**

1. **Check Certificate:**
```bash
openssl x509 -in /path/to/cert.pem -text -noout

# Check:
# - Subject: CN=admin.whusnet.com
# - Subject Alternative Name: DNS:admin.whusnet.com, DNS:ws.admin.whusnet.com
# - Not After: (expiry date)
```

2. **Renew Certificate:**
```bash
sudo certbot renew
sudo systemctl reload nginx
```

---

## 📊 Summary Perubahan

### File yang Perlu Diubah

| File | Variable | Perubahan |
|------|----------|-----------|
| `.env` | `APP_URL` | Cloudflare URL → Domain sendiri |
| `.env` | `SERVICE_URL_NGINX` | Cloudflare URL → Domain sendiri |
| `.env` | `VITE_REVERB_HOST` | Cloudflare URL → Domain/subdomain sendiri |
| `.env` | `APP_DOMAIN` | Empty → Domain sendiri (optional) |
| `.env` | `SESSION_DOMAIN` | null → Domain sendiri (optional) |
| `.env.production` | (sama seperti .env) | |

### File yang TIDAK Perlu Diubah

- ✅ Database configuration (DB_HOST, DB_PORT, dll)
- ✅ Redis configuration (REDIS_HOST, REDIS_PORT, dll)
- ✅ Internal Reverb config (REVERB_HOST=reverb, REVERB_PORT=8081)
- ✅ Telegram, N8N, Gemini configuration
- ✅ Pulse, Log Viewer configuration
- ✅ Session driver, cache driver

---

## 🎯 Quick Reference

### Minimal Changes (Recommended)

Jika ingin perubahan minimal, cukup ubah 3 variable ini:

```env
# .env dan .env.production
APP_URL=https://admin.whusnet.com
SERVICE_URL_NGINX=https://admin.whusnet.com
VITE_REVERB_HOST=admin.whusnet.com
```

Kemudian:
1. Rebuild frontend: `npm run build`
2. Clear caches: `php artisan config:clear && php artisan cache:clear`
3. Restart: `docker-compose restart`

---

## 📞 Support

Jika ada masalah setelah migrasi:

1. Check logs:
```bash
docker logs whusnet-app --tail=100
docker logs whusnet-nginx --tail=100
docker logs whusnet-reverb --tail=100
```

2. Test connectivity:
```bash
curl -I https://admin.whusnet.com
wscat -c wss://admin.whusnet.com
```

3. Rollback jika perlu:
```bash
cp .env.production.backup .env.production
docker-compose restart
```

---

**Last Updated:** 2024-01-15
**Status:** Ready for Migration
