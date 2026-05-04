#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
# Log Analysis Script for WHUSNET Admin Payment
# ═══════════════════════════════════════════════════════════════════

LOG_DIR="/var/www/storage/logs"
REPORT_FILE="/tmp/log-analysis-$(date +%Y%m%d-%H%M%S).txt"

echo "════════════════════════════════════════════════════════════════" | tee "$REPORT_FILE"
echo "Log Analysis Report - $(date)" | tee -a "$REPORT_FILE"
echo "════════════════════════════════════════════════════════════════" | tee -a "$REPORT_FILE"
echo "" | tee -a "$REPORT_FILE"

# ─── 1. Error Summary ──────────────────────────────────────────────
echo "📊 ERROR SUMMARY" | tee -a "$REPORT_FILE"
echo "────────────────────────────────────────────────────────────────" | tee -a "$REPORT_FILE"

if [ -f "$LOG_DIR/laravel.log" ]; then
    ERROR_COUNT=$(grep -c "ERROR" "$LOG_DIR/laravel.log" 2>/dev/null || echo "0")
    CRITICAL_COUNT=$(grep -c "CRITICAL" "$LOG_DIR/laravel.log" 2>/dev/null || echo "0")
    WARNING_COUNT=$(grep -c "WARNING" "$LOG_DIR/laravel.log" 2>/dev/null || echo "0")
    
    echo "  • Errors: ${ERROR_COUNT}" | tee -a "$REPORT_FILE"
    echo "  • Critical: ${CRITICAL_COUNT}" | tee -a "$REPORT_FILE"
    echo "  • Warnings: ${WARNING_COUNT}" | tee -a "$REPORT_FILE"
else
    echo "  ⚠️  laravel.log not found" | tee -a "$REPORT_FILE"
fi
echo "" | tee -a "$REPORT_FILE"

# ─── 2. Top 10 Most Frequent Errors ────────────────────────────────
echo "🔥 TOP 10 MOST FREQUENT ERRORS" | tee -a "$REPORT_FILE"
echo "────────────────────────────────────────────────────────────────" | tee -a "$REPORT_FILE"

if [ -f "$LOG_DIR/error.log" ]; then
    grep "ERROR" "$LOG_DIR/error.log" 2>/dev/null | \
        cut -d' ' -f6- | \
        sort | \
        uniq -c | \
        sort -rn | \
        head -10 | \
        tee -a "$REPORT_FILE"
else
    echo "  ⚠️  error.log not found" | tee -a "$REPORT_FILE"
fi
echo "" | tee -a "$REPORT_FILE"

# ─── 3. OCR Processing Statistics ──────────────────────────────────
echo "📸 OCR PROCESSING STATISTICS" | tee -a "$REPORT_FILE"
echo "────────────────────────────────────────────────────────────────" | tee -a "$REPORT_FILE"

if [ -f "$LOG_DIR/ocr.log" ]; then
    OCR_TOTAL=$(grep -c "OCR" "$LOG_DIR/ocr.log" 2>/dev/null || echo "0")
    OCR_SUCCESS=$(grep -c "completed" "$LOG_DIR/ocr.log" 2>/dev/null || echo "0")
    OCR_FAILED=$(grep -c "failed" "$LOG_DIR/ocr.log" 2>/dev/null || echo "0")
    
    echo "  • Total OCR jobs: ${OCR_TOTAL}" | tee -a "$REPORT_FILE"
    echo "  • Successful: ${OCR_SUCCESS}" | tee -a "$REPORT_FILE"
    echo "  • Failed: ${OCR_FAILED}" | tee -a "$REPORT_FILE"
    
    # Average processing time
    if command -v bc &> /dev/null; then
        AVG_TIME=$(grep "duration_ms" "$LOG_DIR/ocr.log" 2>/dev/null | \
            grep -oP 'duration_ms":\K[0-9.]+' | \
            awk '{sum+=$1; count++} END {if(count>0) print sum/count; else print 0}')
        echo "  • Average processing time: ${AVG_TIME} ms" | tee -a "$REPORT_FILE"
    fi
else
    echo "  ⚠️  ocr.log not found" | tee -a "$REPORT_FILE"
fi
echo "" | tee -a "$REPORT_FILE"

# ─── 4. Queue Job Statistics ───────────────────────────────────────
echo "⚙️  QUEUE JOB STATISTICS" | tee -a "$REPORT_FILE"
echo "────────────────────────────────────────────────────────────────" | tee -a "$REPORT_FILE"

if [ -f "$LOG_DIR/queue.log" ]; then
    QUEUE_TOTAL=$(grep -c "Job:" "$LOG_DIR/queue.log" 2>/dev/null || echo "0")
    QUEUE_COMPLETED=$(grep -c "completed" "$LOG_DIR/queue.log" 2>/dev/null || echo "0")
    QUEUE_FAILED=$(grep -c "failed" "$LOG_DIR/queue.log" 2>/dev/null || echo "0")
    
    echo "  • Total jobs: ${QUEUE_TOTAL}" | tee -a "$REPORT_FILE"
    echo "  • Completed: ${QUEUE_COMPLETED}" | tee -a "$REPORT_FILE"
    echo "  • Failed: ${QUEUE_FAILED}" | tee -a "$REPORT_FILE"
else
    echo "  ⚠️  queue.log not found" | tee -a "$REPORT_FILE"
fi
echo "" | tee -a "$REPORT_FILE"

# ─── 5. Security Events ────────────────────────────────────────────
echo "🔒 SECURITY EVENTS" | tee -a "$REPORT_FILE"
echo "────────────────────────────────────────────────────────────────" | tee -a "$REPORT_FILE"

if [ -f "$LOG_DIR/security.log" ]; then
    FAILED_LOGINS=$(grep -c "Failed login" "$LOG_DIR/security.log" 2>/dev/null || echo "0")
    UNAUTHORIZED=$(grep -c "Unauthorized" "$LOG_DIR/security.log" 2>/dev/null || echo "0")
    
    echo "  • Failed login attempts: ${FAILED_LOGINS}" | tee -a "$REPORT_FILE"
    echo "  • Unauthorized access: ${UNAUTHORIZED}" | tee -a "$REPORT_FILE"
    
    # Top IPs with failed logins
    if [ "$FAILED_LOGINS" -gt 0 ]; then
        echo "" | tee -a "$REPORT_FILE"
        echo "  Top IPs with failed logins:" | tee -a "$REPORT_FILE"
        grep "Failed login" "$LOG_DIR/security.log" 2>/dev/null | \
            grep -oP 'ip":"?\K[0-9.]+' | \
            sort | \
            uniq -c | \
            sort -rn | \
            head -5 | \
            tee -a "$REPORT_FILE"
    fi
else
    echo "  ⚠️  security.log not found" | tee -a "$REPORT_FILE"
fi
echo "" | tee -a "$REPORT_FILE"

# ─── 6. Performance Issues ─────────────────────────────────────────
echo "⚡ PERFORMANCE ISSUES" | tee -a "$REPORT_FILE"
echo "────────────────────────────────────────────────────────────────" | tee -a "$REPORT_FILE"

if [ -f "$LOG_DIR/performance.log" ]; then
    SLOW_OPS=$(grep -c "Slow operation" "$LOG_DIR/performance.log" 2>/dev/null || echo "0")
    SLOW_QUERIES=$(grep -c "Slow query" "$LOG_DIR/performance.log" 2>/dev/null || echo "0")
    
    echo "  • Slow operations: ${SLOW_OPS}" | tee -a "$REPORT_FILE"
    echo "  • Slow queries: ${SLOW_QUERIES}" | tee -a "$REPORT_FILE"
    
    # Top slow operations
    if [ "$SLOW_OPS" -gt 0 ]; then
        echo "" | tee -a "$REPORT_FILE"
        echo "  Top slow operations:" | tee -a "$REPORT_FILE"
        grep "Slow operation" "$LOG_DIR/performance.log" 2>/dev/null | \
            cut -d':' -f4- | \
            sort | \
            uniq -c | \
            sort -rn | \
            head -5 | \
            tee -a "$REPORT_FILE"
    fi
else
    echo "  ⚠️  performance.log not found" | tee -a "$REPORT_FILE"
fi
echo "" | tee -a "$REPORT_FILE"

# ─── 7. Disk Usage ─────────────────────────────────────────────────
echo "💾 DISK USAGE" | tee -a "$REPORT_FILE"
echo "────────────────────────────────────────────────────────────────" | tee -a "$REPORT_FILE"

LOG_SIZE=$(du -sh "$LOG_DIR" 2>/dev/null | cut -f1)
echo "  • Log directory size: ${LOG_SIZE}" | tee -a "$REPORT_FILE"

# Individual log file sizes
echo "" | tee -a "$REPORT_FILE"
echo "  Largest log files:" | tee -a "$REPORT_FILE"
du -h "$LOG_DIR"/*.log 2>/dev/null | sort -rh | head -5 | tee -a "$REPORT_FILE"

echo "" | tee -a "$REPORT_FILE"
echo "════════════════════════════════════════════════════════════════" | tee -a "$REPORT_FILE"
echo "Report saved to: $REPORT_FILE" | tee -a "$REPORT_FILE"
echo "════════════════════════════════════════════════════════════════" | tee -a "$REPORT_FILE"

# Display report location
echo ""
echo "📄 Full report available at: $REPORT_FILE"
