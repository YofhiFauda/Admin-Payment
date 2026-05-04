# 🚀 Production Readiness Checklist - WHUSNET Admin Payment

## 📋 Executive Summary

Project ini adalah aplikasi Laravel 12 untuk manajemen pembayaran dengan fitur:
- OCR processing menggunakan Gemini AI
- Real-time notifications via Laravel Reverb (WebSocket)
- Queue processing dengan Laravel Horizon
- Redis untuk cache, session, dan queue
- Telegram bot integration
- Price anomaly detection

---

## ✅ CRITICAL - Harus Dilakukan Sebelum Production

### 1. **Environment & Security** 🔒

#### A. File `.env` Production
```bash
# ❌ BAHAYA - Jangan gunakan nilai development
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_NEW_KEY_WITH_php_artisan_key:generate

# ✅ Gunakan domain production yang sebenarnya
APP_URL=https://yourdomain.com

# ❌ BAHAYA - Ganti semua password default
DB_PASSWORD=STRONG_RANDOM_PASSWORD_HERE
REDIS_PASSWORD=STRONG_RANDOM_PASSWORD_HERE

# ✅ Rate limiting untuk Gemini API
GEMINI_RPM_LIMIT=12  # Sesuaikan dengan tier Anda
GEMINI_COOLDOWN_SECONDS=5

# ✅ N8N Webhook - gunakan URL production
N8N_WEBHOOK=https://your-n8n-production.com/webhook/upload-foto
N8N_SECRET=GENERATE_STRONG_SECRET_HERE

# ✅ GitHub Token (jika digunakan)
GITHUB_PERSONAL_ACCESS_TOKEN=your_production_token

# ✅ Reverb Production Settings
REVERB_APP_ID=RANDOM_NUMBER
REVERB_APP_KEY=STRONG_RANDOM_KEY
REVERB_APP_SECRET=STRONG_RANDOM_SECRET
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

#### B. Generate Secrets
```bash
# Generate APP_KEY baru
php artisan key:generate

# Generate random secrets untuk Reverb
openssl rand -base64 32  # untuk REVERB_APP_KEY
openssl rand -base64 32  # untuk REVERB_APP_SECRET
openssl rand -base64 32  # untuk N8N_SECRET
```

#### C. File Permissions
```bash
# Set ownership
chown -R www-data:www-data storage bootstrap/cache

# Set permissions
chmod -R 775 storage bootstrap/cache
chmod -R 755 public

# Pastikan .env tidak readable oleh public
chmod 600 .env
```

---

### 2. **Database & Migrations** 🗄️

#### A. Backup Strategy
```bash
# Setup automated backup (tambahkan ke cron)
# Backup database setiap hari jam 2 pagi
0 2 * * * mysqldump -u root -p'PASSWORD' admin-payment > /backup/db_$(date +\%Y\%m\%d).sql

# Backup dengan compression
0 2 * * * mysqldump -u root -p'PASSWORD' admin-payment | gzip > /backup/db_$(date +\%Y\%m\%d).sql.gz

# Retention policy - hapus backup > 30 hari
0 3 * * * find /backup -name "db_*.sql.gz" -mtime +30 -delete
```

#### B. Database Optimization
```sql
-- Tambahkan indexes untuk performa (jika belum ada)
-- Cek dengan: SHOW INDEX FROM transactions;

-- Index untuk query yang sering digunakan
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_transactions_created_at ON transactions(created_at);
CREATE INDEX idx_transactions_branch_id ON transactions(branch_id);
CREATE INDEX idx_price_indexes_item_branch ON price_indexes(master_item_id, branch_id);
```

#### C. Migration Checklist
```bash
# ✅ Test migrations di staging dulu
php artisan migrate --pretend

# ✅ Jalankan migrations di production
php artisan migrate --force

# ✅ Seed data master jika diperlukan
php artisan db:seed --class=MasterDataSeeder --force
```

---

### 3. **Caching & Performance** ⚡

#### A. Optimize Laravel
```bash
# ✅ Cache configuration
php artisan config:cache

# ✅ Cache routes
php artisan route:cache

# ✅ Cache views
php artisan view:cache

# ✅ Optimize autoloader
composer install --optimize-autoloader --no-dev

# ✅ Cache events
php artisan event:cache
```

#### B. Redis Configuration
```bash
# Pastikan Redis persistence aktif (sudah ada di docker-compose.yml)
# - appendonly yes
# - save 900 1
# - save 300 10

# Monitor Redis memory usage
redis-cli -a password1234 INFO memory
redis-cli -a password1234 CONFIG GET maxmemory
```

#### C. OPcache Configuration
Tambahkan ke `docker/php/local.ini`:
```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

---

### 4. **Queue & Job Processing** 🔄

#### A. Horizon Configuration
File: `config/horizon.php`
```php
// Pastikan environment production sudah dikonfigurasi
'production' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default', 'ocr', 'notifications'],
        'balance' => 'auto',
        'maxProcesses' => 10,
        'maxTime' => 0,
        'maxJobs' => 0,
        'memory' => 512,
        'tries' => 3,
        'timeout' => 300,
    ],
],
```

#### B. Queue Monitoring
```bash
# Monitor queue status
php artisan horizon:status

# Monitor failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs (setelah di-fix)
php artisan queue:flush
```

#### C. Job Timeout & Retry
Pastikan semua Jobs punya timeout yang reasonable:
```php
// app/Jobs/OcrProcessingJob.php
public $timeout = 300; // 5 menit
public $tries = 3;
public $backoff = [60, 120, 300]; // Exponential backoff
```

---

### 5. **Logging & Monitoring** 📊

#### A. Log Configuration (Monolog)

**Lihat**: `MONOLOG_PRODUCTION_GUIDE.md` untuk panduan lengkap

Update `.env` production:
```env
LOG_CHANNEL=stack
LOG_STACK=daily,error,slack
LOG_LEVEL=warning
LOG_LEVEL_OCR=info
LOG_LEVEL_QUEUE=info
LOG_DAILY_DAYS=30

# Slack untuk critical errors
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK
LOG_SLACK_USERNAME="WHUSNET Production Alert"
LOG_SLACK_LEVEL=critical
```

**Struktur Log Production:**
```
logs/
├── laravel.log              # Application logs (daily rotation)
├── error.log                # Error-level only (90 days retention)
├── ocr.log                  # OCR-specific logs (14 days)
├── queue.log                # Queue/Job logs (14 days)
├── security.log             # Security events (90 days)
├── performance.log          # Slow queries/requests (7 days)
└── audit.log                # User actions (365 days)
```

**Custom Logging Helper:**
```php
use App\Helpers\LogHelper;

// OCR logging
LogHelper::ocr('info', 'Processing invoice', ['file' => $filename]);

// Security logging
LogHelper::security('Failed login attempt', ['email' => $email]);

// Audit logging
LogHelper::audit('created', 'Transaction', $transaction->id, $changes);

// Performance logging
LogHelper::performance('OCR Processing', $duration, ['file' => $filename]);

// Queue logging
LogHelper::queue('info', 'OcrProcessingJob', ['status' => 'completed']);

// Sanitize sensitive data
$data = LogHelper::sanitize(['password' => 'secret']); // password => ***REDACTED***
```

#### B. Log Rotation & Maintenance

```bash
# Make scripts executable
chmod +x scripts/rotate-logs.sh
chmod +x scripts/analyze-logs.sh

# Setup cron jobs
crontab -e

# Add these lines:
# Rotate logs daily at 3 AM
0 3 * * * /var/www/scripts/rotate-logs.sh >> /var/www/storage/logs/rotation.log 2>&1

# Analyze logs weekly (Monday 8 AM)
0 8 * * 1 /var/www/scripts/analyze-logs.sh

# Cleanup old compressed logs (monthly)
0 4 1 * * find /var/www/backups/logs -name "*.log.gz" -mtime +30 -delete
```

**Manual Commands:**
```bash
# Rotate logs manually
./scripts/rotate-logs.sh

# Analyze logs
./scripts/analyze-logs.sh

# Watch logs real-time
tail -f storage/logs/laravel.log

# Watch errors only
tail -f storage/logs/error.log

# Watch OCR processing
tail -f storage/logs/ocr.log | grep "duration_ms"

# Multi-tail (install: apt-get install multitail)
multitail storage/logs/laravel.log storage/logs/error.log storage/logs/ocr.log
```

#### C. Application Monitoring
Setup monitoring untuk:
- **Uptime**: Pingdom, UptimeRobot, atau StatusCake
- **APM**: New Relic, Datadog, atau Laravel Pulse
- **Error Tracking**: Sentry, Bugsnag, atau Flare

```bash
# Install Sentry (recommended)
composer require sentry/sentry-laravel

# Publish config
php artisan sentry:publish --dsn=YOUR_SENTRY_DSN
```

#### D. Telescope - DISABLE di Production ⚠️
```bash
# ❌ BAHAYA - Telescope di production:
# - Performance overhead (merekam semua request/query)
# - Storage bloat (database membengkak)
# - Security risk (ekspos data sensitif)
# - Memory usage tinggi

# ✅ SOLUSI: Nonaktifkan di .env production
TELESCOPE_ENABLED=false

# ✅ ALTERNATIF yang lebih baik:
# 1. Laravel Pulse (lebih ringan, production-ready)
composer require laravel/pulse
php artisan pulse:install

# 2. Sentry (error tracking only)
composer require sentry/sentry-laravel

# 3. New Relic / DataDog (APM professional)
```

**Lihat**: `TELESCOPE_PRODUCTION_GUIDE.md` untuk detail lengkap

---

### 6. **Security Hardening** 🛡️

#### A. HTTPS & SSL
```bash
# ✅ Pastikan semua traffic menggunakan HTTPS
# Tambahkan di .env
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
```

Update `app/Http/Middleware/TrustProxies.php`:
```php
protected $proxies = '*'; // Jika di belakang load balancer
protected $headers = Request::HEADER_X_FORWARDED_ALL;
```

#### B. Rate Limiting
File: `app/Http/Kernel.php` atau `bootstrap/app.php`
```php
// API rate limiting
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// OCR endpoint - lebih ketat
RateLimiter::for('ocr', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id);
});
```

#### C. Security Headers
Tambahkan middleware untuk security headers:
```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    
    return $response;
}
```

#### D. Input Validation
Pastikan semua FormRequest sudah ada validasi:
```bash
# Audit semua controllers
grep -r "Request \$request" app/Http/Controllers/
```

---

### 7. **Docker & Infrastructure** 🐳

#### A. Docker Compose Production
Buat file `docker-compose.prod.yml`:
```yaml
services:
  app:
    restart: always
    environment:
      APP_ENV: production
      APP_DEBUG: false
    # Remove volume mount untuk code (gunakan COPY di Dockerfile)
    
  nginx:
    restart: always
    # Tambahkan SSL configuration
    
  db:
    restart: always
    # Tambahkan volume untuk backup
    volumes:
      - dbdata:/var/lib/mysql
      - ./backups:/backups
      
  redis:
    restart: always
    # Pastikan persistence aktif
    command: >
      redis-server
      --requirepass ${REDIS_PASSWORD}
      --maxmemory 1gb
      --maxmemory-policy allkeys-lru
      --appendonly yes
      --save 900 1
      --save 300 10
```

#### B. Health Checks
Tambahkan health check endpoints:
```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::connection()->ping() ? 'connected' : 'disconnected',
        'queue' => Horizon::status() === 'running' ? 'running' : 'stopped',
    ]);
});
```

#### C. Resource Limits
Update `docker-compose.prod.yml`:
```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
        reservations:
          cpus: '1'
          memory: 1G
```

---

### 8. **Deployment Strategy** 🚢

#### A. Zero-Downtime Deployment
```bash
#!/bin/bash
# deploy.sh

set -e

echo "🚀 Starting deployment..."

# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Put app in maintenance mode
php artisan down --retry=60 --secret="deployment-secret-key"

# 4. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Run migrations
php artisan migrate --force

# 6. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Restart services
docker-compose restart app horizon reverb

# 8. Bring app back up
php artisan up

echo "✅ Deployment completed!"
```

#### B. Rollback Strategy
```bash
#!/bin/bash
# rollback.sh

set -e

echo "⏪ Rolling back..."

# 1. Maintenance mode
php artisan down

# 2. Checkout previous version
git reset --hard HEAD~1

# 3. Restore dependencies
composer install --no-dev --optimize-autoloader

# 4. Rollback migrations (jika perlu)
# php artisan migrate:rollback --step=1 --force

# 5. Clear & cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart
docker-compose restart app horizon reverb

# 7. Up
php artisan up

echo "✅ Rollback completed!"
```

---

### 9. **Testing Before Production** 🧪

#### A. Staging Environment
```bash
# Setup staging yang identik dengan production
# Gunakan data production yang di-anonymize

# Test deployment script
./deploy.sh

# Test rollback script
./rollback.sh

# Load testing
# Install: npm install -g artillery
artillery quick --count 100 --num 10 https://staging.yourdomain.com
```

#### B. Pre-Production Checklist
- [ ] Test semua fitur critical (login, OCR, payment, notifications)
- [ ] Test queue processing (submit 100 jobs sekaligus)
- [ ] Test WebSocket connections (buka 50 tabs)
- [ ] Test rate limiting (spam API endpoints)
- [ ] Test error handling (matikan Redis, matikan DB)
- [ ] Test backup & restore
- [ ] Test monitoring & alerting
- [ ] Load test dengan traffic 2x expected peak

---

### 10. **Post-Deployment Monitoring** 👀

#### A. First 24 Hours
```bash
# Monitor logs real-time
tail -f storage/logs/laravel.log

# Monitor Horizon
watch -n 5 'php artisan horizon:status'

# Monitor Redis
watch -n 5 'redis-cli -a password1234 INFO stats'

# Monitor MySQL
watch -n 5 'mysqladmin -u root -p processlist'

# Monitor system resources
htop
```

#### B. Metrics to Watch
- Response time (target: < 200ms untuk 95th percentile)
- Error rate (target: < 0.1%)
- Queue wait time (target: < 30 seconds)
- Redis memory usage (target: < 80%)
- Database connections (target: < 50% of max)
- CPU usage (target: < 70%)
- Memory usage (target: < 80%)

---

## 🔧 RECOMMENDED - Peningkatan Opsional

### 1. **CDN untuk Assets**
```bash
# Upload assets ke CDN (CloudFlare, AWS CloudFront, dll)
npm run build
aws s3 sync public/build s3://your-bucket/build --acl public-read

# Update .env
ASSET_URL=https://cdn.yourdomain.com
```

### 2. **Database Read Replicas**
```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            'replica1.mysql.com',
            'replica2.mysql.com',
        ],
    ],
    'write' => [
        'host' => ['master.mysql.com'],
    ],
    // ... other config
],
```

### 3. **Queue Priority**
```php
// Prioritize critical jobs
dispatch(new CriticalJob())->onQueue('high');
dispatch(new NormalJob())->onQueue('default');
dispatch(new LowPriorityJob())->onQueue('low');
```

### 4. **Scheduled Tasks Monitoring**
```bash
# Install Laravel Pulse atau Horizon untuk monitoring
composer require laravel/pulse

# Setup cron monitoring (Dead Man's Snitch, Cronitor)
# Tambahkan di schedule:
$schedule->command('backup:run')
    ->daily()
    ->pingOnSuccess('https://cronitor.link/xxx/success')
    ->pingOnFailure('https://cronitor.link/xxx/failure');
```

---

## ⚠️ COMMON PITFALLS - Hindari Kesalahan Ini

### 1. **Lupa Clear Cache Setelah Deploy**
```bash
# Selalu jalankan setelah deploy
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. **Tidak Test di Staging**
- Jangan deploy langsung ke production
- Selalu test di staging dengan data production-like

### 3. **Tidak Setup Monitoring**
- Setup monitoring SEBELUM production
- Test alerting sebelum production

### 4. **Tidak Ada Backup Strategy**
- Setup automated backup
- Test restore procedure

### 5. **Hardcode Credentials**
```php
// ❌ JANGAN
$password = 'admin123';

// ✅ GUNAKAN
$password = config('services.external.password');
```

### 6. **Tidak Handle Failed Jobs**
```bash
# Monitor dan handle failed jobs
php artisan queue:failed
php artisan queue:retry all
```

### 7. **Tidak Setup Rate Limiting**
- API endpoints harus ada rate limiting
- Protect dari abuse dan DDoS

---

## 📝 DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] Backup database production (jika ada)
- [ ] Review semua changes di staging
- [ ] Update .env dengan nilai production
- [ ] Generate semua secrets baru
- [ ] Test deployment script di staging
- [ ] Notify team tentang deployment window
- [ ] Setup monitoring & alerting
- [ ] Prepare rollback plan

### During Deployment
- [ ] Enable maintenance mode
- [ ] Pull latest code
- [ ] Install dependencies
- [ ] Run migrations
- [ ] Clear & rebuild caches
- [ ] Restart services
- [ ] Disable maintenance mode
- [ ] Smoke test critical features

### Post-Deployment
- [ ] Monitor logs untuk errors
- [ ] Check Horizon status
- [ ] Check Redis status
- [ ] Check database connections
- [ ] Test critical user flows
- [ ] Monitor performance metrics
- [ ] Notify team deployment success
- [ ] Document any issues

---

## 🆘 EMERGENCY CONTACTS & PROCEDURES

### Rollback Procedure
```bash
# Quick rollback
./rollback.sh

# Manual rollback
php artisan down
git reset --hard HEAD~1
composer install --no-dev
php artisan migrate:rollback --force
php artisan cache:clear && php artisan config:cache
docker-compose restart app horizon reverb
php artisan up
```

### Emergency Contacts
- DevOps Lead: [Name] - [Phone]
- Database Admin: [Name] - [Phone]
- Security Team: [Name] - [Phone]
- On-Call Engineer: [Phone]

### Critical Services
- Database: [Connection String]
- Redis: [Connection String]
- Monitoring: [Dashboard URL]
- Logs: [Log Management URL]

---

## 📚 ADDITIONAL RESOURCES

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [Redis Best Practices](https://redis.io/docs/manual/patterns/)

---

**Last Updated**: May 4, 2026
**Version**: 1.0
**Maintainer**: DevOps Team
