# 🚀 CI/CD Documentation - GitHub Actions

## 📚 Dokumentasi Lengkap

Project ini sudah dilengkapi dengan **automated CI/CD pipeline** menggunakan GitHub Actions untuk deployment ke production server dengan **zero downtime**.

---

## 📖 Daftar Dokumentasi

### 🇮🇩 Bahasa Indonesia

| File | Deskripsi | Untuk Siapa |
|------|-----------|-------------|
| **[PANDUAN_CICD_INDONESIA.md](PANDUAN_CICD_INDONESIA.md)** | 📘 Panduan lengkap dalam Bahasa Indonesia | Semua user |
| **[QUICK_START_CICD.md](QUICK_START_CICD.md)** | ⚡ Quick start guide (5 menit) | Developer yang ingin cepat setup |
| **[GITHUB_ACTIONS_SETUP.md](GITHUB_ACTIONS_SETUP.md)** | 🔧 Setup detail step-by-step | DevOps / System Admin |
| **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** | ✅ Checklist pre & post deployment | DevOps / QA |
| **[CICD_ARCHITECTURE.md](CICD_ARCHITECTURE.md)** | 🏗️ Architecture diagram & flow | Technical Lead / Architect |

### 🛠️ Scripts

| File | Deskripsi |
|------|-----------|
| **[scripts/setup-github-actions.sh](scripts/setup-github-actions.sh)** | Script otomatis untuk setup GitHub Secrets (Linux/Mac) |

---

## ⚡ Quick Start (5 Menit)

### 1. Generate SSH Key

```bash
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github-actions -N ""
cat ~/.ssh/github-actions.pub >> ~/.ssh/authorized_keys
```

### 2. Setup GitHub Secrets

Buka: `Settings → Secrets and variables → Actions`

Tambahkan 4 secrets:
- `SSH_PRIVATE_KEY` - Private key dari step 1
- `SERVER_HOST` - IP server
- `SERVER_USER` - Username SSH
- `ENV_FILE` - Isi file `.env` production

### 3. Enable Workflow Permissions

`Settings → Actions → General → Workflow permissions`
- Pilih: "Read and write permissions"

### 4. Deploy

```bash
gh workflow run "Deploy to Production (Zero Downtime)" --ref main
gh run watch
```

**Selesai!** 🎉

---

## 🎯 Fitur Utama

### ✅ Zero Downtime Deployment

- Blue-Green deployment strategy
- Container lama tetap running selama deployment
- Traffic switch hanya setelah health check passed
- **Downtime: 0 detik**

### ✅ Automated Testing

- PHPUnit tests sebelum deployment
- Security audit (composer audit)
- Mencegah broken code masuk production

### ✅ Automatic Rollback

- Database backup otomatis sebelum deployment
- Docker images versi sebelumnya tetap tersimpan
- Quick rollback jika ada masalah

### ✅ Real-time Monitoring

- GitHub Actions logs real-time
- Slack notifications (optional)
- Health check endpoints (`/ping`, `/health`)

### ✅ Security

- Secrets encrypted di GitHub
- SSH key-based authentication
- No credentials in code

---

## 📊 Workflow yang Tersedia

### 1. Deploy Production (Standar)

**File:** `.github/workflows/deploy-production.yml`

**Kapan digunakan:**
- Development/staging environment
- Downtime 30-60 detik OK

**Trigger:**
- Otomatis: Push ke branch `main`
- Manual: GitHub Actions UI atau `gh workflow run`

### 2. Deploy Production (Zero Downtime) ⭐ **RECOMMENDED**

**File:** `.github/workflows/deploy-production-zero-downtime.yml`

**Kapan digunakan:**
- Production environment
- Zero downtime required

**Trigger:**
- Otomatis: Push ke branch `main`
- Manual: GitHub Actions UI atau `gh workflow run`

---

## 🔄 Deployment Flow

```
Developer Push → GitHub Actions → Tests → Build → Deploy → Health Check → Done
     ↓              ↓                ↓       ↓        ↓          ↓          ↓
   main branch   Run tests      Build img  Pull img  Start new  Check OK  Switch
                 PHPUnit        Tag & Push  Backup DB container  Migrate   traffic
                 Security       to GHCR     Old runs  Health OK  Cache     Stop old
```

**Total Time:** ~7-11 menit

---

## 🏥 Health Check Endpoints

### `/ping` - Basic Check

```bash
curl https://yourdomain.com/ping
```

Response:
```json
{
  "status": "ok",
  "timestamp": "2026-05-04T12:00:00+00:00"
}
```

### `/health` - Detailed Check

```bash
curl https://yourdomain.com/health
```

Response:
```json
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

### Quick Rollback

```bash
ssh user@server-ip
cd /var/www/admin-payment
export APP_VERSION=previous-sha
docker-compose pull && docker-compose up -d
docker-compose exec app php artisan cache:clear
```

### Database Rollback

```bash
gunzip < backups/backup_YYYYMMDD_HHMMSS.sql.gz | \
  docker-compose exec -T db mysql -u root -p"${DB_PASSWORD}" ${DB_DATABASE}
```

---

## 📊 Monitoring

### GitHub Actions

```
https://github.com/YOUR_USERNAME/YOUR_REPO/actions
```

### Real-time Logs

```bash
# Via GitHub CLI
gh run watch

# Via SSH
docker-compose logs -f
```

### Container Status

```bash
docker-compose ps
docker stats
```

---

## 🐛 Troubleshooting

### Deployment Failed?

```bash
# Check logs
gh run view <run-id>
docker-compose logs --tail=100

# Check disk space
df -h

# Check containers
docker ps -a
```

### SSH Connection Failed?

```bash
# Test connection
ssh -i ~/.ssh/github-actions user@server-ip -v

# Check authorized_keys
cat ~/.ssh/authorized_keys | grep github-actions
```

### Health Check Failed?

```bash
# Check application
docker-compose logs app --tail=100

# Test database
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();

# Test Redis
>>> Cache::store('redis')->get('test');
```

---

## 📚 Dokumentasi Detail

Untuk informasi lebih lengkap, baca dokumentasi berikut:

1. **[PANDUAN_CICD_INDONESIA.md](PANDUAN_CICD_INDONESIA.md)** - Panduan lengkap Bahasa Indonesia
2. **[QUICK_START_CICD.md](QUICK_START_CICD.md)** - Quick start 5 menit
3. **[GITHUB_ACTIONS_SETUP.md](GITHUB_ACTIONS_SETUP.md)** - Setup detail
4. **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** - Checklist deployment
5. **[CICD_ARCHITECTURE.md](CICD_ARCHITECTURE.md)** - Architecture & diagram

---

## ✅ Success Indicators

Deployment berhasil jika:

1. ✅ GitHub Actions workflow status: **Success**
2. ✅ Health check returns: **200 OK**
3. ✅ All containers: **Running & Healthy**
4. ✅ Application: **Accessible**
5. ✅ Logs: **No errors**

---

## 🎯 Recommended Reading Order

### Untuk Developer:

1. **[QUICK_START_CICD.md](QUICK_START_CICD.md)** - Setup cepat
2. **[PANDUAN_CICD_INDONESIA.md](PANDUAN_CICD_INDONESIA.md)** - Panduan lengkap
3. **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** - Checklist

### Untuk DevOps/SysAdmin:

1. **[GITHUB_ACTIONS_SETUP.md](GITHUB_ACTIONS_SETUP.md)** - Setup detail
2. **[CICD_ARCHITECTURE.md](CICD_ARCHITECTURE.md)** - Architecture
3. **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** - Checklist

### Untuk Technical Lead:

1. **[CICD_ARCHITECTURE.md](CICD_ARCHITECTURE.md)** - Architecture overview
2. **[GITHUB_ACTIONS_SETUP.md](GITHUB_ACTIONS_SETUP.md)** - Technical details
3. **[PANDUAN_CICD_INDONESIA.md](PANDUAN_CICD_INDONESIA.md)** - Complete guide

---

## 🚀 Next Steps

Setelah membaca dokumentasi:

1. ✅ Setup GitHub Secrets
2. ✅ Test deployment manual
3. ✅ Enable automatic deployment
4. ✅ Setup monitoring & notifications
5. ✅ Test rollback procedure
6. ✅ Document custom configurations

---

## 📞 Support

Jika ada pertanyaan atau masalah:

1. Check dokumentasi di atas
2. Check GitHub Actions logs
3. Check server logs: `docker-compose logs`
4. Create GitHub Issue

---

## 📝 License

Project ini menggunakan lisensi yang sama dengan project utama.

---

**Happy Deploying! 🚀**

Automated CI/CD dengan zero downtime untuk Laravel application.

