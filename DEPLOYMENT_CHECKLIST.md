# ✅ Deployment Checklist - GitHub Actions CI/CD

## 📋 Pre-Deployment Checklist

### 1. Server Preparation

- [ ] Server sudah terinstall Docker & Docker Compose
- [ ] Server sudah terinstall Git
- [ ] Server memiliki akses internet untuk pull Docker images
- [ ] Firewall sudah dikonfigurasi (port 80, 443, 22)
- [ ] Domain sudah pointing ke server IP (jika menggunakan domain)
- [ ] SSL certificate sudah tersedia (jika menggunakan HTTPS)

### 2. GitHub Repository Setup

- [ ] Repository sudah dibuat di GitHub
- [ ] Code sudah di-push ke repository
- [ ] Branch `main` sudah ada
- [ ] GitHub Container Registry sudah enabled
- [ ] Workflow permissions sudah diset ke "Read and write permissions"

### 3. GitHub Secrets Configuration

- [ ] `SSH_PRIVATE_KEY` - SSH private key untuk akses server
- [ ] `SERVER_HOST` - Hostname atau IP server
- [ ] `SERVER_USER` - Username SSH server
- [ ] `ENV_FILE` - File .env production (lengkap)
- [ ] `SLACK_WEBHOOK_URL` - (Optional) Webhook URL Slack

### 4. Server Directory Structure

```bash
/var/www/admin-payment/
├── .env                          # Environment file
├── docker-compose.yml            # Docker compose config
├── docker/                       # Docker configs
│   ├── nginx/
│   │   ├── production.conf
│   │   └── ssl/
│   └── mysql/
│       └── production.cnf
└── backups/                      # Database backups
```

- [ ] Directory `/var/www/admin-payment` sudah dibuat
- [ ] Directory `/var/www/admin-payment/backups` sudah dibuat
- [ ] Permissions sudah benar (owner: deploy user)

### 5. Application Configuration

- [ ] `.env.production.example` sudah diupdate dengan nilai yang benar
- [ ] Database credentials sudah benar
- [ ] Redis password sudah diset
- [ ] APP_KEY sudah di-generate
- [ ] APP_URL sudah benar
- [ ] REVERB configuration sudah benar
- [ ] Queue connection sudah diset ke `redis`
- [ ] Cache driver sudah diset ke `redis`

### 6. Docker Configuration

- [ ] `Dockerfile.prod` sudah ada dan benar
- [ ] `docker-compose.prod.yml` sudah ada dan benar
- [ ] NGINX config (`docker/nginx/production.conf`) sudah benar
- [ ] MySQL config (`docker/mysql/production.cnf`) sudah benar
- [ ] Health checks sudah dikonfigurasi di semua services

### 7. Laravel Application

- [ ] Routes `/ping` dan `/health` sudah tersedia
- [ ] Database migrations sudah siap
- [ ] Seeders sudah siap (jika diperlukan)
- [ ] Storage directories sudah ada dan writable
- [ ] Queue workers (Horizon) sudah dikonfigurasi
- [ ] Scheduler tasks sudah dikonfigurasi
- [ ] WebSocket (Reverb) sudah dikonfigurasi

---

## 🚀 Deployment Steps

### Step 1: Setup GitHub Secrets

```bash
# Jalankan script helper (Linux/Mac)
./scripts/setup-github-actions.sh

# Atau setup manual via GitHub UI
# Settings → Secrets and variables → Actions → New repository secret
```

### Step 2: Verify Workflow Files

```bash
# Check workflow syntax
gh workflow list

# View workflow file
cat .github/workflows/deploy-production-zero-downtime.yml
```

### Step 3: Test SSH Connection

```bash
# Test dari local machine
ssh -i ~/.ssh/github-actions user@server-ip

# Test dari GitHub Actions (dry run)
gh workflow run "Deploy to Production (Zero Downtime)" --ref main
```

### Step 4: First Deployment (Manual)

```bash
# Trigger manual deployment
gh workflow run "Deploy to Production (Zero Downtime)" --ref main

# Monitor deployment
gh run watch

# Or via GitHub UI:
# Actions → Deploy to Production (Zero Downtime) → Run workflow
```

### Step 5: Verify Deployment

```bash
# Check application health
curl https://yourdomain.com/health

# Check Docker containers
ssh user@server-ip "cd /var/www/admin-payment && docker-compose ps"

# Check logs
ssh user@server-ip "cd /var/www/admin-payment && docker-compose logs --tail=50"
```

### Step 6: Enable Automatic Deployment

Setelah first deployment berhasil, automatic deployment akan aktif:
- Setiap push ke branch `main` akan trigger deployment otomatis

---

## 🔍 Post-Deployment Verification

### 1. Application Health

- [ ] Homepage bisa diakses
- [ ] Login berfungsi
- [ ] Database connection OK
- [ ] Redis connection OK
- [ ] Queue workers running (Horizon)
- [ ] WebSocket connection OK (Reverb)
- [ ] Scheduler running

### 2. Docker Services

```bash
# Check all services running
docker-compose ps

# Expected output:
# whusnet-app       running (healthy)
# whusnet-nginx     running (healthy)
# whusnet-db        running (healthy)
# whusnet-redis     running (healthy)
# whusnet-horizon   running (healthy)
# whusnet-reverb    running
# whusnet-scheduler running
```

### 3. Logs Check

```bash
# Application logs
docker-compose logs app --tail=50

# NGINX logs
docker-compose logs nginx --tail=50

# Database logs
docker-compose logs db --tail=50

# Horizon logs
docker-compose logs horizon --tail=50
```

### 4. Performance Check

- [ ] Response time < 500ms
- [ ] Memory usage normal
- [ ] CPU usage normal
- [ ] Disk space sufficient

### 5. Security Check

- [ ] HTTPS enabled (jika production)
- [ ] Firewall configured
- [ ] Database tidak exposed ke public
- [ ] Redis tidak exposed ke public
- [ ] `.env` file tidak ter-commit ke Git

---

## 🔄 Rollback Procedure

### Quick Rollback

```bash
# SSH ke server
ssh user@server-ip

# Masuk ke directory
cd /var/www/admin-payment

# Lihat image versions
docker images | grep whusnet-app

# Rollback ke version sebelumnya
export APP_VERSION=previous-sha
docker-compose pull
docker-compose up -d

# Clear cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### Database Rollback

```bash
# Lihat backups
ls -lh backups/

# Restore database
gunzip < backups/backup_20260504_120000.sql.gz | \
  docker-compose exec -T db mysql -u root -p"${DB_PASSWORD}" ${DB_DATABASE}
```

---

## 🐛 Troubleshooting

### Deployment Failed

1. **Check GitHub Actions logs**
   ```bash
   gh run list --limit 5
   gh run view <run-id>
   ```

2. **Check server logs**
   ```bash
   ssh user@server-ip "cd /var/www/admin-payment && docker-compose logs"
   ```

3. **Check disk space**
   ```bash
   ssh user@server-ip "df -h"
   ```

### Health Check Failed

1. **Check application logs**
   ```bash
   docker-compose logs app --tail=100
   ```

2. **Check database connection**
   ```bash
   docker-compose exec app php artisan tinker
   >>> DB::connection()->getPdo();
   ```

3. **Check Redis connection**
   ```bash
   docker-compose exec app php artisan tinker
   >>> Cache::store('redis')->get('test');
   ```

### SSH Connection Failed

1. **Verify SSH key**
   ```bash
   ssh -i ~/.ssh/github-actions user@server-ip -v
   ```

2. **Check authorized_keys**
   ```bash
   ssh user@server-ip "cat ~/.ssh/authorized_keys"
   ```

3. **Check SSH service**
   ```bash
   ssh user@server-ip "sudo systemctl status ssh"
   ```

---

## 📊 Monitoring

### GitHub Actions

- **Workflow runs**: https://github.com/YOUR_USERNAME/YOUR_REPO/actions
- **Deployment history**: Actions → Deploy to Production
- **Logs**: Click on any workflow run

### Server Monitoring

```bash
# Real-time logs
docker-compose logs -f

# Container stats
docker stats

# Disk usage
docker system df

# Network
docker network ls
```

### Application Monitoring

- **Horizon Dashboard**: https://yourdomain.com/horizon
- **Telescope**: https://yourdomain.com/telescope (jika enabled)
- **Laravel Logs**: `storage/logs/laravel.log`

---

## 📞 Support Contacts

- **GitHub Issues**: https://github.com/YOUR_USERNAME/YOUR_REPO/issues
- **Documentation**: `GITHUB_ACTIONS_SETUP.md`
- **Server Admin**: your-admin@email.com

---

## 📚 Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Documentation](https://docs.docker.com/)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [NGINX Configuration](https://nginx.org/en/docs/)

