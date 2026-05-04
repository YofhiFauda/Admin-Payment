# 🖥️ GUI Options for Monolog Logs

## Overview

Monolog sendiri **tidak memiliki GUI built-in**, tapi ada beberapa solusi untuk melihat logs dengan interface yang bagus seperti Telescope.

---

## 1. Laravel Log Viewer ⭐ RECOMMENDED (Simple)

### 📊 Features
- ✅ **Beautiful UI** - Clean, modern interface
- ✅ **Real-time** - Auto-refresh
- ✅ **Filtering** - By level, date, content
- ✅ **Search** - Full-text search
- ✅ **Download** - Export logs
- ✅ **Multiple files** - View different log files
- ✅ **Production-safe** - Low overhead
- ✅ **FREE** - Open source

### 📸 Screenshot
```
┌─────────────────────────────────────────────────────────┐
│ Laravel Log Viewer                                      │
├─────────────────────────────────────────────────────────┤
│ [All Levels ▼] [Today ▼] [Search...]                   │
├─────────────────────────────────────────────────────────┤
│ 🔴 ERROR   | 2026-05-04 10:23:45 | Payment failed      │
│ ⚠️  WARNING | 2026-05-04 10:22:30 | Slow query detected │
│ ℹ️  INFO    | 2026-05-04 10:21:15 | User logged in      │
└─────────────────────────────────────────────────────────┘
```

### 🚀 Installation

#### Option A: rap2hpoutre/laravel-log-viewer (Most Popular)

```bash
composer require rap2hpoutre/laravel-log-viewer
```

**Route:**
```php
// routes/web.php
Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LaravelLogViewerController::class, 'index']);
});
```

**Access:** `https://yourdomain.com/logs`

#### Option B: opcodesio/log-viewer (Modern, Feature-rich)

```bash
composer require opcodesio/log-viewer
php artisan log-viewer:publish
```

**Features:**
- Multiple log files
- Real-time updates
- Advanced filtering
- Dark mode
- API access

**Access:** `https://yourdomain.com/log-viewer`

### 🔒 Security

**IMPORTANT:** Protect with authentication!

```php
// config/log-viewer.php
return [
    'middleware' => ['web', 'auth', 'role:owner'],
    
    // Or use Gate
    'authorize' => function ($request) {
        return $request->user() && $request->user()->role === 'owner';
    },
];
```

### 💰 Cost
**FREE**

### 🎯 Best For
- Small to medium projects
- Quick log viewing
- Production-safe monitoring
- Teams without dedicated logging infrastructure

---

## 2. Laravel Pulse ⭐ RECOMMENDED (Monitoring)

### 📊 Features
- ✅ **Real-time metrics** - Live dashboard
- ✅ **Performance monitoring** - Slow queries, requests
- ✅ **Exception tracking** - Error rates
- ✅ **Queue monitoring** - Job stats
- ✅ **User requests** - Top users
- ✅ **Production-ready** - Low overhead
- ✅ **FREE** - Built-in Laravel

### 📸 Dashboard
```
┌─────────────────────────────────────────────────────────┐
│ Laravel Pulse                                           │
├─────────────────────────────────────────────────────────┤
│ Requests/min: 1,234  │  Slow Queries: 12               │
│ Exceptions: 3        │  Queue Wait: 2.3s               │
├─────────────────────────────────────────────────────────┤
│ Slowest Endpoints                                       │
│ POST /api/ocr ............................ 2,345ms      │
│ GET /dashboard ........................... 1,234ms      │
└─────────────────────────────────────────────────────────┘
```

### 🚀 Installation

```bash
composer require laravel/pulse
php artisan pulse:install
php artisan migrate
```

**Route:**
```php
// routes/web.php
Route::middleware(['auth', 'role:owner'])->group(function () {
    Route::get('/pulse', function () {
        return view('pulse::dashboard');
    });
});
```

**Access:** `https://yourdomain.com/pulse`

### 💰 Cost
**FREE**

### 🎯 Best For
- Production monitoring
- Performance insights
- Real-time metrics
- Complement to Monolog

### ⚠️ Note
Pulse is for **metrics**, not detailed log viewing. Use with Log Viewer for complete solution.

---

## 3. ELK Stack (Elasticsearch + Kibana) 🔧 ADVANCED

### 📊 Features
- ✅ **Powerful search** - Full-text search across all logs
- ✅ **Beautiful dashboards** - Customizable Kibana
- ✅ **Aggregations** - Complex queries
- ✅ **Scalable** - Handle millions of logs
- ✅ **Alerting** - Built-in alerts
- ✅ **FREE** - Open source

### 📸 Kibana Dashboard
```
┌─────────────────────────────────────────────────────────┐
│ Kibana                                                  │
├─────────────────────────────────────────────────────────┤
│ [Time Range: Last 24h] [Search: error]                 │
├─────────────────────────────────────────────────────────┤
│ ┌─────────────────┐  ┌─────────────────┐              │
│ │ Error Rate      │  │ Response Time   │              │
│ │    ▁▂▃▅▇▅▃▂▁   │  │    ▁▂▃▄▅▄▃▂▁   │              │
│ └─────────────────┘  └─────────────────┘              │
│                                                         │
│ Recent Errors:                                          │
│ • Payment gateway timeout (23 occurrences)             │
│ • Database connection failed (12 occurrences)          │
└─────────────────────────────────────────────────────────┘
```

### 🚀 Installation

```bash
# Using Docker Compose
docker-compose -f docker-compose.elk.yml up -d
```

**docker-compose.elk.yml:**
```yaml
version: '3.8'

services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
    environment:
      - discovery.type=single-node
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
      - xpack.security.enabled=false
    ports:
      - "9200:9200"
    volumes:
      - esdata:/usr/share/elasticsearch/data

  logstash:
    image: docker.elastic.co/logstash/logstash:8.11.0
    volumes:
      - ./logstash/pipeline:/usr/share/logstash/pipeline
      - ./storage/logs:/logs:ro
    depends_on:
      - elasticsearch
    environment:
      - "LS_JAVA_OPTS=-Xms256m -Xmx256m"

  kibana:
    image: docker.elastic.co/kibana/kibana:8.11.0
    ports:
      - "5601:5601"
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200
    depends_on:
      - elasticsearch

volumes:
  esdata:
```

**Logstash Pipeline** (`logstash/pipeline/logstash.conf`):
```
input {
  file {
    path => "/logs/laravel*.log"
    start_position => "beginning"
    codec => multiline {
      pattern => "^\[\d{4}-\d{2}-\d{2}"
      negate => true
      what => "previous"
    }
  }
}

filter {
  grok {
    match => { "message" => "\[%{TIMESTAMP_ISO8601:timestamp}\] %{DATA:environment}\.%{DATA:level}: %{GREEDYDATA:log_message}" }
  }
  
  date {
    match => [ "timestamp", "ISO8601" ]
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "laravel-logs-%{+YYYY.MM.dd}"
  }
}
```

**Access Kibana:** `http://localhost:5601`

### 💰 Cost
**FREE** (infrastructure costs only)

### 🎯 Best For
- Large scale applications
- Advanced search needs
- Multiple applications
- Data analysis

### ⚠️ Cons
- Complex setup
- Resource intensive (2GB+ RAM)
- Requires maintenance

---

## 4. Graylog 🔧 CENTRALIZED LOGGING

### 📊 Features
- ✅ **Centralized logging** - Multiple sources
- ✅ **Real-time** - Live log streaming
- ✅ **Alerting** - Built-in alerts
- ✅ **Search** - Powerful search
- ✅ **Dashboards** - Customizable
- ✅ **FREE** - Open source

### 🚀 Installation

```bash
# Using Docker Compose
docker-compose -f docker-compose.graylog.yml up -d
```

**Access:** `http://localhost:9000`

### 💰 Cost
**FREE** (infrastructure costs only)

### 🎯 Best For
- Centralized logging
- Multiple applications
- Self-hosted requirements

---

## 5. Papertrail 💼 CLOUD LOGGING

### 📊 Features
- ✅ **Cloud-based** - No infrastructure
- ✅ **Real-time** - Live tail
- ✅ **Search** - Fast search
- ✅ **Alerting** - Email, Slack
- ✅ **Easy setup** - 5 minutes

### 🚀 Installation

```bash
# Add to config/logging.php
'papertrail' => [
    'driver' => 'monolog',
    'level' => 'warning',
    'handler' => SyslogUdpHandler::class,
    'handler_with' => [
        'host' => env('PAPERTRAIL_URL'),
        'port' => env('PAPERTRAIL_PORT'),
    ],
],
```

**Access:** `https://papertrailapp.com`

### 💰 Cost
- **Starter**: $7/month (1 GB)
- **Professional**: $75/month (10 GB)

### 🎯 Best For
- Quick setup
- Cloud-first teams
- No infrastructure management

---

## 6. Sentry (Error Tracking) 💼

### 📊 Features
- ✅ **Error tracking** - Best-in-class
- ✅ **Stack traces** - With source code
- ✅ **Release tracking** - Deployment tracking
- ✅ **Performance** - Transaction tracing
- ✅ **Alerting** - Email, Slack, PagerDuty

### 🚀 Installation

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN
```

**Access:** `https://sentry.io`

### 💰 Cost
- **Free**: 5,000 events/month
- **Team**: $26/month (50,000 events)

### 🎯 Best For
- Error tracking
- Production monitoring
- Team collaboration

---

## 📊 Comparison Matrix

| Solution | GUI | Setup | Cost | Production | Best For |
|----------|-----|-------|------|------------|----------|
| **Laravel Log Viewer** | ✅ Simple | Easy | FREE | ✅ Yes | Quick viewing |
| **Laravel Pulse** | ✅ Modern | Easy | FREE | ✅ Yes | Metrics |
| **ELK Stack** | ✅ Advanced | Hard | FREE* | ✅ Yes | Large scale |
| **Graylog** | ✅ Good | Medium | FREE* | ✅ Yes | Centralized |
| **Papertrail** | ✅ Good | Easy | $7+ | ✅ Yes | Cloud |
| **Sentry** | ✅ Excellent | Easy | $0-26+ | ✅ Yes | Errors |

*Infrastructure costs apply

---

## 🏆 Recommended Setup for WHUSNET

### Tier 1: Essential (FREE)
```
✅ Monolog                    - Core logging
✅ Laravel Log Viewer         - GUI for logs
✅ Laravel Pulse              - Metrics dashboard
✅ Sentry Free Tier           - Error tracking
```

**Total Cost: FREE** 🎉

### Tier 2: Enhanced ($26/month)
```
✅ Monolog                    - Core logging
✅ Laravel Log Viewer         - GUI for logs
✅ Laravel Pulse              - Metrics dashboard
✅ Sentry Team Plan           - Advanced error tracking
```

**Total Cost: $26/month**

### Tier 3: Advanced ($100+/month)
```
✅ Monolog                    - Core logging
✅ ELK Stack                  - Advanced search & dashboards
✅ Laravel Pulse              - Metrics dashboard
✅ Sentry Business            - Full error tracking
```

**Total Cost: $100+/month**

---

## 🚀 Quick Start: Laravel Log Viewer

### Installation (5 minutes)

```bash
# 1. Install package
composer require opcodesio/log-viewer

# 2. Publish config
php artisan log-viewer:publish

# 3. Protect with auth
```

**config/log-viewer.php:**
```php
<?php

return [
    'route_path' => 'log-viewer',
    
    'middleware' => ['web', 'auth'],
    
    'authorize' => function ($request) {
        return $request->user() && 
               in_array($request->user()->role, ['owner', 'admin']);
    },
    
    'back_to_system_url' => '/dashboard',
    
    'max_log_size_to_display' => 104857600, // 100MB
];
```

**Access:** `https://yourdomain.com/log-viewer`

### Features
- 📁 View all log files
- 🔍 Search logs
- 🎨 Syntax highlighting
- 📊 Level filtering
- 📅 Date filtering
- 💾 Download logs
- 🔄 Auto-refresh
- 🌙 Dark mode

---

## 📋 Implementation Steps

### Step 1: Install Log Viewer (Recommended)

```bash
composer require opcodesio/log-viewer
php artisan log-viewer:publish
```

### Step 2: Secure Access

```php
// config/log-viewer.php
'middleware' => ['web', 'auth', 'role:owner'],
```

### Step 3: Install Pulse (Optional)

```bash
composer require laravel/pulse
php artisan pulse:install
php artisan migrate
```

### Step 4: Setup Sentry (Optional)

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN
```

### Step 5: Test

```bash
# Generate test logs
php artisan tinker
>>> Log::info('Test info log');
>>> Log::error('Test error log');

# Visit log viewer
# https://yourdomain.com/log-viewer
```

---

## 🎯 Final Recommendation

### For WHUSNET Admin Payment:

**Immediate (FREE):**
```bash
# 1. Install Log Viewer
composer require opcodesio/log-viewer
php artisan log-viewer:publish

# 2. Install Pulse
composer require laravel/pulse
php artisan pulse:install
php artisan migrate

# 3. Setup Sentry Free
composer require sentry/sentry-laravel
```

**Result:**
- ✅ Beautiful GUI for logs (Log Viewer)
- ✅ Real-time metrics (Pulse)
- ✅ Error tracking (Sentry)
- ✅ All FREE
- ✅ Production-ready

**Access:**
- Logs: `https://yourdomain.com/log-viewer`
- Metrics: `https://yourdomain.com/pulse`
- Errors: `https://sentry.io`

---

## 📚 Resources

- [Laravel Log Viewer (opcodesio)](https://github.com/opcodesio/log-viewer)
- [Laravel Log Viewer (rap2hpoutre)](https://github.com/rap2hpoutre/laravel-log-viewer)
- [Laravel Pulse](https://laravel.com/docs/pulse)
- [Sentry Laravel](https://docs.sentry.io/platforms/php/guides/laravel/)
- [ELK Stack](https://www.elastic.co/what-is/elk-stack)
- [Graylog](https://www.graylog.org/)

---

**Last Updated**: May 4, 2026
**Version**: 1.0
