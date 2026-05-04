# ⚡ Performance Optimization Guide

## Current Performance Bottlenecks

Berdasarkan analisis project, berikut adalah area yang perlu dioptimasi:

### 1. **OCR Processing** (Gemini API)
- Rate limited: 12 RPM (free tier)
- Processing time: 5-30 detik per image
- Potential bottleneck saat traffic tinggi

### 2. **Database Queries**
- N+1 query problems (perlu eager loading)
- Missing indexes pada kolom yang sering di-query
- Large result sets tanpa pagination

### 3. **Image Processing**
- Upload image besar tanpa compression
- Tidak ada image optimization
- Storage bisa cepat penuh

### 4. **Real-time Features**
- WebSocket connections bisa overload
- Broadcasting ke banyak users sekaligus

---

## Optimization Strategies

### 1. Database Optimization

#### A. Add Missing Indexes

```sql
-- Transactions table
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_transactions_created_at ON transactions(created_at);
CREATE INDEX idx_transactions_branch_id ON transactions(branch_id);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_category_id ON transactions(category_id);
CREATE INDEX idx_transactions_status_created ON transactions(status, created_at);

-- Price indexes table
CREATE INDEX idx_price_indexes_item_branch ON price_indexes(master_item_id, branch_id);
CREATE INDEX idx_price_indexes_created_at ON price_indexes(created_at);

-- Activity logs
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);
CREATE INDEX idx_activity_logs_type ON activity_logs(log_type);

-- Jobs table
CREATE INDEX idx_jobs_queue_reserved ON jobs(queue, reserved_at);
```

#### B. Eager Loading (Prevent N+1)

**❌ BAD (N+1 Problem)**:
```php
$transactions = Transaction::all();
foreach ($transactions as $transaction) {
    echo $transaction->user->name; // N queries
    echo $transaction->category->name; // N queries
}
```

**✅ GOOD (Eager Loading)**:
```php
$transactions = Transaction::with(['user', 'category', 'branch'])->get();
foreach ($transactions as $transaction) {
    echo $transaction->user->name; // No additional queries
    echo $transaction->category->name;
}
```

#### C. Query Optimization

**Use select() untuk limit columns**:
```php
// ❌ BAD - fetch all columns
$users = User::all();

// ✅ GOOD - only needed columns
$users = User::select('id', 'name', 'email')->get();
```

**Use chunk() untuk large datasets**:
```php
// ❌ BAD - load all into memory
$transactions = Transaction::all();

// ✅ GOOD - process in chunks
Transaction::chunk(1000, function ($transactions) {
    foreach ($transactions as $transaction) {
        // Process
    }
});
```

**Use cursor() untuk very large datasets**:
```php
// ✅ BEST - minimal memory usage
foreach (Transaction::cursor() as $transaction) {
    // Process one by one
}
```

#### D. Database Connection Pooling

**config/database.php**:
```php
'mysql' => [
    // ... other config
    'options' => [
        PDO::ATTR_PERSISTENT => true, // Connection pooling
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ],
],
```

---

### 2. Caching Strategy

#### A. Query Result Caching

```php
// Cache expensive queries
$stats = Cache::remember('dashboard.stats', 3600, function () {
    return [
        'total_transactions' => Transaction::count(),
        'total_amount' => Transaction::sum('amount'),
        'pending_count' => Transaction::where('status', 'pending')->count(),
    ];
});
```

#### B. Model Caching

```php
// Cache model data
$categories = Cache::remember('categories.all', 86400, function () {
    return TransactionCategory::all();
});
```

#### C. View Caching

```bash
# Cache views
php artisan view:cache

# Clear view cache
php artisan view:clear
```

#### D. Route Caching

```bash
# Cache routes (production only)
php artisan route:cache

# Clear route cache
php artisan route:clear
```

#### E. Config Caching

```bash
# Cache config
php artisan config:cache

# Clear config cache
php artisan config:clear
```

#### F. Redis Cache Tags

```php
// Cache with tags for easy invalidation
Cache::tags(['transactions', 'user:' . $userId])->put('key', $value, 3600);

// Flush specific tag
Cache::tags(['transactions'])->flush();
```

---

### 3. Image Optimization

#### A. Automatic Compression

**Service**: `app/Services/ImageCompressionService.php` (sudah ada)

Pastikan digunakan di semua upload:
```php
use App\Services\ImageCompressionService;

public function upload(Request $request)
{
    $file = $request->file('image');
    
    // Compress before storing
    $compressor = new ImageCompressionService();
    $compressedPath = $compressor->compress($file);
    
    // Store compressed image
    // ...
}
```

#### B. Image Resizing

```php
use Intervention\Image\Laravel\Facades\Image;

// Create thumbnail
$thumbnail = Image::read($path)
    ->resize(300, 300, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
    })
    ->save(storage_path('app/thumbnails/' . $filename));
```

#### C. Lazy Loading Images

```blade
<!-- Frontend: lazy load images -->
<img src="{{ $image }}" loading="lazy" alt="...">
```

#### D. WebP Format

```php
// Convert to WebP for better compression
Image::read($path)
    ->toWebp(80)
    ->save($webpPath);
```

---

### 4. Queue Optimization

#### A. Job Prioritization

```php
// High priority jobs
dispatch(new CriticalJob())->onQueue('high');

// Normal priority
dispatch(new NormalJob())->onQueue('default');

// Low priority
dispatch(new ReportJob())->onQueue('low');
```

**Horizon config**:
```php
'production' => [
    'supervisor-high' => [
        'queue' => ['high'],
        'maxProcesses' => 5,
        'tries' => 3,
    ],
    'supervisor-default' => [
        'queue' => ['default'],
        'maxProcesses' => 10,
        'tries' => 3,
    ],
    'supervisor-low' => [
        'queue' => ['low'],
        'maxProcesses' => 2,
        'tries' => 1,
    ],
],
```

#### B. Job Batching

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

// Process multiple jobs as batch
Bus::batch([
    new ProcessImage($image1),
    new ProcessImage($image2),
    new ProcessImage($image3),
])->then(function (Batch $batch) {
    // All jobs completed
})->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure
})->finally(function (Batch $batch) {
    // Batch finished
})->dispatch();
```

#### C. Job Chaining

```php
// Chain jobs - run sequentially
ProcessImage::withChain([
    new OptimizeImage($image),
    new GenerateThumbnail($image),
    new NotifyUser($user),
])->dispatch();
```

---

### 5. API Rate Limiting & Throttling

#### A. Gemini API Rate Limiting

**Service**: `app/Services/OCR/GeminiRateLimiter.php` (sudah ada)

Pastikan digunakan:
```php
use App\Services\OCR\GeminiRateLimiter;

$rateLimiter = new GeminiRateLimiter();

if ($rateLimiter->attempt()) {
    // Call Gemini API
    $result = $this->callGeminiApi($image);
} else {
    // Queue for later
    dispatch(new OcrProcessingJob($image))->delay(now()->addSeconds(60));
}
```

#### B. User Rate Limiting

```php
use Illuminate\Support\Facades\RateLimiter;

// Limit OCR requests per user
if (RateLimiter::tooManyAttempts('ocr:' . $userId, 10)) {
    return response()->json([
        'error' => 'Too many requests. Please try again later.'
    ], 429);
}

RateLimiter::hit('ocr:' . $userId, 60); // 10 per minute
```

---

### 6. Frontend Optimization

#### A. Asset Optimization

```javascript
// vite.config.js
export default {
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['axios', 'laravel-echo', 'pusher-js'],
                },
            },
        },
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.log in production
            },
        },
    },
};
```

#### B. Code Splitting

```javascript
// Lazy load components
const Dashboard = () => import('./components/Dashboard.vue');
const Reports = () => import('./components/Reports.vue');
```

#### C. CDN for Assets

```env
# .env
ASSET_URL=https://cdn.yourdomain.com
```

```blade
<!-- Use CDN -->
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<!-- Outputs: https://cdn.yourdomain.com/css/app.css -->
```

---

### 7. PHP Optimization

#### A. OPcache Configuration

**docker/php/local.ini**:
```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  ; Disable in production
opcache.save_comments=1
opcache.fast_shutdown=1
opcache.enable_cli=0

; Preloading (Laravel 12)
opcache.preload=/var/www/preload.php
opcache.preload_user=www-data
```

**preload.php**:
```php
<?php
// Preload Laravel framework
require __DIR__ . '/vendor/autoload.php';
```

#### B. PHP-FPM Tuning

**docker/php-fpm/www.conf**:
```ini
[www]
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; Slow log
request_slowlog_timeout = 5s
slowlog = /var/log/php-fpm-slow.log
```

---

### 8. Database Connection Pooling

#### A. MySQL Configuration

**my.cnf**:
```ini
[mysqld]
max_connections = 200
thread_cache_size = 16
table_open_cache = 4000
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# InnoDB settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
```

#### B. Connection Pooling

```php
// config/database.php
'mysql' => [
    'pool' => [
        'min_connections' => 5,
        'max_connections' => 20,
    ],
],
```

---

### 9. Redis Optimization

#### A. Redis Configuration

```bash
# redis.conf
maxmemory 1gb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000
appendonly yes
appendfsync everysec

# Performance
tcp-backlog 511
timeout 0
tcp-keepalive 300
```

#### B. Redis Pipeline

```php
// Batch multiple Redis commands
Redis::pipeline(function ($pipe) {
    $pipe->set('key1', 'value1');
    $pipe->set('key2', 'value2');
    $pipe->set('key3', 'value3');
});
```

---

### 10. Monitoring & Profiling

#### A. Laravel Telescope (Development)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access: `https://yourdomain.com/telescope`

#### B. Laravel Debugbar (Development)

```bash
composer require barryvdh/laravel-debugbar --dev
```

#### C. Query Logging

```php
// Enable query log
DB::enableQueryLog();

// Your queries here
$users = User::all();

// Get queries
$queries = DB::getQueryLog();
dd($queries);
```

#### D. Slow Query Log

**config/database.php**:
```php
'mysql' => [
    // ... other config
    'slow_query_log' => env('DB_SLOW_QUERY_LOG', true),
    'long_query_time' => env('DB_LONG_QUERY_TIME', 2), // seconds
],
```

---

## Performance Benchmarks

### Target Metrics (Production)

| Metric | Target | Critical |
|--------|--------|----------|
| Page Load Time | < 1s | > 3s |
| API Response Time | < 200ms | > 1s |
| Database Query Time | < 50ms | > 500ms |
| Queue Wait Time | < 30s | > 5min |
| Memory Usage | < 80% | > 95% |
| CPU Usage | < 70% | > 90% |
| Error Rate | < 0.1% | > 1% |

### Load Testing

```bash
# Install Apache Bench
apt-get install apache2-utils

# Test endpoint
ab -n 1000 -c 10 https://yourdomain.com/api/endpoint

# Install Artillery (better)
npm install -g artillery

# Run load test
artillery quick --count 100 --num 10 https://yourdomain.com
```

**artillery.yml**:
```yaml
config:
  target: 'https://yourdomain.com'
  phases:
    - duration: 60
      arrivalRate: 10
      name: Warm up
    - duration: 120
      arrivalRate: 50
      name: Sustained load
    - duration: 60
      arrivalRate: 100
      name: Spike

scenarios:
  - name: Browse and submit
    flow:
      - get:
          url: "/"
      - get:
          url: "/dashboard"
      - post:
          url: "/api/transactions"
          json:
            amount: 100000
            description: "Test"
```

---

## Quick Wins (Implement First)

1. **Enable OPcache** - 30-50% performance boost
2. **Add database indexes** - 10x faster queries
3. **Enable query caching** - Reduce DB load
4. **Compress images** - Reduce storage & bandwidth
5. **Enable Gzip compression** - Reduce response size
6. **Use CDN for assets** - Faster asset delivery
7. **Implement eager loading** - Eliminate N+1 queries
8. **Cache expensive queries** - Reduce computation

---

## Performance Checklist

- [ ] OPcache enabled and configured
- [ ] Database indexes added
- [ ] N+1 queries eliminated (eager loading)
- [ ] Query result caching implemented
- [ ] Image compression enabled
- [ ] Asset optimization (minify, bundle)
- [ ] CDN configured for static assets
- [ ] Gzip compression enabled
- [ ] Redis configured and optimized
- [ ] Queue workers scaled appropriately
- [ ] Slow query logging enabled
- [ ] Performance monitoring setup
- [ ] Load testing completed

---

**Last Updated**: May 4, 2026
