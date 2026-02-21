#!/bin/bash

echo "=== Maruba Koperasi Management System Setup ==="
echo ""
echo "This script will help you complete the installation."
echo ""
echo "Please enter your MySQL root password when prompted."
echo ""

# Create database
echo "1. Creating database..."
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS maruba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
echo "2. Importing database schema..."
mysql -u root -p maruba < App/schema.sql

# Import seed data
echo "3. Importing seed permissions..."
mysql -u root -p maruba < App/seed_permissions.sql

# Create admin user (password: admin123)
echo "4. Creating admin user..."
ADMIN_PASSWORD_HASH=$(php -r "echo password_hash('admin123', PASSWORD_DEFAULT);")
mysql -u root -p maruba -e "INSERT INTO users (name, username, password_hash, role_id, status) VALUES ('Administrator', 'admin', '$ADMIN_PASSWORD_HASH', 1, 'active');"

echo ""
echo "=== Setup Complete! ==="
echo ""
echo "Login credentials:"
echo "Username: admin"
echo "Password: admin123"
echo ""
echo "Access the application at: http://localhost/maruba/login"
echo ""
echo "Note: Make sure Apache mod_rewrite is enabled and AllowOverride All is set for /var/www/html"
