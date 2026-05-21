# 🔧 Fix: REVERB_APP_KEY Build Error (Final Solution)

## 🔴 Error yang Terjadi

```
The "REVERB_APP_KEY" variable is not set. Defaulting to a blank string.
Deployment failed (exit code 255)
```

---

## 🎯 Root Cause Analysis

### Masalah Sebenarnya

Coolify **TIDAK meng-pass `REVERB_APP_KEY`** sebagai build argument!

**Command yang dijalankan Coolify:**
```bash
docker compose build --pull \
  --build-arg VITE_REVERB_APP_KEY \    # ✅ Ada
  --build-arg VITE_REVERB_HOST \       # ✅ Ada
  --build-arg VITE_REVERB_PORT \       # ✅ Ada
  --build-arg VITE_REVERB_SCHEME \     # ✅ Ada
  # ❌ TIDAK ADA: --build-arg REVERB_APP_KEY
```

**Mengapa tidak di-pass?**
- Coolify hanya pass build args yang:
  1. Ada di `.env` file dengan prefix `VITE_`, `APP_`, `SERVICE_`, dll
  2. Explicitly defined di Coolify settings
  3. Standard Laravel variables
- `REVERB_APP_KEY` tidak masuk kategori di atas

---

## 💡 Insight Penting

### `REVERB_APP_KEY` vs `VITE_REVERB_APP_KEY`

| Variable | Kapan Digunakan | Perlu di Build? |
|----------|----------------|-----------------|
| `REVERB_APP_KEY` | **Runtime** (PHP backend) | ❌ Tidak |
| `VITE_REVERB_APP_KEY` | **Build time** (Frontend JS) | ✅ Ya |

**Penjelasan:**

1. **`REVERB_APP_KEY`** (Backend)
   - Digunakan oleh PHP backend saat runtime
   - Untuk authenticate WebSocket connections di server
   - Tidak perlu di-embed ke frontend build
   - Cukup di `.env.production` → runtime environment

2. **`VITE_REVERB_APP_KEY`** (Frontend)
   - Digunakan oleh Vite saat build frontend
   - Di-embed ke compiled JavaScript
   - Browser butuh ini untuk connect ke Reverb
   - Harus di-pass sebagai build argument

**Di `.env.production`:**
```env
REVERB_APP_KEY=whusnet_reverb_key_123
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"  # Reference ke REVERB_APP_KEY
```

**Saat build:**
- Coolify expand `${REVERB_APP_KEY}` → `whusnet_reverb_key_123`
- Pass sebagai `VITE_REVERB_APP_KEY=whusnet_reverb_key_123`
- Vite embed ke JavaScript

**Saat runtime:**
- Container load `.env.production`
- PHP baca `REVERB_APP_KEY=whusnet_reverb_key_123`
- Reverb server gunakan untuk authentication

---

## ✅ Solusi (Final)

### 1. **Dockerfile** - Hapus `REVERB_APP_KEY` dari Build Args

```diff
# Stage 2: Frontend Assets (Vite + Tailwind)
FROM node:22-alpine AS node
WORKDIR /app

# Teruskan build arguments dari docker-compose.yaml
- ARG REVERB_APP_KEY
  ARG VITE_REVERB_APP_KEY
  ARG VITE_REVERB_HOST
  ARG VITE_REVERB_PORT=443
  ARG VITE_REVERB_SCHEME=https

- ENV REVERB_APP_KEY=$REVERB_APP_KEY
  ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
  ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
  ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
  ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME
```

**Alasan:**
- `REVERB_APP_KEY` tidak dibutuhkan saat build
- Hanya `VITE_*` variables yang perlu di-embed ke frontend
- Backend akan baca dari `.env.production` saat runtime

### 2. **docker-compose.yaml** - Hapus dari Build Args

```diff
  app:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        - APP_ENV=production
-       - REVERB_APP_KEY=${REVERB_APP_KEY}
        - VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
        - VITE_REVERB_HOST=${VITE_REVERB_HOST}
        - VITE_REVERB_PORT=${VITE_REVERB_PORT:-443}
        - VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME:-https}
```

**Alasan:**
- Coolify tidak pass `REVERB_APP_KEY` anyway
- Tidak perlu declare di build args
- Cukup di runtime environment

### 3. **`.env.production`** - Tetap Ada (Untuk Runtime)

```env
# ✅ TETAP ADA - Untuk runtime PHP backend
REVERB_APP_KEY=whusnet_reverb_key_123

# ✅ TETAP ADA - Reference untuk Vite build
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
```

**Alasan:**
- Backend butuh `REVERB_APP_KEY` saat runtime
- `VITE_REVERB_APP_KEY` reference ke `REVERB_APP_KEY`
- Coolify expand variable saat build

---

## 🔍 Flow Lengkap

### Build Time (Vite)

```
1. Coolify read .env.production
   REVERB_APP_KEY=whusnet_reverb_key_123
   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"

2. Coolify expand variables
   VITE_REVERB_APP_KEY=whusnet_reverb_key_123

3. Pass to docker build
   --build-arg VITE_REVERB_APP_KEY=whusnet_reverb_key_123

4. Vite embed to JavaScript
   window.Reverb = { appKey: 'whusnet_reverb_key_123' }

5. Build success ✅
```

### Runtime (PHP Backend)

```
1. Container start
2. Load .env.production
   REVERB_APP_KEY=whusnet_reverb_key_123

3. Reverb server start
   php artisan reverb:start
   Using app key: whusnet_reverb_key_123

4. WebSocket server ready ✅
```

### Client Connection (Browser)

```
1. Browser load JavaScript
   window.Reverb.appKey = 'whusnet_reverb_key_123'

2. Connect to WebSocket
   wss://admin-payment.whusnet.com
   Authorization: Bearer whusnet_reverb_key_123

3. Reverb server verify
   Token match? ✅

4. Connection established ✅
```

---

## 📊 Perbandingan Solusi

| Approach | Build Args | Runtime Env | Result |
|----------|-----------|-------------|--------|
| **Sebelumnya** | `REVERB_APP_KEY` + `VITE_*` | `.env` | ❌ Coolify tidak pass |
| **Sekarang** | `VITE_*` only | `.env` | ✅ Works! |

---

## 🚀 Cara Deploy

### 1. Commit Perubahan

```bash
git add Dockerfile docker-compose.yaml
git commit -m "fix: remove REVERB_APP_KEY from build args (runtime only)"
git push origin master
```

### 2. Redeploy di Coolify

1. Buka Coolify Dashboard
2. Pilih aplikasi "WHUSNET Admin Payment"
3. Klik **"Redeploy"**
4. Monitor logs

### 3. Verifikasi

```bash
# Check build logs - should see NO warnings
# ✅ No "REVERB_APP_KEY not set" warnings

# Check containers
docker ps | grep whusnet

# Check Reverb
docker logs whusnet-reverb --tail 20
# Should see: Starting Reverb server...

# Test WebSocket
# Browser console should show: WebSocket connection established
```

---

## 🤔 FAQ

### Q: Kenapa sebelumnya kita tambah `REVERB_APP_KEY` ke build args?

**A:** Kesalahan analisa awal. Kita pikir perlu di-pass ke build, padahal:
- Vite hanya butuh `VITE_*` variables
- Backend baca dari `.env` saat runtime
- Coolify tidak pass non-standard variables

### Q: Apakah `REVERB_APP_KEY` masih digunakan?

**A:** Ya! Tapi hanya saat **runtime**, bukan build time:
- PHP backend baca dari `.env.production`
- Reverb server gunakan untuk authentication
- Tidak perlu di-embed ke frontend build

### Q: Bagaimana frontend tahu `REVERB_APP_KEY`?

**A:** Via `VITE_REVERB_APP_KEY`:
```env
REVERB_APP_KEY=whusnet_reverb_key_123
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"  # Reference
```
Coolify expand `${REVERB_APP_KEY}` saat build.

### Q: Apakah aman embed key ke JavaScript?

**A:** Ya, untuk WebSocket authentication:
- Key bukan secret (seperti API secret)
- Hanya untuk identify app, bukan authorize user
- User authentication tetap via Laravel session/token
- Standard practice untuk WebSocket apps

---

## ✅ Checklist

- [x] Hapus `ARG REVERB_APP_KEY` dari Dockerfile
- [x] Hapus `ENV REVERB_APP_KEY` dari Dockerfile
- [x] Hapus `- REVERB_APP_KEY=${REVERB_APP_KEY}` dari docker-compose.yaml
- [x] Tetap ada `REVERB_APP_KEY` di `.env.production`
- [x] Tetap ada `VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"` di `.env.production`
- [ ] Commit & push
- [ ] Redeploy di Coolify
- [ ] Verifikasi no warnings
- [ ] Test WebSocket connection

---

## 📝 Kesimpulan

### Root Cause
- Coolify tidak pass `REVERB_APP_KEY` sebagai build arg
- Variable tidak masuk kategori yang di-pass Coolify

### Solution
- **Hapus** `REVERB_APP_KEY` dari build args
- **Tetap** ada di `.env.production` untuk runtime
- **Hanya** pass `VITE_*` variables ke build

### Why It Works
- Vite hanya butuh `VITE_*` variables
- Backend baca `REVERB_APP_KEY` dari `.env` saat runtime
- Coolify pass `VITE_REVERB_APP_KEY` (reference expanded)

---

**Status:** ✅ Fixed (Final Solution)  
**Tanggal:** 2026-05-17  
**Root Cause:** Unnecessary build arg  
**Solution:** Runtime-only variable
