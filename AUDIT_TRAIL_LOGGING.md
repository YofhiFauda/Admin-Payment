# Audit Trail & Logging System - WHUSNET

## 📋 Overview

Project ini menggunakan **Monolog** (melalui Laravel Logging) dengan sistem **dual-tracking**:
1. **Database Audit Trail** - Menyimpan aktivitas user di tabel `activity_logs`
2. **File-based Logging** - Menyimpan log terstruktur di berbagai channel Monolog

---

## 🗄️ Database Audit Trail (ActivityLog Model)

### Struktur Tabel `activity_logs`

```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('action');                    // Jenis aksi
    $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
    $table->string('target_id')->nullable();     // Invoice Number fallback
    $table->text('description')->nullable();     // Deskripsi lengkap
    $table->timestamps();                        // created_at, updated_at
});
```

### Data yang Di-capture di Database

| Field | Deskripsi | Contoh |
|-------|-----------|--------|
| `user_id` | ID user yang melakukan aksi | `5` |
| `action` | Jenis aksi yang dilakukan | `approve`, `reject`, `edit`, `create`, `delete` |
| `transaction_id` | ID transaksi terkait (jika ada) | `123` |
| `target_id` | Invoice number sebagai fallback | `INV-2026-001` |
| `description` | Deskripsi lengkap aktivitas | "Menyetujui status Transaksi INV-2026-001" |
| `created_at` | Timestamp kapan aksi dilakukan | `2026-05-05 10:30:00` |

### Jenis Aksi (Actions) yang Di-log

#### 1. **Transaction Management**
- `approve` - Menyetujui transaksi
- `reject` - Menolak transaksi dengan alasan
- `edit` - Mengedit data pengajuan (termasuk revisi management)
- `delete` - Menghapus transaksi
- `settle_debt` - Menyelesaikan hutang
- `force_approve` - Force approve oleh owner/admin

#### 2. **Upload & OCR**
- `upload_invoice` - Upload invoice/nota
- `upload_payment` - Upload bukti pembayaran

#### 3. **Payment Verification**
- `reject_payment` - Teknisi menolak bukti pembayaran

#### 4. **Bank Account Management**
- `create` - Membuat rekening bank (branch/user)
- `edit` - Mengedit rekening bank
- `delete` - Menghapus rekening bank

### Contoh Data yang Tersimpan

```php
ActivityLog::create([
    'user_id'        => 5,
    'action'         => 'approve',
    'transaction_id' => 123,
    'target_id'      => 'INV-2026-001',
    'description'    => 'Menyetujui status Transaksi INV-2026-001',
]);
```

```php
ActivityLog::create([
    'user_id'        => 3,
    'action'         => 'reject',
    'transaction_id' => 124,
    'target_id'      => 'INV-2026-002',
    'description'    => 'Menolak status Transaksi INV-2026-002 dengan alasan: Data tidak lengkap',
]);
```

```php
ActivityLog::create([
    'user_id'        => 7,
    'action'         => 'edit',
    'transaction_id' => 125,
    'target_id'      => 'INV-2026-003',
    'description'    => 'Mengedit data Pengajuan INV-2026-003 (Revisi ke-2)',
]);
```

---

## 📝 File-based Logging (Monolog Channels)

### Channel Konfigurasi

Project ini memiliki **8 custom channels** untuk logging terpisah:

| Channel | Path | Level | Retention | Purpose |
|---------|------|-------|-----------|---------|
| `audit` | `storage/logs/audit.log` | `info` | **365 hari** | Audit trail lengkap |
| `security` | `storage/logs/security.log` | `notice` | **90 hari** | Security events |
| `ocr` | `storage/logs/ocr.log` | `info` | 14 hari | OCR processing |
| `ai_autofill` | `storage/logs/ai-autofill.log` | `info` | 14 hari | AI autofill |
| `queue` | `storage/logs/queue.log` | `info` | 14 hari | Queue jobs |
| `performance` | `storage/logs/performance.log` | `warning` | 7 hari | Slow operations |
| `error` | `storage/logs/error.log` | `error` | 90 hari | Error & critical |
| `daily` | `storage/logs/laravel.log` | `warning` | 30 hari | General logs |

---

## 🔍 LogHelper - Audit Channel

### Method: `LogHelper::audit()`

```php
public static function audit(string $action, string $model, $modelId, array $changes = []): void
{
    Log::channel('audit')->info("Audit: {$action}", [
        'action' => $action,
        'model' => $model,
        'model_id' => $modelId,
        'user_id' => auth()->id(),
        'user_email' => auth()->user()?->email,
        'ip' => request()->ip(),
        'changes' => $changes,
        'timestamp' => now()->toIso8601String(),
    ]);
}
```

### Data yang Di-capture di Audit Log File

| Field | Deskripsi | Contoh |
|-------|-----------|--------|
| `action` | Jenis aksi | `create`, `update`, `delete` |
| `model` | Nama model/entity | `Transaction`, `User`, `BranchBankAccount` |
| `model_id` | ID record yang diubah | `123` |
| `user_id` | ID user yang melakukan aksi | `5` |
| `user_email` | Email user | `admin@whusnet.com` |
| `ip` | IP address user | `192.168.1.100` |
| `changes` | Array perubahan data | `['status' => ['pending' => 'approved']]` |
| `timestamp` | ISO 8601 timestamp | `2026-05-05T10:30:00+07:00` |

### Contoh Log Entry

```json
{
  "level": "info",
  "message": "Audit: update",
  "context": {
    "action": "update",
    "model": "Transaction",
    "model_id": 123,
    "user_id": 5,
    "user_email": "admin@whusnet.com",
    "ip": "192.168.1.100",
    "changes": {
      "status": {
        "old": "pending",
        "new": "approved"
      },
      "approved_by": {
        "old": null,
        "new": 5
      }
    },
    "timestamp": "2026-05-05T10:30:00+07:00"
  }
}
```

---

## 🔒 Security Logging

### Method: `LogHelper::security()`

```php
public static function security(string $message, array $context = []): void
{
    Log::channel('security')->warning($message, array_merge($context, [
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'user_id' => auth()->id(),
        'user_email' => auth()->user()?->email,
        'timestamp' => now()->toIso8601String(),
    ]));
}
```

### Data yang Di-capture

- IP address
- User agent (browser/device info)
- User ID & email
- Timestamp
- Custom context (failed login attempts, unauthorized access, dll)

### Use Cases

- Failed login attempts
- Unauthorized access attempts
- Permission violations
- Suspicious activities
- Role changes
- Password changes

---

## ⚡ Performance Logging

### Method: `LogHelper::performance()`

```php
public static function performance(string $operation, float $duration, array $context = []): void
{
    if ($duration > 1000) { // Only log if > 1 second
        Log::channel('performance')->warning("Slow operation: {$operation}", [
            'duration_ms' => round($duration, 2),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### Data yang Di-capture

- Operation name
- Duration (milliseconds)
- Memory usage (MB)
- Timestamp
- Custom context

---

## 🛡️ Data Sanitization

### Method: `LogHelper::sanitize()`

Secara otomatis menyembunyikan data sensitif sebelum logging:

```php
$sensitive = [
    'password',
    'token',
    'secret',
    'api_key',
    'apikey',
    'api-key',
    'credit_card',
    'card_number',
    'cvv',
    'ssn',
    'authorization',
    'bearer',
];
```

**Output:** `'***REDACTED***'`

---

## 📊 Exception Logging

### Method: `LogHelper::exception()`

```php
public static function exception(\Throwable $exception, array $context = []): void
{
    Log::error($exception->getMessage(), [
        'exception' => get_class($exception),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'user_id' => auth()->id(),
        'ip' => request()->ip(),
        'url' => request()->fullUrl(),
        'method' => request()->method(),
        'timestamp' => now()->toIso8601String(),
    ]);
}
```

### Data yang Di-capture

- Exception class
- Error message
- File & line number
- Stack trace
- User context (ID, IP)
- Request context (URL, method)
- Timestamp

---

## 🔄 Real-time Broadcasting

Setiap aktivitas yang disimpan ke database juga di-broadcast via WebSocket:

```php
broadcast(new \App\Events\ActivityLogged($log));
```

Ini memungkinkan:
- Real-time notification di UI
- Live activity feed
- Instant updates tanpa refresh

---

## 📈 Access Control

### ActivityLogController

**Akses berdasarkan role:**

| Role | Akses |
|------|-------|
| **Owner** | Melihat semua log (Admin, Atasan, Owner) |
| **Admin/Atasan** | Melihat log sendiri + log dari Teknisi |
| **Teknisi** | Tidak ada akses ke activity log |

```php
// Owner sees everything
if ($user->isOwner()) {
    // No filter
}

// Admin/Atasan sees own logs + teknisi logs
if ($user->isAdmin() || $user->isAtasan()) {
    $query->where(function ($q) use ($user) {
        $q->where('user_id', $user->id)
          ->orWhereHas('user', function ($u) {
              $u->where('role', 'teknisi');
          });
    });
}
```

---

## 🎯 Summary: Apa yang Di-capture?

### ✅ Database (activity_logs table)

1. **User Actions**
   - Who: `user_id`, relasi ke `users` table
   - What: `action` (approve, reject, edit, create, delete, dll)
   - When: `created_at`, `updated_at`
   - Where: `transaction_id`, `target_id` (invoice number)
   - Why: `description` (deskripsi lengkap)

2. **Transaction Lifecycle**
   - Approval/rejection dengan alasan
   - Edit history dengan revision count
   - Upload invoice/payment
   - Debt settlement
   - Force approve

3. **Bank Account Management**
   - Create/edit/delete rekening
   - Branch & user bank accounts

### ✅ File Logs (Monolog channels)

1. **Audit Channel** (`storage/logs/audit.log`)
   - Action, model, model_id
   - User ID & email
   - IP address
   - Changes (before/after)
   - ISO 8601 timestamp
   - **Retention: 365 hari**

2. **Security Channel** (`storage/logs/security.log`)
   - Security events
   - IP, user agent
   - Failed attempts
   - Unauthorized access
   - **Retention: 90 hari**

3. **Performance Channel** (`storage/logs/performance.log`)
   - Slow operations (>1s)
   - Memory usage
   - Duration metrics
   - **Retention: 7 hari**

4. **Error Channel** (`storage/logs/error.log`)
   - Exceptions
   - Stack traces
   - Request context
   - **Retention: 90 hari**

---

## 🔧 Configuration

### Environment Variables

```env
LOG_CHANNEL=stack
LOG_STACK=daily,error,security,audit
LOG_LEVEL=warning
LOG_DAILY_DAYS=30

# Specific channel levels
LOG_LEVEL_OCR=info
LOG_LEVEL_QUEUE=info

# Slack notifications (optional)
LOG_SLACK_WEBHOOK_URL=
LOG_SLACK_USERNAME="WHUSNET Alert"
LOG_SLACK_LEVEL=critical
```

---

## 📌 Best Practices

1. **Gunakan Database untuk User Actions** - Mudah di-query, di-filter, dan ditampilkan di UI
2. **Gunakan File Logs untuk Technical Details** - Debugging, monitoring, compliance
3. **Sanitize Sensitive Data** - Gunakan `LogHelper::sanitize()` atau `LogHelper::safe()`
4. **Log Exceptions Properly** - Gunakan `LogHelper::exception()` untuk context lengkap
5. **Monitor Performance** - Gunakan `LogHelper::performance()` untuk operasi lambat
6. **Security Events** - Selalu log ke `security` channel untuk compliance
7. **Retention Policy** - Audit logs disimpan 1 tahun, sesuai compliance requirement

---

## 🚀 Usage Examples

### Database Audit Trail

```php
use App\Models\ActivityLog;

// Log approval
ActivityLog::create([
    'user_id'        => auth()->id(),
    'action'         => 'approve',
    'transaction_id' => $transaction->id,
    'target_id'      => $transaction->invoice_number,
    'description'    => "Menyetujui status Transaksi {$transaction->invoice_number}",
]);

// Log rejection with reason
ActivityLog::create([
    'user_id'        => auth()->id(),
    'action'         => 'reject',
    'transaction_id' => $transaction->id,
    'target_id'      => $transaction->invoice_number,
    'description'    => "Menolak status Transaksi {$transaction->invoice_number} dengan alasan: {$reason}",
]);
```

### File-based Logging

```php
use App\Helpers\LogHelper;

// Audit trail
LogHelper::audit('update', 'Transaction', $transaction->id, [
    'status' => ['pending' => 'approved'],
]);

// Security event
LogHelper::security('Unauthorized access attempt', [
    'route' => 'admin.dashboard',
    'role' => 'teknisi',
]);

// Performance monitoring
$start = microtime(true);
// ... operation ...
$duration = (microtime(true) - $start) * 1000;
LogHelper::performance('OCR Processing', $duration, [
    'file_size' => $fileSize,
]);

// Exception logging
try {
    // ... code ...
} catch (\Exception $e) {
    LogHelper::exception($e, [
        'transaction_id' => $transaction->id,
    ]);
}
```

---

## 📚 Related Files

- `config/logging.php` - Monolog channel configuration
- `app/Helpers/LogHelper.php` - Logging helper methods
- `app/Models/ActivityLog.php` - Database audit model
- `app/Http/Controllers/ActivityLogController.php` - Activity log viewer
- `database/migrations/2026_02_24_000004_create_activity_logs_table.php` - Table schema

---

**Last Updated:** May 5, 2026
