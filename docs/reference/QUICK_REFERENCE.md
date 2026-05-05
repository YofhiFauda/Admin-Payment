# ⚡ Quick Reference - Production Commands

## 🚀 Deployment

### Initial Deployment
```bash
# Setup environment
cp .env.production.example .env
php artisan key:generate

# Deploy
./deploy.sh

# Verify
curl https://yourdomain.com/health
```

### Update Deployment
```bash
git pull origin main
./deploy.sh
```

### Rollback
```bash
./rollback.sh
```

---

## � Maintenance Commands

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Rebuild Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Optimize
```bash
composer install --optimize-autoloader --no-dev
php artisan optimize
```

---

## 🗄️ Database

### Backup
```bash
# Manual backup
mysqldump -u root -p admin-payment | gzip > backup_$(date +%Y%m%d).sql.gz

# Restore
gunzip < backup.sql.gz | mysql -u root -p admin-payment
```

### Migrations
```bash
# Run migrations
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback --step=1 --force

# Check migration status
php artisan migrate:status

# Fresh migration (⚠️ DANGER: Drops all tables!)
php artisan migrate:fresh --force
```

### Price Index Commands
```bash
# Recalculate price index (incremental - daily)
php artisan price-index:recalculate --mode=incremental

# Recalculate price index (full - weekly)
php artisan price-index:recalculate --mode=full

# Populate master items from existing data
php artisan items:populate
```

### Database Info
```bash
# Show database info
php artisan db:show

# Show table info
php artisan db:table transactions
```

---

## � Queue Management

### Horizon
```bash
# Start Horizon
php artisan horizon

# Check status
php artisan horizon:status

# Terminate Horizon
php artisan horizon:terminate

# Pause Horizon
php artisan horizon:pause

# Continue Horizon
php artisan horizon:continue
```

### Queue Commands
```bash
# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Flush failed jobs
php artisan queue:flush

# Clear queue
php artisan queue:clear redis --queue=default
```

---

## � Docker Commands

### Container Management
```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart app
docker-compose restart horizon
docker-compose restart reverb

# View logs
docker-compose logs -f app
docker-compose logs -f horizon

# Execute command in container
docker-compose exec app php artisan list
```

### Container Status
```bash
# List containers
docker-compose ps

# Check resource usage
docker stats

# Inspect container
docker inspect whusnet-app
```

---

## 📊 Monitoring

### Health Checks
```bash
# Basic check
curl https://yourdomain.com/ping

# Detailed check
curl https://yourdomain.com/health | jq

# Metrics
curl https://yourdomain.com/metrics | jq
```

### Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# OCR logs
tail -f storage/logs/ocr.log

# AI autofill logs
tail -f storage/logs/ai-autofill.log

# Follow all logs
tail -f storage/logs/*.log
```

### System Resources
```bash
# Disk usage
df -h

# Memory usage
free -h

# CPU usage
top

# Process list
ps aux | grep php
```

---

## � Redis Commands

### Connection
```bash
# Connect to Redis
redis-cli -a password1234

# Ping
redis-cli -a password1234 ping
```

### Info
```bash
# Memory info
redis-cli -a password1234 INFO memory

# Stats
redis-cli -a password1234 INFO stats

# All info
redis-cli -a password1234 INFO
```

### Cache Management
```bash
# Flush all cache
redis-cli -a password1234 FLUSHALL

# Flush specific database
redis-cli -a password1234 -n 0 FLUSHDB

# Get all keys
redis-cli -a password1234 KEYS "*"

# Get key value
redis-cli -a password1234 GET key_name
```

---

## 🗃️ MySQL Commands

### Connection
```bash
# Connect to MySQL
mysql -u root -p admin-payment

# From Docker
docker-compose exec db mysql -u root -p admin-payment
```

### Queries
```sql
-- Show tables
SHOW TABLES;

-- Table structure
DESCRIBE transactions;

-- Table size
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'admin-payment'
ORDER BY (data_length + index_length) DESC;

-- Active connections
SHOW PROCESSLIST;

-- Slow queries
SHOW VARIABLES LIKE 'slow_query_log';
```

---

## 🔒 Security

### File Permissions
```bash
# Set correct permissions
chmod 600 .env
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Generate Secrets
```bash
# APP_KEY
php artisan key:generate

# Random secret
openssl rand -base64 32

# Random password
openssl rand -base64 24
```

### Check Vulnerabilities
```bash
# Composer audit
composer audit

# NPM audit
npm audit
npm audit fix
```

---

## 🧪 Testing

### Run Tests
```bash
# All tests
php artisan test

# Specific test
php artisan test --filter=TransactionTest

# With coverage
php artisan test --coverage
```

### Load Testing
```bash
# Apache Bench
ab -n 1000 -c 10 https://yourdomain.com/

# Artillery
artillery quick --count 100 --num 10 https://yourdomain.com
```

---

## � Debugging

### Enable Query Log
```php
// In tinker or controller
DB::enableQueryLog();
// ... your queries
dd(DB::getQueryLog());
```

### Tinker
```bash
# Start tinker
php artisan tinker

# Example commands
>>> User::count()
>>> Transaction::latest()->first()
>>> Cache::get('key')
>>> Redis::ping()

# Price Index examples
>>> $pi = \App\Models\PriceIndex::first()
>>> $pi->getEffectiveAvgPrice()  // Get effective AVG (manual or auto)
>>> $pi->isAvgManual()            // Check if using manual
>>> $pi->avg_price                // Auto price
>>> $pi->avg_price_manual         // Manual price (can be NULL)
```

### Debug Mode (NEVER in production!)
```bash
# Enable debug (local only!)
APP_DEBUG=true

# Disable debug (production)
APP_DEBUG=false
```

---

## � Dependencies

### Update Dependencies
```bash
# Composer
composer update
composer install

# NPM
npm update
npm install
npm ci  # Clean install
```

### Build Assets
```bash
# Development
npm run dev

# Production
npm run build

# Watch
npm run watch
```

---

## 🔄 Scheduled Tasks

### Run Scheduler
```bash
# Run scheduler once
php artisan schedule:run

# List scheduled tasks
php artisan schedule:list

# Test specific task
php artisan schedule:test
```

### Cron Setup
```bash
# Add to crontab
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

---

## � Emergency Commands

### Application Down
```bash
# Put in maintenance mode
php artisan down

# With secret bypass
php artisan down --secret="bypass-key"

# Bring back up
php artisan up
```

### Quick Restart
```bash
# Restart all services
docker-compose restart

# Restart PHP-FPM
docker-compose restart app

# Restart queue workers
docker-compose restart horizon

# Restart WebSocket
docker-compose restart reverb
```

### Emergency Rollback
```bash
# Quick rollback
./rollback.sh

# Manual rollback
git reset --hard HEAD~1
composer install --no-dev
php artisan migrate:rollback --force
php artisan cache:clear
php artisan config:cache
docker-compose restart app horizon reverb
```

---

## 📊 Performance

### Check Performance
```bash
# Response time
curl -o /dev/null -s -w '%{time_total}\n' https://yourdomain.com

# With details
curl -o /dev/null -s -w 'Total: %{time_total}s\nDNS: %{time_namelookup}s\nConnect: %{time_connect}s\nTransfer: %{time_starttransfer}s\n' https://yourdomain.com
```

### Optimize
```bash
# OPcache reset
php artisan opcache:clear

# Optimize autoloader
composer dump-autoload --optimize

# Clear compiled
php artisan clear-compiled
php artisan optimize
```

---

## 🔐 User Management

### Create User
```bash
php artisan tinker
>>> User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')])
```

### Reset Password
```bash
php artisan tinker
>>> $user = User::where('email', 'user@example.com')->first()
>>> $user->password = bcrypt('newpassword')
>>> $user->save()
```

---

## 📝 Useful Aliases

Add to `~/.bashrc` or `~/.zshrc`:

```bash
# Laravel aliases
alias art='php artisan'
alias tinker='php artisan tinker'
alias migrate='php artisan migrate'
alias fresh='php artisan migrate:fresh --seed'

# Docker aliases
alias dc='docker-compose'
alias dcup='docker-compose up -d'
alias dcdown='docker-compose down'
alias dcrestart='docker-compose restart'
alias dclogs='docker-compose logs -f'

# Git aliases
alias gs='git status'
alias gp='git pull'
alias gc='git commit -m'
alias gpo='git push origin'

# Monitoring
alias logs='tail -f storage/logs/laravel.log'
alias health='curl -s https://yourdomain.com/health | jq'
```

---

## � Quick Contacts

### Emergency
- DevOps: [Phone]
- Database Admin: [Phone]
- Security: [Phone]

### Dashboards
- App: https://yourdomain.com
- Horizon: https://yourdomain.com/horizon
- Pulse: https://yourdomain.com/pulse

### Monitoring
- Uptime: [UptimeRobot URL]
- Errors: [Sentry URL]
- APM: [New Relic URL]

---

**Last Updated**: May 4, 2026  
**Keep this handy for quick reference!** 📌
