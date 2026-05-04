# 📝 What to Log - Quick Reference

## 🎯 Log Channels

| Channel | Purpose | Retention | What to Log |
|---------|---------|-----------|-------------|
| **laravel.log** | General app logs | 30 days | User actions, system events |
| **error.log** | Errors only | 90 days | Exceptions, failures |
| **ocr.log** | OCR processing | 14 days | OCR jobs, Gemini API |
| **queue.log** | Queue jobs | 14 days | Job status, duration |
| **security.log** | Security events | 90 days | Failed logins, unauthorized access |
| **audit.log** | User actions | 365 days | Create/update/delete operations |
| **performance.log** | Performance | 7 days | Slow queries, slow requests |

---

## ✅ What to Log

### 1. User Actions
```php
// Login
Log::info('User logged in', ['user_id' => $user->id]);

// Transaction created
LogHelper::audit('created', 'Transaction', $transaction->id, [
    'amount' => $transaction->amount,
]);
```

### 2. Errors
```php
// Exception
Log::error('Payment failed', [
    'transaction_id' => $transaction->id,
    'error' => $exception->getMessage(),
]);
```

### 3. OCR Processing
```php
// OCR completed
LogHelper::ocr('info', 'OCR completed', [
    'transaction_id' => $transaction->id,
    'duration_ms' => $duration,
    'items_found' => count($items),
]);
```

### 4. Queue Jobs
```php
// Job completed
LogHelper::queue('info', 'OcrProcessingJob', [
    'status' => 'completed',
    'duration_ms' => $duration,
]);
```

### 5. Security Events
```php
// Failed login
LogHelper::security('Failed login attempt', [
    'email' => $request->email,
    'ip' => request()->ip(),
]);
```

### 6. Performance Issues
```php
// Slow operation
LogHelper::performance('OCR Processing', $duration, [
    'transaction_id' => $transaction->id,
]);
```

---

## ❌ What NOT to Log

| Never Log | Why | Alternative |
|-----------|-----|-------------|
| Passwords | Security risk | Log email only |
| API Keys | Security risk | Log service name |
| Credit Cards | PCI compliance | Log last 4 digits |
| Session IDs | Security risk | Log user ID |
| Tokens | Security risk | Log token type |

### Use Sanitize

```php
use App\Helpers\LogHelper;

$data = LogHelper::sanitize([
    'email' => 'user@example.com',
    'password' => 'secret',  // → ***REDACTED***
]);
```

---

## 📊 Log Levels

| Level | When | Production |
|-------|------|------------|
| DEBUG | Development only | ❌ No |
| INFO | Informational | ⚠️ Selective |
| WARNING | Warning conditions | ✅ Yes |
| ERROR | Error conditions | ✅ Yes |
| CRITICAL | Critical issues | ✅ Yes |

**Production:** `LOG_LEVEL=warning`

---

## 🎯 Common Scenarios

### Transaction Created
```php
Log::info('Transaction created', [
    'transaction_id' => $transaction->id,
    'amount' => $transaction->amount,
]);

LogHelper::audit('created', 'Transaction', $transaction->id, [
    'invoice_number' => $transaction->invoice_number,
]);
```

### OCR Processing
```php
LogHelper::ocr('info', 'Processing started', [
    'transaction_id' => $transaction->id,
    'file' => $filename,
]);

LogHelper::ocr('info', 'Processing completed', [
    'duration_ms' => $duration,
    'items_found' => count($items),
]);
```

### Failed Login
```php
LogHelper::security('Failed login', [
    'email' => $request->email,
    'ip' => request()->ip(),
]);
```

### Slow Query
```php
LogHelper::performance('Slow query', $duration, [
    'query' => $sql,
    'time_ms' => $time,
]);
```

---

## 📚 Full Documentation

- `MONOLOG_LOGGING_GUIDE.md` - Complete guide
- `LOGGING_QUICK_REFERENCE.md` - Usage examples
- `MONOLOG_PRODUCTION_GUIDE.md` - Production setup

---

**Last Updated**: May 4, 2026
