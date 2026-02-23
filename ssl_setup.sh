#!/bin/bash

# Maruba SSL Setup Script
# Configures HTTPS for production deployment

set -e

echo "🔒 Maruba SSL Setup Tool"
echo "====================="

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

# Function to check if OpenSSL is available
check_openssl() {
    if ! command -v openssl &> /dev/null; then
        print_error "OpenSSL is not installed"
        print_status "Installing OpenSSL..."
        sudo apt update && sudo apt install -y openssl
    fi
}

# Function to create self-signed certificate
create_self_signed_cert() {
    print_status "Creating self-signed SSL certificate..."
    
    # Create SSL directory
    mkdir -p ssl
    
    # Generate private key
    openssl genrsa -out ssl/maruba.key 2048
    
    # Generate certificate signing request
    openssl req -new -key ssl/maruba.key -out ssl/maruba.csr -subj "/C=ID/ST=Sumatera Utara/L=Medan/O=Maruba Koperasi/CN=localhost"
    
    # Generate self-signed certificate
    openssl x509 -req -days 365 -in ssl/maruba.csr -signkey ssl/maruba.key -out ssl/maruba.crt
    
    # Copy certificate and key to Apache directory
    sudo cp ssl/maruba.crt /opt/lampp/etc/ssl.crt/
    sudo cp ssl/maruba.key /opt/lampp/etc/ssl.key/
    
    # Set proper permissions
    sudo chmod 600 /opt/lampp/etc/ssl.key/maruba.key
    sudo chmod 644 /opt/lampp/etc/ssl.crt/maruba.crt
    
    print_success "Self-signed certificate created"
}

# Function to configure Apache for SSL
configure_apache_ssl() {
    print_status "Configuring Apache for SSL..."
    
    # Enable SSL module
    sudo /opt/lampp/lampp startssl
    
    # Create SSL configuration for Apache
    sudo tee /opt/lampp/etc/extra/httpd-ssl.conf > /dev/null << 'EOF'
Listen 443 https
SSLPassPhraseDialog  builtin
SSLSessionCache        "shmcb:/opt/lampp/logs/ssl_scache(512000)"
SSLSessionCacheTimeout  300

<VirtualHost _default_:443>
    DocumentRoot "/opt/lampp/htdocs/maruba"
    ServerName localhost
    
    ErrorLog "logs/error_log"
    CustomLog "logs/ssl_request_log" \
        "%t %h %{SSL_PROTOCOL}x %{SSL_CIPHER}x \"%r\" %b"
    
    SSLEngine on
    SSLCertificateFile "/opt/lampp/etc/ssl.crt/maruba.crt"
    SSLCertificateKeyFile "/opt/lampp/etc/ssl.key/maruba.key"
    
    <FilesMatch "\.(cgi|shtml|phtml|php)$">
        SSLOptions +StdEnvVars
    </FilesMatch>
    <Directory "/opt/lampp/cgi-bin">
        SSLOptions +StdEnvVars
    </Directory>
    
    BrowserMatch "MSIE [2-5]" \
        nokeepalive ssl-unclean-shutdown \
        downgrade-1.0 force-response-1.0
</VirtualHost>
EOF
    
    # Include SSL configuration in main Apache config
    if ! grep -q "Include etc/extra/httpd-ssl.conf" /opt/lampp/etc/httpd.conf; then
        echo "Include etc/extra/httpd-ssl.conf" | sudo tee -a /opt/lampp/etc/httpd.conf > /dev/null
    fi
    
    print_success "Apache SSL configuration completed"
}

# Function to configure .htaccess for HTTPS
configure_htaccess() {
    print_status "Configuring .htaccess for HTTPS..."
    
    # Create .htaccess file
    cat > .htaccess << 'EOF'
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Hide .htaccess file
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>

# Protect sensitive files
<FilesMatch "\.(env|log|sql|key|pem|crt)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes

# PHP security settings
<IfModule mod_php.c>
    php_flag display_errors Off
    php_flag log_errors On
    php_value error_log /opt/lampp/logs/php_error.log
    php_value max_execution_time 300
    php_value memory_limit 256M
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
</IfModule>
EOF
    
    print_success ".htaccess HTTPS configuration completed"
}

# Function to update application configuration
update_app_config() {
    print_status "Updating application configuration for HTTPS..."
    
    # Update .env file
    if [ -f .env ]; then
        if ! grep -q "APP_URL" .env; then
            echo "APP_URL=https://localhost/maruba" >> .env
        else
            sed -i 's|APP_URL=http://localhost/maruba|APP_URL=https://localhost/maruba|g' .env
        fi
    fi
    
    # Update bootstrap.php
    if [ -f App/src/bootstrap.php ]; then
        sed -i 's|http://localhost|https://localhost|g' App/src/bootstrap.php
    fi
    
    print_success "Application configuration updated"
}

# Function to test SSL configuration
test_ssl_config() {
    print_status "Testing SSL configuration..."
    
    # Restart Apache
    sudo /opt/lampp/lampp restartapache
    
    # Wait for Apache to start
    sleep 3
    
    # Test HTTPS connection
    if curl -k -s -o /dev/null -w "%{http_code}" https://localhost/ | grep -q "200"; then
        print_success "HTTPS is working correctly"
    else
        print_warning "HTTPS test failed - check Apache logs"
    fi
    
    # Test SSL certificate
    if openssl s_client -connect localhost:443 -showcerts 2>/dev/null | grep -q "maruba.crt"; then
        print_success "SSL certificate is valid"
    else
        print_warning "SSL certificate validation failed"
    fi
}

# Function to create SSL monitoring script
create_ssl_monitor() {
    print_status "Creating SSL monitoring script..."
    
    cat > ssl_monitor.sh << 'EOF'
#!/bin/bash

# SSL Certificate Monitoring Script
# Checks SSL certificate expiration and validity

DOMAIN="localhost"
PORT="443"
DAYS_WARNING=30

# Get certificate expiration date
EXPIRY_DATE=$(echo | openssl s_client -connect $DOMAIN:$PORT 2>/dev/null | openssl x509 -noout -enddate | cut -d= -f2)

# Convert to timestamp
EXPIRY_TIMESTAMP=$(date -d "$EXPIRY_DATE" +%s)
CURRENT_TIMESTAMP=$(date +%s)
DAYS_LEFT=$(( ($EXPIRY_TIMESTAMP - $CURRENT_TIMESTAMP) / 86400 ))

echo "SSL Certificate Status for $DOMAIN:$PORT"
echo "=========================================="
echo "Expiration Date: $EXPIRY_DATE"
echo "Days Left: $DAYS_LEFT"

if [ $DAYS_LEFT -lt $DAYS_WARNING ]; then
    echo "WARNING: Certificate expires in $DAYS_LEFT days!"
    # Send alert (configure email/slack notification here)
else
    echo "Certificate is valid"
fi
EOF
    
    chmod +x ssl_monitor.sh
    print_success "SSL monitoring script created"
}

# Function to generate SSL report
generate_ssl_report() {
    local report_file="ssl_report_$(date +%Y%m%d_%H%M%S).txt"
    
    echo "Maruba SSL Setup Report" > "$report_file"
    echo "=====================" >> "$report_file"
    echo "Generated: $(date)" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "SSL Configuration:" >> "$report_file"
    echo "- Certificate: Self-signed" >> "$report_file"
    echo "- Key Size: 2048 bits" >> "$report_file"
    echo "- Validity: 365 days" >> "$report_file"
    echo "- Domain: localhost" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Security Features:" >> "$report_file"
    echo "- HTTPS enforced" >> "$report_file"
    echo "- Security headers configured" >> "$report_file"
    echo "- HSTS enabled" >> "$report_file"
    echo "- CSP configured" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Files Created:" >> "$report_file"
    echo "- ssl/maruba.key (private key)" >> "$report_file"
    echo "- ssl/maruba.crt (certificate)" >> "$report_file"
    echo "- .htaccess (HTTPS redirect)" >> "$report_file"
    echo "- ssl_monitor.sh (monitoring script)" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Next Steps:" >> "$report_file"
    echo "1. Test HTTPS functionality" >> "$report_file"
    echo "2. Set up SSL monitoring (cron job)" >> "$report_file"
    echo "3. Consider production certificate (Let's Encrypt)" >> "$report_file"
    echo "4. Configure backup for SSL files" >> "$report_file"
    
    print_success "SSL report generated: $report_file"
}

# Main SSL setup function
setup_ssl() {
    local issues=0
    
    print_status "Starting SSL setup..."
    echo ""
    
    # Check dependencies
    check_openssl || issues=$((issues + 1))
    echo ""
    
    # Create self-signed certificate
    create_self_signed_cert || issues=$((issues + 1))
    echo ""
    
    # Configure Apache SSL
    configure_apache_ssl || issues=$((issues + 1))
    echo ""
    
    # Configure .htaccess
    configure_htaccess || issues=$((issues + 1))
    echo ""
    
    # Update application config
    update_app_config || issues=$((issues + 1))
    echo ""
    
    # Test SSL configuration
    test_ssl_config || issues=$((issues + 1))
    echo ""
    
    # Create monitoring script
    create_ssl_monitor || issues=$((issues + 1))
    echo ""
    
    print_status "SSL setup completed"
    
    if [ $issues -eq 0 ]; then
        print_success "SSL setup completed successfully! 🔒"
        print_status "You can now access the application at: https://localhost/maruba"
    else
        print_warning "Found $issues SSL setup issues"
        print_warning "Please review and fix the issues above"
    fi
    
    # Generate report
    generate_ssl_report
    
    return $issues
}

# Run the setup
if [ "${BASH_SOURCE[0]}" != "${0}" ]; then
    setup_ssl
fi
