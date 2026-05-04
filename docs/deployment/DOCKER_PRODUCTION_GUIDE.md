# 🐳 Docker Production Setup Guide

## 📋 Overview

Project ini menggunakan **multi-stage Docker build** untuk production yang optimal:
- ✅ Smaller image size (Alpine-based)
- ✅ Security hardened
- ✅ OPcache & JIT enabled
- ✅ Production-optimized PHP-FPM
- ✅ NGINX with rate limiting & security headers
- ✅ Resource limits configured

---

## 📁 File Structure

```
.
├── Dockerfile.prod                    # Production Dockerfile (multi-stage)
├── docker-compose.prod.yml            # Production compose file
├── docker/
│   ├── nginx/
│   │   ├── production.conf           # NGINX production config
│   │   └── ssl/                      # SSL certificates
│   ├── php/
│   │   └── production.ini            # PHP production config
│   ├── php-fpm/
│   │   └── production.conf           # PHP-FPM production config
│   └── mysql/
│       └── production.cnf            # MySQL production config
├── preload.php                        # OPcache preloading
└── .github/workflows/                 # CI/CD workflows
    ├── test.yml                      # Run tests on PR
    ├── security-scan.yml             # Security scanning
    └── deploy-production.yml         # Deploy to production
```

---

## 🚀 Quick Start

### 1. Setup SSL Certificates

```bash
# Create SSL directory
mkdir -p docker/nginx/ssl

# Option A: Let's Encrypt (Recommended)
certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com
cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem docker/nginx/ssl/
cp /etc/letsencrypt/live/yourdomain.com/privkey.pem docker/nginx/ssl/

# Option B: Self-signed (Development/Testing only)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/privkey.pem \
  -out docker/nginx/ssl/fullchain.pem \
  -subj "/CN=yourdomain.com"
```

### 2. Configure Environment

```bash
# Copy production environment template
cp .env.production.example .env

# Edit with production values
nano .env
```

**Critical variables:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_PASSWORD=STRONG_PASSWORD
REDIS_PASSWORD=STRONG_PASSWORD

# Generate these:
APP_KEY=base64:...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
```

### 3. Build & Deploy

```bash
# Build production image
docker-compose -f docker-compose.prod.yml build

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Cache configuration
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

# Verify health
curl https://yourdomain.com/health
```

---

## 🔧 Configuration Details

### Dockerfile.prod (Multi-stage Build)

**Stage 1: Composer Dependencies**
- Installs production dependencies only (`--no-dev`)
- Optimized autoloader
- Cached layer for faster rebuilds

**Stage 2: Node.js Build**
- Builds frontend assets
- Minified and optimized
- Separate stage for better caching

**Stage 3: Production Runtime**
- Alpine-based (smaller image)
- OPcache enabled with preloading
- JIT compilation enabled
- Security hardened (non-root user)
- Only production files included

**Key Features:**
```dockerfile
# OPcache with preloading
opcache.enable = 1
opcache.preload = /var/www/preload.php
opcache.jit = tracing

# Security
expose_php = Off
disable_functions = exec,passthru,shell_exec,...

# Performance
realpath_cache_size = 4096K
opcache.max_accelerated_files = 20000
```

### NGINX Production Config

**Security Features:**
- ✅ HTTPS only (HTTP redirects to HTTPS)
- ✅ Security headers (HSTS, CSP, X-Frame-Options, etc)
- ✅ Rate limiting (per endpoint)
- ✅ Connection limiting
- ✅ Hidden server tokens

**Performance Features:**
- ✅ Gzip compression
- ✅ Static file caching (1 year)
- ✅ FastCGI buffering
- ✅ HTTP/2 enabled
- ✅ Keepalive connections

**Rate Limiting:**
```nginx
# General: 10 req/s
limit_req_zone $binary_remote_addr zone=general:10m rate=10r/s;

# API: 60 req/min
limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;

# Login: 5 req/min
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
```

### PHP-FPM Production Config

**Process Manager:**
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

**Monitoring:**
- Status page: `/fpm-status`
- Ping page: `/fpm-ping`
- Slow log: Queries > 5s

### MySQL Production Config

**Optimizations:**
```ini
# InnoDB buffer pool (adjust based on RAM)
innodb_buffer_pool_size = 1G

# Connection pooling
max_connections = 200
thread_cache_size = 16

# Query cache (use Redis instead)
table_open_cache = 4000

# Slow query log
slow_query_log = 1
long_query_time = 2
```

---

## 📊 Resource Limits

### Container Resources

```yaml
app:
  deploy:
    resources:
      limits:
        cpus: '2'
        memory: 2G
      reservations:
        cpus: '1'
        memory: 1G

nginx:
  deploy:
    resources:
      limits:
        cpus: '1'
        memory: 512M

redis:
  deploy:
    resources:
      limits:
        cpus: '1'
        memory: 1G
```

### Recommended Server Specs

**Minimum:**
- 2 CPU cores
- 4GB RAM
- 40GB SSD
- 100 Mbps network

**Recommended:**
- 4 CPU cores
- 8GB RAM
- 80GB SSD
- 1 Gbps network

**For High Traffic:**
- 8+ CPU cores
- 16GB+ RAM
- 160GB+ SSD
- 1 Gbps network

---

## 🔍 Monitoring & Debugging

### Health Checks

```bash
# Application health
curl https://yourdomain.com/health

# PHP-FPM status
curl http://localhost/fpm-status

# NGINX status
docker-compose -f docker-compose.prod.yml exec nginx nginx -t
```

### Logs

```bash
# Application logs
docker-compose -f docker-compose.prod.yml logs -f app

# NGINX logs
docker-compose -f docker-compose.prod.yml logs -f nginx

# Horizon logs
docker-compose -f docker-compose.prod.yml logs -f horizon

# All logs
docker-compose -f docker-compose.prod.yml logs -f
```

### Performance Monitoring

```bash
# Container stats
docker stats

# PHP-FPM pool status
docker-compose -f docker-compose.prod.yml exec app php-fpm -t

# OPcache status
docker-compose -f docker-compose.prod.yml exec app php -r "print_r(opcache_get_status());"

# MySQL slow queries
docker-compose -f docker-compose.prod.yml exec db mysql -u root -p -e "SELECT * FROM mysql.slow_log LIMIT 10;"
```

---

## 🔄 Updates & Maintenance

### Update Application

```bash
# Pull latest code
git pull origin main

# Rebuild image
docker-compose -f docker-compose.prod.yml build --no-cache

# Restart services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear caches
docker-compose -f docker-compose.prod.yml exec app php artisan optimize:clear
docker-compose -f docker-compose.prod.yml exec app php artisan optimize
```

### Update Dependencies

```bash
# Update Composer
docker-compose -f docker-compose.prod.yml exec app composer update

# Update NPM
docker-compose -f docker-compose.prod.yml run --rm node npm update

# Rebuild image
docker-compose -f docker-compose.prod.yml build --no-cache
```

### Database Backup

```bash
# Manual backup
docker-compose -f docker-compose.prod.yml exec db mysqldump \
  -u root -p"$DB_PASSWORD" admin-payment | gzip > backup_$(date +%Y%m%d).sql.gz

# Automated backup (add to crontab)
0 2 * * * cd /var/www/admin-payment && docker-compose -f docker-compose.prod.yml exec -T db mysqldump -u root -p"$DB_PASSWORD" admin-payment | gzip > backups/backup_$(date +\%Y\%m\%d).sql.gz
```

### Restore Database

```bash
# Restore from backup
gunzip < backup.sql.gz | docker-compose -f docker-compose.prod.yml exec -T db mysql -u root -p"$DB_PASSWORD" admin-payment
```

---

## 🔒 Security Best Practices

### 1. Use Secrets Management

```bash
# Don't commit .env to git
echo ".env" >> .gitignore

# Use Docker secrets (Swarm mode)
docker secret create db_password /path/to/password.txt
```

### 2. Regular Updates

```bash
# Update base images
docker-compose -f docker-compose.prod.yml pull

# Rebuild with latest security patches
docker-compose -f docker-compose.prod.yml build --no-cache
```

### 3. Network Security

```yaml
# Only expose necessary ports
ports:
  - "127.0.0.1:3306:3306"  # MySQL only on localhost
  - "127.0.0.1:6379:6379"  # Redis only on localhost
  - "443:443"               # HTTPS public
```

### 4. File Permissions

```bash
# Set correct permissions
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache
```

---

## 🚨 Troubleshooting

### Container Won't Start

```bash
# Check logs
docker-compose -f docker-compose.prod.yml logs app

# Check configuration
docker-compose -f docker-compose.prod.yml config

# Rebuild from scratch
docker-compose -f docker-compose.prod.yml down -v
docker-compose -f docker-compose.prod.yml build --no-cache
docker-compose -f docker-compose.prod.yml up -d
```

### High Memory Usage

```bash
# Check container stats
docker stats

# Adjust PHP-FPM settings
# Edit docker/php-fpm/production.conf
pm.max_children = 30  # Reduce if needed

# Adjust OPcache
# Edit docker/php/production.ini
opcache.memory_consumption = 128  # Reduce if needed
```

### Slow Performance

```bash
# Check OPcache status
docker-compose -f docker-compose.prod.yml exec app php -r "var_dump(opcache_get_status());"

# Check slow queries
docker-compose -f docker-compose.prod.yml exec db mysql -u root -p -e "SHOW PROCESSLIST;"

# Check Redis memory
docker-compose -f docker-compose.prod.yml exec redis redis-cli -a "$REDIS_PASSWORD" INFO memory
```

### SSL Certificate Issues

```bash
# Test SSL
openssl s_client -connect yourdomain.com:443

# Renew Let's Encrypt
certbot renew
cp /etc/letsencrypt/live/yourdomain.com/*.pem docker/nginx/ssl/
docker-compose -f docker-compose.prod.yml restart nginx
```

---

## 📚 Additional Resources

- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [NGINX Optimization](https://www.nginx.com/blog/tuning-nginx/)
- [PHP-FPM Tuning](https://www.php.net/manual/en/install.fpm.configuration.php)
- [MySQL Performance](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)

---

**Last Updated**: May 4, 2026  
**Version**: 1.0
