# ✅ Deployment Checklist: Reverb Migration

Checklist lengkap untuk deploy perubahan dari polling ke Reverb.

---

## 📋 PRE-DEPLOYMENT

### 1. Environment Check

- [ ] **Reverb Server Installed**
  ```bash
  php artisan reverb:install
  ```

- [ ] **Environment Variables Set**
  ```bash
  # Check .env file
  BROADCAST_CONNECTION=reverb
  REVERB_APP_ID=your-app-id
  REVERB_APP_KEY=your-app-key
  REVERB_APP_SECRET=your-app-secret
  REVERB_HOST=127.0.0.1
  REVERB_PORT=8080
  REVERB_SCHEME=http
  ```

- [ ] **Config Cache Cleared**
  ```bash
  php artisan config:clear
  php artisan cache:clear
  ```

- [ ] **Dependencies Installed**
  ```bash
  composer install
  npm install
  npm run build
  ```

---

### 2. Code Review

- [ ] **File Changes Verified**
  - `resources/views/dashboard/index.blade.php` (2 changes)
  - `resources/views/layouts/app.blade.php` (1 change)

- [ ] **No Syntax Errors**
  ```bash
  php artisan view:clear
  php artisan view:cache
  ```

- [ ] **Git Status Clean**
  ```bash
  git status
  git diff
  ```

---

### 3. Local Testing

- [ ] **Reverb Server Starts Successfully**
  ```bash
  php artisan reverb:start
  # Expected: Server running on ws://127.0.0.1:8080
  ```

- [ ] **Browser Console Check**
  - Open browser console (F12)
  - Expected logs:
    ```
    📡 [DASHBOARD] Echo listener initialized for pending list
    📡 [DASHBOARD] Echo listener initialized for branch cost breakdown
    ```

- [ ] **Dashboard Pending List Test**
  - Login as Admin
  - Open dashboard
  - From another tab, submit transaction as Teknisi
  - Expected: Dashboard updates instantly (<2 seconds)

- [ ] **Dashboard Branch Cost Test**
  - Login as Admin
  - Open dashboard
  - Submit transaction with branch allocation
  - Expected: Branch cost updates instantly (<2 seconds)

- [ ] **Notification Badge Test**
  - Login as Teknisi
  - From another user, approve/reject transaction
  - Expected: Badge updates instantly + toast appears

- [ ] **Multiple Users Test**
  - Open 3-5 browser tabs with different users
  - Submit/approve transactions
  - Expected: All dashboards update instantly

- [ ] **Connection Loss Test**
  - Stop Reverb server
  - Expected: No crash, graceful degradation
  - Start Reverb server
  - Expected: Reconnects automatically

---

## 🚀 DEPLOYMENT

### 1. Backup

- [ ] **Database Backup**
  ```bash
  php artisan backup:run
  # Or manual mysqldump
  mysqldump -u user -p database > backup_$(date +%Y%m%d_%H%M%S).sql
  ```

- [ ] **Code Backup**
  ```bash
  git tag -a v1.0-before-reverb -m "Before Reverb migration"
  git push origin v1.0-before-reverb
  ```

- [ ] **Current Reverb Config Backup** (if exists)
  ```bash
  cp .env .env.backup
  cp config/broadcasting.php config/broadcasting.php.backup
  ```

---

### 2. Deploy Code

- [ ] **Pull Latest Code**
  ```bash
  git pull origin main
  # Or your deployment branch
  ```

- [ ] **Install Dependencies**
  ```bash
  composer install --no-dev --optimize-autoloader
  npm install
  npm run build
  ```

- [ ] **Clear Caches**
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  php artisan route:clear
  ```

- [ ] **Optimize**
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

---

### 3. Start Reverb Server

#### Option A: Manual (Development/Testing)
```bash
php artisan reverb:start
```

#### Option B: Supervisor (Production - Recommended)

- [ ] **Create Supervisor Config**
  ```bash
  sudo nano /etc/supervisor/conf.d/reverb.conf
  ```

  ```ini
  [program:reverb]
  process_name=%(program_name)s
  command=php /path/to/your/project/artisan reverb:start
  autostart=true
  autorestart=true
  user=www-data
  redirect_stderr=true
  stdout_logfile=/path/to/your/project/storage/logs/reverb.log
  stopwaitsecs=3600
  ```

- [ ] **Update Supervisor**
  ```bash
  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl start reverb
  ```

- [ ] **Check Status**
  ```bash
  sudo supervisorctl status reverb
  # Expected: reverb RUNNING
  ```

#### Option C: PM2 (Alternative)

- [ ] **Create PM2 Config**
  ```bash
  pm2 start "php artisan reverb:start" --name reverb
  pm2 save
  pm2 startup
  ```

- [ ] **Check Status**
  ```bash
  pm2 status
  # Expected: reverb online
  ```

---

### 4. Verify Deployment

- [ ] **Reverb Server Running**
  ```bash
  # Check process
  ps aux | grep reverb
  
  # Check port
  netstat -tulpn | grep 8080
  ```

- [ ] **Application Accessible**
  - Open application URL
  - Login successful
  - No errors in browser console

- [ ] **WebSocket Connection**
  - Open browser console
  - Expected: `Echo connected` or similar
  - Check Network tab → WS (WebSocket) connection established

- [ ] **Dashboard Loads**
  - Navigate to dashboard
  - No errors
  - Data displays correctly

---

## 🧪 POST-DEPLOYMENT TESTING

### 1. Smoke Tests (5 minutes)

- [ ] **Dashboard Pending List**
  - Login as Admin → Open dashboard
  - From another tab, submit transaction
  - Expected: Dashboard updates instantly

- [ ] **Dashboard Branch Cost**
  - Login as Admin → Open dashboard
  - Submit transaction with branch allocation
  - Expected: Branch cost updates instantly

- [ ] **Notification Badge**
  - Login as Teknisi
  - From another user, approve transaction
  - Expected: Badge updates + toast appears

---

### 2. Load Testing (15 minutes)

- [ ] **Multiple Users**
  - 5-10 users login simultaneously
  - Submit/approve multiple transactions
  - Expected: All dashboards update smoothly

- [ ] **Sustained Load**
  - Keep 5 users logged in for 15 minutes
  - Perform various actions
  - Expected: No lag, no disconnections

- [ ] **Server Resources**
  ```bash
  # Monitor CPU/Memory
  htop
  
  # Monitor Reverb logs
  tail -f storage/logs/reverb.log
  ```
  - Expected: CPU <30%, Memory stable

---

### 3. Edge Cases (10 minutes)

- [ ] **Reverb Restart**
  ```bash
  # Stop Reverb
  sudo supervisorctl stop reverb
  # Or: pm2 stop reverb
  
  # Wait 10 seconds
  
  # Start Reverb
  sudo supervisorctl start reverb
  # Or: pm2 start reverb
  ```
  - Expected: Users reconnect automatically

- [ ] **High Frequency Updates**
  - Submit 10 transactions rapidly (within 1 minute)
  - Expected: All updates processed, no lag

- [ ] **Browser Refresh**
  - Refresh dashboard page
  - Expected: Reconnects, data loads correctly

---

## 📊 MONITORING

### 1. Real-time Monitoring

- [ ] **Reverb Server Logs**
  ```bash
  tail -f storage/logs/reverb.log
  ```
  - Watch for errors
  - Monitor connection count

- [ ] **Laravel Logs**
  ```bash
  tail -f storage/logs/laravel.log
  ```
  - Watch for broadcast errors
  - Monitor event dispatching

- [ ] **System Resources**
  ```bash
  htop
  # Or
  top
  ```
  - Monitor CPU usage
  - Monitor memory usage

---

### 2. Metrics to Track (First 24 Hours)

- [ ] **Request Count**
  - Check server access logs
  - Expected: 95%+ reduction in polling requests

- [ ] **Response Time**
  - Check application performance
  - Expected: Faster overall response

- [ ] **Error Rate**
  - Check error logs
  - Expected: No increase in errors

- [ ] **User Feedback**
  - Ask users about experience
  - Expected: Positive feedback on responsiveness

---

## 🐛 TROUBLESHOOTING

### Issue: Reverb Won't Start

**Check:**
```bash
# Port already in use?
netstat -tulpn | grep 8080

# Kill existing process
kill -9 $(lsof -t -i:8080)

# Try again
php artisan reverb:start
```

---

### Issue: WebSocket Connection Failed

**Check:**
```bash
# Firewall blocking port 8080?
sudo ufw status
sudo ufw allow 8080

# Nginx/Apache proxy config?
# Add WebSocket proxy configuration
```

---

### Issue: Dashboard Not Updating

**Check:**
```javascript
// Browser console
console.log(typeof window.Echo); // Should be 'object'

// Check channel subscription
window.Echo.connector.channels; // Should show 'private-transactions'
```

**Fix:**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Restart Reverb
sudo supervisorctl restart reverb
```

---

## 🔄 ROLLBACK PLAN

If critical issues occur:

### 1. Quick Rollback (Keep Reverb)

- [ ] **Restore Polling Code**
  ```bash
  git revert HEAD
  git push origin main
  ```

- [ ] **Deploy Rollback**
  ```bash
  git pull origin main
  php artisan config:clear
  php artisan cache:clear
  ```

---

### 2. Full Rollback (Remove Reverb)

- [ ] **Stop Reverb**
  ```bash
  sudo supervisorctl stop reverb
  # Or: pm2 stop reverb
  ```

- [ ] **Restore Code**
  ```bash
  git checkout v1.0-before-reverb
  ```

- [ ] **Update Environment**
  ```bash
  # .env
  BROADCAST_CONNECTION=log
  ```

- [ ] **Clear Caches**
  ```bash
  php artisan config:clear
  php artisan cache:clear
  ```

---

## ✅ SIGN-OFF

### Development Team

- [ ] Code reviewed by: ________________
- [ ] Local testing passed by: ________________
- [ ] Date: ________________

### QA Team

- [ ] Smoke tests passed by: ________________
- [ ] Load tests passed by: ________________
- [ ] Date: ________________

### DevOps Team

- [ ] Deployment successful by: ________________
- [ ] Monitoring configured by: ________________
- [ ] Date: ________________

### Product Owner

- [ ] Approved for production by: ________________
- [ ] Date: ________________

---

## 📝 POST-DEPLOYMENT NOTES

**Deployment Date:** ________________  
**Deployed By:** ________________  
**Reverb Version:** ________________  
**Laravel Version:** ________________

**Issues Encountered:**
- [ ] None
- [ ] Minor (describe): ________________
- [ ] Major (describe): ________________

**Performance Metrics (After 24 Hours):**
- Request reduction: ______%
- Average update delay: ______ seconds
- Server CPU usage: ______%
- User satisfaction: ______/10

**Next Steps:**
- [ ] Monitor for 7 days
- [ ] Collect user feedback
- [ ] Optimize if needed
- [ ] Document lessons learned

---

**Status:** ⏳ **READY FOR DEPLOYMENT**  
**Risk Level:** 🟢 **LOW**  
**Estimated Downtime:** 🟢 **ZERO** (rolling deployment)
