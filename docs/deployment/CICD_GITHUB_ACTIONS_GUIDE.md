# 🔄 CI/CD with GitHub Actions - Complete Guide

## 📋 Overview

Project ini menggunakan **GitHub Actions** untuk automated CI/CD pipeline:
- ✅ Automated testing on every PR
- ✅ Security scanning (dependencies, SAST, Docker)
- ✅ Automated deployment to production
- ✅ Docker image building & publishing
- ✅ Slack notifications
- ✅ Rollback capability

---

## 🏗️ Pipeline Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    GitHub Actions Pipeline                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Pull Request → Test → Code Quality → Security Scan         │
│                   ↓                                          │
│  Push to main → Test → Build Docker → Deploy → Notify       │
│                                                              │
│  Schedule → Security Scan → Notify                          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 Workflows

### 1. **test.yml** - Automated Testing

**Triggers:**
- Pull requests to `main` or `develop`
- Push to `develop` 

**Jobs:**

#### A. PHPUnit Tests
```yaml
- PHP 8.4
- MySQL 8.0
- Redis 7.2
- Run migrations
- Run tests with coverage
- Upload to Codecov
```

#### B. Code Quality
```yaml
- Laravel Pint (code style)
- Composer security audit
```

#### C. Frontend Tests
```yaml
- Node.js 22
- NPM tests
- Build assets
- NPM security audit
```

**Example:**
```bash
# Triggered automatically on PR
git checkout -b feature/new-feature
git push origin feature/new-feature
# Creates PR → Tests run automatically
```

---

### 2. **security-scan.yml** - Security Scanning

**Triggers:**
- Daily at 2 AM UTC (scheduled)
- Manual trigger (workflow_dispatch)

**Jobs:**

#### A. Dependency Scanning
```yaml
- Composer audit (PHP dependencies)
- NPM audit (Node dependencies)
- Upload results as artifacts
```

#### B. SAST (Static Analysis)
```yaml
- Trivy filesystem scan
- Upload to GitHub Security
```

#### C. Docker Image Scanning
```yaml
- Build Docker image
- Trivy container scan
- Upload to GitHub Security
```

#### D. Notifications
```yaml
- Send results to Slack
- Alert on vulnerabilities
```

**Manual Trigger:**
```bash
# Via GitHub UI
Actions → Security Scan → Run workflow

# Via GitHub CLI
gh workflow run security-scan.yml
```

---

### 3. **deploy-production.yml** - Production Deployment

**Triggers:**
- Push to `main` branch
- Manual trigger with environment selection

**Jobs:**

#### A. Test
```yaml
- Run full test suite
- Security audit
- Must pass before deployment
```

#### B. Build Docker Image
```yaml
- Multi-stage build
- Push to GitHub Container Registry (ghcr.io)
- Tag with branch, SHA, and 'latest'
- Cache layers for faster builds
```

#### C. Deploy to Server
```yaml
- SSH to production server
- Pull latest Docker images
- Backup database
- Stop services gracefully
- Start new containers
- Run migrations
- Clear & rebuild caches
- Health check
- Notify success/failure
```

**Deployment Flow:**
```bash
# Automatic deployment
git push origin main
# → Tests run → Build image → Deploy → Notify

# Manual deployment
# Via GitHub UI: Actions → Deploy to Production → Run workflow
```

---

## 🔐 Required Secrets

Setup these secrets in GitHub repository settings:
**Settings → Secrets and variables → Actions → New repository secret**

### Server Access
```
SSH_PRIVATE_KEY       # SSH private key for server access
SERVER_HOST           # Server IP or hostname (e.g., 192.168.1.100)
SERVER_USER           # SSH username (e.g., ubuntu, root)
```

### Application
```
ENV_FILE              # Complete .env file content for production
GITHUB_TOKEN          # Auto-provided by GitHub (no setup needed)
```

### Notifications
```
SLACK_WEBHOOK_URL     # Slack webhook for notifications
```

### Optional (for advanced features)
```
CODECOV_TOKEN         # For code coverage reports
SENTRY_AUTH_TOKEN     # For Sentry releases
```

---

## 🚀 Setup Instructions

### Step 1: Generate SSH Key

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github-actions

# Copy public key to server
ssh-copy-id -i ~/.ssh/github-actions.pub user@server

# Test connection
ssh -i ~/.ssh/github-actions user@server

# Copy private key content
cat ~/.ssh/github-actions
# Copy the entire output (including BEGIN and END lines)
```

### Step 2: Add Secrets to GitHub

1. Go to your repository on GitHub
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret:

**SSH_PRIVATE_KEY:**
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
...
-----END OPENSSH PRIVATE KEY-----
```

**SERVER_HOST:**
```
192.168.1.100
```

**SERVER_USER:**
```
ubuntu
```

**ENV_FILE:**
```
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
...
(entire .env file content)
```

**SLACK_WEBHOOK_URL:**
```
https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX
```

### Step 3: Setup Slack Webhook

1. Go to https://api.slack.com/apps
2. Create New App → From scratch
3. Add features → Incoming Webhooks
4. Activate Incoming Webhooks
5. Add New Webhook to Workspace
6. Select channel (e.g., #deployments)
7. Copy Webhook URL
8. Add to GitHub Secrets as `SLACK_WEBHOOK_URL`

### Step 4: Prepare Production Server

```bash
# SSH to server
ssh user@server

# Install Docker & Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Create deployment directory
sudo mkdir -p /var/www/admin-payment
sudo chown $USER:$USER /var/www/admin-payment

# Create backups directory
mkdir -p /var/www/admin-payment/backups

# Test Docker
docker --version
docker-compose --version
```

### Step 5: Test Deployment

```bash
# Create a test branch
git checkout -b test-deployment

# Make a small change
echo "# Test" >> README.md

# Commit and push
git add .
git commit -m "Test deployment"
git push origin test-deployment

# Create PR and check if tests pass
# If tests pass, merge to main
# Deployment will trigger automatically
```

---

## 📊 Monitoring Deployments

### GitHub Actions UI

1. Go to **Actions** tab in your repository
2. See all workflow runs
3. Click on a run to see details
4. View logs for each job
5. Download artifacts (test results, coverage, etc.)

### Slack Notifications

You'll receive notifications for:
- ✅ Deployment success
- ❌ Deployment failure
- ⚠️ Security vulnerabilities detected
- 📊 Test results

**Example Notification:**
```
✅ Production deployment successful

Environment: Production
Version: abc1234
Deployed by: username
Duration: 5m 32s
```

### Server Logs

```bash
# SSH to server
ssh user@server

# View deployment logs
cd /var/www/admin-payment
docker-compose logs -f

# View application logs
docker-compose exec app tail -f storage/logs/laravel.log
```

---

## 🔄 Deployment Workflow

### Automatic Deployment (Recommended)

```bash
# 1. Create feature branch
git checkout -b feature/new-feature

# 2. Make changes
# ... edit files ...

# 3. Commit and push
git add .
git commit -m "Add new feature"
git push origin feature/new-feature

# 4. Create Pull Request
# → Tests run automatically
# → Code review
# → Merge to main

# 5. Automatic deployment
# → Tests run again
# → Build Docker image
# → Deploy to production
# → Slack notification
```

### Manual Deployment

```bash
# Via GitHub UI
1. Go to Actions tab
2. Select "Deploy to Production"
3. Click "Run workflow"
4. Select branch (usually main)
5. Click "Run workflow"

# Via GitHub CLI
gh workflow run deploy-production.yml
```

### Rollback

```bash
# Option 1: Via GitHub Actions
1. Go to Actions tab
2. Select "Deploy to Production"
3. Click "Run workflow"
4. Select "rollback" option
5. Click "Run workflow"

# Option 2: Manual SSH
ssh user@server
cd /var/www/admin-payment
./rollback.sh
```

---

## 🧪 Testing CI/CD Pipeline

### Test Locally with Act

```bash
# Install act (GitHub Actions local runner)
# macOS
brew install act

# Linux
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash

# Run tests locally
act pull_request

# Run specific workflow
act -W .github/workflows/test.yml

# Run with secrets
act -s GITHUB_TOKEN=your_token
```

### Test Deployment to Staging

```bash
# Create staging environment
# Copy deploy-production.yml to deploy-staging.yml
# Change environment to staging

# Deploy to staging
git push origin develop
# → Triggers staging deployment
```

---

## 🔧 Customization

### Add New Test

Edit `.github/workflows/test.yml`:

```yaml
- name: Run custom test
  run: php artisan test:custom
```

### Add New Deployment Step

Edit `.github/workflows/deploy-production.yml`:

```yaml
- name: Custom deployment step
  run: |
    ssh ${{ secrets.SERVER_USER }}@${{ secrets.SERVER_HOST }} << 'EOF'
      cd /var/www/admin-payment
      # Your custom commands here
    EOF
```

### Change Notification Format

Edit notification step:

```yaml
- name: Notify deployment success
  run: |
    curl -X POST ${{ secrets.SLACK_WEBHOOK_URL }} \
      -H 'Content-Type: application/json' \
      -d '{
        "text": "Custom notification message",
        "attachments": [{
          "color": "good",
          "fields": [
            {"title": "Custom Field", "value": "Custom Value"}
          ]
        }]
      }'
```

### Add Environment Variables

```yaml
env:
  CUSTOM_VAR: value
  
jobs:
  deploy:
    env:
      JOB_VAR: value
    steps:
      - name: Use env var
        run: echo $CUSTOM_VAR
```

---

## 🐛 Troubleshooting

### Deployment Fails

```bash
# Check GitHub Actions logs
# Go to Actions tab → Failed run → View logs

# Common issues:
1. SSH connection failed
   → Check SSH_PRIVATE_KEY secret
   → Verify SERVER_HOST and SERVER_USER
   → Test SSH manually: ssh -i key user@host

2. Docker build failed
   → Check Dockerfile.prod syntax
   → Verify all dependencies are available
   → Check build logs in Actions

3. Migration failed
   → Check database connection
   → Verify .env file on server
   → Check migration files

4. Health check failed
   → Check application logs
   → Verify all services are running
   → Test health endpoint manually
```

### Tests Fail

```bash
# Run tests locally
php artisan test

# Check specific test
php artisan test --filter=TestName

# Check test logs in GitHub Actions
# Actions tab → Failed run → Test job → View logs
```

### Security Scan Fails

```bash
# Check vulnerabilities
composer audit
npm audit

# Fix vulnerabilities
composer update
npm audit fix

# If false positive, add to ignore list
```

### Slack Notifications Not Working

```bash
# Test webhook manually
curl -X POST $SLACK_WEBHOOK_URL \
  -H 'Content-Type: application/json' \
  -d '{"text":"Test message"}'

# Verify webhook URL in GitHub Secrets
# Regenerate webhook if needed
```

---

## 📈 Best Practices

### 1. Branch Strategy

```
main (production)
  ↑
develop (staging)
  ↑
feature/* (development)
```

### 2. Commit Messages

```bash
# Good
git commit -m "feat: add user authentication"
git commit -m "fix: resolve login bug"
git commit -m "docs: update README"

# Bad
git commit -m "update"
git commit -m "fix bug"
```

### 3. Pull Request Process

1. Create feature branch
2. Make changes
3. Write tests
4. Push and create PR
5. Wait for CI to pass
6. Request code review
7. Address feedback
8. Merge to develop
9. Test in staging
10. Merge to main (production)

### 4. Deployment Schedule

- **Avoid**: Friday afternoons, holidays
- **Best**: Tuesday-Thursday mornings
- **Always**: Have someone on-call

### 5. Monitoring

- Monitor for 1 hour after deployment
- Check error rates
- Verify critical features
- Be ready to rollback

---

## 📚 Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Slack Incoming Webhooks](https://api.slack.com/messaging/webhooks)
- [SSH Key Management](https://docs.github.com/en/authentication/connecting-to-github-with-ssh)

---

## ✅ Checklist

### Initial Setup
- [ ] Generate SSH key
- [ ] Add SSH key to server
- [ ] Add secrets to GitHub
- [ ] Setup Slack webhook
- [ ] Prepare production server
- [ ] Test SSH connection
- [ ] Test deployment to staging

### Before Each Deployment
- [ ] All tests passing
- [ ] Code reviewed
- [ ] Changelog updated
- [ ] Database migrations tested
- [ ] Backup strategy confirmed
- [ ] Rollback plan ready
- [ ] Team notified

### After Deployment
- [ ] Health check passed
- [ ] Critical features tested
- [ ] Error logs checked
- [ ] Performance metrics normal
- [ ] Team notified
- [ ] Documentation updated

---

**Last Updated**: May 4, 2026  
**Version**: 1.0

**Happy deploying! 🚀**
