# 🚀 Quick Start - GitHub Actions CI/CD

## ⚡ Setup dalam 5 Menit

### 1. Generate SSH Key

```bash
# Di server production
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github-actions -N ""

# Copy public key ke authorized_keys
cat ~/.ssh/github-actions.pub >> ~/.ssh/authorized_keys

# Copy private key (untuk GitHub Secret)
cat ~/.ssh/github-actions
```

### 2. Setup GitHub Secrets

Buka: `https://github.com/YOUR_USERNAME/YOUR_REPO/settings/secrets/actions`

Tambahkan 4 secrets ini:

| Secret Name | Value |
|-------------|-------|
| `SSH_PRIVATE_KEY` | Isi dari `~/.ssh/github-actions` (private key) |
| `SERVER_HOST` | IP server (contoh: `123.456.789.0`) |
| `SERVER_USER` | Username SSH (contoh: `root`) |
| `ENV_FILE` | Copy seluruh isi file `.env` production |

### 3. Enable Workflow Permissions

Buka: `https://github.com/YOUR_USERNAME/YOUR_REPO/settings/actions`

- Pilih: **"Read and write permissions"**
- Check: **"Allow GitHub Actions to create and approve pull requests"**

### 4. Test Deployment

```bash
# Via GitHub CLI
gh workflow run "Deploy to Production (Zero Downtime)" --ref main

# Monitor
gh run watch

# Atau via GitHub UI:
# Actions → Deploy to Production (Zero Downtime) → Run workflow
```

### 5. Verify

```bash
# Check health
curl https://yourdomain.com/health

# Check containers
ssh user@server-ip "docker-compose ps"
```

---

## 🎯 Workflow yang Tersedia

### 1. Deploy Production (Standar)

**File:** `.github/workflows/deploy-production.yml`

**Kapan digunakan:**
- Development/staging environment
- Downtime 30-60 detik OK
- Setup lebih simple

**Trigger:**
```bash
gh workflow run "Deploy to Production" --ref main
```

### 2. Deploy Production (Zero Downtime) ⭐ **RECOMMENDED**

**File:** `.github/workflows/deploy-production-zero-downtime.yml`

**Kapan digunakan:**
- Production environment
- Zero downtime required
- Blue-Green deployment

**Trigger:**
```bash
gh workflow run "Deploy to Production (Zero Downtime)" --ref main
```

---

## 📋 Proses Deployment

### Workflow Steps:

1. **Test** (2-3 menit)
   - Setup PHP 8.4
   - Install dependencies
   - Run PHPUnit tests
   - Security audit

2. **Build** (3-5 menit)
   - Build Docker image
   - Push ke GitHub Container Registry
   - Tag: `latest`, `main-{sha}`

3. **Deploy** (2-3 menit)
   - Pull new image
   - Backup database
   - Start new container
   - Health check
   - Switch traffic
   - Stop old container

**Total Time:** ~7-11 menit

---

## 🔄 Automatic Deployment

Setelah setup, setiap push ke `main` akan trigger deployment otomatis:

```bash
git add .
git commit -m "feat: new feature"
git push origin main
```

GitHub Actions akan otomatis:
1. Run tests
2. Build image
3. Deploy ke server
4. Send notification (jika Slack configured)

---

## 📊 Monitoring

### GitHub Actions UI

```
https://github.com/YOUR_USERNAME/YOUR_REPO/actions
```

### Real-time Logs

```bash
# Via GitHub CLI
gh run watch

# Via SSH
ssh user@server-ip "cd /var/www/admin-payment && docker-compose logs -f"
```

### Health Check

```bash
# Basic ping
curl https://yourdomain.com/ping

# Detailed health
curl https://yourdomain.com/health
```

---

## 🐛 Troubleshooting

### Deployment Failed?

```bash
# 1. Check GitHub Actions logs
gh run list --limit 5
gh run view <run-id>

# 2. Check server logs
ssh user@server-ip "cd /var/www/admin-payment && docker-compose logs --tail=100"

# 3. Check disk space
ssh user@server-ip "df -h"
```

### SSH Connection Failed?

```bash
# Test SSH connection
ssh -i ~/.ssh/github-actions user@server-ip -v

# Check authorized_keys
ssh user@server-ip "cat ~/.ssh/authorized_keys | grep github-actions"
```

### Health Check Failed?

```bash
# Check application
docker-compose exec app php artisan list

# Check database
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();

# Check Redis
docker-compose exec app php artisan tinker
>>> Cache::store('redis')->get('test');
```

---

## 🔄 Rollback

### Quick Rollback

```bash
# SSH ke server
ssh user@server-ip

# Masuk ke directory
cd /var/www/admin-payment

# Lihat image versions
docker images | grep whusnet-app

# Rollback ke version sebelumnya
export APP_VERSION=<previous-sha>
docker-compose pull
docker-compose up -d

# Clear cache
docker-compose exec app php artisan cache:clear
```

### Database Rollback

```bash
# Lihat backups
ls -lh backups/

# Restore
gunzip < backups/backup_YYYYMMDD_HHMMSS.sql.gz | \
  docker-compose exec -T db mysql -u root -p"${DB_PASSWORD}" ${DB_DATABASE}
```

---

## 📞 Need Help?

- **Full Documentation:** `GITHUB_ACTIONS_SETUP.md`
- **Checklist:** `DEPLOYMENT_CHECKLIST.md`
- **GitHub Issues:** https://github.com/YOUR_USERNAME/YOUR_REPO/issues

---

## 🎉 Success Indicators

✅ Deployment successful jika:
- GitHub Actions workflow status: ✅ Success
- Health check: `curl https://yourdomain.com/health` returns 200
- All containers running: `docker-compose ps` shows all healthy
- Application accessible: Homepage loads correctly
- No errors in logs: `docker-compose logs --tail=50`

