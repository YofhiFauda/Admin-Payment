# 📝 Monolog Logging Guide - What to Log

## 🎯 Overview

Panduan lengkap tentang **apa saja yang di-log** di WHUSNET Admin Payment menggunakan Monolog.

---

## 📊 Log Channels & Purpose

### 1. **laravel.log** (Main Application Log)

**Purpose:** General application logs

**What to Log:**
- ✅ User authentication (login/logout)
- ✅ Important business logic events
- ✅ Configuration changes
- ✅ System events
- ✅ General information

**Examples:**
```php
Log::info('User logged in', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip' => request()->ip(),
]);

Log::info('Configuration updated', [
    'setting' => 'payment_gateway',
    'old_value' => 'midtrans',
    'new_value' => 'xendit',
]);

Log::warning('High memory usage detected', [
    'memory_mb' => memory_get_usage(true) / 1024 / 1024,
    'threshold' => 512,
]);
```

**Retention:** 30 days

---

### 2. **error.log** (Errors Only)

**Purpose:** Application errors and critical issues

**What to Log:**
- ✅ Exceptions and errors
- ✅ Failed operations
- ✅ Critical system issues
- ✅ Database errors
- ✅ External API failures

**Examples:**
```php
Log::error('Payment processing failed', [
    'transaction_id' => $transaction->id,
    'error' => $exception->getMessage(),
    'gateway' => 'midtrans',
    'amount' => $transaction->amount,
]);

Log::error('Database connection failed', [
    'connection' => 'mysql',
    'host' => config('database.connections.mysql.host'),
    'error' => $exception->getMessage(),
]);

Log::critical('Redis connection lost', [
    'host' => config('database.redis.default.host'),
    'port' => config('database.redis.default.port'),
]);
```

**Retention:** 90 days (longer for compliance)

---

### 3. **ocr.log** (OCR Processing)

**Purpose:** OCR (Optical Character Recognition) processing logs

**What to Log:**
- ✅ OCR job started
- ✅ OCR processing progress
- ✅ OCR completed (with duration)
- ✅ OCR failed (with reason)
- ✅ Items extracted
- ✅ Gemini API calls

**Examples:**
```php
use App\Helpers\LogHelper;

// OCR started
LogHelper::ocr('info', 'OCR processing started', [
    'transaction_id' => $transaction->id,
    'upload_id' => $uploadId,
    'file' => $filename,
    'size_kb' => $filesize / 1024,
]);

// OCR completed
LogHelper::ocr('info', 'OCR processing completed', [
    'transaction_id' => $transaction->id,
    'duration_ms' => $duration,
    'items_found' => count($items),
    'total_amount' => $totalAmount,
]);

// OCR failed
LogHelper::ocr('error', 'OCR processing failed', [
    'transaction_id' => $transaction->id,
    'error' => $exception->getMessage(),
    'file' => $filename,
    'attempts' => $attempts,
]);

// Gemini API call
LogHelper::ocr('debug', 'Gemini API request', [
    'model' => 'gemini-1.5-flash',
    'prompt_length' => strlen($prompt),
    'image_size' => $imageSize,
]);
```

**Retention:** 14 days

---

### 4. **queue.log** (Queue Jobs)

**Purpose:** Queue job processing logs

**What to Log:**
- ✅ Job started
- ✅ Job completed (with duration)
- ✅ Job failed
- ✅ Job retried
- ✅ Queue metrics

**Examples:**
```php
use App\Helpers\LogHelper;

// Job started
LogHelper::queue('info', 'OcrProcessingJob', [
    'status' => 'started',
    'transaction_id' => $this->transaction->id,
    'queue' => 'ocr',
    'attempts' => $this->attempts(),
]);

// Job completed
LogHelper::queue('info', 'OcrProcessingJob', [
    'status' => 'completed',
    'transaction_id' => $this->transaction->id,
    'duration_ms' => $duration,
    'items_processed' => count($items),
]);

// Job failed
LogHelper::queue('error', 'OcrProcessingJob', [
    'status' => 'failed',
    'transaction_id' => $this->transaction->id,
    'error' => $exception->getMessage(),
    'attempts' => $this->attempts(),
]);

// Job permanently failed
LogHelper::queue('critical', 'OcrProcessingJob', [
    'status' => 'failed_permanently',
    'transaction_id' => $this->transaction->id,
    'error' => $exception->getMessage(),
    'max_attempts' => 3,
]);
```

**Retention:** 14 days

---

### 5. **security.log** (Security Events)

**Purpose:** Security-related events and potential threats

**What to Log:**
- ✅ Failed login attempts
- ✅ Unauthorized access attempts
- ✅ Permission denied
- ✅ Suspicious activities
- ✅ Password changes
- ✅ Role changes

**Examples:**
```php
use App\Helpers\LogHelper;

// Failed login
LogHelper::security('Failed login attempt', [
    'email' => $request->email,
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

// Unauthorized access
LogHelper::security('Unauthorized access attempt', [
    'user_id' => auth()->id(),
    'route' => request()->path(),
    'method' => request()->method(),
    'required_role' => 'owner',
    'user_role' => auth()->user()->role,
]);

// Password changed
LogHelper::security('Password changed', [
    'user_id' => $user->id,
    'email' => $user->email,
    'changed_by' => auth()->id(),
]);

// Role changed
LogHelper::security('User role changed', [
    'user_id' => $user->id,
    'old_role' => $oldRole,
    'new_role' => $newRole,
    'changed_by' => auth()->id(),
]);

// Suspicious activity
LogHelper::security('Multiple failed login attempts', [
    'email' => $email,
    'ip' => request()->ip(),
    'attempts' => $attempts,
    'time_window' => '5 minutes',
]);
```

**Retention:** 90 days (compliance requirement)

---

### 6. **audit.log** (User Actions)

**Purpose:** Audit trail of user actions for compliance

**What to Log:**
- ✅ Create operations
- ✅ Update operations
- ✅ Delete operations
- ✅ Important business actions
- ✅ Data changes

**Examples:**
```php
use App\Helpers\LogHelper;

// Transaction created
LogHelper::audit('created', 'Transaction', $transaction->id, [
    'invoice_number' => $transaction->invoice_number,
    'amount' => $transaction->amount,
    'category' => $transaction->category,
    'branch_id' => $transaction->branch_id,
]);

// Transaction updated
LogHelper::audit('updated', 'Transaction', $transaction->id, [
    'changes' => [
        'status' => ['pending' => 'approved'],
        'approved_by' => [null => auth()->id()],
    ],
]);

// Transaction deleted
LogHelper::audit('deleted', 'Transaction', $transaction->id, [
    'invoice_number' => $transaction->invoice_number,
    'amount' => $transaction->amount,
    'reason' => 'Duplicate entry',
]);

// Payment approved
LogHelper::audit('approved', 'Transaction', $transaction->id, [
    'invoice_number' => $transaction->invoice_number,
    'amount' => $transaction->amount,
    'approved_by' => auth()->id(),
    'approved_at' => now(),
]);

// User created
LogHelper::audit('created', 'User', $user->id, [
    'name' => $user->name,
    'email' => $user->email,
    'role' => $user->role,
    'branch_id' => $user->branch_id,
]);
```

**Retention:** 365 days (1 year for compliance)

---

### 7. **performance.log** (Performance Issues)

**Purpose:** Track slow operations and performance bottlenecks

**What to Log:**
- ✅ Slow requests (> 1 second)
- ✅ Slow queries (> 1 second)
- ✅ Slow jobs (> 1 second)
- ✅ High memory usage
- ✅ Long-running operations

**Examples:**
```php
use App\Helpers\LogHelper;

// Slow operation
$start = microtime(true);
// ... expensive operation ...
$duration = (microtime(true) - $start) * 1000;

LogHelper::performance('OCR Processing', $duration, [
    'transaction_id' => $transaction->id,
    'file_size_mb' => $filesize / 1024 / 1024,
    'items_found' => count($items),
]);

// Slow query
LogHelper::slowQuery($sql, $time, $bindings);

// High memory usage
if (memory_get_usage(true) > 512 * 1024 * 1024) {
    Log::channel('performance')->warning('High memory usage', [
        'memory_mb' => memory_get_usage(true) / 1024 / 1024,
        'peak_mb' => memory_get_peak_usage(true) / 1024 / 1024,
        'operation' => 'OCR processing',
    ]);
}
```

**Retention:** 7 days

---

## 🎯 What to Log in Different Scenarios

### Scenario 1: User Authentication

```php
// Login success
Log::info('User logged in', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

// Login failed
LogHelper::security('Failed login attempt', [
    'email' => $request->email,
    'ip' => request()->ip(),
]);

// Logout
Log::info('User logged out', [
    'user_id' => auth()->id(),
    'session_duration' => $duration,
]);
```

---

### Scenario 2: Transaction Processing

```php
// Transaction created
Log::info('Transaction created', [
    'transaction_id' => $transaction->id,
    'invoice_number' => $transaction->invoice_number,
    'amount' => $transaction->amount,
    'user_id' => auth()->id(),
]);

LogHelper::audit('created', 'Transaction', $transaction->id, [
    'invoice_number' => $transaction->invoice_number,
    'amount' => $transaction->amount,
]);

// Transaction updated
Log::info('Transaction updated', [
    'transaction_id' => $transaction->id,
    'changes' => $changes,
]);

LogHelper::audit('updated', 'Transaction', $transaction->id, $changes);

// Transaction approved
Log::info('Transaction approved', [
    'transaction_id' => $transaction->id,
    'approved_by' => auth()->id(),
]);

LogHelper::audit('approved', 'Transaction', $transaction->id, [
    'approved_by' => auth()->id(),
]);
```

---

### Scenario 3: OCR Processing

```php
// OCR started
LogHelper::ocr('info', 'OCR processing started', [
    'transaction_id' => $transaction->id,
    'upload_id' => $uploadId,
    'file' => $filename,
]);

// OCR progress
LogHelper::ocr('debug', 'Sending to Gemini API', [
    'transaction_id' => $transaction->id,
    'model' => 'gemini-1.5-flash',
]);

// OCR completed
LogHelper::ocr('info', 'OCR completed', [
    'transaction_id' => $transaction->id,
    'duration_ms' => $duration,
    'items_found' => count($items),
]);

// OCR failed
LogHelper::ocr('error', 'OCR failed', [
    'transaction_id' => $transaction->id,
    'error' => $exception->getMessage(),
]);
```

---

### Scenario 4: Payment Processing

```php
// Payment initiated
Log::info('Payment initiated', [
    'transaction_id' => $transaction->id,
    'amount' => $transaction->amount,
    'gateway' => 'midtrans',
]);

// Payment success
Log::info('Payment successful', [
    'transaction_id' => $transaction->id,
    'payment_id' => $paymentId,
    'amount' => $amount,
]);

// Payment failed
Log::error('Payment failed', [
    'transaction_id' => $transaction->id,
    'error' => $exception->getMessage(),
    'gateway' => 'midtrans',
]);
```

---

### Scenario 5: Queue Jobs

```php
// Job started
LogHelper::queue('info', 'OcrProcessingJob', [
    'status' => 'started',
    'transaction_id' => $this->transaction->id,
]);

// Job completed
LogHelper::queue('info', 'OcrProcessingJob', [
    'status' => 'completed',
    'duration_ms' => $duration,
]);

// Job failed
LogHelper::queue('error', 'OcrProcessingJob', [
    'status' => 'failed',
    'error' => $exception->getMessage(),
]);
```

---

### Scenario 6: External API Calls

```php
// API request
Log::info('External API request', [
    'service' => 'Gemini AI',
    'endpoint' => '/v1/models/gemini-1.5-flash:generateContent',
    'method' => 'POST',
]);

// API response
Log::info('External API response', [
    'service' => 'Gemini AI',
    'status' => 200,
    'duration_ms' => $duration,
]);

// API error
Log::error('External API error', [
    'service' => 'Gemini AI',
    'status' => 429,
    'error' => 'Rate limit exceeded',
]);
```

---

## 📋 Log Levels Guide

### When to Use Each Level

| Level | When to Use | Examples |
|-------|-------------|----------|
| **DEBUG** | Development debugging | API requests, variable dumps |
| **INFO** | Informational messages | User login, transaction created |
| **NOTICE** | Normal but significant | Configuration changes |
| **WARNING** | Warning conditions | Slow queries, high memory |
| **ERROR** | Error conditions | Failed operations, exceptions |
| **CRITICAL** | Critical conditions | System failures, data loss |
| **ALERT** | Action must be taken | Service down, database offline |
| **EMERGENCY** | System unusable | Complete system failure |

### Production Recommendations

```env
# Production
LOG_LEVEL=warning  # Only warning and above

# Staging
LOG_LEVEL=info     # Info and above

# Development
LOG_LEVEL=debug    # Everything
```

---

## 🔒 What NOT to Log

### ❌ Never Log These:

1. **Passwords**
   ```php
   // ❌ BAD
   Log::info('User login', ['password' => $password]);
   
   // ✅ GOOD
   Log::info('User login', ['email' => $email]);
   ```

2. **API Keys / Tokens**
   ```php
   // ❌ BAD
   Log::info('API call', ['api_key' => $apiKey]);
   
   // ✅ GOOD
   Log::info('API call', ['service' => 'Gemini AI']);
   ```

3. **Credit Card Numbers**
   ```php
   // ❌ BAD
   Log::info('Payment', ['card_number' => $cardNumber]);
   
   // ✅ GOOD
   Log::info('Payment', ['card_last4' => substr($cardNumber, -4)]);
   ```

4. **Personal Identifiable Information (PII)**
   ```php
   // ❌ BAD
   Log::info('User data', ['ssn' => $ssn, 'phone' => $phone]);
   
   // ✅ GOOD
   Log::info('User data', ['user_id' => $userId]);
   ```

5. **Session IDs / Cookies**
   ```php
   // ❌ BAD
   Log::info('Request', ['session_id' => session()->getId()]);
   
   // ✅ GOOD
   Log::info('Request', ['user_id' => auth()->id()]);
   ```

### Use LogHelper::sanitize()

```php
use App\Helpers\LogHelper;

// Automatically redact sensitive data
$data = LogHelper::sanitize([
    'email' => 'user@example.com',
    'password' => 'secret123',  // Will be ***REDACTED***
    'api_key' => 'sk-xxx',      // Will be ***REDACTED***
    'amount' => 100000,         // Will be kept
]);

Log::info('User data', $data);
```

---

## 📊 Log Structure Best Practices

### Good Log Structure

```php
// ✅ GOOD - Structured, searchable
Log::info('Transaction created', [
    'transaction_id' => $transaction->id,
    'invoice_number' => $transaction->invoice_number,
    'amount' => $transaction->amount,
    'user_id' => auth()->id(),
    'branch_id' => $transaction->branch_id,
    'timestamp' => now()->toIso8601String(),
]);
```

### Bad Log Structure

```php
// ❌ BAD - Unstructured, hard to search
Log::info("Transaction {$transaction->id} created by user {$user->id} with amount {$amount}");
```

### Include Context

```php
// ✅ GOOD - Rich context
Log::error('Payment failed', [
    'transaction_id' => $transaction->id,
    'error' => $exception->getMessage(),
    'error_code' => $exception->getCode(),
    'gateway' => 'midtrans',
    'amount' => $transaction->amount,
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'timestamp' => now()->toIso8601String(),
]);
```

---

## 🎓 Summary

### What to Log:

✅ **User Actions**
- Login/logout
- Create/update/delete operations
- Important business actions

✅ **System Events**
- Application start/stop
- Configuration changes
- Service status

✅ **Errors & Exceptions**
- Failed operations
- Exceptions with stack traces
- External API failures

✅ **Performance Metrics**
- Slow queries
- Slow requests
- High memory usage

✅ **Security Events**
- Failed login attempts
- Unauthorized access
- Suspicious activities

✅ **Business Events**
- Transactions created/updated
- Payments processed
- OCR processing

### What NOT to Log:

❌ Passwords  
❌ API keys/tokens  
❌ Credit card numbers  
❌ Personal identifiable information  
❌ Session IDs  

### Use LogHelper for:

- `LogHelper::ocr()` - OCR processing
- `LogHelper::security()` - Security events
- `LogHelper::audit()` - User actions
- `LogHelper::performance()` - Performance issues
- `LogHelper::queue()` - Queue jobs
- `LogHelper::sanitize()` - Remove sensitive data

---

**Last Updated**: May 4, 2026  
**Version**: 1.0
