# ⚡ Quick Start - Pulse & Log Viewer

## 🚀 Installation (5 Minutes)

### On Production Server (Linux)

```bash
# Method 1: Using script
chmod +x INSTALL_PULSE_AND_LOG_VIEWER.sh
./INSTALL_PULSE_AND_LOG_VIEWER.sh

# Method 2: Manual
composer require laravel/pulse
php artisan migrate
composer require opcodesio/log-viewer
php artisan config:cache
```

---

## 🔧 Configuration

### Add to `.env`:

```env
# Pulse
PULSE_ENABLED=true
PULSE_SLOW_QUERIES_THRESHOLD=1000
PULSE_SLOW_REQUESTS_THRESHOLD=1000

# Log Viewer
LOG_VIEWER_PATH=log-viewer
LOG_VIEWER_TIMEZONE=Asia/Jakarta
```

---

## 🖥️ Access

```
📊 Log Viewer: https://yourdomain.com/log-viewer
📈 Pulse:      https://yourdomain.com/pulse
```

**Authentication:** Owner & Admin only (already configured)

---

## 📊 What Each Does

### Laravel Pulse
- ✅ Real-time metrics
- ✅ Slow queries
- ✅ Slow requests
- ✅ Exceptions
- ✅ Queue monitoring
- ✅ Server resources

### Laravel Log Viewer
- ✅ View all logs
- ✅ Filter by level
- ✅ Search logs
- ✅ Download logs
- ✅ Real-time updates
- ✅ Beautiful UI

---

## 🎯 Quick Usage

### Debugging Slow Performance
1. Open Pulse → Check slow queries
2. Open Log Viewer → Filter performance.log
3. Identify issue → Fix → Monitor

### Investigating Errors
1. Open Pulse → Check exceptions
2. Open Log Viewer → Filter ERROR level
3. View stack trace → Fix → Deploy

### Monitoring Queue
1. Open Pulse → Check queues card
2. See pending/processed/failed jobs
3. Open Log Viewer → Filter queue.log

---

## 📋 Files Created

✅ `config/pulse.php`  
✅ `config/log-viewer.php`  
✅ `app/Providers/PulseServiceProvider.php`  
✅ `database/migrations/2026_05_04_000001_create_pulse_tables.php`  
✅ `INSTALL_PULSE_AND_LOG_VIEWER.sh`  
✅ `PULSE_LOG_VIEWER_SETUP.md` (Complete guide)  

---

## 🎉 Done!

**Full Documentation:** `PULSE_LOG_VIEWER_SETUP.md`

---

**Last Updated**: May 4, 2026
