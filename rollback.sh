#!/bin/bash

# ═══════════════════════════════════════════════════════════════════
#  WHUSNET Admin Payment - Rollback Script
#  Emergency rollback to previous version
# ═══════════════════════════════════════════════════════════════════

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKUP_DIR="./backups"

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

# Confirm rollback
confirm_rollback() {
    echo ""
    log_warning "⚠️  WARNING: This will rollback to the previous version"
    echo ""
    read -p "Are you sure you want to continue? (yes/no): " -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log_info "Rollback cancelled"
        exit 0
    fi
}

# Find latest backup
find_latest_backup() {
    LATEST_DB_BACKUP=$(ls -t ${BACKUP_DIR}/db_backup_*.sql.gz 2>/dev/null | head -n 1)
    LATEST_CODE_BACKUP=$(ls -t ${BACKUP_DIR}/code_backup_*.tar.gz 2>/dev/null | head -n 1)
    
    if [ -z "$LATEST_DB_BACKUP" ] || [ -z "$LATEST_CODE_BACKUP" ]; then
        log_error "No backups found in $BACKUP_DIR"
        exit 1
    fi
    
    log_info "Found database backup: $LATEST_DB_BACKUP"
    log_info "Found code backup: $LATEST_CODE_BACKUP"
}

# Restore database
restore_database() {
    log_info "Restoring database from backup..."
    
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
    
    # Create a backup of current state before restore
    EMERGENCY_BACKUP="${BACKUP_DIR}/emergency_backup_$(date +%Y%m%d_%H%M%S).sql.gz"
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$EMERGENCY_BACKUP"
    log_success "Created emergency backup: $EMERGENCY_BACKUP"
    
    # Restore from backup
    gunzip < "$LATEST_DB_BACKUP" | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
    
    log_success "Database restored"
}

# Restore code
restore_code() {
    log_info "Restoring code from backup..."
    
    # Extract backup
    tar -xzf "$LATEST_CODE_BACKUP"
    
    log_success "Code restored"
}

# Main rollback
main() {
    log_info "⏪ Starting rollback at $(date)"
    echo ""
    
    # Confirm
    confirm_rollback
    
    # Find backups
    find_latest_backup
    
    echo ""
    log_info "🔧 Putting application in maintenance mode..."
    php artisan down --render="errors::503"
    log_success "Maintenance mode enabled"
    
    echo ""
    log_info "📦 Restoring code..."
    restore_code
    
    echo ""
    log_info "📦 Installing dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    log_success "Dependencies installed"
    
    echo ""
    log_info "🗄️  Restoring database..."
    read -p "Do you want to restore the database? (yes/no): " -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        restore_database
    else
        log_warning "Database restore skipped"
    fi
    
    echo ""
    log_info "🗑️  Clearing caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    log_success "Caches cleared"
    
    echo ""
    log_info "⚡ Optimizing application..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    log_success "Application optimized"
    
    echo ""
    log_info "🔄 Restarting services..."
    
    if command -v docker-compose &> /dev/null; then
        docker-compose restart app horizon reverb
        log_success "Services restarted"
    else
        log_warning "Docker not found, skipping container restart"
    fi
    
    echo ""
    log_info "✨ Bringing application back online..."
    php artisan up
    log_success "Application is now live"
    
    echo ""
    log_success "🎉 Rollback completed at $(date)"
    echo ""
    log_warning "⚠️  Please verify the application is working correctly"
    echo ""
}

# Run main function
main

exit 0
