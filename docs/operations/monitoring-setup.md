# 📊 Monitoring & Alerting Setup Guide

## Health Check Endpoints

Project ini sudah dilengkapi dengan health check endpoints yang dapat digunakan untuk monitoring:

### 1. `/ping` - Basic Health Check
- **Purpose**: Quick check tanpa hit database
- **Response Time**: < 10ms
- **Use Case**: Load balancer health check

```bash
curl https://yourdomain.com/ping
# Response: {"status":"ok"}
```

### 2. `/health` - Detailed Health Check
- **Purpose**: Check semua services (DB, Redis, Queue, Storage)
- **Response Time**: < 500ms
- **Use Case**: Monitoring dashboard, alerting

```bash
curl https://yourdomain.com/health
```

**Response Example:**
```json
{
  "status": "healthy",
  "timestamp": "2026-05-04T10:30:00+07:00",
  "services": {
    "database": {
      "status": "connected",
      "connection": "mysql"
    },
    "redis": {
      "status": "connected",
      "memory_usage": "45.2M"
    },
    "queue": {
      "status": "running",
      "supervisors": 1
    },
    "storage": {
      "status": "ok",
      "used_percent": 45.5,
      "free_space": "25.3 GB"
    }
  }
}
```

**Status Codes:**
- `200` - healthy atau degraded (masih operational)
- `503` - unhealthy (critical services down)

### 3. `/ready` - Readiness Check
- **Purpose**: Check apakah app siap menerima traffic
- **Use Case**: Kubernetes readiness probe, load balancer

```bash
curl https://yourdomain.com/ready
```

### 4. `/alive` - Liveness Check
- **Purpose**: Check apakah app masih hidup
- **Use Case**: Kubernetes liveness probe

```bash
curl https://yourdomain.com/alive
```

### 5. `/metrics` - Metrics Endpoint
- **Purpose**: Expose metrics untuk monitoring tools
- **Use Case**: Prometheus, Grafana, custom monitoring

```bash
curl https://yourdomain.com/metrics
```

**Response Example:**
```json
{
  "database_size_mb": 1250.45,
  "database_connections": 12,
  "redis_memory_used_mb": 45.2,
  "redis_connected_clients": 8,
  "redis_total_commands": 1234567,
  "queue_pending": 5,
  "queue_failed": 0,
  "app_version": "1.0.0",
  "app_env": "production",
  "php_version": "8.4.0",
  "laravel_version": "12.0.0"
}
```

---

## Monitoring Tools Setup

### Option 1: UptimeRobot (Free, Simple)

1. **Sign up**: https://uptimerobot.com
2. **Add Monitor**:
   - Monitor Type: HTTP(s)
   - URL: `https://yourdomain.com/health`
   - Monitoring Interval: 5 minutes
   - Alert Contacts: Email, SMS, Slack

3. **Alert Conditions**:
   - Status Code is not 200
   - Response contains "unhealthy"
   - Response time > 5000ms

### Option 2: Pingdom (Paid, Advanced)

1. **Sign up**: https://pingdom.com
2. **Create Check**:
   - Check Type: HTTP
   - URL: `https://yourdomain.com/health`
   - Check Interval: 1 minute

3. **Advanced Settings**:
   - Response validation: Contains "healthy"
   - Alert after: 2 consecutive failures
   - Integrations: Slack, PagerDuty, Email

### Option 3: New Relic (APM + Monitoring)

1. **Install New Relic PHP Agent**:
```bash
# Add to Dockerfile
RUN curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-10.x.x.x-linux.tar.gz | tar -C /tmp -zx \
    && export NR_INSTALL_USE_CP_NOT_LN=1 \
    && export NR_INSTALL_SILENT=1 \
    && /tmp/newrelic-php5-*/newrelic-install install \
    && rm -rf /tmp/newrelic-php5-*
```

2. **Configure** (`/usr/local/etc/php/conf.d/newrelic.ini`):
```ini
extension=newrelic.so
newrelic.license=YOUR_LICENSE_KEY
newrelic.appname=WHUSNET Admin Payment
newrelic.daemon.address=/tmp/.newrelic.sock
newrelic.loglevel=info
```

3. **Add to .env**:
```env
NEWRELIC_LICENSE_KEY=your_license_key
NEWRELIC_APPNAME=WHUSNET Admin Payment
```

### Option 4: Sentry (Error Tracking)

1. **Install**:
```bash
composer require sentry/sentry-laravel
```

2. **Publish config**:
```bash
php artisan sentry:publish --dsn=YOUR_SENTRY_DSN
```

3. **Add to .env**:
```env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.2
```

4. **Test**:
```bash
php artisan sentry:test
```

### Option 5: Laravel Pulse (Built-in)

1. **Install**:
```bash
composer require laravel/pulse
php artisan pulse:install
php artisan migrate
```

2. **Configure** (`config/pulse.php`):
```php
'recorders' => [
    Recorders\CacheInteractions::class => [
        'enabled' => env('PULSE_CACHE_INTERACTIONS_ENABLED', true),
    ],
    Recorders\Exceptions::class => [
        'enabled' => env('PULSE_EXCEPTIONS_ENABLED', true),
    ],
    Recorders\Queues::class => [
        'enabled' => env('PULSE_QUEUES_ENABLED', true),
    ],
    Recorders\SlowJobs::class => [
        'enabled' => env('PULSE_SLOW_JOBS_ENABLED', true),
        'threshold' => 1000, // ms
    ],
    Recorders\SlowQueries::class => [
        'enabled' => env('PULSE_SLOW_QUERIES_ENABLED', true),
        'threshold' => 1000, // ms
    ],
    Recorders\SlowRequests::class => [
        'enabled' => env('PULSE_SLOW_REQUESTS_ENABLED', true),
        'threshold' => 1000, // ms
    ],
],
```

3. **Access Dashboard**:
```
https://yourdomain.com/pulse
```

---

## Alerting Setup

### Slack Notifications

1. **Create Slack Webhook**:
   - Go to: https://api.slack.com/apps
   - Create New App → Incoming Webhooks
   - Copy Webhook URL

2. **Add to .env**:
```env
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

3. **Configure Logging** (`config/logging.php`):
```php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'WHUSNET Alert',
    'emoji' => ':rotating_light:',
    'level' => 'critical',
],

'stack' => [
    'driver' => 'stack',
    'channels' => ['daily', 'slack'],
],
```

4. **Test**:
```php
Log::critical('Test critical alert', ['test' => true]);
```

### Email Notifications

1. **Configure Mail** (`.env`):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

2. **Create Notification**:
```bash
php artisan make:notification SystemAlert
```

3. **Send Alert**:
```php
use App\Notifications\SystemAlert;

// Send to admin
User::where('role', 'admin')->each(function ($admin) {
    $admin->notify(new SystemAlert('Critical: Database connection lost'));
});
```

### PagerDuty Integration

1. **Install**:
```bash
composer require pagerduty/pagerduty
```

2. **Create Service** in PagerDuty dashboard

3. **Add to .env**:
```env
PAGERDUTY_INTEGRATION_KEY=your_integration_key
```

4. **Create Alert Helper**:
```php
// app/Services/PagerDutyService.php
use PagerDuty\Event;

class PagerDutyService
{
    public function trigger(string $description, array $details = [])
    {
        $event = new Event(config('services.pagerduty.key'));
        $event->trigger($description, $details);
    }
}
```

---

## Custom Monitoring Script

Create `monitor.sh` untuk monitoring manual:

```bash
#!/bin/bash

# Monitor script
DOMAIN="https://yourdomain.com"
SLACK_WEBHOOK="YOUR_SLACK_WEBHOOK_URL"

# Check health
HEALTH=$(curl -s "$DOMAIN/health")
STATUS=$(echo $HEALTH | jq -r '.status')

if [ "$STATUS" != "healthy" ]; then
    # Send alert to Slack
    curl -X POST $SLACK_WEBHOOK \
        -H 'Content-Type: application/json' \
        -d "{
            \"text\": \":rotating_light: ALERT: Application is $STATUS\",
            \"attachments\": [{
                \"color\": \"danger\",
                \"text\": \`$HEALTH\`
            }]
        }"
fi

# Check response time
RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' "$DOMAIN/ping")
if (( $(echo "$RESPONSE_TIME > 1.0" | bc -l) )); then
    echo "WARNING: Slow response time: ${RESPONSE_TIME}s"
fi
```

Add to crontab:
```bash
# Run every 5 minutes
*/5 * * * * /path/to/monitor.sh
```

---

## Grafana Dashboard Setup

### 1. Install Prometheus Exporter

```bash
composer require ensi/laravel-prometheus
php artisan vendor:publish --provider="Ensi\LaravelPrometheus\ServiceProvider"
```

### 2. Configure Metrics

```php
// config/prometheus.php
return [
    'namespace' => 'whusnet',
    'metrics' => [
        'http_requests_total' => [
            'type' => 'counter',
            'help' => 'Total HTTP requests',
            'labels' => ['method', 'route', 'status'],
        ],
        'http_request_duration_seconds' => [
            'type' => 'histogram',
            'help' => 'HTTP request duration',
            'labels' => ['method', 'route'],
        ],
    ],
];
```

### 3. Expose Metrics Endpoint

```php
// routes/web.php
Route::get('/prometheus/metrics', function () {
    return app(\Prometheus\CollectorRegistry::class)->getMetricFamilySamples();
});
```

### 4. Configure Prometheus

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'whusnet'
    scrape_interval: 30s
    static_configs:
      - targets: ['yourdomain.com']
    metrics_path: '/prometheus/metrics'
```

### 5. Import Grafana Dashboard

Use template: https://grafana.com/grafana/dashboards/11074

---

## Recommended Alerts

### Critical Alerts (Immediate Action)
- Application down (health check fails)
- Database connection lost
- Redis connection lost
- Disk space > 90%
- Memory usage > 90%
- Error rate > 1%

### Warning Alerts (Monitor)
- Response time > 1s
- Queue size > 1000
- Failed jobs > 10
- Disk space > 80%
- Memory usage > 80%

### Info Alerts (FYI)
- Deployment completed
- Backup completed
- Scheduled task completed

---

## Monitoring Checklist

- [ ] Setup uptime monitoring (UptimeRobot/Pingdom)
- [ ] Setup error tracking (Sentry)
- [ ] Setup APM (New Relic/Datadog)
- [ ] Configure Slack alerts
- [ ] Configure email alerts
- [ ] Setup log aggregation
- [ ] Create Grafana dashboards
- [ ] Test all alerts
- [ ] Document escalation procedures
- [ ] Train team on monitoring tools

---

**Last Updated**: May 4, 2026
