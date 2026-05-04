# 🏗️ CI/CD Architecture - GitHub Actions

## 📊 Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         DEVELOPER                                │
│                                                                   │
│  git add . && git commit -m "feat: ..." && git push origin main │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      GITHUB REPOSITORY                           │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Trigger: Push to 'main' branch                           │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     GITHUB ACTIONS RUNNER                        │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  JOB 1: TEST (2-3 min)                                   │   │
│  │  ├─ Setup PHP 8.4                                        │   │
│  │  ├─ Setup MySQL & Redis                                  │   │
│  │  ├─ Install Composer dependencies                        │   │
│  │  ├─ Run PHPUnit tests                                    │   │
│  │  └─ Security audit (composer audit)                      │   │
│  └─────────────────────────────────────────────────────────┘   │
│                             │                                     │
│                             ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  JOB 2: BUILD (3-5 min)                                  │   │
│  │  ├─ Setup Docker Buildx                                  │   │
│  │  ├─ Login to GitHub Container Registry                   │   │
│  │  ├─ Build Docker image (Dockerfile.prod)                 │   │
│  │  ├─ Tag: latest, main-{sha}                              │   │
│  │  └─ Push to ghcr.io/username/repo                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                             │                                     │
│                             ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  JOB 3: DEPLOY (2-3 min)                                 │   │
│  │  ├─ Setup SSH connection                                 │   │
│  │  ├─ Copy deployment files                                │   │
│  │  └─ Execute deployment script on server                  │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │ SSH Connection
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      PRODUCTION SERVER                           │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  DEPLOYMENT STEPS (Zero Downtime)                        │   │
│  │                                                           │   │
│  │  1. Pull new Docker image from GHCR                      │   │
│  │     docker-compose pull                                  │   │
│  │                                                           │   │
│  │  2. Backup database                                      │   │
│  │     mysqldump → backups/backup_YYYYMMDD.sql.gz           │   │
│  │                                                           │   │
│  │  3. Start new container (Blue-Green)                     │   │
│  │     docker-compose up -d --scale app=2                   │   │
│  │                                                           │   │
│  │  4. Health check new container                           │   │
│  │     curl http://new-container/health                     │   │
│  │                                                           │   │
│  │  5. Run migrations                                       │   │
│  │     php artisan migrate --force                          │   │
│  │                                                           │   │
│  │  6. Cache configs                                        │   │
│  │     php artisan config:cache                             │   │
│  │     php artisan route:cache                              │   │
│  │                                                           │   │
│  │  7. Switch NGINX to new container                        │   │
│  │     docker-compose up -d --force-recreate nginx          │   │
│  │                                                           │   │
│  │  8. Stop old container                                   │   │
│  │     docker stop old-container                            │   │
│  │                                                           │   │
│  │  9. Scale back to 1 container                            │   │
│  │     docker-compose up -d --scale app=1                   │   │
│  │                                                           │   │
│  │  10. Update background services                          │   │
│  │      docker-compose up -d horizon reverb scheduler       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  RUNNING CONTAINERS                                       │   │
│  │  ├─ whusnet-app (Laravel PHP-FPM)                        │   │
│  │  ├─ whusnet-nginx (Web Server)                           │   │
│  │  ├─ whusnet-db (MySQL 8.0)                               │   │
│  │  ├─ whusnet-redis (Cache & Queue)                        │   │
│  │  ├─ whusnet-horizon (Queue Worker)                       │   │
│  │  ├─ whusnet-reverb (WebSocket)                           │   │
│  │  └─ whusnet-scheduler (Cron Jobs)                        │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      NOTIFICATION (Optional)                     │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Slack Webhook                                            │   │
│  │  ✅ Deployment successful                                 │   │
│  │  Version: main-abc1234                                    │   │
│  │  Deployed by: username                                    │   │
│  │  Downtime: 0 seconds                                      │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Zero Downtime Deployment Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  BEFORE DEPLOYMENT                                               │
│                                                                   │
│  ┌──────────────┐                                                │
│  │   NGINX      │                                                │
│  └──────┬───────┘                                                │
│         │                                                         │
│         ▼                                                         │
│  ┌──────────────┐                                                │
│  │  OLD APP     │  ◄── Currently serving traffic                 │
│  │  Container   │                                                │
│  └──────────────┘                                                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  DURING DEPLOYMENT (Blue-Green)                                  │
│                                                                   │
│  ┌──────────────┐                                                │
│  │   NGINX      │                                                │
│  └──────┬───────┘                                                │
│         │                                                         │
│         ├────────────────┬────────────────┐                      │
│         ▼                ▼                                        │
│  ┌──────────────┐  ┌──────────────┐                             │
│  │  OLD APP     │  │  NEW APP     │  ◄── Health check            │
│  │  Container   │  │  Container   │      Running migrations      │
│  │  (Running)   │  │  (Starting)  │      Warming up              │
│  └──────────────┘  └──────────────┘                             │
│         │                ▲                                        │
│         │                │                                        │
│         │                └── If healthy, switch traffic          │
│         │                                                         │
│         └── Still serving traffic (NO DOWNTIME)                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  AFTER DEPLOYMENT                                                │
│                                                                   │
│  ┌──────────────┐                                                │
│  │   NGINX      │                                                │
│  └──────┬───────┘                                                │
│         │                                                         │
│         ▼                                                         │
│  ┌──────────────┐                                                │
│  │  NEW APP     │  ◄── Now serving traffic                       │
│  │  Container   │                                                │
│  └──────────────┘                                                │
│                                                                   │
│  ┌──────────────┐                                                │
│  │  OLD APP     │  ◄── Stopped & removed                         │
│  │  (Stopped)   │                                                │
│  └──────────────┘                                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔐 Security & Secrets Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  GITHUB REPOSITORY SECRETS                                       │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  SSH_PRIVATE_KEY    (RSA/ED25519 private key)           │   │
│  │  SERVER_HOST        (123.456.789.0)                      │   │
│  │  SERVER_USER        (root/ubuntu/deploy)                 │   │
│  │  ENV_FILE           (Complete .env production)           │   │
│  │  SLACK_WEBHOOK_URL  (https://hooks.slack.com/...)        │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  GITHUB ACTIONS RUNNER                                           │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Secrets are injected as environment variables           │   │
│  │  ├─ ${{ secrets.SSH_PRIVATE_KEY }}                       │   │
│  │  ├─ ${{ secrets.SERVER_HOST }}                           │   │
│  │  ├─ ${{ secrets.SERVER_USER }}                           │   │
│  │  └─ ${{ secrets.ENV_FILE }}                              │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  SSH CONNECTION (Encrypted)                                      │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  ssh -i $SSH_PRIVATE_KEY $SERVER_USER@$SERVER_HOST       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  PRODUCTION SERVER                                               │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  .env file is created from ENV_FILE secret               │   │
│  │  ├─ APP_KEY (encrypted)                                  │   │
│  │  ├─ DB_PASSWORD (encrypted)                              │   │
│  │  ├─ REDIS_PASSWORD (encrypted)                           │   │
│  │  └─ API_KEYS (encrypted)                                 │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📦 Docker Image Registry Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  BUILD STAGE (GitHub Actions Runner)                            │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. Build Docker image from Dockerfile.prod              │   │
│  │     docker build -t whusnet-app:latest .                 │   │
│  │                                                           │   │
│  │  2. Tag with multiple tags                               │   │
│  │     - ghcr.io/username/repo:latest                       │   │
│  │     - ghcr.io/username/repo:main-abc1234                 │   │
│  │                                                           │   │
│  │  3. Login to GitHub Container Registry                   │   │
│  │     echo $GITHUB_TOKEN | docker login ghcr.io            │   │
│  │                                                           │   │
│  │  4. Push to registry                                     │   │
│  │     docker push ghcr.io/username/repo:latest             │   │
│  │     docker push ghcr.io/username/repo:main-abc1234       │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  GITHUB CONTAINER REGISTRY (ghcr.io)                            │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  ghcr.io/username/repo                                   │   │
│  │  ├─ latest (always newest)                               │   │
│  │  ├─ main-abc1234 (specific commit)                       │   │
│  │  ├─ main-def5678 (previous commit)                       │   │
│  │  └─ main-ghi9012 (older commit)                          │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  PRODUCTION SERVER                                               │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. Login to registry                                    │   │
│  │     echo $GITHUB_TOKEN | docker login ghcr.io            │   │
│  │                                                           │   │
│  │  2. Pull image                                           │   │
│  │     docker-compose pull                                  │   │
│  │                                                           │   │
│  │  3. Start containers                                     │   │
│  │     docker-compose up -d                                 │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Rollback Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│  ROLLBACK TRIGGER                                                │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Option 1: Manual via SSH                                │   │
│  │  Option 2: GitHub Actions workflow_dispatch              │   │
│  │  Option 3: Automated (if health check fails)             │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  ROLLBACK STEPS                                                  │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. Identify previous version                            │   │
│  │     docker images | grep whusnet-app                     │   │
│  │                                                           │   │
│  │  2. Pull previous image                                  │   │
│  │     export APP_VERSION=main-def5678                      │   │
│  │     docker-compose pull                                  │   │
│  │                                                           │   │
│  │  3. Stop current containers                              │   │
│  │     docker-compose down                                  │   │
│  │                                                           │   │
│  │  4. Restore database (if needed)                         │   │
│  │     gunzip < backups/backup_YYYYMMDD.sql.gz | mysql      │   │
│  │                                                           │   │
│  │  5. Start previous version                               │   │
│  │     docker-compose up -d                                 │   │
│  │                                                           │   │
│  │  6. Clear cache                                          │   │
│  │     php artisan cache:clear                              │   │
│  │     php artisan config:clear                             │   │
│  │                                                           │   │
│  │  7. Health check                                         │   │
│  │     curl http://localhost/health                         │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📊 Monitoring & Logging

```
┌─────────────────────────────────────────────────────────────────┐
│  MONITORING LAYERS                                               │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Layer 1: GitHub Actions                                 │   │
│  │  ├─ Workflow status (success/failure)                    │   │
│  │  ├─ Job duration                                         │   │
│  │  ├─ Test results                                         │   │
│  │  └─ Build logs                                           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Layer 2: Docker Containers                              │   │
│  │  ├─ Container health checks                              │   │
│  │  ├─ Resource usage (CPU, Memory)                         │   │
│  │  ├─ Container logs                                       │   │
│  │  └─ Network connectivity                                 │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Layer 3: Application                                    │   │
│  │  ├─ Health endpoints (/ping, /health)                    │   │
│  │  ├─ Laravel logs (storage/logs/laravel.log)              │   │
│  │  ├─ Horizon dashboard                                    │   │
│  │  └─ Queue metrics                                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Layer 4: Notifications                                  │   │
│  │  ├─ Slack webhooks                                       │   │
│  │  ├─ Email alerts                                         │   │
│  │  └─ Custom webhooks                                      │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎯 Key Benefits

### 1. **Zero Downtime**
- Blue-Green deployment strategy
- Traffic switches only after health check passes
- Old container keeps serving during deployment

### 2. **Automated Testing**
- PHPUnit tests run before deployment
- Security audit (composer audit)
- Prevents broken code from reaching production

### 3. **Rollback Safety**
- Automatic database backups before deployment
- Previous Docker images retained
- Quick rollback in case of issues

### 4. **Visibility**
- Real-time logs in GitHub Actions
- Slack notifications
- Health check endpoints

### 5. **Security**
- Secrets encrypted in GitHub
- SSH key-based authentication
- No credentials in code

