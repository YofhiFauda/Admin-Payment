# 🚀 WHUSNET Admin Payment - Production Deployment Guide

## 📚 Dokumentasi Lengkap

Project ini sudah dilengkapi dengan dokumentasi lengkap untuk deployment production:

### 1. **PRODUCTION_READINESS_CHECKLIST.md** ⭐ MULAI DI SINI
   - Checklist lengkap persiapan production
   - Step-by-step deployment guide
   - Rollback procedures
   - Post-deployment monitoring
   - **Baca ini terlebih dahulu!**

### 2. **SECURITY_CHECKLIST.md** 🔒
   - Security best practices
   - Implementation guide untuk security features
   - Incident response plan
   - Regular security tasks

### 3. **PERFORMANCE_OPTIMIZATION.md** ⚡
   - Database optimization
   - Caching strategies
   - Image optimization
   - Query optimization
   - Load testing guide

### 4. **monitoring-setup.md** 📊
   - Health check endpoints
   - Monitoring tools setup (UptimeRobot, New Relic, Sentry)
   - Alerting configuration
   - Custom monitoring scripts
   - Grafana dashboard setup

### 5. **.env.production.example** 🔧
   - Template environment variables untuk production
   - Semua konfigurasi yang diperlukan
   - Security settings

### 6. **deploy.sh** 🚢
   - Automated deployment script
   - Zero-downtime deployment
   - Automatic backup sebelum deploy
   - Health checks

### 7. **rollback.sh** ⏪
   - Emergency rollback script
   - Restore database dan code
   - Quick recovery

---

## 🎯 Quick Start - Deployment ke Production

### Prerequisites
- Server dengan Docker & Docker Compose
- Domain dengan SSL certificate
- Database MySQL 8.0+
- Redis 7.2+
- Minimum 2GB RAM, 2 CPU cores

### Step 1: Clone & Setup
```bash
# Clone repository
git clone <repository-url>
cd admin-payment

# Copy environment file
cp .env.production.example .env

# Edit .env dengan nilai production
nano .env
```

### Step 2: Generate Secrets
```bash
# Generate APP_KEY
php artisan key:generate

# Generate secrets untuk Reverb, N8N, dll
openssl rand -base64 32  # Untuk REVERB_APP_KEY
openssl rand -base64 32  # Untuk REVERB_APP_SECRET
openssl rand -base64 32  # Untuk N8N_SECRET
```

### Step 3: Build & Deploy
```bash
# Build Docker images
docker-compose -f docker-compose.yml build

# Start services
docker-compose up -d

# Install dependencies
docker-compose exec app composer install --no-dev --optimize-autoloader

# Run migrations
docker-compose exec app php artisan migrate --force

# Optimize
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Build frontend assets
docker-compose exec node npm ci
docker-compose exec node npm run build
```

### Step 4: Verify
```bash
# Check health
curl https://yourdomain.com/health

# Check services
docker-compose ps

# Check logs
docker-compose logs -f app
```

---

## 🔥 Critical Configuration

### 1. Environment Variables (WAJIB DIGANTI)
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_NEW_KEY

# Database
DB_PASSWORD=STRONG_PASSWORD_HERE

# Redis
REDIS_PASSWORD=STRONG_PASSWORD_HERE

# Reverb
REVERB_APP_KEY=STRONG_KEY_HERE
REVERB_APP_SECRET=STRONG_SECRET_HERE

# N8N
N8N_SECRET=STRONG_SECRET_HERE
```

### 2. File Permissions
```bash
chmod 600 .env
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 3. SSL/HTTPS
- Pastikan SSL certificate valid
- Force HTTPS di production
- Update `APP_URL` dengan https://

### 4. Database Backup
```bash
# Setup automated backup (crontab)
0 2 * * * mysqldump -u root -p'PASSWORD' admin-payment | gzip > /backup/db_$(date +\%Y\%m\%d).sql.gz
```

---

## 📊 Monitoring Endpoints

Project ini sudah dilengkapi dengan health check endpoints:

- **GET /ping** - Basic health check (< 10ms)
- **GET /health** - Detailed health check (DB, Redis, Queue, Storage)
- **GET /ready** - Readiness check (untuk load balancer)
- **GET /alive** - Liveness check (untuk Kubernetes)
- **GET /metrics** - Metrics untuk monitoring tools

### Example Response
```bash
curl https://yourdomain.com/health
```

```json
{
  "status": "healthy",
  "timestamp": "2026-05-04T10:30:00+07:00",
  "services": {
    "database": {"status": "connected"},
    "redis": {"status": "connected"},
    "queue": {"status": "running"},
    "storage": {"status": "ok"}
  }
}
```

---

## 🚨 Emergency Procedures

### Application Down
```bash
# Quick check
docker-compose ps
docker-compose logs app

# Restart services
docker-compose restart app horizon reverb

# If still down, rollback
./rollback.sh
```

### Database Issues
```bash
# Check connection
docker-compose exec app php artisan db:show

# Check slow queries
docker-compose exec db mysql -u root -p -e "SHOW PROCESSLIST;"

# Restart database (CAREFUL!)
docker-compose restart db
```

### Queue Issues
```bash
# Check Horizon status
docker-compose exec app php artisan horizon:status

# Restart Horizon
docker-compose restart horizon

# Clear failed jobs
docker-compose exec app php artisan queue:flush
```

### Redis Issues
```bash
# Check Redis
docker-compose exec redis redis-cli -a password1234 ping

# Check memory
docker-compose exec redis redis-cli -a password1234 INFO memory

# Restart Redis (CAREFUL - will lose cache)
docker-compose restart redis
```

---

## 📈 Performance Targets

| Metric | Target | Action if Exceeded |
|--------|--------|-------------------|
| Response Time | < 200ms | Check slow queries, add caching |
| Error Rate | < 0.1% | Check logs, fix bugs |
| Queue Wait | < 30s | Scale workers |
| Memory Usage | < 80% | Optimize code, scale up |
| CPU Usage | < 70% | Optimize queries, scale up |
| Disk Usage | < 80% | Clean logs, optimize storage |

---

## 🔄 Regular Maintenance

### Daily
- Monitor error logs
- Check failed jobs
- Review security alerts

### Weekly
- Review performance metrics
- Check disk space
- Update dependencies (if needed)

### Monthly
- Security audit
- Database optimization
- Backup testing
- Performance review

### Quarterly
- Penetration testing
- Disaster recovery drill
- Security training
- Infrastructure review

---

## 📞 Support & Contacts

### Emergency Contacts
- DevOps Lead: [Name] - [Phone]
- Database Admin: [Name] - [Phone]
- Security Team: [Name] - [Phone]

### Monitoring Dashboards
- Application: https://yourdomain.com/pulse
- Horizon: https://yourdomain.com/horizon
- Uptime: [UptimeRobot Dashboard]
- Errors: [Sentry Dashboard]

### Documentation
- API Docs: https://yourdomain.com/docs/api
- Internal Wiki: [Link]
- Runbook: [Link]

---

## 🎓 Training Resources

### For Developers
1. Read all documentation files
2. Setup local development environment
3. Review code architecture
4. Practice deployment in staging

### For DevOps
1. Understand infrastructure setup
2. Practice deployment procedures
3. Test rollback procedures
4. Setup monitoring & alerting

### For Support Team
1. Learn application features
2. Understand common issues
3. Know escalation procedures
4. Access to monitoring tools

---

## ✅ Pre-Production Checklist

### Infrastructure
- [ ] Server provisioned with adequate resources
- [ ] Domain configured with SSL
- [ ] Database setup with backups
- [ ] Redis configured
- [ ] Firewall rules configured
- [ ] Load balancer configured (if applicable)

### Application
- [ ] All environment variables set
- [ ] Secrets generated and secured
- [ ] Database migrations tested
- [ ] Assets built and optimized
- [ ] Caching configured
- [ ] Queue workers configured

### Security
- [ ] SSL certificate valid
- [ ] HTTPS enforced
- [ ] Security headers configured
- [ ] Rate limiting enabled
- [ ] File permissions correct
- [ ] Secrets not in git

### Monitoring
- [ ] Health checks working
- [ ] Uptime monitoring configured
- [ ] Error tracking configured
- [ ] Log aggregation setup
- [ ] Alerts configured
- [ ] Dashboards created

### Testing
- [ ] Smoke tests passed
- [ ] Load testing completed
- [ ] Security scan completed
- [ ] Backup/restore tested
- [ ] Rollback procedure tested

### Documentation
- [ ] Deployment guide reviewed
- [ ] Runbook created
- [ ] Emergency procedures documented
- [ ] Team trained
- [ ] Contacts updated

---

## 🚀 Deployment Commands

### Initial Deployment
```bash
# Make scripts executable (Linux/Mac)
chmod +x deploy.sh rollback.sh

# Run deployment
./deploy.sh
```

### Subsequent Deployments
```bash
# Pull latest code
git pull origin main

# Run deployment script
./deploy.sh

# Monitor logs
tail -f storage/logs/laravel.log
```

### Rollback
```bash
# Emergency rollback
./rollback.sh

# Verify application
curl https://yourdomain.com/health
```

---

## 📝 Post-Deployment

### Immediate (First Hour)
- [ ] Verify all services running
- [ ] Check health endpoints
- [ ] Monitor error logs
- [ ] Test critical features
- [ ] Verify queue processing
- [ ] Check WebSocket connections

### First 24 Hours
- [ ] Monitor performance metrics
- [ ] Review error rates
- [ ] Check resource usage
- [ ] Verify backups running
- [ ] Monitor user feedback

### First Week
- [ ] Performance optimization if needed
- [ ] Address any issues found
- [ ] Update documentation
- [ ] Team retrospective
- [ ] Plan improvements

---

## 🎉 Success Criteria

Deployment dianggap sukses jika:
- ✅ All health checks passing
- ✅ Error rate < 0.1%
- ✅ Response time < 200ms (95th percentile)
- ✅ All critical features working
- ✅ Queue processing normally
- ✅ No data loss
- ✅ Monitoring & alerts working
- ✅ Team can access all systems

---

## 📚 Additional Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [Redis Best Practices](https://redis.io/docs/manual/patterns/)
- [OWASP Security Guidelines](https://owasp.org/www-project-top-ten/)

---

**Version**: 1.0  
**Last Updated**: May 4, 2026  
**Maintainer**: DevOps Team

**Good luck with your production deployment! 🚀**
