#!/bin/bash

# KSP LAM GABE JAYA Application Deployment Script
echo "=== APLIKASI KSP Deployment Script ==="
echo

# Check if we're in the right directory
if [ ! -f "App/schema.sql" ]; then
    echo "Error: Please run this script from the maruba directory"
    exit 1
fi

# Function to check command success
check_command() {
    if [ $? -ne 0 ]; then
        echo "Error: $1 failed"
        exit 1
    fi
}

echo "1. Checking PHP version..."
php --version | head -1
check_command "PHP check"

echo "2. Checking MySQL connection..."
mysql --version
check_command "MySQL check"

echo "3. Setting up database..."
mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS maruba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
check_command "Database creation"

echo "4. Importing database schema..."
mysql -u root -proot maruba < App/schema.sql
check_command "Schema import"

echo "5. Setting up tenant tables..."
mysql -u root -proot maruba < setup_tenant_tables.sql
check_command "Tenant tables setup"

echo "6. Creating necessary directories..."
mkdir -p App/public/uploads/{members,loans,surveys,receipts,documents}
mkdir -p App/public/uploads/surveys/photos
mkdir -p App/public/uploads/repayments/proofs
chmod 755 App/public/uploads
chmod 755 App/public/uploads/*

echo "7. Setting up .env file..."
if [ ! -f ".env" ]; then
    cat > .env << EOF
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=maruba
DB_USER=root
DB_PASS=root

# Application Configuration
APP_NAME=KSP LAM GABE JAYA
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost/maruba

# Main Domain for Multi-tenant
MAIN_DOMAIN=localhost

# Email Configuration (optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=noreply@ksp-lamgabejaya.id
SMTP_FROM_NAME=KSP LAM GABE JAYA

# WhatsApp Business API (optional)
WHATSAPP_API_KEY=
WHATSAPP_PHONE_NUMBER_ID=

# File Upload
MAX_FILE_SIZE=5242880
UPLOAD_PATH=uploads/

# Security
APP_SECRET=$(openssl rand -base64 32)
EOF
    echo "Created .env file with default configuration"
else
    echo ".env file already exists"
fi

echo "8. Setting proper permissions..."
find App/src -type f -name "*.php" -exec chmod 644 {} \;
find App/public -type f -name "*.php" -exec chmod 644 {} \;
chmod 755 *.php
chmod 755 *.sh

echo "9. Testing application..."
php test.php
check_command "Application test"

echo
echo "=== Deployment Complete! ==="
echo
echo "Application is now ready at: http://localhost/maruba"
echo
echo "Default login credentials:"
echo "Username: admin"
echo "Password: admin123"
echo
echo "Next steps:"
echo "1. Configure your web server (Apache/Nginx)"
echo "2. Set up proper domain and SSL certificates"
echo "3. Configure email and WhatsApp settings in .env"
echo "4. Create your first tenant"
echo "5. Test all features thoroughly"
echo
echo "For support, contact: support@aplikasiksp.id"
