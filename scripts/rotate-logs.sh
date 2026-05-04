#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
# Log Rotation Script for WHUSNET Admin Payment
# ═══════════════════════════════════════════════════════════════════

LOG_DIR="/var/www/storage/logs"
BACKUP_DIR="/var/www/backups/logs"
RETENTION_DAYS=30
COMPRESS_AFTER_DAYS=7

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

echo "════════════════════════════════════════════════════════════════"
echo "Starting log rotation at $(date)"
echo "════════════════════════════════════════════════════════════════"

# ─── 1. Compress logs older than 7 days ────────────────────────────
echo "→ Compressing logs older than ${COMPRESS_AFTER_DAYS} days..."
find "$LOG_DIR" -name "*.log" -type f -mtime +${COMPRESS_AFTER_DAYS} -exec gzip {} \;
COMPRESSED=$(find "$LOG_DIR" -name "*.log.gz" -type f -mtime -1 | wc -l)
echo "  ✓ Compressed ${COMPRESSED} log files"

# ─── 2. Move compressed logs to backup directory ───────────────────
echo "→ Moving compressed logs to backup directory..."
find "$LOG_DIR" -name "*.log.gz" -type f -exec mv {} "$BACKUP_DIR/" \;
MOVED=$(find "$BACKUP_DIR" -name "*.log.gz" -type f -mtime -1 | wc -l)
echo "  ✓ Moved ${MOVED} compressed logs to backup"

# ─── 3. Delete old compressed logs ─────────────────────────────────
echo "→ Deleting compressed logs older than ${RETENTION_DAYS} days..."
DELETED=$(find "$BACKUP_DIR" -name "*.log.gz" -type f -mtime +${RETENTION_DAYS} | wc -l)
find "$BACKUP_DIR" -name "*.log.gz" -type f -mtime +${RETENTION_DAYS} -delete
echo "  ✓ Deleted ${DELETED} old compressed logs"

# ─── 4. Delete empty log files ─────────────────────────────────────
echo "→ Deleting empty log files..."
EMPTY=$(find "$LOG_DIR" -name "*.log" -type f -size 0 | wc -l)
find "$LOG_DIR" -name "*.log" -type f -size 0 -delete
echo "  ✓ Deleted ${EMPTY} empty log files"

# ─── 5. Calculate disk usage ───────────────────────────────────────
echo "→ Calculating disk usage..."
LOG_SIZE=$(du -sh "$LOG_DIR" | cut -f1)
BACKUP_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
echo "  • Current logs: ${LOG_SIZE}"
echo "  • Backup logs: ${BACKUP_SIZE}"

# ─── 6. Check disk space ───────────────────────────────────────────
echo "→ Checking disk space..."
DISK_USAGE=$(df -h "$LOG_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
echo "  • Disk usage: ${DISK_USAGE}%"

if [ "$DISK_USAGE" -gt 80 ]; then
    echo "  ⚠️  WARNING: Disk usage is above 80%!"
    # Send alert (optional - uncomment if you have alerting setup)
    # curl -X POST "$SLACK_WEBHOOK_URL" -d '{"text":"⚠️ Disk usage is above 80% on production server!"}'
fi

echo "════════════════════════════════════════════════════════════════"
echo "Log rotation completed successfully at $(date)"
echo "════════════════════════════════════════════════════════════════"
