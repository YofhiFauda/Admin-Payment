# 📝 Monolog Production Logging Guide - Best Practices

## 🎯 Overview

Laravel menggunakan **Monolog** sebagai logging library default. Panduan ini memberikan best practices untuk production logging yang optimal: performa tinggi, storage efisien, dan monitoring yang efektif.

---

## ✅ Rekomendasi Konfigurasi Production

### 1. **Struktur Logging yang Optimal**

```
logs/
├── laravel.log              # Application logs (daily rotation)
├── laravel-YYYY-MM-DD.log   # Archived daily logs
├── error.log                # Error-level only (daily rotation)
├── ocr.log                  # OCR-specific logs
├── queue.log                # Queue/Job logs
├── security.log             # Security events
├── performance.log          # Slow queries, slow requests
└── audit.log                # User actions audit trail
```

---

## 🔧 Konfigurasi Production-Ready

### File: `config/logging.php`

```php
<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [

        // ═══════════════════════════════════════════════════════════════
        // PRODUCTION STACK - Multi-channel dengan filtering
        // ═══════════════════════════════════════════════════════════════
        
        'stack' => [
            'driver' => 'stack',
            'channels' => env('LOG_STACK', 'daily,error,slack'),
            'ignore_exceptions' => false,
        ],

        // ═══════════════════════════════════════════════════════════════
        // DAILY LOGS - Semua level dengan rotation
        // ═══════════════════════════════════════════════════════════════
        
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'warning'), // warning di production
            'days' => env('LOG_DAILY_DAYS', 30),
            'replace_placeholders' => true,
            'permission' => 0664,
            'locking' => false, // Disable untuk performa
        ],

        // ═══════════════════════════════════════════════════════════════
        // ERROR LOGS - Hanya error & critical
        // ═══════════════════════════════════════════════════════════════
        
        'error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
            'days' => 90, // Simpan lebih lama untuk error
            'replace_placeholders' => true,
        ],

        // ═══════════════════════════════════════════════════════════════
        // SLACK - Critical errors only
        // ═══════════════════════════════════════════════════════════════
        
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'WHUSNET Alert'),
            'emoji' => ':rotating_light:',
            'level' => 'critical',
            'replace_placeholders' => true,
            'context' => true, // Include context data
        ],

        // ═══════════════════════════════════════════════════════════════
        // CUSTOM CHANNELS - Feature-specific logs
        // ═══════════════════════════════════════════════════════════════
        
        'ocr' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ocr.log'),
            'level' => env('LOG_LEVEL_OCR', 'info'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'queue' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queue.log'),
            'level' => env('LOG_LEVEL_QUEUE', 'info'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'notice',
            'days' => 90, // Compliance requirement
            'replace_placeholders' => true,
        ],

        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 365, // 1 year retention
            'replace_placeholders' => true,
        ],

        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'warning',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        // ═══════════════════════════════════════════════════════════════
        // ADVANCED MONOLOG CHANNELS
        // ═══════════════════════════════════════════════════════════════

        // JSON Format - untuk parsing otomatis (ELK, Splunk, etc)
        'json' => [
            'driver' => 'monolog',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/laravel.json'),
                'maxFiles' => 30,
            ],
            'formatter' => JsonFormatter::class,
            'formatter_with' => [
                'batchMode' => JsonFormatter::BATCH_MODE_NEWLINES,
                'appendNewline' => true,
            ],
            'processors' => [
                PsrLogMessageProcessor::class,
                IntrospectionProcessor::class,
                WebProcessor::class,
                MemoryUsageProcessor::class,
            ],
        ],

        // Syslog - untuk centralized logging
        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'warning'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        // Papertrail - cloud logging service
        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'warning',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        // ═══════════════════════════════════════════════════════════════
        // UTILITY CHANNELS
        // ═══════════════════════════════════════════════════════════════

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
```

---

## 🌍 Environment Variables

### File: `.env.production.example`

```env
# ─── Logging Configuration ─────────────────────────────────────────
LOG_CHANNEL=stack
LOG_STACK=daily,error,slack

# Log levels: debug, info, notice, warning, error, critical, alert, emergency
LOG_LEVEL=warning
LOG_LEVEL_OCR=info
LOG_LEVEL_QUEUE=info

# Daily log retention
LOG_DAILY_DAYS=30

# Deprecations
LOG_DEPRECATIONS_CHANNEL=null
LOG_DEPRECATIONS_TRACE=false

# Slack notifications (critical errors only)
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
LOG_SLACK_USERNAME="WHUSNET Production Alert"

# Papertrail (optional - cloud logging)
PAPERTRAIL_URL=logs.papertrailapp.com
PAPERTRAIL_PORT=12345

# Sentry (optional - error tracking)
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.1
```

---

## 📊 Custom Logging Helpers

### File: `app/Helpers/LogHelper.php`

```php
<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogHelper
{
    /**
     * Log OCR processing
     */
    public static function ocr(string $level, string $message, array $context = []): void
    {
        Log::channel('ocr')->{$level}($message, array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'memory' => memory_get_usage(true),
        ]));
    }

    /**
     * Log security events
     */
    public static function security(string $message, array $context = []): void
    {
        Log::channel('security')->warning($message, array_merge($context, [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log audit trail
     */
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

    /**
     * Log performance issues
     */
    public static function performance(string $operation, float $duration, array $context = []): void
    {
        if ($duration > 1000) { // > 1 second
            Log::channel('performance')->warning("Slow operation: {$operation}", array_merge($context, [
                'duration_ms' => $duration,
                'memory_mb' => memory_get_usage(true) / 1024 / 1024,
                'timestamp' => now()->toIso8601String(),
            ]));
        }
    }

    /**
     * Log queue job
     */
    public static function queue(string $level, string $jobName, array $context = []): void
    {
        Log::channel('queue')->{$level}("Job: {$jobName}", array_merge($context, [
            'job' => $jobName,
            'queue' => $context['queue'] ?? 'default',
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Sanitize sensitive data before logging
     */
    public static function sanitize(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'api_key', 'credit_card'];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            } elseif (Str::contains(strtolower($key), $sensitive)) {
                $data[$key] = '***REDACTED***';
            }
        }
        
        return $data;
    }
}
```

### Autoload Helper

Tambahkan ke `composer.json`:

```json
{
    "autoload": {
        "files": [
            "app/Helpers/LogHelper.php"
        ]
    }
}
```

Jalankan:
```bash
composer dump-autoload
```

---

## 🎯 Usage Examples

### 1. **Basic Logging**

```php
use Illuminate\Support\Facades\Log;

// Standard logging
Log::info('User logged in', ['user_id' => $user->id]);
Log::warning('High memory usage', ['memory' => memory_get_usage()]);
Log::error('Payment failed', ['transaction_id' => $transaction->id]);
Log::critical('Database connection lost');

// Channel-specific logging
Log::channel('ocr')->info('OCR processing started', ['file' => $filename]);
Log::channel('security')->warning('Failed login attempt', ['email' => $email]);
Log::channel('audit')->info('Transaction created', ['id' => $transaction->id]);
```

### 2. **Using Custom Helper**

```php
use App\Helpers\LogHelper;

// OCR logging
LogHelper::ocr('info', 'Processing invoice', [
    'file' => $filename,
    'size' => $filesize,
]);

// Security logging
LogHelper::security('Unauthorized access attempt', [
    'route' => request()->path(),
    'method' => request()->method(),
]);

// Audit logging
LogHelper::audit('created', 'Transaction', $transaction->id, [
    'amount' => $transaction->amount,
    'status' => $transaction->status,
]);

// Performance logging
$start = microtime(true);
// ... expensive operation ...
$duration = (microtime(true) - $start) * 1000;
LogHelper::performance('OCR Processing', $duration, [
    'file' => $filename,
]);

// Queue logging
LogHelper::queue('info', 'OcrProcessingJob', [
    'queue' => 'ocr',
    'attempts' => 1,
]);

// Sanitize sensitive data
$data = LogHelper::sanitize([
    'email' => 'user@example.com',
    'password' => 'secret123', // Will be redacted
    'amount' => 100000,
]);
Log::info('User data', $data);
```

### 3. **Contextual Logging in Controllers**

```php
namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;

class PembelianController extends Controller
{
    public function store(Request $request)
    {
        $start = microtime(true);
        
        try {
            // Log request
            Log::info('Creating transaction', [
                'user_id' => auth()->id(),
                'branch_id' => $request->branch_id,
            ]);
            
            $transaction = Transaction::create($request->validated());
            
            // Audit log
            LogHelper::audit('created', 'Transaction', $transaction->id, [
                'amount' => $transaction->amount,
                'category' => $transaction->category,
            ]);
            
            // Performance log
            $duration = (microtime(true) - $start) * 1000;
            LogHelper::performance('Transaction Creation', $duration);
            
            return response()->json($transaction);
            
        } catch (\Exception $e) {
            Log::error('Transaction creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);
            
            throw $e;
        }
    }
}
```

### 4. **Logging in Jobs**

```php
namespace App\Jobs;

use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;

class OcrProcessingJob implements ShouldQueue
{
    public function handle()
    {
        $start = microtime(true);
        
        LogHelper::queue('info', 'OcrProcessingJob', [
            'status' => 'started',
            'transaction_id' => $this->transaction->id,
        ]);
        
        try {
            // Process OCR
            $result = $this->processOcr();
            
            $duration = (microtime(true) - $start) * 1000;
            
            LogHelper::ocr('info', 'OCR completed', [
                'transaction_id' => $this->transaction->id,
                'duration_ms' => $duration,
                'items_found' => count($result['items']),
            ]);
            
            LogHelper::queue('info', 'OcrProcessingJob', [
                'status' => 'completed',
                'duration_ms' => $duration,
            ]);
            
        } catch (\Exception $e) {
            LogHelper::queue('error', 'OcrProcessingJob', [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception)
    {
        LogHelper::queue('critical', 'OcrProcessingJob', [
            'status' => 'failed_permanently',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
```

---

## 🔍 Monitoring & Analysis

### 1. **Log Rotation Script**

File: `scripts/rotate-logs.sh`

```bash
#!/bin/bash

# Compress logs older than 7 days
find /var/www/storage/logs -name "*.log" -mtime +7 -exec gzip {} \;

# Delete compressed logs older than 30 days
find /var/www/storage/logs -name "*.log.gz" -mtime +30 -delete

# Delete empty log files
find /var/www/storage/logs -name "*.log" -size 0 -delete

echo "Log rotation completed at $(date)"
```

Tambahkan ke crontab:
```bash
# Rotate logs daily at 3 AM
0 3 * * * /var/www/scripts/rotate-logs.sh >> /var/www/storage/logs/rotation.log 2>&1
```

### 2. **Log Analysis Commands**

```bash
# Count errors by type
grep "ERROR" storage/logs/laravel.log | awk '{print $5}' | sort | uniq -c | sort -rn

# Find slow queries
grep "Slow query" storage/logs/performance.log | wc -l

# Top 10 most frequent errors
grep "ERROR" storage/logs/laravel.log | cut -d' ' -f6- | sort | uniq -c | sort -rn | head -10

# Failed login attempts
grep "Failed login" storage/logs/security.log | wc -l

# OCR processing time average
grep "OCR completed" storage/logs/ocr.log | grep -oP 'duration_ms":\K[0-9]+' | awk '{sum+=$1; count++} END {print sum/count}'

# Queue job failures
grep "failed_permanently" storage/logs/queue.log | wc -l

# Memory usage spikes
grep "memory_mb" storage/logs/performance.log | grep -oP 'memory_mb":\K[0-9.]+' | sort -rn | head -10
```

### 3. **Real-time Monitoring**

```bash
# Watch all logs
tail -f storage/logs/laravel.log

# Watch errors only
tail -f storage/logs/error.log

# Watch OCR processing
tail -f storage/logs/ocr.log | grep "duration_ms"

# Watch security events
tail -f storage/logs/security.log

# Multi-tail (install: apt-get install multitail)
multitail storage/logs/laravel.log storage/logs/error.log storage/logs/ocr.log
```

---

## 🚀 Advanced: Centralized Logging

### 1. **ELK Stack (Elasticsearch, Logstash, Kibana)**

```yaml
# docker-compose.elk.yml
services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
    environment:
      - discovery.type=single-node
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
    ports:
      - "9200:9200"
    volumes:
      - esdata:/usr/share/elasticsearch/data

  logstash:
    image: docker.elastic.co/logstash/logstash:8.11.0
    volumes:
      - ./logstash/pipeline:/usr/share/logstash/pipeline
      - ./storage/logs:/logs
    depends_on:
      - elasticsearch

  kibana:
    image: docker.elastic.co/kibana/kibana:8.11.0
    ports:
      - "5601:5601"
    depends_on:
      - elasticsearch

volumes:
  esdata:
```

Logstash config (`logstash/pipeline/logstash.conf`):
```
input {
  file {
    path => "/logs/laravel.json"
    codec => "json"
    type => "laravel"
  }
}

filter {
  if [type] == "laravel" {
    date {
      match => [ "timestamp", "ISO8601" ]
    }
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "laravel-%{+YYYY.MM.dd}"
  }
}
```

### 2. **Graylog**

Install Monolog Graylog handler:
```bash
composer require graylog2/gelf-php
```

Add to `config/logging.php`:
```php
'graylog' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\GelfHandler::class,
    'handler_with' => [
        'publisher' => new \Gelf\Publisher(
            new \Gelf\Transport\UdpTransport(
                env('GRAYLOG_HOST', 'localhost'),
                env('GRAYLOG_PORT', 12201)
            )
        ),
    ],
    'processors' => [PsrLogMessageProcessor::class],
],
```

### 3. **Papertrail (Cloud Service)**

Already configured in `config/logging.php`. Just add to `.env`:
```env
PAPERTRAIL_URL=logs.papertrailapp.com
PAPERTRAIL_PORT=12345
```

---

## 📋 Production Checklist

### Pre-Deployment
- [ ] Set `LOG_LEVEL=warning` di `.env` production
- [ ] Configure `LOG_STACK=daily,error,slack`
- [ ] Setup Slack webhook untuk critical alerts
- [ ] Set log retention days (`LOG_DAILY_DAYS=30`)
- [ ] Test log rotation script
- [ ] Setup log monitoring (ELK/Graylog/Papertrail)
- [ ] Configure log permissions (664)
- [ ] Add log directory to backup strategy

### Post-Deployment
- [ ] Monitor log file sizes
- [ ] Check log rotation is working
- [ ] Verify Slack notifications
- [ ] Test error logging
- [ ] Monitor disk space usage
- [ ] Setup alerts for disk space
- [ ] Review log retention policy
- [ ] Document log analysis procedures

---

## 🎓 Best Practices

### ✅ DO

1. **Use appropriate log levels**
   - `debug`: Development only
   - `info`: Informational messages
   - `warning`: Warning conditions
   - `error`: Error conditions
   - `critical`: Critical conditions requiring immediate attention

2. **Include context**
   ```php
   Log::error('Payment failed', [
       'transaction_id' => $transaction->id,
       'user_id' => $user->id,
       'amount' => $amount,
       'error' => $e->getMessage(),
   ]);
   ```

3. **Use structured logging (JSON)**
   - Easier to parse and analyze
   - Better for log aggregation tools

4. **Sanitize sensitive data**
   - Never log passwords, tokens, credit cards
   - Use `LogHelper::sanitize()`

5. **Log performance metrics**
   - Track slow operations
   - Monitor memory usage

6. **Use separate channels**
   - Easier to filter and analyze
   - Better retention policies per channel

### ❌ DON'T

1. **Don't log in loops**
   ```php
   // ❌ BAD
   foreach ($items as $item) {
       Log::info('Processing item', ['id' => $item->id]);
   }
   
   // ✅ GOOD
   Log::info('Processing items', ['count' => count($items)]);
   ```

2. **Don't log sensitive data**
   ```php
   // ❌ BAD
   Log::info('User login', ['password' => $password]);
   
   // ✅ GOOD
   Log::info('User login', ['email' => $email]);
   ```

3. **Don't use `debug` level in production**
   - Too verbose
   - Performance impact
   - Storage waste

4. **Don't ignore log rotation**
   - Disk space will fill up
   - Performance degradation

5. **Don't log everything**
   - Be selective
   - Focus on actionable information

---

## 📊 Performance Impact

| Configuration | Disk Usage | Performance Impact | Recommended For |
|---------------|------------|-------------------|-----------------|
| `LOG_LEVEL=debug` | Very High | High | Development only |
| `LOG_LEVEL=info` | High | Medium | Staging |
| `LOG_LEVEL=warning` | Low | Low | Production |
| `LOG_LEVEL=error` | Very Low | Very Low | High-traffic production |
| JSON format | +20% | +5% | Log aggregation |
| Multiple channels | +30% | +10% | Detailed monitoring |

---

## 🔗 Resources

- [Monolog Documentation](https://github.com/Seldaek/monolog)
- [Laravel Logging Documentation](https://laravel.com/docs/logging)
- [PSR-3 Logger Interface](https://www.php-fig.org/psr/psr-3/)
- [ELK Stack Guide](https://www.elastic.co/what-is/elk-stack)
- [Graylog Documentation](https://docs.graylog.org/)

---

**Last Updated**: May 4, 2026
**Version**: 1.0
**Maintainer**: DevOps Team
