# Log Files Summary - Production

## ✅ Log Files yang Ada di Production

Berdasarkan pengecekan di container `whusnet-app`:

```
total 213M
-rwxrwxr-x 1 www-data www-data 105M May 16 11:32 error-2026-05-16.log
-rw-r--r-- 1 root     root      57K May 17 13:38 error-2026-05-17.log
-rw-rw-r-- 1 www-data www-data 105M May 16 11:32 laravel-2026-05-16.log
-rw-rw-r-- 1 root     root      57K May 17 13:38 laravel-2026-05-17.log
-rwxrwxr-x 1 www-data www-data 3.8M May 16 08:38 laravel.log
-rw-r--r-- 1 www-data www-data 3.3K May 16 11:28 ocr-2026-05-16.log
-rwxrwxr-x 1 www-data www-data    0 May 16 06:06 php-fpm-slow.log
```

### Log Files yang Ada ✅

1. **laravel.log** (3.8M) - Main Laravel log
2. **laravel-2026-05-16.log** (105M) - Laravel log kemarin
3. **laravel-2026-05-17.log** (57K) - Laravel log hari ini
4. **error-2026-05-16.log** (105M) - Error log kemarin
5. **error-2026-05-17.log** (57K) - Error log hari ini
6. **ocr-2026-05-16.log** (3.3K) - OCR processing log
7. **php-fpm-slow.log** (0) - PHP-FPM slow queries (empty)

### Log Files yang TIDAK Ada ❌

Script `analyze-logs.sh` mencari file-file ini tapi tidak ada:
- ❌ `queue.log` - Queue job logs (tidak dikonfigurasi)
- ❌ `security.log` - Security events (tidak dikonfigurasi)
- ❌ `performance.log` - Performance issues (tidak dikonfigurasi)

## 🔍 Recent Errors Found

### Error 1: Config Cache Serialization (CRITICAL)
```
Your configuration files could not be serialized because the value at 
"log-viewer.authorize" is non-serializable.
```

**Cause**: Closure `fn($request) => true` di `config/log-viewer.php` tidak bisa di-serialize

**Impact**: 
- `php artisan config:cache` gagal
- Performance degradation (config tidak ter-cache)

**Status**: ✅ FIXED - Changed to static method reference

### Error 2: Tinker Parse Errors
```
PHP Parse error: Syntax error, unexpected T_NS_SEPARATOR
```

**Cause**: Command tinker dengan syntax yang kompleks dari PowerShell

**Impact**: Minor - hanya saat debugging

**Status**: ✅ RESOLVED - Use simpler commands

### Error 3: Missing Command
```
There are no commands defined in the "api-docs" namespace
```

**Cause**: Command `CheckApiDocsAccess` belum ter-deploy

**Impact**: Cannot run debug command

**Status**: ⏳ PENDING - Needs deployment

## 📊 Log Configuration

### Current Logging Setup

**File**: `config/logging.php`

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'stderr', 'error'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'warning',
        'days' => 30,
    ],
    'error' => [
        'driver' => 'daily',
        'path' => storage_path('logs/error.log'),
        'level' => 'error',
        'days' => 30,
    ],
]
```

### Log Rotation

- **Daily rotation**: Enabled
- **Retention**: 30 days
- **Max size**: No limit (should add)

### Missing Log Channels

To add the missing log files, update `config/logging.php`:

```php
'channels' => [
    // ... existing channels ...
    
    'queue' => [
        'driver' => 'daily',
        'path' => storage_path('logs/queue.log'),
        'level' => 'info',
        'days' => 14,
    ],
    
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90, // Keep longer for audit
    ],
    
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'warning',
        'days' => 7,
    ],
]
```

## 🔧 How to View Logs

### Option 1: Docker Commands (Recommended)

```bash
# View live logs
docker exec -it whusnet-app tail -f storage/logs/laravel-2026-05-17.log

# View last 50 lines
docker exec whusnet-app tail -50 storage/logs/laravel-2026-05-17.log

# Search for errors
docker exec whusnet-app grep "ERROR" storage/logs/laravel-2026-05-17.log

# Count errors
docker exec whusnet-app bash -c "grep -c 'ERROR' storage/logs/laravel-2026-05-17.log"

# View specific date
docker exec whusnet-app cat storage/logs/laravel-2026-05-16.log
```

### Option 2: Log Viewer UI

Access via browser (owner only):
```
https://layer-silver-armstrong-speech.trycloudflare.com/log-viewer
```

**Requirements**:
- Login as owner
- Role: owner

### Option 3: Analysis Scripts

```bash
# PowerShell (Windows)
.\scripts\analyze-logs-docker.ps1

# Bash (Linux/Mac/Git Bash)
bash scripts/analyze-logs-docker.sh

# Inside container
docker exec -it whusnet-app bash
sh scripts/analyze-logs.sh
```

## 📈 Log Size Management

### Current Size: 213M

**Breakdown**:
- 105M - laravel-2026-05-16.log
- 105M - error-2026-05-16.log
- 3.8M - laravel.log
- 57K - laravel-2026-05-17.log
- 57K - error-2026-05-17.log
- 3.3K - ocr-2026-05-16.log

### Recommendations

1. **Add log rotation by size**:
   ```php
   'daily' => [
       'driver' => 'daily',
       'path' => storage_path('logs/laravel.log'),
       'level' => 'warning',
       'days' => 30,
       'max_files' => 30,
       'max_size' => 10485760, // 10MB
   ],
   ```

2. **Clean old logs regularly**:
   ```bash
   # Manual cleanup
   docker exec whusnet-app find storage/logs -name "*.log" -mtime +30 -delete
   
   # Or via artisan (if command exists)
   docker exec whusnet-app php artisan log:clear --days=30
   ```

3. **Monitor disk usage**:
   ```bash
   docker exec whusnet-app du -sh storage/logs/
   ```

## 🚨 Action Items

### Immediate (Critical)

- [x] Fix config cache serialization issue
- [ ] Deploy fixed config to production
- [ ] Test config:cache command
- [ ] Clear existing cache

### Short Term

- [ ] Add missing log channels (queue, security, performance)
- [ ] Implement log size limits
- [ ] Set up automated log cleanup
- [ ] Add log monitoring alerts

### Long Term

- [ ] Integrate with centralized logging (e.g., ELK, Loki)
- [ ] Set up log aggregation
- [ ] Create log analysis dashboard
- [ ] Implement log-based alerting

## 🔗 Related Files

- `config/logging.php` - Logging configuration
- `config/log-viewer.php` - Log Viewer configuration (FIXED)
- `scripts/analyze-logs-docker.ps1` - PowerShell log analysis
- `scripts/analyze-logs-docker.sh` - Bash log analysis
- `scripts/analyze-logs.sh` - Container-internal analysis

## 📝 Notes

1. **Why script showed "not found"**: Script ran on local machine, not in container
2. **Logs are daily rotated**: New file created each day
3. **Large log files**: May 16 logs are 105MB each - need size limits
4. **Config cache issue**: Fixed by using static method instead of Closure
5. **Missing logs**: queue, security, performance channels not configured

## ✅ Next Steps

1. **Deploy config fix**:
   ```bash
   git add config/log-viewer.php
   git commit -m "fix: use static method for log-viewer authorization to enable config caching"
   git push origin main
   ```

2. **Test config cache**:
   ```bash
   docker exec whusnet-app php artisan config:cache
   # Should succeed now
   ```

3. **Monitor logs**:
   ```bash
   docker exec -it whusnet-app tail -f storage/logs/laravel-2026-05-17.log
   ```
