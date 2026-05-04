# 🔧 Troubleshooting Guide - WHUSNET Admin Payment

Common issues and their solutions for the WHUSNET Admin Payment system.

---

## 📋 Table of Contents

- [Docker Issues](#docker-issues)
- [Database Issues](#database-issues)
- [Queue & Redis Issues](#queue--redis-issues)
- [OCR & n8n Issues](#ocr--n8n-issues)
- [WebSocket Issues](#websocket-issues)
- [Performance Issues](#performance-issues)
- [Authentication Issues](#authentication-issues)
- [File Upload Issues](#file-upload-issues)
- [Payment Verification Issues](#payment-verification-issues)
- [Deployment Issues](#deployment-issues)

---

## 🐳 Docker Issues

### Issue: Containers won't start

**Symptoms:**
```bash
docker-compose up -d
# Error: Cannot start service app: driver failed
```

**Solutions:**

1. **Check Docker is running:**
```bash
docker --version
docker-compose --version
```

2. **Check port conflicts:**
```bash
# Check if ports are already in use
netstat -ano | findstr :8000
netstat -ano | findstr :3306
netstat -ano | findstr :6379
```

3. **Remove old containers:**
```bash
docker-compose down
docker-compose up -d --force-recreate
```

4. **Check disk space:**
```bash
docker system df
# Clean up if needed
docker system prune -a
```

---

### Issue: "Connection refused" to database

**Symptoms:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Solutions:**

1. **Wait for database to be ready:**
```bash
# Check database logs
docker-compose logs db

# Wait for "ready for connections" message
```

2. **Verify database credentials:**
```env
# .env
DB_HOST=whusnet-db  # Must match service name in docker-compose.yml
DB_PORT=3306
DB_DATABASE=admin-payment
DB_USERNAME=admin
DB_PASSWORD=root
```

3. **Test database connection:**
```bash
docker exec -it whusnet-db mysql -u admin -proot admin-payment
```

---

### Issue: Permission denied errors

**Symptoms:**
```
Permission denied: /var/www/html/storage/logs/laravel.log
```

**Solutions:**

```bash
# Fix permissions
docker exec -it whusnet-app bash
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
```

---

## 🗄️ Database Issues

### Issue: Migration fails

**Symptoms:**
```bash
php artisan migrate
# SQLSTATE[42S01]: Base table or view already exists
```

**Solutions:**

1. **Fresh migration (⚠️ destroys data):**
```bash
php artisan migrate:fresh
```

2. **Rollback and re-migrate:**
```bash
php artisan migrate:rollback
php artisan migrate
```

3. **Check migration status:**
```bash
php artisan migrate:status
```

---

### Issue: Database connection pool exhausted

**Symptoms:**
```
SQLSTATE[HY000] [1040] Too many connections
```

**Solutions:**

1. **Increase MySQL max_connections:**
```sql
-- In MySQL
SET GLOBAL max_connections = 200;
```

2. **Optimize database queries:**
```bash
# Check slow queries
docker exec -it whusnet-db mysql -u admin -proot -e "SHOW PROCESSLIST;"
```

3. **Use connection pooling:**
```env
# .env
DB_CONNECTION=mysql
DB_POOL_SIZE=10
```

---

## 🔴 Queue & Redis Issues

### Issue: Queue not processing

**Symptoms:**
- Jobs stuck in `pending` status
- OCR not completing
- Notifications not sending

**Solutions:**

1. **Check Horizon status:**
```bash
# Visit http://localhost:8000/horizon
# Or check logs
docker-compose logs horizon
```

2. **Restart Horizon:**
```bash
docker-compose restart horizon
```

3. **Check Redis connection:**
```bash
docker exec -it whusnet-redis redis-cli ping
# Should return: PONG
```

4. **Clear failed jobs:**
```bash
php artisan queue:flush
php artisan queue:restart
```

5. **Check queue configuration:**
```env
# .env
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

---

### Issue: Redis memory full

**Symptoms:**
```
OOM command not allowed when used memory > 'maxmemory'
```

**Solutions:**

1. **Check Redis memory:**
```bash
docker exec -it whusnet-redis redis-cli INFO memory
```

2. **Clear Redis cache:**
```bash
docker exec -it whusnet-redis redis-cli FLUSHDB
```

3. **Increase Redis memory:**
```yaml
# docker-compose.yml
redis:
  command: redis-server --maxmemory 512mb --maxmemory-policy allkeys-lru
```

---

## 🤖 OCR & n8n Issues

### Issue: OCR stuck in "Processing"

**Symptoms:**
- Upload completes but OCR never finishes
- Status remains "Antrean" or "Sedang Diproses"

**Solutions:**

1. **Check n8n webhook:**
```bash
# Test webhook manually
curl -X POST http://your-n8n-url/webhook/ocr-nota \
  -H "X-SECRET: your-secret" \
  -F "file=@test-image.jpg"
```

2. **Check n8n logs:**
```bash
# If n8n is self-hosted
docker logs n8n-container
```

3. **Verify n8n configuration:**
```env
# .env
N8N_WEBHOOK_URL=https://your-n8n-url/webhook/ocr-nota
N8N_SECRET=your-secret-key
```

4. **Check Gemini API quota:**
```bash
# Visit Google Cloud Console
# Check Gemini API usage and quotas
```

5. **Manual retry:**
```bash
# In Laravel Tinker
php artisan tinker
>>> $transaction = Transaction::find(123);
>>> dispatch(new OcrProcessingJob($transaction));
```

---

### Issue: OCR returns low confidence

**Symptoms:**
- `ai_status` = "low-confidence"
- Extracted data is incorrect

**Solutions:**

1. **Check image quality:**
   - Image should be clear and well-lit
   - Text should be readable
   - Minimum resolution: 800x600

2. **Retry with better image:**
   - Re-upload with higher quality image
   - Ensure receipt is flat (not crumpled)

3. **Manual data entry:**
   - Use "Manual Input" option
   - Fill form manually

---

## 🔌 WebSocket Issues

### Issue: Real-time updates not working

**Symptoms:**
- Dashboard doesn't update automatically
- Notifications don't appear in real-time
- Console shows WebSocket connection errors

**Solutions:**

1. **Check Reverb service:**
```bash
docker-compose logs reverb
```

2. **Verify Reverb configuration:**
```env
# .env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

3. **Check browser console:**
```javascript
// Should see:
// "Reverb connected"
// If not, check for errors
```

4. **Test WebSocket connection:**
```bash
# Visit http://localhost:8080
# Should see Reverb status page
```

5. **Restart Reverb:**
```bash
docker-compose restart reverb
```

6. **Fallback to polling (temporary):**
```javascript
// In resources/js/dashboard.js
// Comment out Echo listeners
// Uncomment polling intervals
```

---

### Issue: "WebSocket connection failed"

**Symptoms:**
```
WebSocket connection to 'ws://localhost:8080' failed
```

**Solutions:**

1. **Check firewall:**
```bash
# Allow port 8080
# Windows Firewall or antivirus might block it
```

2. **Check Reverb is running:**
```bash
docker-compose ps reverb
# Should show "Up"
```

3. **Check CORS configuration:**
```php
// config/cors.php
'allowed_origins' => ['http://localhost:8000'],
```

---

## ⚡ Performance Issues

### Issue: Slow page load

**Symptoms:**
- Pages take > 3 seconds to load
- Dashboard is sluggish

**Solutions:**

1. **Enable caching:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **Optimize database queries:**
```bash
# Check slow queries
php artisan telescope:prune
# Review queries in Telescope
```

3. **Enable Redis caching:**
```env
# .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

4. **Optimize images:**
```bash
# Images are automatically compressed on upload
# Check storage/app/public/uploads
```

5. **Use CDN for assets:**
```env
# .env
ASSET_URL=https://your-cdn-url
```

---

### Issue: High memory usage

**Symptoms:**
- Server runs out of memory
- PHP Fatal error: Allowed memory size exhausted

**Solutions:**

1. **Increase PHP memory limit:**
```ini
; php.ini
memory_limit = 512M
```

2. **Optimize queries:**
```php
// Use chunk() for large datasets
Transaction::chunk(100, function ($transactions) {
    // Process transactions
});
```

3. **Clear caches:**
```bash
php artisan cache:clear
php artisan view:clear
```

---

## 🔐 Authentication Issues

### Issue: Can't login

**Symptoms:**
- "These credentials do not match our records"
- Login form doesn't submit

**Solutions:**

1. **Verify credentials:**
```bash
# Check user in database
php artisan tinker
>>> User::where('email', 'admin@whusnet.com')->first();
```

2. **Reset password:**
```bash
php artisan tinker
>>> $user = User::where('email', 'admin@whusnet.com')->first();
>>> $user->password = Hash::make('newpassword');
>>> $user->save();
```

3. **Check role:**
```bash
# Ensure user has correct role
>>> $user->role; // Should be 'admin', 'owner', etc.
```

4. **Clear sessions:**
```bash
php artisan session:flush
```

---

### Issue: Session expires too quickly

**Symptoms:**
- Logged out after a few minutes
- "Session expired" message

**Solutions:**

```env
# .env
SESSION_LIFETIME=120  # Increase to 120 minutes
SESSION_DRIVER=redis  # Use Redis for persistence
```

---

## 📁 File Upload Issues

### Issue: File upload fails

**Symptoms:**
- "The file failed to upload"
- 413 Request Entity Too Large

**Solutions:**

1. **Increase upload limits:**
```ini
; php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

2. **Nginx configuration:**
```nginx
# docker/nginx/default.conf
client_max_body_size 10M;
```

3. **Check storage permissions:**
```bash
chmod -R 775 storage/app/public
```

4. **Check disk space:**
```bash
df -h
```

---

### Issue: Uploaded images not displaying

**Symptoms:**
- 404 error for images
- Broken image icons

**Solutions:**

1. **Create storage link:**
```bash
php artisan storage:link
```

2. **Check file exists:**
```bash
ls -la storage/app/public/uploads
```

3. **Verify URL:**
```php
// Should be:
asset('storage/uploads/filename.jpg')
// Not:
asset('uploads/filename.jpg')
```

---

## 💳 Payment Verification Issues

### Issue: Payment verification stuck

**Symptoms:**
- Status remains "Sedang Diverifikasi AI"
- Payment proof uploaded but not processed

**Solutions:**

1. **Check n8n Layer 4 workflow:**
```bash
# Verify webhook is receiving requests
# Check n8n execution logs
```

2. **Manual verification:**
```bash
php artisan tinker
>>> $transaction = Transaction::find(123);
>>> $transaction->update(['status' => 'completed']);
```

3. **Force approve:**
```bash
# As Owner, use "Force Approve" button
# Provide reason for manual approval
```

---

### Issue: Selisih nominal detected incorrectly

**Symptoms:**
- AI flags correct payment as mismatch
- False positive on payment verification

**Solutions:**

1. **Review OCR extraction:**
```bash
php artisan tinker
>>> $transaction = Transaction::find(123);
>>> $transaction->ocr_result;
```

2. **Use Force Approve:**
   - Login as Owner
   - Navigate to flagged transaction
   - Click "Force Approve"
   - Provide reason

3. **Improve image quality:**
   - Re-upload clearer payment proof
   - Ensure all text is readable

---

## 🚀 Deployment Issues

### Issue: Production deployment fails

**Symptoms:**
- CI/CD pipeline fails
- Application doesn't start after deployment

**Solutions:**

1. **Check environment variables:**
```bash
# Verify all required env vars are set
php artisan config:show
```

2. **Run migrations:**
```bash
php artisan migrate --force
```

3. **Clear all caches:**
```bash
php artisan optimize:clear
php artisan optimize
```

4. **Check logs:**
```bash
tail -f storage/logs/laravel.log
```

5. **Rollback if needed:**
```bash
# Use rollback script
./rollback.sh
```

---

### Issue: Assets not loading in production

**Symptoms:**
- CSS/JS files return 404
- Styles not applied

**Solutions:**

1. **Build assets:**
```bash
npm run build
```

2. **Check asset URL:**
```env
# .env
ASSET_URL=https://your-domain.com
```

3. **Verify Nginx configuration:**
```nginx
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

---

## 🆘 Emergency Procedures

### Complete System Reset (Development Only)

⚠️ **WARNING:** This will destroy all data!

```bash
# 1. Stop all services
docker-compose down -v

# 2. Remove all containers and volumes
docker system prune -a --volumes

# 3. Fresh start
docker-compose up -d --build

# 4. Setup application
docker exec -it whusnet-app bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
```

---

### Database Backup & Restore

**Backup:**
```bash
docker exec whusnet-db mysqldump -u admin -proot admin-payment > backup.sql
```

**Restore:**
```bash
docker exec -i whusnet-db mysql -u admin -proot admin-payment < backup.sql
```

---

## 📞 Getting Help

If you can't resolve the issue:

1. **Check logs:**
   - Laravel: `storage/logs/laravel.log`
   - Horizon: http://localhost:8000/horizon
   - Docker: `docker-compose logs [service]`

2. **Search documentation:**
   - [Documentation Index](../../DOCUMENTATION_INDEX.md)
   - [FAQ](../reference/FAQ.md)

3. **Contact support:**
   - Email: support@whusnet.com
   - Slack: #admin-payment-support
   - Create GitHub issue

4. **Provide information:**
   - Error message (full stack trace)
   - Steps to reproduce
   - Environment (dev/staging/production)
   - Laravel version
   - PHP version

---

**Last Updated:** 4 Mei 2026  
**Maintainer:** WHUSNET Development Team

---

*For more help, see [FAQ.md](../reference/FAQ.md) or contact support.*
