# 🚀 Panduan Lengkap CI/CD GitHub Actions

## 📖 Ringkasan

Project ini sudah dilengkapi dengan **2 workflow GitHub Actions** untuk automated deployment:

1. **Deploy Production** - Deployment standar (downtime 30-60 detik)
2. **Deploy Production (Zero Downtime)** - Deployment tanpa downtime ⭐ **RECOMMENDED**

---

## 🎯 Apa yang Sudah Tersedia?

### ✅ Workflow Files

- `.github/workflows/deploy-production.yml` - Deployment standar
- `.github/workflows/deploy-production-zero-downtime.yml` - Zero downtime deployment

### ✅ Health Check Endpoints

- `/ping` - Basic health check (sudah ditambahkan ke `routes/web.php`)
- `/health` - Detailed health check dengan database, Redis, storage checks

### ✅ Docker Configuration

- `Dockerfile.prod` - Production Docker image
- `docker-compose.prod.yml` - Production services configuration
- NGINX, MySQL, Redis, Horizon, Reverb, Scheduler

---

## 🔧 Cara Setup (5 Langkah)

### Langkah 1: Generate SSH Key di Server

```bash
# Login ke server production
ssh user@your-server-ip

# Generate SSH key pair
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github-actions -N ""

# Copy public key ke authorized_keys
cat ~/.ssh/github-actions.pub >> ~/.ssh/authorized_keys

# Set permissions
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh

# Copy private key (untuk GitHub Secret)
cat ~/.ssh/github-actions
# Copy output ini untuk langkah berikutnya
```

### Langkah 2: Setup GitHub Secrets

1. Buka repository GitHub Anda
2. Klik **Settings** → **Secrets and variables** → **Actions**
3. Klik **New repository secret**
4. Tambahkan 4 secrets berikut:

| Nama Secret | Nilai | Contoh |
|-------------|-------|--------|
| `SSH_PRIVATE_KEY` | Private key dari langkah 1 | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SERVER_HOST` | IP atau domain server | `123.456.789.0` atau `server.domain.com` |
| `SERVER_USER` | Username SSH | `root` atau `ubuntu` atau `deploy` |
| `ENV_FILE` | Isi lengkap file `.env` production | Copy seluruh isi `.env` |

**Optional (untuk notifikasi):**

| Nama Secret | Nilai |
|-------------|-------|
| `SLACK_WEBHOOK_URL` | Webhook URL Slack untuk notifikasi |

### Langkah 3: Enable Workflow Permissions

1. Buka repository GitHub
2. **Settings** → **Actions** → **General**
3. Scroll ke **Workflow permissions**
4. Pilih: **"Read and write permissions"**
5. Check: **"Allow GitHub Actions to create and approve pull requests"**
6. Klik **Save**

### Langkah 4: Persiapan Server

```bash
# Login ke server
ssh user@your-server-ip

# Buat directory project
sudo mkdir -p /var/www/admin-payment
sudo mkdir -p /var/www/admin-payment/backups

# Set ownership (ganti 'user' dengan username SSH Anda)
sudo chown -R user:user /var/www/admin-payment

# Install Docker & Docker Compose (jika belum)
# Ubuntu/Debian:
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify
docker --version
docker-compose --version
```

### Langkah 5: Test Deployment

#### Via GitHub UI:

1. Buka repository GitHub
2. Klik tab **Actions**
3. Pilih workflow: **"Deploy to Production (Zero Downtime)"**
4. Klik **Run workflow**
5. Pilih branch: `main`
6. Klik **Run workflow**
7. Monitor progress di halaman workflow

#### Via GitHub CLI:

```bash
# Install GitHub CLI (jika belum)
# https://cli.github.com/

# Login
gh auth login

# Trigger deployment
gh workflow run "Deploy to Production (Zero Downtime)" --ref main

# Monitor real-time
gh run watch
```

---

## 📊 Proses Deployment

### Timeline:

```
┌─────────────────────────────────────────────────────────┐
│  TOTAL: ~7-11 menit                                      │
├─────────────────────────────────────────────────────────┤
│  1. TEST (2-3 menit)                                     │
│     ├─ Setup PHP 8.4                                     │
│     ├─ Install dependencies                              │
│     ├─ Run PHPUnit tests                                 │
│     └─ Security audit                                    │
│                                                           │
│  2. BUILD (3-5 menit)                                    │
│     ├─ Build Docker image                                │
│     ├─ Tag image                                         │
│     └─ Push to GitHub Container Registry                 │
│                                                           │
│  3. DEPLOY (2-3 menit)                                   │
│     ├─ Pull new image                                    │
│     ├─ Backup database                                   │
│     ├─ Start new container                               │
│     ├─ Health check                                      │
│     ├─ Run migrations                                    │
│     ├─ Switch traffic                                    │
│     └─ Stop old container                                │
└─────────────────────────────────────────────────────────┘
```

### Zero Downtime Strategy:

1. **Container lama tetap running** selama deployment
2. **Container baru di-start** dan di-health check
3. **Traffic di-switch** ke container baru setelah healthy
4. **Container lama di-stop** setelah traffic pindah
5. **Downtime: 0 detik** ✅

---

## 🔄 Automatic Deployment

Setelah setup selesai, setiap kali Anda push ke branch `main`, deployment akan otomatis berjalan:

```bash
# Edit code
vim app/Http/Controllers/SomeController.php

# Commit & push
git add .
git commit -m "feat: add new feature"
git push origin main

# GitHub Actions akan otomatis:
# 1. Run tests
# 2. Build Docker image
# 3. Deploy ke server
# 4. Send notification (jika Slack configured)
```

---

## 🏥 Health Check

Workflow akan melakukan health check di 2 endpoint:

### 1. `/ping` - Basic Check

```bash
curl https://yourdomain.com/ping

# Response:
{
  "status": "ok",
  "timestamp": "2026-05-04T12:00:00+00:00"
}
```

### 2. `/health` - Detailed Check

```bash
curl https://yourdomain.com/health

# Response:
{
  "status": "healthy",
  "timestamp": "2026-05-04T12:00:00+00:00",
  "checks": {
    "database": "ok",
    "redis": "ok",
    "storage": "ok",
    "horizon": "running"
  }
}
```

---

## 🔄 Rollback

### Quick Rollback via SSH:

```bash
# 1. SSH ke server
ssh user@your-server-ip

# 2. Masuk ke directory project
cd /var/www/admin-payment

# 3. Lihat image versions yang tersedia
docker images | grep whusnet-app

# Output:
# whusnet-app  latest        abc1234  (current)
# whusnet-app  main-def5678  def5678  (previous)
# whusnet-app  main-ghi9012  ghi9012  (older)

# 4. Rollback ke version sebelumnya
export APP_VERSION=main-def5678
docker-compose pull
docker-compose up -d

# 5. Clear cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# 6. Verify
curl http://localhost/health
```

### Restore Database (jika diperlukan):

```bash
# 1. Lihat backups yang tersedia
ls -lh backups/

# Output:
# backup_20260504_120000.sql.gz
# backup_20260503_120000.sql.gz

# 2. Restore database
gunzip < backups/backup_20260504_120000.sql.gz | \
  docker-compose exec -T db mysql -u root -p"${DB_PASSWORD}" ${DB_DATABASE}

# 3. Verify
docker-compose exec app php artisan tinker
>>> DB::table('users')->count();
```

---

## 📊 Monitoring

### 1. GitHub Actions UI

```
https://github.com/YOUR_USERNAME/YOUR_REPO/actions
```

- Lihat status workflow (success/failed)
- Lihat logs detail setiap step
- Download artifacts (jika ada)

### 2. Real-time Logs

```bash
# Via GitHub CLI
gh run watch

# Via SSH
ssh user@server-ip "cd /var/www/admin-payment && docker-compose logs -f"

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f horizon
```

### 3. Container Status

```bash
# Check all containers
docker-compose ps

# Expected output:
# whusnet-app       running (healthy)
# whusnet-nginx     running (healthy)
# whusnet-db        running (healthy)
# whusnet-redis     running (healthy)
# whusnet-horizon   running (healthy)
# whusnet-reverb    running
# whusnet-scheduler running

# Container stats (CPU, Memory)
docker stats
```

---

## 🐛 Troubleshooting

### Problem 1: Deployment Failed

**Solusi:**

```bash
# 1. Check GitHub Actions logs
gh run list --limit 5
gh run view <run-id>

# 2. Check server logs
ssh user@server-ip "cd /var/www/admin-payment && docker-compose logs --tail=100"

# 3. Check disk space
ssh user@server-ip "df -h"

# 4. Check Docker
ssh user@server-ip "docker ps -a"
```

### Problem 2: SSH Connection Failed

**Error:** `Permission denied (publickey)`

**Solusi:**

```bash
# 1. Test SSH connection manual
ssh -i ~/.ssh/github-actions user@server-ip -v

# 2. Check authorized_keys di server
ssh user@server-ip "cat ~/.ssh/authorized_keys | grep github-actions"

# 3. Check permissions
ssh user@server-ip "ls -la ~/.ssh/"
# authorized_keys harus 600
# .ssh directory harus 700

# 4. Regenerate SSH key jika perlu
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github-actions -N ""
```

### Problem 3: Health Check Failed

**Error:** `Health check failed`

**Solusi:**

```bash
# 1. Check application logs
docker-compose logs app --tail=100

# 2. Check database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();

# 3. Check Redis connection
docker-compose exec app php artisan tinker
>>> Cache::store('redis')->get('test');

# 4. Check NGINX
docker-compose logs nginx --tail=50

# 5. Manual health check
curl http://localhost/health
curl http://localhost/ping
```

### Problem 4: Docker Login Failed

**Error:** `Error response from daemon: Get https://ghcr.io/v2/: unauthorized`

**Solusi:**

1. Check GitHub Container Registry enabled:
   - Settings → Packages → Enable improved container support

2. Check workflow permissions:
   - Settings → Actions → General → Workflow permissions
   - Pilih "Read and write permissions"

3. Regenerate GITHUB_TOKEN (otomatis, tidak perlu action)

---

## 📚 Dokumentasi Lengkap

Project ini dilengkapi dengan dokumentasi lengkap:

1. **`GITHUB_ACTIONS_SETUP.md`** - Setup lengkap step-by-step
2. **`DEPLOYMENT_CHECKLIST.md`** - Checklist pre-deployment & post-deployment
3. **`QUICK_START_CICD.md`** - Quick start guide (5 menit)
4. **`CICD_ARCHITECTURE.md`** - Architecture diagram & flow
5. **`PANDUAN_CICD_INDONESIA.md`** - Panduan dalam Bahasa Indonesia (file ini)

### Script Helper:

- **`scripts/setup-github-actions.sh`** - Script otomatis untuk setup secrets (Linux/Mac)

---

## ✅ Checklist Setup

Gunakan checklist ini untuk memastikan semua sudah siap:

### Pre-Deployment:

- [ ] Server sudah install Docker & Docker Compose
- [ ] SSH key sudah di-generate
- [ ] Public key sudah ditambahkan ke `~/.ssh/authorized_keys`
- [ ] GitHub Secrets sudah dikonfigurasi (4 secrets)
- [ ] Workflow permissions sudah enabled
- [ ] Directory `/var/www/admin-payment` sudah dibuat
- [ ] File `.env` production sudah siap

### Post-Deployment:

- [ ] Workflow berjalan sukses (✅ green)
- [ ] Health check returns 200 OK
- [ ] All containers running & healthy
- [ ] Application accessible via browser
- [ ] No errors in logs
- [ ] Database migrations applied
- [ ] Queue workers running (Horizon)
- [ ] WebSocket working (Reverb)

---

## 🎉 Success Indicators

Deployment berhasil jika:

1. ✅ **GitHub Actions workflow status: Success**
2. ✅ **Health check: `curl https://yourdomain.com/health` returns 200**
3. ✅ **All containers running: `docker-compose ps` shows all healthy**
4. ✅ **Application accessible: Homepage loads correctly**
5. ✅ **No errors in logs: `docker-compose logs --tail=50`**

---

## 📞 Butuh Bantuan?

Jika ada masalah atau pertanyaan:

1. **Check dokumentasi lengkap:** `GITHUB_ACTIONS_SETUP.md`
2. **Check troubleshooting:** Section di atas
3. **Check GitHub Actions logs:** https://github.com/YOUR_USERNAME/YOUR_REPO/actions
4. **Check server logs:** `docker-compose logs`

---

## 🚀 Next Steps

Setelah setup berhasil:

1. ✅ Test deployment manual via GitHub Actions UI
2. ✅ Test automatic deployment (push ke main)
3. ✅ Setup Slack notification (optional)
4. ✅ Test rollback procedure
5. ✅ Monitor first production deployment
6. ✅ Document any custom configurations

---

**Selamat! CI/CD Anda sudah siap! 🎉**

Setiap push ke `main` akan otomatis:
- Run tests
- Build Docker image
- Deploy ke production
- Zero downtime
- Automatic rollback jika gagal

