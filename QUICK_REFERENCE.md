# ⚡ Quick Reference: Reverb Migration

Panduan cepat untuk referensi sehari-hari.

---

## 🚀 QUICK START

### Start Reverb Server
```bash
# Development
php artisan reverb:start

# Production (Supervisor)
sudo supervisorctl start reverb

# Production (PM2)
pm2 start reverb
```

### Check Status
```bash
# Check if running
ps aux | grep reverb

# Check port
netstat -tulpn | grep 8080

# Check logs
tail -f storage/logs/reverb.log
```

---

## 🔍 DEBUGGING

### Browser Console Commands

```javascript
// Check Echo loaded
console.log(typeof window.Echo); // Should be 'object'

// Check connected channels
window.Echo.connector.channels;

// Check connection status
window.Echo.connector.socket.readyState; // 1 = connected

// Manual test notification badge update
updateNotificationBadge();

// Manual test pending list refresh
refreshPendingList();

// Manual test branch cost refresh
silentRefreshBranchCost();
```

### Expected Console Logs

**On Page Load:**
```
📡 [DASHBOARD] Echo listener initialized for pending list
📡 [DASHBOARD] Echo listener initialized for branch cost breakdown
```

**On Transaction Update:**
```
🔔 [DASHBOARD] Transaction Updated: {transaction_id: 123, ...}
```

**On Notification:**
```
🔔 [NOTIF] Notification Received: {title: "...", message: "..."}
```

---

## 🛠️ COMMON COMMANDS

### Reverb Management

```bash
# Start
php artisan reverb:start

# Stop (Ctrl+C or)
pkill -f "artisan reverb:start"

# Restart (Supervisor)
sudo supervisorctl restart reverb

# Restart (PM2)
pm2 restart reverb

# View logs
tail -f storage/logs/reverb.log

# Clear logs
> storage/logs/reverb.log
```

### Cache Management

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📊 MONITORING COMMANDS

### Check Server Load

```bash
# CPU & Memory
htop

# Reverb process
ps aux | grep reverb

# Network connections
netstat -an | grep 8080 | wc -l  # Count connections

# Disk usage
df -h
```

### Check Logs

```bash
# Reverb logs
tail -f storage/logs/reverb.log

# Laravel logs
tail -f storage/logs/laravel.log

# Nginx/Apache logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

---

## 🐛 TROUBLESHOOTING QUICK FIXES

### Issue: Dashboard Not Updating

**Quick Fix:**
```bash
# 1. Clear caches
php artisan config:clear
php artisan cache:clear

# 2. Restart Reverb
sudo supervisorctl restart reverb

# 3. Hard refresh browser (Ctrl+Shift+R)
```

---

### Issue: WebSocket Connection Failed

**Quick Fix:**
```bash
# 1. Check Reverb running
ps aux | grep reverb

# 2. Check port available
netstat -tulpn | grep 8080

# 3. Restart Reverb
sudo supervisorctl restart reverb

# 4. Check firewall
sudo ufw status
sudo ufw allow 8080
```

---

### Issue: High CPU Usage

**Quick Fix:**
```bash
# 1. Check connections
netstat -an | grep 8080 | wc -l

# 2. Check logs for errors
tail -100 storage/logs/reverb.log

# 3. Restart Reverb
sudo supervisorctl restart reverb

# 4. Monitor
htop
```

---

### Issue: Memory Leak

**Quick Fix:**
```bash
# 1. Check memory usage
free -h

# 2. Restart Reverb
sudo supervisorctl restart reverb

# 3. Monitor for 10 minutes
watch -n 10 'ps aux | grep reverb'
```

---

## 📁 FILE LOCATIONS

### Code Files Changed
```
resources/views/dashboard/index.blade.php
resources/views/layouts/app.blade.php
```

### Configuration Files
```
.env
config/broadcasting.php
routes/channels.php
```

### Log Files
```
storage/logs/reverb.log
storage/logs/laravel.log
```

### Supervisor Config
```
/etc/supervisor/conf.d/reverb.conf
```

---

## 🔧 CONFIGURATION SNIPPETS

### .env (Reverb)
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Supervisor Config
```ini
[program:reverb]
process_name=%(program_name)s
command=php /path/to/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/reverb.log
stopwaitsecs=3600
```

### PM2 Config
```bash
pm2 start "php artisan reverb:start" --name reverb
pm2 save
pm2 startup
```

---

## 📞 ESCALATION

### Level 1: Self-Service
- Check this guide
- Check logs
- Restart Reverb
- Clear caches

### Level 2: Team Lead
- Persistent errors after restart
- High CPU/Memory usage
- Multiple users affected

### Level 3: DevOps/Senior Dev
- Server down
- Database issues
- Security concerns

---

## 📚 DOCUMENTATION LINKS

- **Full Migration Report:** `REALTIME_MIGRATION_REPORT.md`
- **Testing Guide:** `TESTING_REALTIME_GUIDE.md`
- **Deployment Checklist:** `DEPLOYMENT_CHECKLIST.md`
- **Before/After Comparison:** `BEFORE_AFTER_COMPARISON.md`
- **Summary:** `SUMMARY_PERUBAHAN.md`

---

## 🎯 KEY METRICS TO WATCH

### Normal Values (Production)
```
CPU Usage:        < 30%
Memory Usage:     < 500MB
Connections:      10-100
Response Time:    < 1 second
Error Rate:       < 0.1%
```

### Alert Thresholds
```
CPU Usage:        > 70%  ⚠️
Memory Usage:     > 1GB   ⚠️
Connections:      > 500   ⚠️
Response Time:    > 5s    ⚠️
Error Rate:       > 1%    ⚠️
```

---

## ✅ HEALTH CHECK SCRIPT

Save as `check-reverb.sh`:

```bash
#!/bin/bash

echo "🔍 Reverb Health Check"
echo "====================="

# Check process
if ps aux | grep -q "[r]everb:start"; then
    echo "✅ Reverb process: RUNNING"
else
    echo "❌ Reverb process: NOT RUNNING"
fi

# Check port
if netstat -tulpn | grep -q ":8080"; then
    echo "✅ Port 8080: LISTENING"
else
    echo "❌ Port 8080: NOT LISTENING"
fi

# Check connections
CONN_COUNT=$(netstat -an | grep 8080 | wc -l)
echo "📊 Active connections: $CONN_COUNT"

# Check CPU
CPU=$(ps aux | grep "[r]everb:start" | awk '{print $3}')
echo "💻 CPU usage: ${CPU}%"

# Check memory
MEM=$(ps aux | grep "[r]everb:start" | awk '{print $4}')
echo "🧠 Memory usage: ${MEM}%"

# Check logs for errors
ERROR_COUNT=$(tail -100 storage/logs/reverb.log | grep -i error | wc -l)
echo "⚠️  Recent errors: $ERROR_COUNT"

echo "====================="
```

Usage:
```bash
chmod +x check-reverb.sh
./check-reverb.sh
```

---

## 🚨 EMERGENCY ROLLBACK

**If everything breaks:**

```bash
# 1. Stop Reverb
sudo supervisorctl stop reverb

# 2. Revert code
git revert HEAD
git push origin main

# 3. Deploy
git pull origin main
php artisan config:clear
php artisan cache:clear

# 4. Update .env
# Change: BROADCAST_CONNECTION=log

# 5. Clear cache again
php artisan config:cache

# Done! Polling restored.
```

---

**Last Updated:** 30 April 2026  
**Version:** 1.0  
**Maintained By:** Development Team
