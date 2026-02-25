#!/bin/bash

# Production Environment Setup Script
# This script configures the application for production deployment

echo "🚀 Setting up Production Environment..."

# Check if .env file exists
if [ ! -f "/opt/lampp/htdocs/maruba/.env" ]; then
    echo "📝 Creating .env file from template..."
    cp /opt/lampp/htdocs/maruba/.env.example /opt/lampp/htdocs/maruba/.env
else
    echo "📝 .env file already exists, updating..."
fi

# Update .env for production
echo "⚙️  Configuring production environment variables..."
cat > /opt/lampp/htdocs/maruba/.env << 'EOF'
# Maruba Koperasi Environment Configuration
# Production Environment Settings

# Database Configuration
DB_HOST=localhost
DB_NAME=maruba
DB_USER=root
DB_PASS=root
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_general_ci

# Application Configuration
APP_NAME=Maruba Koperasi
APP_ENV=production
APP_DEBUG=false
APP_URL=https://localhost/maruba

# Security Configuration
JWT_SECRET=your_jwt_secret_key_here_change_in_production
CSRF_TOKEN_SECRET=your_csrf_secret_key_here_change_in_production
SESSION_LIFETIME=7200

# Email Configuration
SMTP_HOST=localhost
SMTP_PORT=587
SMTP_USERNAME=noreply@maruba.id
SMTP_PASSWORD=your_smtp_password_here
SMTP_FROM=noreply@maruba.id

# File Upload Configuration
UPLOAD_MAX_SIZE=10485760
UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,pdf,doc,docx,xls,xlsx
UPLOAD_PATH=/opt/lampp/htdocs/maruba/uploads

# Cache Configuration
CACHE_DRIVER=file
CACHE_PREFIX=maruba_
CACHE_TTL=3600

# Logging Configuration
LOG_LEVEL=error
LOG_PATH=/opt/lampp/htdocs/maruba/logs
LOG_MAX_FILES=10
LOG_MAX_SIZE=10M

# API Configuration
API_VERSION=v1
API_RATE_LIMIT=1000
API_TIMEOUT=30

# Mobile App Configuration
MOBILE_APP_VERSION=1.0.0
MOBILE_JWT_SECRET=your_mobile_jwt_secret_here_change_in_production

# Monitoring Configuration
MONITORING_ENABLED=true
MONITORING_ALERT_EMAIL=admin@maruba.id
MONITORING_WEBHOOK_URL=https://maruba.id/webhooks/monitoring

# Backup Configuration
BACKUP_ENABLED=true
BACKUP_PATH=/opt/lampp/htdocs/maruba/backups
BACKUP_RETENTION_DAYS=30
BACKUP_ENCRYPTION=true
BACKUP_SCHEDULE=daily

# SSL Configuration
SSL_ENABLED=true
SSL_CERT_PATH=/opt/lampp/htdocs/maruba/ssl/maruba.crt
SSL_KEY_PATH=/opt/lampp/htdocs/maruba/ssl/maruba.key

# Performance Configuration
MEMORY_LIMIT=256M
MAX_EXECUTION_TIME=300
UPLOAD_MAX_FILESIZE=10M
POST_MAX_SIZE=10M

# Security Configuration
SECURITY_HEADERS_ENABLED=true
RATE_LIMITING_ENABLED=true
INPUT_VALIDATION_ENABLED=true
XSS_PROTECTION_ENABLED=true
CSRF_PROTECTION_ENABLED=true
SQL_INJECTION_PROTECTION_ENABLED=true

# Production Settings
DISPLAY_ERRORS=0
ERROR_REPORTING=0
LOG_ERRORS=1
DEBUG_BAR=false
PROFILER=false

# Session Security
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=Strict
SESSION_COOKIE_LIFETIME=7200

# File Permissions
UPLOAD_PERMISSIONS=644
UPLOAD_DIR_PERMISSIONS=755
LOG_PERMISSIONS=600

# Database Connection Pooling
DB_POOL_ENABLED=true
DB_POOL_MIN_CONNECTIONS=5
DB_POOL_MAX_CONNECTIONS=20
DB_POOL_IDLE_TIMEOUT=60

# Redis Configuration (if used)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# Third-party Integrations
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_PHONE_NUMBER=

# Google Services
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_API_KEY=

# Payment Gateway
PAYMENT_GATEWAY_ENABLED=false
PAYMENT_GATEWAY_PROVIDER=
PAYMENT_GATEWAY_API_KEY=
PAYMENT_GATEWAY_SECRET=
PAYMENT_GATEWAY_WEBHOOK_URL=

# Cloud Storage (Optional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_REGION=
AWS_BUCKET=
AWS_ENDPOINT=

# Analytics
ANALYTICS_ENABLED=false
ANALYTICS_PROVIDER=
ANALYTICS_API_KEY=

# Maintenance Mode
MAINTENANCE_MODE=false
MAINTENANCE_MESSAGE="System under maintenance. Please try again later."
MAINTENANCE_ALLOWED_IPS=127.0.0.1,::1
EOF

# Update bootstrap.php for production
echo "🔧 Updating bootstrap.php for production..."
cp /opt/lampp/htdocs/maruba/App/src/bootstrap.php /opt/lampp/htdocs/maruba/App/src/bootstrap.php.backup

# Update bootstrap.php to use production settings
sed -i 's/APP_ENV=development/APP_ENV=production/' /opt/lampp/htdocs/maruba/App/src/bootstrap.php
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' /opt/lampp/htdocs/maruba/App/src/bootstrap.php
sed -i 's/ini_set.*display_errors.*1/ini_set("display_errors", "0")/' /opt/lampp/htdocs/maruba/App/src/bootstrap.php
sed -i 's/error_reporting.*E_ALL/error_reporting(0)/' /opt/lampp/htdocs/maruba/App/src/bootstrap.php

# Create production directories
echo "📁 Creating production directories..."
mkdir -p /opt/lampp/htdocs/maruba/logs
mkdir -p /opt/lampp/htdocs/maruba/uploads
mkdir -p /opt/lampp/htdocs/maruba/backups
mkdir -p /opt/lampp/htdocs/maruba/cache
mkdir -p /opt/lampp/htdocs/maruba/sessions

# Set proper permissions
echo "🔒 Setting file permissions..."
chmod 755 /opt/lampp/htdocs/maruba/logs
chmod 755 /opt/lampp/htdocs/maruba/uploads
chmod 755 /opt/lampp/htdocs/maruba/backups
chmod 755 /opt/lampp/htdocs/maruba/cache
chmod 755 /opt/lampp/htdocs/maruba/sessions

chmod 644 /opt/lampp/htdocs/maruba/.env
chmod 600 /opt/lampp/htdocs/maruba/.env

# Create production .htaccess
echo "📝 Creating production .htaccess..."
cat > /opt/lampp/htdocs/maruba/.htaccess << 'EOF'
# Production .htaccess

# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

# Security Headers
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';"
</IfModule>

# Hide sensitive files
<FilesMatch "\.(env|log|conf|key|pem|crt|sql)$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^(composer|package|gulpfile|webpack|gruntfile|\.env)\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes

# PHP Settings
<IfModule mod_php.c>
    php_flag display_errors Off
    php_flag log_errors On
    php_value error_log /opt/lampp/htdocs/maruba/logs/php_errors.log
    php_value max_execution_time 300
    php_value memory_limit 256M
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_input_vars 3000
    php_value max_input_time 300
</IfModule>

# Gzip Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE
    AddOutputFilterByType text/plain
    AddOutputFilterByType text/html
    AddOutputFilterByType text/xml
    AddOutputFilterByType text/css
    AddOutputFilterByType application/xml
    AddOutputFilterByType application/xhtml+xml
    AddOutputFilterByType application/rss+xml
    AddOutputFilterByType application/javascript
    AddOutputFilterByType application/x-javascript
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>

# Error Pages
ErrorDocument 401 /opt/lampp/htdocs/maruba/error_pages/401.html
ErrorDocument 403 /opt/lampp/htdocs/maruba/error_pages/403.html
ErrorDocument 404 /opt/lampp/htdocs/maruba/error_pages/404.html
ErrorDocument 500 /opt/lampp/htdocs/maruba/error_pages/500.html

# Maintenance Mode
# Uncomment the following lines to enable maintenance mode
# RewriteEngine On
# RewriteCond %{REQUEST_URI} !^/maintenance\.html$
# RewriteRule ^(.*)$ /maintenance.html [L]
EOF

# Create error pages directory
mkdir -p /opt/lampp/htdocs/maruba/error_pages

# Create basic error pages
cat > /opt/lampp/htdocs/maruba/error_pages/404.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-code { font-size: 72px; color: #e74c3c; }
        .error-message { font-size: 24px; color: #333; }
        .back-link { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="error-code">404</div>
    <div class="error-message">Page Not Found</div>
    <p><a href="/" class="back-link">Back to Home</a></p>
</body>
</html>
EOF

cat > /opt/lampp/htdocs/maruba/error_pages/500.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>500 - Internal Server Error</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error-code { font-size: 72px; color: #e74c3c; }
        .error-message { font-size: 24px; color: #333; }
        .back-link { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="error-code">500</div>
    <div class="error-message">Internal Server Error</div>
    <p><a href="/" class="back-link">Back to Home</a></p>
</body>
</html>
EOF

# Create maintenance page
cat > /opt/lampp/htdocs/maruba/maintenance.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>System Maintenance</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 100px 20px; background: #f5f5f5; }
        .maintenance-icon { font-size: 72px; color: #f39c12; }
        .maintenance-title { font-size: 36px; color: #333; margin: 20px 0; }
        .maintenance-message { font-size: 18px; color: #666; margin: 20px 0; }
        .progress-bar { width: 300px; height: 20px; background: #ddd; border-radius: 10px; margin: 20px auto; overflow: hidden; }
        .progress-fill { height: 100%; background: #4CAF50; width: 75%; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { width: 75%; } 50% { width: 85%; } 100% { width: 75%; } }
    </style>
</head>
<body>
    <div class="maintenance-icon">🔧</div>
    <div class="maintenance-title">System Under Maintenance</div>
    <div class="maintenance-message">We're currently performing maintenance. Please try again later.</div>
    <div class="progress-bar">
        <div class="progress-fill"></div>
    </div>
    <div class="maintenance-message">Estimated completion: 2 hours</div>
</body>
</html>
EOF

# Create log files
echo "📝 Creating log files..."
touch /opt/lampp/htdocs/maruba/logs/php_errors.log
touch /opt/lampp/htdocs/maruba/logs/access.log
touch /opt/lampp/htdocs/maruba/logs/error.log
touch /opt/lampp/htdocs/maruba/logs/security.log

# Set log file permissions
chmod 644 /opt/lampp/htdocs/maruba/logs/*.log

# Create production startup script
echo "🚀 Creating production startup script..."
cat > /opt/lampp/htdocs/maruba/scripts/start_production.sh << 'EOF'
#!/bin/bash

# Production Startup Script
echo "🚀 Starting Maruba Koperasi in Production Mode..."

# Check if SSL certificate exists
if [ ! -f "/opt/lampp/htdocs/maruba/ssl/maruba.crt" ]; then
    echo "⚠️  SSL certificate not found. Please run: ./scripts/setup_ssl.sh"
fi

# Check if .env file exists
if [ ! -f "/opt/lampp/htdocs/maruba/.env" ]; then
    echo "⚠️  .env file not found. Please run: ./scripts/setup_production_env.sh"
fi

# Check log directory
if [ ! -d "/opt/lampp/htdocs/maruba/logs" ]; then
    echo "📁 Creating log directory..."
    mkdir -p /opt/lampp/htdocs/maruba/logs
fi

# Set permissions
chmod 755 /opt/lampp/htdocs/maruba/logs
chmod 644 /opt/lampp/htdocs/maruba/logs/*.log

# Start monitoring
echo "📊 Starting monitoring..."
./scripts/monitoring/start_monitoring.sh

echo "✅ Production startup complete!"
echo "🌐 Application available at: https://localhost/maruba"
EOF

chmod +x /opt/lampp/htdocs/maruba/scripts/start_production.sh

echo "✅ Production Environment Setup Complete!"
echo ""
echo "📋 Configuration Summary:"
echo "   - Environment: Production"
echo "   - Debug Mode: Disabled"
echo "   - Error Reporting: Disabled"
echo "   - SSL: Enabled (if certificate exists)"
echo "   - Security Headers: Enabled"
echo "   - File Permissions: Set"
echo "   - Log Directory: Created"
echo "   - Error Pages: Created"
echo "   - Maintenance Page: Created"
echo ""
echo "🚀 Next Steps:"
echo "1. Run: ./scripts/fix_syntax_errors.sh"
echo "2. Run: ./scripts/setup_ssl.sh (if not already done)"
echo "3. Restart Apache: sudo /opt/lampp/lampp restart"
echo "4. Test: https://localhost/maruba"
echo "5. Monitor: ./scripts/monitoring/start_monitoring.sh"

exit 0
