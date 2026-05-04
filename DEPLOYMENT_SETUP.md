# 🚀 Deployment Setup Guide

## Overview

This project uses **GitHub Actions** for automated deployment with **zero-downtime** strategy using Docker and blue-green deployment.

## 📋 Prerequisites

### Server Requirements
- Docker & Docker Compose installed
- SSH access configured
- Minimum 4GB RAM, 2 CPU cores
- Ports 80, 443, 8081 open

### GitHub Repository Setup
You need to configure the following **GitHub Secrets** in your repository settings:

## 🔐 Required GitHub Secrets

Go to: `Settings` → `Secrets and variables` → `Actions` → `New repository secret`

### 1. SSH_PRIVATE_KEY
**Description:** Private SSH key for server access

**How to generate:**
```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_actions

# Copy the private key
cat ~/.ssh/github_actions

# Add the public key to your server
ssh-copy-id -i ~/.ssh/github_actions.pub user@your-server.com
```

**Value:** Paste the entire private key content (including `-----BEGIN` and `-----END` lines)

---

### 2. SERVER_HOST
**Description:** Your server's hostname or IP address

**Example:**
```
123.456.789.0
```
or
```
server.yourdomain.com
```

---

### 3. SERVER_USER
**Description:** SSH username for server access

**Example:**
```
root
```
or
```
ubuntu
```

---

### 4. ENV_FILE
**Description:** Complete production `.env` file content

**How to create:**
```bash
# Copy your production .env
cat .env.production
```

**Important variables to set:**
```env
APP_NAME="WHUSNET Admin Payment"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Add all other required variables...
```

---

### 5. SLACK_WEBHOOK_URL (Optional)
**Description:** Slack webhook URL for deployment notifications

**How to get:**
1. Go to https://api.slack.com/apps
2. Create a new app or select existing
3. Enable "Incoming Webhooks"
4. Create a webhook for your channel
5. Copy the webhook URL

**Example:**
```
https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX
```

**Note:** If not set, deployment will continue without notifications.

---

## 🎯 Deployment Workflow

### Automatic Deployment
Pushes to `main` branch automatically trigger deployment:

```bash
git push origin main
```

### Manual Deployment
1. Go to `Actions` tab in GitHub
2. Select "Deploy to Production"
3. Click "Run workflow"
4. Choose options:
   - **skip_tests**: Skip test execution (use with caution)

### Rollback
1. Go to `Actions` tab
2. Select "Deploy to Production"
3. Click "Run workflow"
4. The rollback job will execute

Or manually on server:
```bash
cd /var/www/admin-payment
./rollback.sh
```

---

## 📊 Deployment Process

### Zero-Downtime Strategy

```
┌─────────────────────────────────────────────────────────┐
│ 1. Pull new Docker images (old container still running) │
├─────────────────────────────────────────────────────────┤
│ 2. Backup database                                       │
├─────────────────────────────────────────────────────────┤
│ 3. Start new container alongside old (blue-green)       │
├─────────────────────────────────────────────────────────┤
│ 4. Health check new container                           │
├─────────────────────────────────────────────────────────┤
│ 5. Run migrations on new container                      │
├─────────────────────────────────────────────────────────┤
│ 6. Optimize caches on new container                     │
├─────────────────────────────────────────────────────────┤
│ 7. Switch NGINX to new container                        │
├─────────────────────────────────────────────────────────┤
│ 8. Gracefully stop old container (30s timeout)          │
├─────────────────────────────────────────────────────────┤
│ 9. Update background services (Horizon, Reverb, etc)    │
├─────────────────────────────────────────────────────────┤
│ 10. Final health checks                                 │
├─────────────────────────────────────────────────────────┤
│ 11. Cleanup old images                                  │
└─────────────────────────────────────────────────────────┘
```

**Downtime:** ~0 seconds (requests continue to old container until new is ready)

---

## 🔧 Server Setup

### Initial Server Setup

```bash
# 1. Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# 2. Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# 3. Create deployment directory
sudo mkdir -p /var/www/admin-payment
sudo chown $USER:$USER /var/www/admin-payment

# 4. Create backups directory
mkdir -p /var/www/admin-payment/backups

# 5. Add SSH key for GitHub Actions
# (Add the public key from SSH_PRIVATE_KEY to ~/.ssh/authorized_keys)
```

### Verify Setup

```bash
# Check Docker
docker --version
docker-compose --version

# Check permissions
ls -la /var/www/admin-payment

# Test SSH connection (from your local machine)
ssh -i ~/.ssh/github_actions user@your-server.com "echo 'SSH connection successful'"
```

---

## 🐛 Troubleshooting

### Deployment Failed

**Check GitHub Actions logs:**
1. Go to `Actions` tab
2. Click on failed workflow
3. Review error messages

**Check server logs:**
```bash
ssh user@server.com
cd /var/www/admin-payment
docker-compose logs --tail 100 app
```

### Health Check Failed

```bash
# Check if containers are running
docker-compose ps

# Check app logs
docker-compose logs app

# Check NGINX logs
docker-compose logs nginx

# Manual health check
docker-compose exec app php artisan list
```

### Rollback Not Working

```bash
# List available images
docker images ghcr.io/your-repo/your-image

# Manual rollback to specific version
export APP_VERSION=abc123def456
docker-compose pull
docker-compose up -d --force-recreate
```

### Database Issues

```bash
# Check database connection
docker-compose exec app php artisan db:show

# Check migrations
docker-compose exec app php artisan migrate:status

# Restore from backup
cd /var/www/admin-payment/backups
gunzip -c backup_20240101_120000.sql.gz | docker-compose exec -T db mysql -u root -p"${DB_PASSWORD}" "${DB_DATABASE}"
```

---

## 📝 Maintenance

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f horizon
```

### Update Environment Variables

```bash
# 1. Update .env file on server
nano /var/www/admin-payment/.env

# 2. Restart services
docker-compose up -d --force-recreate
```

### Manual Deployment

```bash
cd /var/www/admin-payment

# Pull latest images
docker-compose pull

# Restart services
docker-compose up -d --force-recreate

# Check status
docker-compose ps
```

### Cleanup Old Images

```bash
# Remove unused images
docker image prune -a

# Remove old backups (keep last 7 days)
find /var/www/admin-payment/backups -name "*.sql.gz" -mtime +7 -delete
```

---

## 🔒 Security Checklist

- [ ] SSH key-based authentication only (disable password auth)
- [ ] Firewall configured (UFW or iptables)
- [ ] SSL certificates installed (Let's Encrypt)
- [ ] Database password is strong and unique
- [ ] Redis password is set
- [ ] `.env` file has correct permissions (600)
- [ ] GitHub secrets are properly configured
- [ ] Server has automatic security updates enabled
- [ ] Backup strategy is in place
- [ ] Monitoring and alerting configured

---

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)

---

## 🆘 Support

If you encounter issues:

1. Check this documentation
2. Review GitHub Actions logs
3. Check server logs
4. Contact DevOps team

---

**Last Updated:** 2024
**Maintained by:** DevOps Team
