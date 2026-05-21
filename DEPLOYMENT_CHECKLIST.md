# 🚀 Deployment Checklist: Payment Verification Fix

**Version:** 4.5.1  
**Date:** 21 Mei 2026  
**Issue:** Fix застрял status "Sedang Diverifikasi AI"

---

## ✅ Pre-Deployment Checklist

### 1. Code Review
- [x] `app/Http/Controllers/Api/V1/OcrNotaController.php` - Status fixed
- [x] `app/Http/Controllers/Api/AiAutoFillController.php` - Mapping fixed
- [x] `tests/Feature/TelegramNotificationPolicyTest.php` - Test updated
- [x] `OCR_Nota_Kontan_v4.5.json` - N8N workflow fixed
- [x] Documentation created

### 2. Testing
```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter TelegramNotificationPolicyTest

# Check for syntax errors
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 3. Backup
```bash
# Backup database
mysqldump -u root -p admin_payment > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup n8n workflow
cp OCR_Nota_Kontan_v4.5.json OCR_Nota_Kontan_v4.5.json.backup
```

---

## 🚀 Deployment Steps

### Step 1: Deploy Laravel Changes

```bash
# 1. Commit changes
git add app/Http/Controllers/Api/V1/OcrNotaController.php
git add app/Http/Controllers/Api/AiAutoFillController.php
git add tests/Feature/TelegramNotificationPolicyTest.php
git add docs/fixes/
git add scripts/fix-stuck-transactions.php
git add scripts/check-stuck-transactions.sql
git add DEPLOYMENT_CHECKLIST.md

git commit -m "fix: payment verification stuck status issue

- Fix OcrNotaController: use valid status with ai_status tracking
- Fix AiAutoFillController: remove invalid status mapping
- Update tests and documentation
- Add recovery scripts

Fixes застрял status 'Sedang Diverifikasi AI'
"

# 2. Push to repository
git push origin main

# 3. Deploy to production (adjust to your process)
# Example for Coolify/Docker:
# - Push will trigger auto-deployment
# - Or manually: ssh to server and pull changes
```

### Step 2: Update N8N Workflow

```bash
# 1. Login to n8n dashboard
# URL: https://your-n8n-instance.com

# 2. Import workflow
# - Workflows → Import from File
# - Select: OCR_Nota_Kontan_v4.5.json
# - Confirm import (will update existing workflow)

# 3. Activate workflow
# - Click "Active" toggle
# - Verify webhook URLs are correct
```

### Step 3: Fix Stuck Transactions (If Any)

```bash
# 1. Check for stuck transactions
php scripts/fix-stuck-transactions.php --dry-run

# 2. If found, fix them
php scripts/fix-stuck-transactions.php

# 3. Verify fix
mysql -u root -p admin_payment -e "
  SELECT status, COUNT(*) 
  FROM transactions 
  WHERE status = 'Sedang Diverifikasi AI';
"
# Should return 0 rows
```

---

## 🧪 Post-Deployment Testing

### Test 1: Upload Transfer (Match)

```bash
# 1. Login as Admin/Atasan
# 2. Go to transaction detail (waiting_payment)
# 3. Click "Upload Bukti Transfer"
# 4. Upload valid transfer proof
# 5. Expected:
#    - Status changes to "Menunggu Pembayaran" (not застрял!)
#    - After 30-60 seconds, status changes to "Selesai"
#    - Telegram notification sent
```

### Test 2: Upload Transfer (Flagged)

```bash
# 1. Upload transfer proof with wrong amount
# 2. Expected:
#    - Status changes to "Flagged"
#    - Telegram notification to all owners
#    - PaymentDiscrepancyAudit created
```

### Test 3: Check Logs

```bash
# Laravel log
tail -f storage/logs/ai_autofill.log | grep "UPLOAD TRANSFER"

# Expected output:
# ✅ [UPLOAD TRANSFER] N8N WEBHOOK SUCCESS
# ✅ [PAYMENT VERIFY] Processing callback
# ✅ [PAYMENT VERIFY] Transfer MATCH

# N8N log
# - Check n8n dashboard → Executions
# - Filter by workflow: "OCR Nota Kontan"
# - Verify successful executions
```

---

## 📊 Monitoring (First 24 Hours)

### Every Hour: Check Status Distribution

```sql
SELECT 
    status, 
    ai_status,
    COUNT(*) as count,
    MAX(updated_at) as last_update
FROM transactions
WHERE payment_method = 'transfer'
  AND created_at > NOW() - INTERVAL 24 HOUR
GROUP BY status, ai_status;
```

**Expected:**
- No `status = 'Sedang Diverifikasi AI'`
- `status = 'waiting_payment'` with `ai_status = 'processing'` should be < 5 minutes old
- Most should be `status = 'completed'` with `ai_status = 'completed'`

### Check for Stuck Transactions

```bash
# Run every hour
php scripts/fix-stuck-transactions.php --dry-run

# Should output:
# ✅ Tidak ada transaksi yang застрял
```

### Monitor N8N Executions

```
1. Login to n8n dashboard
2. Workflows → OCR Nota Kontan
3. Executions → Last 24 hours
4. Check for:
   - Success rate > 95%
   - No 429 errors
   - Callback nodes successful
```

---

## 🚨 Rollback Plan (If Needed)

### If Critical Issues Found:

```bash
# 1. Rollback Laravel code
git revert HEAD
git push origin main

# 2. Rollback n8n workflow
# - Import: OCR_Nota_Kontan_v4.5.json.backup
# - Activate

# 3. Restore database (if needed)
mysql -u root -p admin_payment < backup_YYYYMMDD_HHMMSS.sql

# 4. Notify team
# - Post in Slack/Telegram
# - Update status page
```

---

## ✅ Success Criteria

**Deployment dianggap berhasil jika:**

- [ ] No застрял transactions in last 1 hour
- [ ] Upload transfer works correctly (match & flagged)
- [ ] Status changes within 2 minutes
- [ ] Telegram notifications sent correctly
- [ ] No errors in Laravel log
- [ ] N8N executions > 95% success rate
- [ ] User feedback positive

---

## 📞 Emergency Contacts

**If issues occur:**

1. **IT Support:** [Your contact]
2. **DevOps:** [Your contact]
3. **Product Owner:** [Your contact]

**Escalation:**
- Minor issues: Fix within 4 hours
- Major issues: Rollback immediately

---

## 📝 Post-Deployment Report

**To be filled after deployment:**

### Deployment Info
- **Deployed by:** _______________
- **Deployment time:** _______________
- **Downtime:** _______________

### Testing Results
- [ ] Test 1 (Match): PASS / FAIL
- [ ] Test 2 (Flagged): PASS / FAIL
- [ ] Test 3 (Logs): PASS / FAIL

### Issues Found
- Issue 1: _______________
- Issue 2: _______________

### Actions Taken
- Action 1: _______________
- Action 2: _______________

### Final Status
- [ ] ✅ Deployment successful
- [ ] ⚠️ Deployment with minor issues
- [ ] ❌ Deployment failed (rolled back)

---

**Prepared by:** AI Assistant  
**Date:** 21 Mei 2026  
**Version:** 4.5.1
