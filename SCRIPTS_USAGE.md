# Scripts Usage Guide

## Log Analysis Scripts

### 1. `analyze-logs.sh` - For Server/Direct Access
**When to use**: Ketika Anda SSH ke server dan ingin analyze logs langsung

**Location**: Harus dijalankan di dalam server/container
```bash
# SSH ke server dulu
ssh user@server

# Atau masuk ke container
docker exec -it whusnet-app bash

# Baru jalankan
sh scripts/analyze-logs.sh
```

**Path yang dicek**: `/var/www/storage/logs` (path di dalam container)

---

### 2. `analyze-logs-docker.sh` - For Docker from Host
**When to use**: Ketika Anda di local machine dan ingin analyze logs di container

**Location**: Dijalankan dari **host machine** (di luar container)
```bash
# Langsung dari local machine
sh scripts/analyze-logs-docker.sh
```

**How it works**: Script akan otomatis exec ke dalam container dan analyze logs

---

## Perbedaan

| Feature | analyze-logs.sh | analyze-logs-docker.sh |
|---------|----------------|------------------------|
| Run from | Inside container/server | Host machine (local) |
| Docker command | ❌ No | ✅ Yes (docker exec) |
| Path | Direct: `/var/www/storage/logs` | Via docker exec |
| Use case | SSH to server | Remote analysis |

---

## Why Your Command Failed

```bash
$ sh scripts/analyze-logs.sh
⚠️  laravel.log not found
```

**Reason**: Script mencari file di path `/var/www/storage/logs` di **local machine** Anda, tapi file log ada di **dalam Docker container**.

**Solution**: Gunakan `analyze-logs-docker.sh` atau jalankan script di dalam container:

```bash
# Option 1: Use Docker version (from local)
sh scripts/analyze-logs-docker.sh

# Option 2: Run inside container
docker exec -it whusnet-app bash
sh scripts/analyze-logs.sh
exit
```

---

## Other Useful Scripts

### `fix-log-viewer.sh`
Fix Log Viewer 403 error
```bash
# Run inside container
docker exec -it whusnet-app bash
sh scripts/fix-log-viewer.sh
```

### `post-deploy.sh`
Run after deployment
```bash
# Run inside container
docker exec whusnet-app sh scripts/post-deploy.sh
```

### `test-api-docs-access.sh`
Test API documentation access
```bash
# Run inside container
docker exec -it whusnet-app bash
bash scripts/test-api-docs-access.sh
```

---

## Quick Log Commands

### View Live Logs
```bash
# Laravel log
docker exec -it whusnet-app tail -f storage/logs/laravel.log

# All logs
docker logs -f whusnet-app
```

### View Last N Lines
```bash
# Last 50 lines
docker exec whusnet-app tail -50 storage/logs/laravel.log

# Last 100 lines
docker exec whusnet-app tail -100 storage/logs/laravel.log
```

### Search for Errors
```bash
# Search for ERROR
docker exec whusnet-app grep "ERROR" storage/logs/laravel.log

# Search for specific error
docker exec whusnet-app grep "403" storage/logs/laravel.log

# Count errors
docker exec whusnet-app grep -c "ERROR" storage/logs/laravel.log
```

### List Log Files
```bash
docker exec whusnet-app ls -lh storage/logs/
```

### Check Log Size
```bash
docker exec whusnet-app du -sh storage/logs/
```

### Clear Old Logs (if command exists)
```bash
docker exec whusnet-app php artisan log:clear
```

---

## Troubleshooting

### Script not found
```bash
# Make sure you're in project root
pwd
# Should show: /path/to/admin-payment

# Check if script exists
ls -la scripts/
```

### Permission denied
```bash
# On Linux/Mac, make executable
chmod +x scripts/*.sh

# On Windows, use sh or bash
sh scripts/analyze-logs-docker.sh
bash scripts/analyze-logs-docker.sh
```

### Container not running
```bash
# Check container status
docker ps | grep whusnet

# Start container if stopped
docker start whusnet-app
```

---

## Best Practices

1. **Use Docker version from local**: `analyze-logs-docker.sh`
2. **Monitor logs in real-time**: `docker logs -f whusnet-app`
3. **Check logs after deployment**: Always check for errors
4. **Regular log rotation**: Don't let logs grow too large
5. **Use Log Viewer UI**: Access via `/log-viewer` (owner only)

---

## Related Documentation

- `FIX_LOG_VIEWER_403.md` - Log Viewer access fix
- `FIX_API_DOCS_403.md` - API Documentation access fix
- `DEPLOY_API_DOCS_FIX.md` - Deployment guide
