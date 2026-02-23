#!/bin/bash

# SSL Certificate Setup Script
# This script generates SSL certificate and configures Apache for HTTPS

echo "🔐 Setting up SSL Certificate..."

# Create SSL directory
mkdir -p /opt/lampp/htdocs/maruba/ssl
mkdir -p /opt/lampp/etc/ssl

# Generate SSL certificate
echo "📝 Generating SSL certificate..."
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /opt/lampp/htdocs/maruba/ssl/maruba.key \
    -out /opt/lampp/htdocs/maruba/ssl/maruba.crt \
    -subj "/C=ID/ST=Jambi/L=Jambi/O=Maruba Koperasi/CN=localhost"

# Copy certificates to XAMPP SSL directory
echo "📋 Copying certificates to XAMPP..."
cp /opt/lampp/htdocs/maruba/ssl/maruba.key /opt/lampp/etc/ssl/
cp /opt/lampp/htdocs/maruba/ssl/maruba.crt /opt/lampp/etc/ssl/

# Set proper permissions
echo "🔒 Setting file permissions..."
chmod 600 /opt/lampp/htdocs/maruba/ssl/maruba.key
chmod 644 /opt/lampp/htdocs/maruba/ssl/maruba.crt
chmod 600 /opt/lampp/etc/ssl/maruba.key
chmod 644 /opt/lampp/etc/ssl/maruba.crt

# Create Apache SSL configuration
echo "⚙️  Configuring Apache SSL..."
cat > /opt/lampp/etc/extra/httpd-ssl.conf << 'EOF'
Listen 443

<VirtualHost *:443>
    DocumentRoot "/opt/lampp/htdocs/maruba"
    ServerName localhost
    ServerAdmin admin@maruba.id
    
    SSLEngine on
    SSLCertificateFile "/opt/lampp/etc/ssl/maruba.crt"
    SSLCertificateKeyFile "/opt/lampp/etc/ssl/maruba.key"
    
    # SSL Configuration
    SSLProtocol all -SSLv2 -SSLv3
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on
    SSLPassPhraseDialog builtin
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Directory permissions
    <Directory "/opt/lampp/htdocs/maruba">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Force HTTPS
        RewriteEngine On
        RewriteCond %{HTTPS} off
        RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
    </Directory>
    
    # Error logs
    ErrorLog "/opt/lampp/logs/ssl_error_log"
    CustomLog "/opt/lampp/logs/ssl_access_log" combined
    
    # PHP configuration
    <FilesMatch "\.php$">
        SetHandler application/x-httpd-php
        AddHandler application/x-httpd-php .php
    </FilesMatch>
    
    # PHP settings
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value memory_limit 256M
    php_value max_execution_time 300
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    DocumentRoot "/opt/lampp/htdocs/maruba"
    ServerName localhost
    
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>
EOF

# Enable SSL module in Apache configuration
echo "🔧 Enabling SSL module..."
if ! grep -q "LoadModule ssl_module" /opt/lampp/etc/httpd.conf; then
    echo "LoadModule ssl_module modules/mod_ssl.so" >> /opt/lampp/etc/httpd.conf
fi

if ! grep -q "LoadModule rewrite_module" /opt/lampp/etc/httpd.conf; then
    echo "LoadModule rewrite_module modules/mod_rewrite.so" >> /opt/lampp/etc/httpd.conf
fi

# Include SSL configuration
if ! grep -q "Include etc/extra/httpd-ssl.conf" /opt/lampp/etc/httpd.conf; then
    echo "Include etc/extra/httpd-ssl.conf" >> /opt/lampp/etc/httpd.conf
fi

# Create .htaccess for HTTPS redirect
echo "📝 Creating .htaccess for HTTPS redirect..."
cat > /opt/lampp/htdocs/maruba/.htaccess << 'EOF'
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
</IfModule>

# Hide .htaccess file
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>
EOF

# Test SSL configuration
echo "🧪 Testing SSL configuration..."
if openssl x509 -in /opt/lampp/htdocs/maruba/ssl/maruba.crt -text -noout > /dev/null 2>&1; then
    echo "✅ SSL certificate generated successfully"
else
    echo "❌ SSL certificate generation failed"
    exit 1
fi

if [ -f /opt/lampp/etc/ssl/maruba.key ]; then
    echo "✅ SSL private key generated successfully"
else
    echo "❌ SSL private key generation failed"
    exit 1
fi

echo "🔒 SSL Certificate Setup Complete!"
echo ""
echo "📋 Certificate Information:"
openssl x509 -in /opt/lampp/htdocs/maruba/ssl/maruba.crt -text -noout | grep -E "(Subject:|Issuer:|Not Before:|Not After:)"

echo ""
echo "🚀 Next Steps:"
echo "1. Restart Apache: sudo /opt/lampp/lampp restart"
echo "2. Test HTTPS: https://localhost/maruba"
echo "3. Verify SSL certificate in browser"
echo "4. Update application URLs to use HTTPS"

echo ""
echo "📊 SSL Configuration Summary:"
echo "   - Certificate: /opt/lampp/htdocs/maruba/ssl/maruba.crt"
echo "   - Private Key: /opt/lampp/htdocs/maruba/ssl/maruba.key"
echo "   - Apache Config: /opt/lampp/etc/extra/httpd-ssl.conf"
echo "   - Htaccess: /opt/lampp/htdocs/maruba/.htaccess"
echo "   - Validity: 365 days"
echo "   - Domain: localhost"

exit 0
