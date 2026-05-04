# 🚀 aaPanel Deployment Guide - Complete

## 📋 Overview

Panduan lengkap untuk deploy WHUSNET Admin Payment di **aaPanel** dengan:
- ✅ Laravel 12 + PHP 8.4
- ✅ Redis untuk cache & queue
- ✅ Laravel Reverb (WebSocket)
- ✅ Laravel Horizon (Queue)
- ✅ CI/CD dengan GitHub Actions
- ✅ SSL/HTTPS
- ✅ Zero-downtime deployment

---

## 🎯 Prerequisites

### Server Requirements
- Ubuntu 20.04/22.04 LTS
- Minimum 2GB RAM (4GB recommended)
- 2 CPU cores (4 cores recommended)
- 40GB SSD storage
- Root access

### Domain Requirements
- Domain sudah pointing ke server IP
- SSL certificate (Let's Encrypt via aaPanel)

---

## 📦 Part 1: Install aaPanel

### Step 1: Install aaPanel

```bash
# SSH ke server
ssh root@your-server-ip

# Update system
apt update && apt upgrade -y

# Install aaPanel
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh && bash install.sh aapanel

# Tunggu instalasi selesai (5-10 menit)
# Catat URL, username, dan password yang muncul
```

**Output Example:**
```
==================================================================
Congratulations! Installed successfully!
==================================================================
aaPanel Internet Address: http://123.456.789.0:7800/abc123def
aaPanel Internal Address: http://172.31.0.1:7800/abc123def
username: admin
password: 12345678
==================================================================
```

### Step 2: Login ke aaPanel

1. Buka browser: `http://your-server-ip:7800/abc123def`
2. Login dengan username & password
3. Pilih bahasa: English
4. Install LNMP Stack (recommended)

---

## 📦 Part 2: Install Required Software

### Step 1: Install via aaPanel App Store

**Klik "App Store" → Install:**

1. **NGINX** - Latest version
2. **MySQL 8.0** - Latest version
3. **PHP 8.4** - Latest version
4. **Redis** - Latest version
5. **Supervisor** - Latest version
6. **Composer** - Latest version

**Tunggu semua selesai install (~15-30 menit)**

### Step 2: Configure PHP 8.4

**Settings → PHP 8.4 → Install Extensions:**

Install extensions berikut:
- ✅ opcache
- ✅ redis
- ✅ imagick
- ✅ fileinfo
- ✅ exif
- ✅ intl
- ✅ zip
- ✅ bcmath
- ✅ gd

**PHP 8.4 → Configuration File:**

Edit `php.ini`:
```ini
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_time = 300

; OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1

; JIT (PHP 8.4)
opcache.jit=tracing
opcache.jit_buffer_size=128M
```

**Save & Restart PHP-FPM**

### Step 3: Configure Redis

**App Store → Redis → Settings:**

```bash
# Edit redis.conf
maxmemory 1gb
maxmemory-policy allkeys-lru
requirepass YOUR_STRONG_PASSWORD

# Save and restart Redis
```

**Test Redis:**
```bash
redis-cli
AUTH YOUR_STRONG_PASSWORD
PING
# Should return: PONG
```

---

## 📦 Part 3: Create Website in aaPanel

### Step 1: Add Website

**Website → Add Site:**

```
Domain: yourdomain.com
Root Directory: /www/wwwroot/yourdomain.com
PHP Version: PHP-84
Database: Create (admin_payment)
```

**Click "Submit"**

### Step 2: Configure SSL

**Website → yourdomain.com → SSL:**

1. Select "Let's Encrypt"
2. Enter email
3. Check "Force HTTPS"
4. Click "Apply"

**Wait for SSL certificate (~2 minutes)**

### Step 3: Configure NGINX

**Website → yourdomain.com → Site Directory:**

```
Site Directory: /www/wwwroot/yourdomain.com/public
Run Directory: /www/wwwroot/yourdomain.com
```

**Website → yourdomain.com → Config File:**

Replace with this configuration:

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /www/wwwroot/yourdomain.com/public;
    index index.php index.html;
    
    # SSL Configuration (managed by aaPanel)
    ssl_certificate /www/server/panel/vhost/cert/yourdomain.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:HIGH:!aNULL:!MD5:!RC4:!DHE;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Client settings
    client_max_body_size 64M;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript;
    
    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/tmp/php-cgi-84.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        
        # HTTPS headers
        fastcgi_param HTTPS on;
        fastcgi_param HTTP_X_FORWARDED_PROTO https;
    }
    
    # Deny access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Access logs
    access_log /www/wwwlogs/yourdomain.com.log;
    error_log /www/wwwlogs/yourdomain.com.error.log;
}
```

**Save & Reload NGINX**

---

## 📦 Part 4: Deploy Laravel Application

### Step 1: Clone Repository

```bash
# SSH ke server
ssh root@your-server-ip

# Navigate to web root
cd /www/wwwroot/yourdomain.com

# Backup default files
mv index.html index.html.backup

# Clone repository
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git .

# Or if already cloned, pull latest
git pull origin main
```

### Step 2: Install Dependencies

```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install NPM dependencies
npm install

# Build assets
npm run build
```

### Step 3: Configure Environment

```bash
# Copy .env file
cp .env.example .env

# Edit .env
nano .env
```

**Update these values:**
```env
APP_NAME="WHUSNET Admin Payment"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Generate this with: php artisan key:generate
APP_KEY=base64:GENERATE_THIS

# Database (from aaPanel)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_payment
DB_USERNAME=admin_payment
DB_PASSWORD=YOUR_DB_PASSWORD

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Reverb (WebSocket)
REVERB_APP_ID=123456
REVERB_APP_KEY=YOUR_REVERB_KEY
REVERB_APP_SECRET=YOUR_REVERB_SECRET
REVERB_HOST=yourdomain.com
REVERB_PORT=8080
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

**Generate keys:**
```bash
# Generate APP_KEY
php artisan key:generate

# Generate Reverb keys
openssl rand -base64 32  # For REVERB_APP_KEY
openssl rand -base64 32  # For REVERB_APP_SECRET
```

### Step 4: Set Permissions

```bash
# Set ownership
chown -R www:www /www/wwwroot/yourdomain.com

# Set permissions
chmod -R 755 /www/wwwroot/yourdomain.com
chmod -R 775 /www/wwwroot/yourdomain.com/storage
chmod -R 775 /www/wwwroot/yourdomain.com/bootstrap/cache

# Secure .env
chmod 600 /www/wwwroot/yourdomain.com/.env
```

### Step 5: Run Migrations

```bash
# Run migrations
php artisan migrate --force

# Seed data (if needed)
php artisan db:seed --force

# Cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Step 6: Test Application

```bash
# Test in browser
https://yourdomain.com

# Should see Laravel application
```

---

## 📦 Part 5: Setup Laravel Horizon (Queue Worker)

### Step 1: Install Horizon

```bash
cd /www/wwwroot/yourdomain.com

# Already installed via composer
# Just publish config
php artisan horizon:install
```

### Step 2: Configure Supervisor

**aaPanel → App Store → Supervisor → Settings → Add:**

**Process 1: Horizon**
```
Name: laravel-horizon
Run Directory: /www/wwwroot/yourdomain.com
Start Command: /usr/bin/php artisan horizon
Processes: 1
User: www
Auto Start: Yes
Auto Restart: Yes
```

**Click "Confirm"**

**Start the process:**
```bash
# Via aaPanel Supervisor interface
# Or via command line:
supervisorctl start laravel-horizon
supervisorctl status
```

### Step 3: Verify Horizon

```bash
# Check Horizon status
php artisan horizon:status

# Access Horizon dashboard
https://yourdomain.com/horizon
```

---

## 📦 Part 6: Setup Laravel Reverb (WebSocket)

### Step 1: Configure Reverb

```bash
cd /www/wwwroot/yourdomain.com

# Config already in .env
# Just verify
php artisan reverb:install
```

### Step 2: Setup Supervisor for Reverb

**aaPanel → Supervisor → Add:**

**Process 2: Reverb**
```
Name: laravel-reverb
Run Directory: /www/wwwroot/yourdomain.com
Start Command: /usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080
Processes: 1
User: www
Auto Start: Yes
Auto Restart: Yes
```

**Click "Confirm" and Start**

### Step 3: Configure NGINX for WebSocket

**Website → yourdomain.com → Config File:**

Add this BEFORE the main server block:

```nginx
# WebSocket upstream
upstream reverb {
    server 127.0.0.1:8080;
}

# WebSocket server
server {
    listen 443 ssl http2;
    server_name ws.yourdomain.com;
    
    ssl_certificate /www/server/panel/vhost/cert/yourdomain.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/yourdomain.com/privkey.pem;
    
    location / {
        proxy_pass http://reverb;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
    }
}
```

**Or add to main server block:**

```nginx
# Inside main server block
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}
```

**Save & Reload NGINX**

### Step 4: Open Firewall Port

**aaPanel → Security:**

Add rule:
```
Port: 8080
Protocol: TCP
Description: Laravel Reverb WebSocket
```

**Or via command line:**
```bash
ufw allow 8080/tcp
```

### Step 5: Test Reverb

```bash
# Check if Reverb is running
supervisorctl status laravel-reverb

# Test WebSocket connection
# In browser console:
# Should connect successfully
```

---

## 📦 Part 7: Setup Laravel Scheduler (Cron)

### Step 1: Add Cron Job

**aaPanel → Cron:**

Click "Add"

```
Type: Shell Script
Name: Laravel Scheduler
Period: Every minute (N Minutes: 1)
Script:
cd /www/wwwroot/yourdomain.com && php artisan schedule:run >> /dev/null 2>&1
```

**Save**

### Step 2: Verify Cron

```bash
# Check cron logs
tail -f /www/server/cron/*.log

# Or check Laravel logs
tail -f /www/wwwroot/yourdomain.com/storage/logs/laravel.log
```

---

## 📦 Part 8: Setup CI/CD with GitHub Actions

### Step 1: Generate SSH Key

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions-aapanel" -f ~/.ssh/github-actions-aapanel

# Copy public key to server
ssh-copy-id -i ~/.ssh/github-actions-aapanel.pub root@your-server-ip

# Test connection
ssh -i ~/.ssh/github-actions-aapanel root@your-server-ip

# Get private key content
cat ~/.ssh/github-actions-aapanel
# Copy the entire output
```

### Step 2: Add GitHub Secrets

**GitHub Repository → Settings → Secrets and variables → Actions → New repository secret**

Add these secrets:

| Name | Value |
|------|-------|
| `SSH_PRIVATE_KEY` | Content of `~/.ssh/github-actions-aapanel` |
| `SERVER_HOST` | Your server IP (e.g., 123.456.789.0) |
| `SERVER_USER` | root |
| `DEPLOY_PATH` | /www/wwwroot/yourdomain.com |
| `ENV_FILE` | Complete content of your .env file |
| `SLACK_WEBHOOK_URL` | Your Slack webhook URL (optional) |

### Step 3: Create Deployment Workflow

Create file: `.github/workflows/deploy-aapanel.yml`

```yaml
name: Deploy to aaPanel

on:
  push:
    branches:
      - main
  workflow_dispatch:

jobs:
  deploy:
    name: Deploy to aaPanel Server
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Setup SSH
        uses: webfactory/ssh-agent@v0.9.0
        with:
          ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}
      
      - name: Add server to known hosts
        run: |
          mkdir -p ~/.ssh
          ssh-keyscan -H ${{ secrets.SERVER_HOST }} >> ~/.ssh/known_hosts
      
      - name: Deploy to server
        run: |
          ssh ${{ secrets.SERVER_USER }}@${{ secrets.SERVER_HOST }} << 'ENDSSH'
            set -e
            
            echo "🚀 Starting deployment..."
            
            # Navigate to project
            cd ${{ secrets.DEPLOY_PATH }}
            
            # Enable maintenance mode
            php artisan down || true
            
            # Pull latest code
            git pull origin main
            
            # Install dependencies
            composer install --no-dev --optimize-autoloader --no-interaction
            
            # Build assets
            npm ci
            npm run build
            
            # Run migrations
            php artisan migrate --force
            
            # Clear and cache
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan event:cache
            
            # Restart services
            supervisorctl restart laravel-horizon
            supervisorctl restart laravel-reverb
            
            # Disable maintenance mode
            php artisan up
            
            echo "✅ Deployment completed!"
          ENDSSH
      
      - name: Verify deployment
        run: |
          sleep 5
          curl -f https://yourdomain.com/ping || exit 1
      
      - name: Notify success
        if: success()
        run: |
          echo "✅ Deployment successful!"
```

### Step 4: Test Deployment

```bash
# Make a change
echo "# Test" >> README.md

# Commit and push
git add .
git commit -m "test: CI/CD deployment"
git push origin main

# Check GitHub Actions tab
# Deployment should run automatically
```

---

## 📦 Part 9: Monitoring & Maintenance

### Setup Monitoring

**1. Enable Laravel Telescope (Development only)**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**2. Setup Log Rotation**

**aaPanel → Files → /www/wwwroot/yourdomain.com/storage/logs**

Create cron job:
```bash
# Compress old logs
find /www/wwwroot/yourdomain.com/storage/logs -name "*.log" -mtime +7 -exec gzip {} \;

# Delete old compressed logs
find /www/wwwroot/yourdomain.com/storage/logs -name "*.log.gz" -mtime +30 -delete
```

**3. Monitor Services**

```bash
# Check Supervisor status
supervisorctl status

# Check NGINX status
systemctl status nginx

# Check PHP-FPM status
systemctl status php-fpm-84

# Check Redis status
systemctl status redis

# Check MySQL status
systemctl status mysql
```

### Backup Strategy

**aaPanel → Database → admin_payment → Backup:**

Setup automated backup:
```
Backup Type: Database
Period: Daily at 2:00 AM
Keep: 7 days
```

**Or via cron:**
```bash
# Add to aaPanel Cron
0 2 * * * mysqldump -u admin_payment -p'PASSWORD' admin_payment | gzip > /www/backup/db_$(date +\%Y\%m\%d).sql.gz
```

---

## 📦 Part 10: Troubleshooting

### Issue 1: 502 Bad Gateway

**Check:**
```bash
# Check PHP-FPM
systemctl status php-fpm-84

# Check NGINX error log
tail -f /www/wwwlogs/yourdomain.com.error.log

# Check Laravel log
tail -f /www/wwwroot/yourdomain.com/storage/logs/laravel.log
```

**Fix:**
```bash
# Restart PHP-FPM
systemctl restart php-fpm-84

# Restart NGINX
systemctl restart nginx
```

### Issue 2: Queue Not Processing

**Check:**
```bash
# Check Horizon status
supervisorctl status laravel-horizon

# Check Horizon logs
tail -f /tmp/laravel-horizon.log
```

**Fix:**
```bash
# Restart Horizon
supervisorctl restart laravel-horizon

# Or manually
php artisan horizon:terminate
supervisorctl start laravel-horizon
```

### Issue 3: WebSocket Not Connecting

**Check:**
```bash
# Check Reverb status
supervisorctl status laravel-reverb

# Check if port is open
netstat -tlnp | grep 8080

# Check firewall
ufw status
```

**Fix:**
```bash
# Restart Reverb
supervisorctl restart laravel-reverb

# Open port
ufw allow 8080/tcp
```

### Issue 4: Permission Denied

**Fix:**
```bash
# Set correct ownership
chown -R www:www /www/wwwroot/yourdomain.com

# Set correct permissions
chmod -R 755 /www/wwwroot/yourdomain.com
chmod -R 775 /www/wwwroot/yourdomain.com/storage
chmod -R 775 /www/wwwroot/yourdomain.com/bootstrap/cache
```

---

## ✅ Deployment Checklist

### Initial Setup
- [ ] aaPanel installed
- [ ] LNMP stack installed
- [ ] PHP 8.4 extensions installed
- [ ] Redis installed and configured
- [ ] Website created in aaPanel
- [ ] SSL certificate installed
- [ ] NGINX configured

### Application Setup
- [ ] Repository cloned
- [ ] Dependencies installed
- [ ] .env configured
- [ ] APP_KEY generated
- [ ] Permissions set
- [ ] Migrations run
- [ ] Application accessible

### Services Setup
- [ ] Horizon configured and running
- [ ] Reverb configured and running
- [ ] Scheduler cron job added
- [ ] All services auto-start enabled

### CI/CD Setup
- [ ] SSH key generated
- [ ] GitHub Secrets configured
- [ ] Deployment workflow created
- [ ] Test deployment successful

### Monitoring
- [ ] Log rotation configured
- [ ] Database backup configured
- [ ] Monitoring tools setup
- [ ] Health checks working

---

## 🎉 Summary

**You now have:**
- ✅ Laravel 12 running on aaPanel
- ✅ Redis for cache & queue
- ✅ Laravel Horizon for queue processing
- ✅ Laravel Reverb for WebSocket
- ✅ SSL/HTTPS enabled
- ✅ CI/CD with GitHub Actions
- ✅ Automated backups
- ✅ Monitoring & logging

**Next Steps:**
1. Test all features
2. Monitor logs for errors
3. Setup additional monitoring (optional)
4. Train team on deployment process

---

**Last Updated**: May 4, 2026  
**Version**: 1.0  
**Platform**: aaPanel + Ubuntu
