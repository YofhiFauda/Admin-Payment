# Error 500 Troubleshooting Guide

**Date**: 2026-05-20  
**Error**: HTTP 500 Internal Server Error  
**Context**: After deploying branch distribution fixes

---

## 🔍 **Error Analysis**

### **Symptoms**:
```
GET /index.php 500
PHP-FPM: executing too slow (5.011983 sec)
```

### **NOT Related To**:
- ✅ Branch distribution fixes (HTML/JS changes only)
- ✅ Chrome error (just browser error page display)

### **Likely Causes**:
1. **Syntax error** in Blade file
2. **Cache corruption**
3. **Database/performance issue**
4. **Missing dependency**

---

## ✅ **Step-by-Step Troubleshooting**

### **Step 1: Check Laravel Logs** ⭐ (DO THIS FIRST)

```bash
# Local/SSH
tail -100 storage/logs/laravel.log

# Docker
docker exec <container-name> tail -100 /var/www/storage/logs/laravel.log

# Or check latest log file
ls -lt storage/logs/
cat storage/logs/laravel-2026-05-20.log
```

**Look for**:
- `ParseError`
- `SyntaxError`
- `Undefined variable`
- `Class not found`
- `Call to undefined method`

---

### **Step 2: Clear ALL Caches**

```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear

# Restart queue workers
php artisan queue:restart

# Restart PHP-FPM (if needed)
sudo systemctl restart php8.3-fpm
```

---

### **Step 3: Check PHP Syntax**

```bash
# Check edit-rembush.blade.php
php -l resources/views/transactions/edit-rembush.blade.php

# Check all blade files
find resources/views -name "*.blade.php" -exec php -l {} \;
```

---

### **Step 4: Enable Debug Mode** (Staging Only!)

```bash
# .env
APP_DEBUG=true
APP_ENV=local

# Clear config cache
php artisan config:clear

# Try accessing the page again
# You'll see detailed error message
```

---

### **Step 5: Check Recent Changes**

```bash
# See what files changed
git status
git diff

# If needed, rollback
git checkout resources/views/transactions/edit-rembush.blade.php
php artisan view:clear
```

---

### **Step 6: Check Database Connection**

```bash
# Test database
php artisan tinker
>>> DB::connection()->getPdo();
>>> \App\Models\User::count();
```

---

### **Step 7: Check Composer Dependencies**

```bash
# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Dump autoload
composer dump-autoload
```

---

## 🎯 **Quick Fixes to Try**

### **Fix #1: Clear Everything**
```bash
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
composer dump-autoload
```

### **Fix #2: Restart Services**
```bash
# PHP-FPM
sudo systemctl restart php8.3-fpm

# Nginx
sudo systemctl restart nginx

# Queue workers
php artisan queue:restart
```

### **Fix #3: Check Permissions**
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🔍 **Specific to Our Changes**

### **What We Changed**:
1. `resources/views/transactions/edit-rembush.blade.php` (HTML attributes)
2. `resources/js/transactions/form-rembush/index.js` (guard + logging)
3. `app/Http/Controllers/RembushController.php` (logging)

### **Potential Issues**:

#### **Issue #1: Blade Syntax Error**

**Check**:
```bash
php -l resources/views/transactions/edit-rembush.blade.php
```

**If error, rollback**:
```bash
git checkout resources/views/transactions/edit-rembush.blade.php
php artisan view:clear
```

#### **Issue #2: JavaScript Build**

**Check**:
```bash
npm run build
```

**If error**:
```bash
npm ci
npm run build
```

#### **Issue #3: Controller Logging**

**Check** `app/Http/Controllers/RembushController.php`:
```php
// Line ~290
Log::channel('ocr')->info('💾 [OCR FLOW] STORE PROCESS STARTED', [
    'branches_data' => $request->branches,  // ← Could be large!
]);
```

**If this causes memory issue, simplify**:
```php
'branches_count' => $request->branches ? count($request->branches) : 0,
// Remove: 'branches_data' => $request->branches,
```

---

## 🚨 **Emergency Rollback**

If nothing works, rollback all changes:

```bash
# Rollback files
git checkout resources/views/transactions/edit-rembush.blade.php
git checkout resources/js/transactions/form-rembush/index.js
git checkout app/Http/Controllers/RembushController.php

# Rebuild
npm run build

# Clear caches
php artisan optimize:clear

# Restart
php artisan queue:restart
```

---

## 📊 **Common Error 500 Causes**

| Cause | Symptom | Fix |
|-------|---------|-----|
| **Syntax Error** | ParseError in logs | Check PHP syntax, rollback |
| **Cache Corruption** | Stale views | Clear all caches |
| **Memory Limit** | PHP-FPM slow | Increase memory_limit |
| **Database Issue** | Connection timeout | Check DB connection |
| **Missing Class** | Class not found | composer dump-autoload |
| **Permission** | Can't write to storage | Fix permissions |

---

## 🎯 **Most Likely Solution**

Based on the error pattern, try this first:

```bash
# 1. Clear caches
php artisan optimize:clear

# 2. Check logs
tail -50 storage/logs/laravel.log

# 3. If you see specific error, fix it
# 4. If no error in logs, restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

---

## 📝 **Report Back**

After trying the steps above, report:
1. What error message appears in `storage/logs/laravel.log`?
2. Does clearing cache fix it?
3. Does rollback fix it?

This will help identify the exact root cause.

---

## ✍️ **Note**

**Error 500 is NOT caused by our branch distribution fixes** (HTML/JS changes don't cause 500 errors). It's likely:
- Unrelated server issue
- Cache corruption
- Or coincidental timing with another deployment

