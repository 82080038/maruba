#!/bin/bash

# Maruba Production Deployment Script
# Deploys the application to production environment

set -e

echo "🚀 Maruba Production Deployment"
echo "=========================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}=== $1 ===${NC}"
}

# Configuration
PROD_ENV_FILE="/opt/lampp/htdocs/maruba/.env.production"
BACKUP_DIR="/opt/lampp/htdocs/maruba/backups"
DEPLOY_LOG="/opt/lampp/htdocs/maruba/deploy.log"

# Function to create backup
create_backup() {
    print_header "Creating Backup"
    
    local backup_name="backup_$(date +%Y%m%d_%H%M%S)"
    local backup_path="$BACKUP_DIR/$backup_name"
    
    mkdir -p "$backup_path"
    
    # Backup database
    print_status "Backing up database..."
    mysqldump -u root -proot --single-transaction --routines --triggers maruba > "$backup_path/database.sql"
    
    # Backup application files
    print_status "Backing up application files..."
    tar -czf "$backup_path/application.tar.gz" \
        --exclude='.git' \
        --exclude='node_modules' \
        --exclude='testing' \
        --exclude='*.log' \
        --exclude='backups' \
        --exclude='ssl' \
        .
    
    # Backup configuration
    cp .env "$backup_path/.env.backup" 2>/dev/null || true
    
    print_success "Backup created: $backup_path"
    echo "$backup_name" > "$BACKUP_DIR/latest_backup.txt"
}

# Function to validate production environment
validate_production_env() {
    print_header "Validating Production Environment"
    
    # Check if production env file exists
    if [ ! -f "$PROD_ENV_FILE" ]; then
        print_error "Production environment file not found: $PROD_ENV_FILE"
        return 1
    fi
    
    # Check required services
    print_status "Checking required services..."
    
    if ! pgrep -f apache2 > /dev/null && ! pgrep -f httpd > /dev/null; then
        print_error "Apache is not running"
        return 1
    fi
    
    if ! pgrep -f mysqld > /dev/null; then
        print_error "MySQL is not running"
        return 1
    fi
    
    # Check database connection
    print_status "Testing database connection..."
    if ! mysql -u root -proot -e "SELECT 1" maruba > /dev/null 2>&1; then
        print_error "Cannot connect to database"
        return 1
    fi
    
    # Check SSL certificate
    if [ ! -f "ssl/maruba.crt" ] || [ ! -f "ssl/maruba.key" ]; then
        print_warning "SSL certificates not found. Running SSL setup..."
        ./ssl_setup.sh
    fi
    
    print_success "Production environment validated"
    return 0
}

# Function to update configuration
update_configuration() {
    print_header "Updating Configuration"
    
    # Copy production environment file
    if [ -f "$PROD_ENV_FILE" ]; then
        cp "$PROD_ENV_FILE" .env
        print_success "Production environment file loaded"
    else
        print_warning "Production environment file not found, using current .env"
    fi
    
    # Set production settings
    sed -i 's/APP_ENV=development/APP_ENV=production/' .env
    sed -i 's/DEBUG=true/DEBUG=false/' .env
    sed -i 's/LOG_LEVEL=debug/LOG_LEVEL=error/' .env
    
    # Update Apache configuration for production
    if ! grep -q "Include etc/extra/httpd-ssl.conf" /opt/lampp/etc/httpd.conf; then
        echo "Include etc/extra/httpd-ssl.conf" >> /opt/lampp/etc/httpd.conf
    fi
    
    # Restart Apache to apply changes
    print_status "Restarting Apache..."
    sudo /opt/lampp/lampp restartapache
    
    print_success "Configuration updated for production"
}

# Function to run production tests
run_production_tests() {
    print_header "Running Production Tests"
    
    # Test database connectivity
    print_status "Testing database connectivity..."
    if ! mysql -u root -proot -e "SELECT COUNT(*) FROM users" maruba > /dev/null; then
        print_error "Database test failed"
        return 1
    fi
    
    # Test application access
    print_status "Testing application access..."
    local http_code=$(curl -s -o /dev/null -w '%{http_code}' http://localhost/maruba/)
    if [ "$http_code" != "200" ]; then
        print_error "Application test failed (HTTP $http_code)"
        return 1
    fi
    
    # Test HTTPS access
    print_status "Testing HTTPS access..."
    local https_code=$(curl -s -o /dev/null -w '%{http_code}' https://localhost/ 2>/dev/null || echo "000")
    if [ "$https_code" != "200" ] && [ "$https_code" != "302" ]; then
        print_warning "HTTPS test failed (HTTP $https_code) - SSL may not be configured"
    fi
    
    # Test login functionality
    print_status "Testing login functionality..."
    local login_code=$(curl -s -o /dev/null -w '%{http_code}' -d "username=admin&password=admin" http://localhost/maruba/login)
    if [ "$login_code" != "200" ] && [ "$login_code" != "302" ]; then
        print_warning "Login test failed (HTTP $login_code)"
    fi
    
    print_success "Production tests completed"
    return 0
}

# Function to optimize for production
optimize_production() {
    print_header "Optimizing for Production"
    
    # Clear development cache
    print_status "Clearing development cache..."
    if [ -d "cache" ]; then
        rm -rf cache/*
        print_success "Cache cleared"
    fi
    
    # Optimize database
    print_status "Optimizing database..."
    mysql -u root -proot maruba << EOF
OPTIMIZE TABLE users;
OPTIMIZE TABLE members;
OPTIMIZE TABLE loans;
OPTIMIZE TABLE repayments;
OPTIMIZE TABLE products;
OPTIMIZE TABLE surveys;
EOF
    
    # Set proper file permissions
    print_status "Setting file permissions..."
    find . -type f -name "*.php" -exec chmod 644 {} \;
    find . -type d -exec chmod 755 {} \;
    chmod 600 .env
    chmod 600 ssl/maruba.key
    
    # Enable OPcache if available
    if php -m | grep -q opcache; then
        print_status "OPcache is available"
    else
        print_warning "OPcache not available - consider installing for better performance"
    fi
    
    print_success "Production optimization completed"
}

# Function to setup monitoring
setup_monitoring() {
    print_header "Setting Up Monitoring"
    
    # Setup monitoring cron jobs
    print_status "Setting up monitoring cron jobs..."
    
    # Create crontab entry
    local cron_file="/tmp/maruba_cron"
    cat > "$cron_file" << EOF
# Maruba Production Monitoring
*/5 * * * * /opt/lampp/htdocs/maruba/monitoring/scripts/app_monitor.sh
*/15 * * * * /opt/lampp/htdocs/maruba/monitoring/scripts/performance_monitor.sh
*/10 * * * * /opt/lampp/htdocs/maruba/monitoring/scripts/log_monitor.sh
0 0 * * * /opt/lampp/htdocs/maruba/monitoring/scripts/rotate_logs.sh
0 8 * * * /opt/lampp/htdocs/maruba/ssl_monitor.sh
EOF
    
    crontab "$cron_file"
    rm "$cron_file"
    
    # Test monitoring scripts
    print_status "Testing monitoring scripts..."
    if /opt/lampp/htdocs/maruba/monitoring/scripts/app_monitor.sh; then
        print_success "App monitor working"
    else
        print_warning "App monitor test failed"
    fi
    
    print_success "Monitoring setup completed"
}

# Function to create deployment documentation
create_deployment_docs() {
    print_header "Creating Deployment Documentation"
    
    local docs_file="DEPLOYMENT_$(date +%Y%m%d).md"
    
    cat > "$docs_file" << EOF
# Maruba Production Deployment Documentation

## Deployment Information
- **Date**: $(date)
- **Version**: $(git log -1 --format="%H:%M:%S" 2>/dev/null || echo "Unknown")
- **Environment**: Production
- **Backup Location**: $BACKUP_DIR/\$(cat $BACKUP_DIR/latest_backup.txt 2>/dev/null || echo "Unknown")

## System Requirements
- Apache Web Server
- MySQL 8.0+
- PHP 8.1+
- SSL Certificate
- Monitoring Tools

## Configuration Files
- **Environment**: .env (copied from .env.production)
- **Apache**: /opt/lampp/etc/httpd.conf
- **SSL**: ssl/maruba.crt and ssl/maruba.key
- **Database**: MySQL maruba database

## Services Status
- **Apache**: $(pgrep -f apache2 > /dev/null && echo "Running" || echo "Stopped")
- **MySQL**: $(pgrep -f mysqld > /dev/null && echo "Running" || echo "Stopped")
- **SSL**: $(test -f ssl/maruba.crt && echo "Configured" || echo "Not configured")

## Access URLs
- **HTTP**: http://localhost/maruba
- **HTTPS**: https://localhost/maruba
- **Monitoring**: http://localhost/maruba/monitoring/dashboard/

## Backup Information
- **Latest Backup**: $(cat $BACKUP_DIR/latest_backup.txt 2>/dev/null || echo "None")
- **Backup Directory**: $BACKUP_DIR
- **Database Backup**: Included in application backup

## Monitoring
- **App Monitor**: Runs every 5 minutes
- **Performance Monitor**: Runs every 15 minutes
- **Log Monitor**: Runs every 10 minutes
- **Log Rotation**: Runs daily at midnight
- **SSL Monitor**: Runs daily at 8 AM

## Troubleshooting
1. Check Apache logs: /opt/lampp/logs/error_log
2. Check PHP logs: /opt/lampp/logs/php_error_log
3. Check MySQL logs: /opt/lampp/logs/mysql_error.log
4. Check application logs: monitoring/logs/
5. Run system tests: ./test_all_systems.sh

## Rollback Procedure
1. Stop Apache: sudo /opt/lampp/lampp stopapache
2. Restore database: mysql -u root -proot maruba < backup/database.sql
3. Restore files: tar -xzf backup/application.tar.gz
4. Restart Apache: sudo /opt/lampp/lampp startapache

## Contact Information
- **System Administrator**: [Your Email]
- **Support**: [Your Support Contact]
EOF
    
    print_success "Deployment documentation created: $docs_file"
}

# Function to log deployment
log_deployment() {
    local message="$1"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $message" >> "$DEPLOY_LOG"
}

# Main deployment function
main() {
    log_deployment "Starting production deployment"
    
    print_status "Starting Maruba production deployment..."
    echo ""
    
    # Pre-deployment checks
    if [ "$1" = "--skip-backup" ]; then
        print_warning "Skipping backup as requested"
    else
        create_backup
    fi
    echo ""
    
    # Validate environment
    if ! validate_production_env; then
        print_error "Production environment validation failed"
        log_deployment "Deployment failed: Environment validation"
        exit 1
    fi
    echo ""
    
    # Update configuration
    update_configuration
    echo ""
    
    # Run production tests
    if ! run_production_tests; then
        print_error "Production tests failed"
        log_deployment "Deployment failed: Production tests"
        exit 1
    fi
    echo ""
    
    # Optimize for production
    optimize_production
    echo ""
    
    # Setup monitoring
    setup_monitoring
    echo ""
    
    # Create documentation
    create_deployment_docs
    echo ""
    
    # Final checks
    print_header "Final Deployment Checks"
    
    local app_status=$(curl -s -o /dev/null -w '%{http_code}' http://localhost/maruba/)
    local ssl_status=$(curl -s -o /dev/null -w '%{http_code}' https://localhost/ 2>/dev/null || echo "000")
    
    print_status "Application HTTP Status: $app_status"
    print_status "Application HTTPS Status: $ssl_status"
    
    if [ "$app_status" = "200" ]; then
        print_success "🎉 Production deployment completed successfully!"
        log_deployment "Deployment completed successfully"
        echo ""
        print_status "Next steps:"
        echo "1. Test all functionality at http://localhost/maruba"
        echo "2. Verify HTTPS at https://localhost/maruba"
        echo "3. Check monitoring dashboard at http://localhost/maruba/monitoring/dashboard/"
        echo "4. Review deployment documentation"
        echo "5. Set up production alerts and notifications"
    else
        print_error "❌ Deployment failed - Application not accessible"
        log_deployment "Deployment failed: Application not accessible"
        exit 1
    fi
}

# Run deployment
if [ "${BASH_SOURCE[0]}" != "${0}" ]; then
    main "$@"
fi
