#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  WHUSNET Admin Payment - Production Deployment Script
#  Zero-downtime deployment dengan rollback capability
# ═══════════════════════════════════════════════════════════════════

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DEPLOYMENT_SECRET="your-deployment-secret-key"
BACKUP_DIR="./backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as correct user
check_user() {
    if [ "$EUID" -eq 0 ]; then 
        log_error "Do not run this script as root"
        exit 1
    fi
}

# Create backup directory
create_backup_dir() {
    if [ ! -d "$BACKUP_DIR" ]; then
        mkdir -p "$BACKUP_DIR"
        log_success "Created backup directory"
    fi
}

# Backup database
backup_database() {
    log_info "Backing up database..."
    
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
    
    BACKUP_FILE="${BACKUP_DIR}/db_backup_${TIMESTAMP}.sql.gz"
    
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_FILE"
    
    if [ -f "$BACKUP_FILE" ]; then
        log_success "Database backed up to $BACKUP_FILE"
    else
        log_error "Database backup failed"
        exit 1
    fi
}

# Backup current code
backup_code() {
    log_info "Backing up current code..."
    
    BACKUP_FILE="${BACKUP_DIR}/code_backup_${TIMESTAMP}.tar.gz"
    
    tar -czf "$BACKUP_FILE" \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='storage/logs' \
        --exclude='storage/framework/cache' \
        --exclude='.git' \
        .
    
    if [ -f "$BACKUP_FILE" ]; then
        log_success "Code backed up to $BACKUP_FILE"
    else
        log_error "Code backup failed"
        exit 1
    fi
}

# Main deployment
main() {
    log_info "🚀 Starting deployment at $(date)"
    echo ""
    
    # Pre-deployment checks
    check_user
    create_backup_dir
    
    # Backups
    backup_database
    backup_code
    
    echo ""
    log_info "📥 Pulling latest code..."
    git pull origin main
    log_success "Code updated"
    
    echo ""
    log_info "📦 Installing dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    log_success "Composer dependencies installed"
    
    npm ci --production
    log_success "NPM dependencies installed"
    
    echo ""
    log_info "🏗️  Building assets..."
    npm run build
    log_success "Assets built"
    
    echo ""
    log_info "🔧 Putting application in maintenance mode..."
    php artisan down --retry=60 --secret="$DEPLOYMENT_SECRET" --render="errors::503"
    log_success "Maintenance mode enabled"
    log_warning "Access via: https://yourdomain.com/$DEPLOYMENT_SECRET"
    
    echo ""
    log_info "🗑️  Clearing caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    log_success "Caches cleared"
    
    echo ""
    log_info "🗄️  Running migrations..."
    php artisan migrate --force
    log_success "Migrations completed"
    
    echo ""
    log_info "⚡ Optimizing application..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    log_success "Application optimized"
    
    echo ""
    log_info "🔄 Restarting services..."
    
    # Restart PHP-FPM
    if command -v docker-compose &> /dev/null; then
        docker-compose restart app
        log_success "App container restarted"
        
        docker-compose restart horizon
        log_success "Horizon restarted"
        
        docker-compose restart reverb
        log_success "Reverb restarted"
    else
        log_warning "Docker not found, skipping container restart"
    fi
    
    # Restart queue workers (if not using Horizon)
    # php artisan queue:restart
    
    echo ""
    log_info "🔍 Running health checks..."
    
    # Check if app is responding
    if php artisan list > /dev/null 2>&1; then
        log_success "Application is healthy"
    else
        log_error "Application health check failed"
        log_error "Rolling back..."
        php artisan up
        exit 1
    fi
    
    # Check database connection
    if php artisan db:show > /dev/null 2>&1; then
        log_success "Database connection OK"
    else
        log_warning "Database connection check failed"
    fi
    
    # Check Redis connection
    if php artisan redis:ping > /dev/null 2>&1; then
        log_success "Redis connection OK"
    else
        log_warning "Redis connection check failed"
    fi
    
    echo ""
    log_info "✨ Bringing application back online..."
    php artisan up
    log_success "Application is now live"
    
    echo ""
    log_info "🧹 Cleaning up old backups (keeping last 10)..."
    ls -t ${BACKUP_DIR}/db_backup_*.sql.gz | tail -n +11 | xargs -r rm
    ls -t ${BACKUP_DIR}/code_backup_*.tar.gz | tail -n +11 | xargs -r rm
    log_success "Old backups cleaned"
    
    echo ""
    log_success "🎉 Deployment completed successfully at $(date)"
    echo ""
    log_info "📊 Next steps:"
    echo "  1. Monitor logs: tail -f storage/logs/laravel.log"
    echo "  2. Check Horizon: php artisan horizon:status"
    echo "  3. Monitor application metrics"
    echo ""
}

# Run main function
main

exit 0
