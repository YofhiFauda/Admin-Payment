# ⚡ aaPanel Quick Start Guide (30 Minutes)

## 🎯 Goal

Deploy WHUSNET Admin Payment di aaPanel dalam **30 menit** dengan Redis, Reverb, dan CI/CD.

---

## ✅ Prerequisites

- Server Ubuntu 20.04/22.04 (2GB RAM minimum)
- Domain sudah pointing ke server
- Root access

---

## 🚀 Step-by-Step (30 Minutes)

### Step 1: Install aaPanel (5 minutes)

```bash
# SSH ke server
ssh root@your-server-ip

# Install aaPanel
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh && bash install.sh aapanel

# Catat URL, username, password yang muncul
```

### Step 2: Install Software (10 minutes)

**Login ke aaPanel → App Store → Install:**

1. NGINX (latest)
2. MySQL 8.0
3. PHP 8.4
4. Redis (latest)
5. Supervisor
6. Composer

**PHP 8.4 → Install Extensions:**
- opcache, redis, imagick, fileinfo, exif, intl, zip, bcmath, gd

### Step 3: Create Website (3 minutes)

**Website → Add Site:**
```
Domain: yourdomain.com
PHP: PHP-84
Database: Create (admin_payment)
```

**SSL → Let's Encrypt:**
- Enter email
- Check "Force HTTPS"
- Apply

### Step 4: Deploy Application (5 minutes)

```bash
# SSH ke server
cd /www/wwwroot/yourdomain.com

# Clone repository
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git .

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Setup .env
cp .env.example .env
nano .env  # Edit database, Redis, etc

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Set permissions
chown -R www:www /www/wwwroot/yourdomain.com
chmod -R 775 storage bootstrap/cache
```

### Step 5: Configure NGINX (2 minutes)

**Website → yourdomain.com → Config File:**

Replace with:
```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /www/wwwroot/yourdomain.com/public;
    index index.php index.html;
    
    # SSL (managed by aaPanel)
    ssl_certificate /www/server/panel/vhost/cert/yourdomain.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/yourdomain.com/privkey.pem;
    
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
    }
    
    # Deny sensitive files
    location ~ /\.(env|git) {
        deny all;
    }
}
```

**Save & Reload NGINX**

### Step 6: Setup Horizon & Reverb (3 minutes)

**aaPanel → Supervisor → Add:**

**Process 1: Horizon**
```
Name: laravel-horizon
Directory: /www/wwwroot/yourdomain.com
Command: /usr/bin/php artisan horizon
User: www
Auto Start: Yes
```

**Process 2: Reverb**
```
Name: laravel-reverb
Directory: /www/wwwroot/yourdomain.com
Command: /usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080
User: www
Auto Start: Yes
```

**Start both processes**

### Step 7: Setup Cron (1 minute)

**aaPanel → Cron → Add:**
```
Type: Shell Script
Name: Laravel Scheduler
Period: Every minute
Script: cd /www/wwwroot/yourdomain.com && php artisan schedule:run
```

### Step 8: Setup CI/CD (5 minutes)

**Generate SSH key (local machine):**
```bash
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github-actions-aapanel
ssh-copy-id -i ~/.ssh/github-actions-aapanel.pub root@your-server-ip
cat ~/.ssh/github-actions-aapanel  # Copy this
```

**GitHub → Settings → Secrets → Add:**
- `SSH_PRIVATE_KEY`: (paste SSH private key)
- `SERVER_HOST`: your-server-ip
- `SERVER_USER`: root
- `DEPLOY_PATH`: /www/wwwroot/yourdomain.com

**Create `.github/workflows/deploy-aapanel.yml`** (already created)

**Test:**
```bash
git add .
git commit -m "test: CI/CD"
git push origin main
# Check GitHub Actions tab
```

---

## ✅ Verification

```bash
# 1. Check website
https://yourdomain.com

# 2. Check Horizon
https://yourdomain.com/horizon

# 3. Check services
supervisorctl status

# 4. Check logs
tail -f /www/wwwroot/yourdomain.com/storage/logs/laravel.log
```

---

## 🎉 Done!

**You now have:**
- ✅ Laravel running on aaPanel
- ✅ Redis configured
- ✅ Horizon running
- ✅ Reverb running
- ✅ SSL/HTTPS enabled
- ✅ CI/CD with GitHub Actions

**Total Time: ~30 minutes**

---

## 📚 Full Documentation

For detailed guide, read: **AAPANEL_DEPLOYMENT_GUIDE.md**

---

**Last Updated**: May 4, 2026
