# 🚀 Setup CI/CD GitHub Actions - WHUSNET Admin Payment

## 📋 Daftar GitHub Secrets yang Diperlukan

Buka repository GitHub → **Settings** → **Secrets and variables** → **Actions**

### 🔐 Server Access Secrets

```bash
# SSH Private Key untuk akses ke server
SSH_PRIVATE_KEY
# Contoh generate:
# ssh-keygen -t ed25519 -C "github-actions@yourdomain.com"
# Copy isi file ~/.ssh/id_ed25519 (private key)

# Hostname/IP server production
SERVER_HOST
# Contoh: 123.456.789.0 atau server.yourdomain.com

# Username SSH server
SERVER_USER
# Contoh: root atau ubuntu atau deploy
```

### 📄 Environment File Secret

```bash
# File .env production (copy seluruh isi file .env production)
ENV_FILE
# Contoh isi:
# APP_NAME="WHUSNET Admin Payment"
# APP_ENV=production
# APP_KEY=base64:xxxxxxxxxxxxx
# APP_DEBUG=false
# APP_URL=https://yourdomain.com
# ... (semua environment variables production)
```

### 🔔 Notification Secrets (Optional)

```bash
# Slack Webhook URL untuk notifikasi deployment
SLACK_WEBHOOK_URL
# Contoh: https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXX
# Cara setup: https://api.slack.com/messaging/webhooks
```

---

## 🎯 Cara Setup GitHub Secrets

### 1. Generate SSH Key Pair

Di server production Anda:

```bash
# Generate SSH key pair
ssh-keygen -t ed25519 -C "github-actions@yourdomain.com" -f ~/.ssh/github-actions

# Copy public key ke authorized_keys
cat ~/.ssh/github-actions.pub >> ~/.ssh/authorized_keys

# Set permissions
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh

# Copy private key (untuk GitHub Secret)
cat ~/.ssh/github-actions
```

### 2. Test SSH Connection

Dari komputer lokal, test koneksi:

```bash
ssh -i ~/.ssh/github-actions user@your-server-ip
```

### 3. Tambahkan Secrets ke GitHub

1. Buka repository GitHub
2. **Settings** → **Secrets and variables** → **Actions**
3. Klik **New repository secret**
4. Tambahkan satu per satu:

| Name | Value |
|------|-------|
| `SSH_PRIVATE_KEY` | Isi file `~/.ssh/github-actions` (private key) |
| `SERVER_HOST` | IP atau domain server (contoh: `123.456.789.0`) |
| `SERVER_USER` | Username SSH (contoh: `root` atau `ubuntu`) |
| `ENV_FILE` | Copy seluruh isi file `.env` production |
| `SLACK_WEBHOOK_URL` | (Optional) Webhook URL Slack |

---

## 🚀 Cara Menggunakan Workflow

### Workflow 1: Deploy Production (Standar)

**File:** `.github/workflows/deploy-production.yml`

**Trigger:**
- Otomatis saat push ke branch `main`
- Manual via GitHub Actions UI

**Proses:**
1. ✅ Run tests (PHPUnit)
2. 🔒 Security audit (Composer)
3. 🐳 Build Docker image
4. 📦 Push ke GitHub Container Registry
5. 🚀 Deploy ke server
6. 🔄 Restart services
7. 🏥 Health check
8. 📢 Notifikasi Slack

**Downtime:** ~30-60 detik

### Workflow 2: Deploy Production (Zero Downtime)

**File:** `.github/workflows/deploy-production-zero-downtime.yml`

**Trigger:**
- Otomatis saat push ke branch `main`
- Manual via GitHub Actions UI

**Proses:**
1. ✅ Run tests (PHPUnit)
2. 🔒 Security audit (Composer)
3. 🐳 Build Docker image
4. 📦 Push ke GitHub Container Registry
5. 🚀 Deploy dengan Blue-Green strategy:
   - Start container baru
   - Health check container baru
   - Switch NGINX ke container baru
   - Stop container lama
6. 🏥 Health check
7. 📢 Notifikasi Slack

**Downtime:** 0 detik (zero downtime)

---

## 🎮 Cara Trigger Deployment

### 1. Automatic Deployment (Push ke main)

```bash
git add .
git commit -m "feat: add new feature"
git push origin main
```

Workflow akan otomatis berjalan.

### 2. Manual Deployment (GitHub UI)

1. Buka repository GitHub
2. **Actions** tab
3. Pilih workflow: "Deploy to Production" atau "Deploy to Production (Zero Downtime)"
4. Klik **Run workflow**
5. Pilih branch: `main`
6. Klik **Run workflow**

### 3. Manual Deployment (GitHub CLI)

```bash
# Install GitHub CLI
# https://cli.github.com/

# Login
gh auth login

# Trigger workflow
gh workflow run "Deploy to Production (Zero Downtime)" --ref main
```

---

## 📊 Monitoring Deployment

### 1. Via GitHub Actions UI

1. Buka repository GitHub
2. **Actions** tab
3. Klik workflow run yang sedang berjalan
4. Lihat logs real-time

### 2. Via GitHub CLI

```bash
# List workflow runs
gh run list

# View specific run
gh run view <run-id>

# Watch run in real-time
gh run watch
```

### 3. Via Slack (jika sudah setup)

Notifikasi otomatis akan dikirim ke Slack channel:
- ✅ Deployment success
- ❌ Deployment failed
- 📊 Deployment details (version, deployer, downtime)

---

## 🔄 Rollback Deployment

### Manual Rollback via SSH

```bash
# SSH ke server
ssh user@your-server-ip

# Masuk ke directory project
cd /var/www/admin-payment

# Lihat backup database
ls -lh backups/

# Restore database (jika perlu)
gunzip < backups/backup_20260504_120000.sql.gz | docker-compose exec -T db mysql -u root -p"${DB_PASSWORD}" ${DB_DATABASE}

# Pull image versi sebelumnya
docker pull ghcr.io/your-username/your-repo:previous-sha

# Update docker-compose.yml dengan image versi lama
export APP_VERSION=previous-sha
docker-compose up -d

# Clear cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### Rollback via GitHub Actions

Workflow sudah menyediakan job `rollback` (manual trigger):

```bash
# Via GitHub CLI
gh workflow run "Deploy to Production" --ref main -f environment=production

# Atau via GitHub UI:
# Actions → Deploy to Production → Run workflow → pilih "rollback"
```

---

## 🏥 Health Checks

Workflow melakukan health check di beberapa endpoint:

1. **`/ping`** - Basic health check
2. **`/health`** - Detailed health check (database, redis, queue)

Pastikan routes ini tersedia di aplikasi Laravel Anda.

### Tambahkan Health Check Routes (jika belum ada)

Edit `routes/web.php`:

```php
// Health check endpoints
Route::get('/ping', function () {
    return response()->json(['status' => 'ok'], 200);
});

Route::get('/health', function () {
    try {
        // Check database
        DB::connection()->getPdo();
        
        // Check redis
        Cache::store('redis')->get('health-check');
        
        return response()->json([
            'status' => 'healthy',
            'database' => 'ok',
            'redis' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 503);
    }
});
```

---

## 🔧 Troubleshooting

### Error: "Permission denied (publickey)"

**Solusi:**
1. Pastikan SSH private key sudah ditambahkan ke GitHub Secrets
2. Pastikan public key sudah ada di `~/.ssh/authorized_keys` di server
3. Test koneksi SSH manual: `ssh -i ~/.ssh/github-actions user@server-ip`

### Error: "Docker login failed"

**Solusi:**
1. Pastikan GitHub Container Registry enabled di repository settings
2. Pastikan workflow memiliki permission `packages: write`
3. Check di **Settings** → **Actions** → **General** → **Workflow permissions** → pilih "Read and write permissions"

### Error: "Health check failed"

**Solusi:**
1. Pastikan routes `/ping` dan `/health` tersedia
2. Check logs: `docker-compose logs app`
3. Check NGINX logs: `docker-compose logs nginx`
4. Pastikan firewall tidak block port 80/443

### Error: "Database connection failed"

**Solusi:**
1. Pastikan `.env` file sudah benar di server
2. Check database container: `docker-compose ps db`
3. Check database logs: `docker-compose logs db`
4. Test koneksi: `docker-compose exec app php artisan tinker` → `DB::connection()->getPdo();`

---

## 📚 Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [GitHub Container Registry](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry)

---

## 🎯 Next Steps

1. ✅ Setup GitHub Secrets
2. ✅ Test SSH connection ke server
3. ✅ Tambahkan health check routes
4. ✅ Setup Slack webhook (optional)
5. ✅ Test deployment manual via GitHub Actions UI
6. ✅ Enable automatic deployment on push to main
7. ✅ Monitor first deployment
8. ✅ Test rollback procedure

---

## 📞 Support

Jika ada masalah, check:
1. GitHub Actions logs
2. Server logs: `docker-compose logs`
3. Application logs: `storage/logs/laravel.log`

