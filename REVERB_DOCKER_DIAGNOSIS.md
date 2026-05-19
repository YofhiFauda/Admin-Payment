# 🔍 Diagnosis: Reverb Tidak Berfungsi di Docker Compose

## 📋 Ringkasan Masalah

Reverb (WebSocket server Laravel) tidak berfungsi pada project yang berjalan di Docker Compose. Berikut adalah analisis lengkap dan solusi.

---

## 🔎 Analisis Konfigurasi Saat Ini

### 1. **Environment Variables - MASALAH UTAMA** ⚠️

#### File `.env` (Production)
```env
# Internal container connection
REVERB_HOST=reverb          # ✅ Benar untuk backend
REVERB_PORT=8081            # ✅ Benar
REVERB_SCHEME=http          # ✅ Benar untuk internal

# Frontend/browser connection
VITE_REVERB_HOST=sunset-papers-affiliates-committees.trycloudflare.com  # ❌ MASALAH!
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

**MASALAH:** Domain Cloudflare Tunnel (`sunset-papers-affiliates-committees.trycloudflare.com`) kemungkinan:
- Tidak mengarah ke Reverb container
- Tidak dikonfigurasi untuk WebSocket
- Sudah expired/tidak aktif
- Tidak ada routing ke port 8081

### 2. **Docker Compose Configuration**

#### Service Reverb
```yaml
reverb:
  image: whusnet-app:${APP_VERSION:-latest}
  restart: unless-stopped
  ports:
    - "8081:8081"  # ✅ Port exposed
  environment:
    CONTAINER_ROLE: reverb
  volumes:
    - storage_data:/var/www/storage
    - ./config:/var/www/config:ro
    - ./app:/var/www/app:ro
```

**Status:** ✅ Konfigurasi Docker Compose sudah benar

#### Entrypoint Script
```bash
elif [ "$ROLE" = "reverb" ]; then
    echo "📡 Starting Laravel Reverb on 0.0.0.0:8081..."
    exec php artisan reverb:start --host=0.0.0.0 --port=8081
```

**Status:** ✅ Reverb dijalankan dengan benar

### 3. **Nginx Configuration**

```nginx
location /app/ {
    resolver 127.0.0.11 ipv6=off valid=10s;
    set $reverb_upstream http://reverb:8081;
    proxy_pass $reverb_upstream;
    proxy_http_version 1.1;
    
    proxy_set_header Upgrade    $http_upgrade;
    proxy_set_header Connection $connection_upgrade;
    # ... headers lainnya
}
```

**Status:** ✅ Nginx sudah dikonfigurasi untuk proxy WebSocket

### 4. **Broadcasting Configuration**

```php
// config/broadcasting.php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT', 443),
        'scheme' => env('REVERB_SCHEME', 'https'),
        'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
    ],
],
```

**Status:** ✅ Konfigurasi broadcasting sudah benar

### 5. **Echo.js Configuration**

```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: (import.meta.env.VITE_REVERB_HOST || '').replace(/^https?:\/\/|^https?\/\//, ''),
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    authEndpoint: window.location.origin + '/broadcasting/auth',
});
```

**Status:** ✅ Echo.js sudah dikonfigurasi dengan benar

---

## 🎯 Akar Masalah

### **Masalah Utama: Domain Reverb Tidak Sesuai**

Browser mencoba koneksi ke:
```
wss://sunset-papers-affiliates-committees.trycloudflare.com:443/app/
```

Tetapi:
1. Domain ini berbeda dengan domain aplikasi utama (`aaron-requests-affiliates-say.trycloudflare.com`)
2. Cloudflare Tunnel kemungkinan tidak dikonfigurasi untuk WebSocket
3. Tidak ada routing dari domain ini ke Reverb container

### **Masalah Sekunder: Mismatch Domain**

```env
# Domain aplikasi utama
SERVICE_FQDN_NGINX=aaron-requests-affiliates-say.trycloudflare.com

# Domain Reverb (BERBEDA!)
VITE_REVERB_HOST=sunset-papers-affiliates-committees.trycloudflare.com
```

---

## ✅ Solusi

### **Opsi 1: Gunakan Domain yang Sama (RECOMMENDED)** ⭐

Reverb harus diakses melalui domain yang sama dengan aplikasi utama, dengan path `/app/`:

```env
# .env
VITE_REVERB_HOST=aaron-requests-affiliates-say.trycloudflare.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

**Keuntungan:**
- ✅ Tidak perlu konfigurasi tambahan di Cloudflare
- ✅ Nginx sudah dikonfigurasi untuk proxy `/app/` ke Reverb
- ✅ Tidak ada masalah CORS
- ✅ Satu domain untuk semua traffic

**Cara Kerja:**
```
Browser → wss://aaron-requests-affiliates-say.trycloudflare.com/app/
         ↓
Cloudflare Tunnel → Nginx (port 80)
                    ↓
                    location /app/ → reverb:8081
```

### **Opsi 2: Konfigurasi Subdomain Terpisah**

Jika ingin menggunakan subdomain terpisah untuk Reverb:

1. **Buat subdomain di Cloudflare:**
   ```
   ws.admin-payment.whusnet.com → reverb:8081
   ```

2. **Update `.env`:**
   ```env
   VITE_REVERB_HOST=ws.admin-payment.whusnet.com
   VITE_REVERB_PORT=443
   VITE_REVERB_SCHEME=https
   ```

3. **Konfigurasi Cloudflare Tunnel untuk WebSocket**

**Kelemahan:**
- ❌ Perlu konfigurasi DNS tambahan
- ❌ Perlu konfigurasi Cloudflare Tunnel tambahan
- ❌ Lebih kompleks

---

## 🔧 Langkah Perbaikan (Opsi 1 - Recommended)

### 1. Update Environment Variables

```bash
# Edit .env
VITE_REVERB_HOST=aaron-requests-affiliates-say.trycloudflare.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

### 2. Update `.env.example` dan `.env.production.template`

Pastikan template juga diupdate untuk deployment berikutnya:

```env
VITE_REVERB_HOST=admin-payment.whusnet.com  # Sesuaikan dengan domain production
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

### 3. Rebuild Frontend Assets

Karena `VITE_REVERB_*` di-embed saat build:

```bash
# Rebuild Docker image
docker-compose build app

# Atau jika development
npm run build
```

### 4. Restart Services

```bash
docker-compose down
docker-compose up -d
```

### 5. Verifikasi

#### A. Cek Reverb Container Running
```bash
docker-compose ps reverb
docker-compose logs reverb
```

Expected output:
```
📡 Starting Laravel Reverb on 0.0.0.0:8081...
```

#### B. Cek Reverb Health dari Container Lain
```bash
docker-compose exec app nc -zv reverb 8081
```

Expected: `reverb (172.x.x.x:8081) open`

#### C. Cek dari Browser Console
```javascript
// Buka browser console di aplikasi
console.log(window.Echo);
```

Expected: Object dengan `connector.pusher.connection.state = "connected"`

#### D. Test WebSocket Connection
```bash
# Dari host machine
curl -i -N \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: test" \
  http://localhost:8081/app/whusnet_reverb_key_123
```

Expected: HTTP 101 Switching Protocols

---

## 🐛 Debugging Checklist

Jika masih tidak berfungsi setelah perbaikan:

### 1. **Cek Reverb Container Logs**
```bash
docker-compose logs -f reverb
```

Look for:
- ✅ `Starting Laravel Reverb on 0.0.0.0:8081`
- ❌ Connection errors
- ❌ Port binding errors

### 2. **Cek Nginx Logs**
```bash
docker-compose logs -f nginx | grep "/app/"
```

Look for:
- ✅ 101 Switching Protocols
- ❌ 502 Bad Gateway
- ❌ 504 Gateway Timeout

### 3. **Cek Browser Console**
```javascript
// Buka Developer Tools → Console
window.Echo.connector.pusher.connection.bind('state_change', function(states) {
    console.log('WebSocket state:', states.current);
});
```

Expected states:
- `initialized` → `connecting` → `connected`

### 4. **Cek Network Tab**
- Buka Developer Tools → Network → WS (WebSocket)
- Look for connection to `/app/`
- Status should be `101 Switching Protocols`

### 5. **Test Broadcasting Auth**
```bash
# Dari container app
docker-compose exec app php artisan tinker

# Di tinker:
broadcast(new \App\Events\TestEvent());
```

### 6. **Cek Environment Variables di Container**
```bash
docker-compose exec reverb env | grep REVERB
docker-compose exec app env | grep REVERB
```

Pastikan semua variable terisi dengan benar.

---

## 📝 Catatan Penting

### 1. **VITE Variables di-embed saat Build**

Variable `VITE_*` di-embed ke JavaScript bundle saat `npm run build`. Jika mengubah `.env`, harus rebuild:

```bash
# Development
npm run build

# Production (Docker)
docker-compose build app
```

### 2. **Cloudflare Tunnel Limitations**

Cloudflare Tunnel gratis memiliki batasan:
- WebSocket timeout (biasanya 100 detik)
- Tidak semua region support WebSocket dengan baik
- Perlu konfigurasi khusus untuk WebSocket

### 3. **Nginx WebSocket Timeout**

Nginx dikonfigurasi dengan:
```nginx
proxy_read_timeout 300s;  # 5 menit
```

Jika koneksi terputus sebelum 5 menit, cek Cloudflare Tunnel timeout.

### 4. **Redis Connection**

Reverb menggunakan Redis untuk pub/sub. Pastikan Redis berjalan:

```bash
docker-compose ps redis
docker-compose exec app redis-cli -h $REDIS_HOST ping
```

Expected: `PONG`

---

## 🎓 Penjelasan Arsitektur

### Flow Koneksi WebSocket

```
┌─────────────────────────────────────────────────────────────────┐
│                         BROWSER                                 │
│  wss://aaron-requests-affiliates-say.trycloudflare.com/app/    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    CLOUDFLARE TUNNEL                            │
│  (SSL Termination, WebSocket Support)                           │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    NGINX (Port 80)                              │
│  location /app/ {                                               │
│    proxy_pass http://reverb:8081;                               │
│    proxy_http_version 1.1;                                      │
│    proxy_set_header Upgrade $http_upgrade;                      │
│    proxy_set_header Connection "upgrade";                       │
│  }                                                               │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    REVERB CONTAINER                             │
│  php artisan reverb:start --host=0.0.0.0 --port=8081           │
│  (Laravel Reverb WebSocket Server)                              │
└─────────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    REDIS CONTAINER                              │
│  (Pub/Sub untuk broadcasting)                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Broadcasting Auth Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  1. Browser → POST /broadcasting/auth                           │
│     Headers: Cookie (Laravel session), X-CSRF-TOKEN             │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. Laravel → routes/channels.php                               │
│     Cek authorization untuk channel yang diminta                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. Return auth signature                                       │
│     { auth: "signature", channel_data: {...} }                  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  4. Browser → WebSocket connection dengan signature             │
│     wss://.../app/?auth=signature                               │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  5. Reverb validates signature → Connection established         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Quick Fix Commands

```bash
# 1. Update .env
sed -i 's/VITE_REVERB_HOST=.*/VITE_REVERB_HOST=aaron-requests-affiliates-say.trycloudflare.com/' .env

# 2. Rebuild dan restart
docker-compose build app
docker-compose down
docker-compose up -d

# 3. Cek logs
docker-compose logs -f reverb

# 4. Test connection
docker-compose exec app nc -zv reverb 8081
```

---

## 📚 Referensi

- [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb)
- [Laravel Broadcasting Documentation](https://laravel.com/docs/11.x/broadcasting)
- [Laravel Echo Documentation](https://laravel.com/docs/11.x/broadcasting#client-side-installation)
- [Nginx WebSocket Proxying](https://nginx.org/en/docs/http/websocket.html)
- [Cloudflare Tunnel WebSocket Support](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/configuration/websocket/)

---

**Dibuat:** 2026-05-19  
**Status:** Diagnosis Complete - Awaiting Fix Implementation
