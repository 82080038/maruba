#!/bin/bash
# Maruba Koperasi - Complete Setup Script
# This script sets up the entire application from GitHub

echo "🚀 Maruba Koperasi - Complete Setup Script"
echo "=========================================="

# Check if Docker is running
if ! docker --version > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Stop existing containers
echo "🛑 Stopping existing containers..."
docker stop maruba-app maruba-mysql 2>/dev/null || true
docker rm maruba-app maruba-mysql 2>/dev/null || true

# Create network
echo "🌐 Creating Docker network..."
docker network create maruba-network 2>/dev/null || true

# Start MySQL container
echo "🗄️ Starting MySQL container..."
docker run -d \
    --name maruba-mysql \
    --network maruba-network \
    -e MYSQL_ROOT_PASSWORD=root \
    -e MYSQL_DATABASE=maruba \
    -p 3306:3306 \
    mysql:8.0

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
for i in {1..30}; do
    if docker exec maruba-mysql mysqladmin ping -h"localhost" --silent; then
        echo "✅ MySQL is ready!"
        break
    fi
    echo "Waiting for MySQL... ($i/30)"
    sleep 2
done

# Import database
echo "📥 Importing database..."
if [ -f "database_setup.sql" ]; then
    docker exec -i maruba-mysql mysql -uroot -proot maruba < database_setup.sql
    echo "✅ Database imported successfully!"
else
    echo "⚠️ Database setup file not found. Creating basic database..."
    docker exec maruba-mysql mysql -uroot -proot -e "
    CREATE DATABASE IF NOT EXISTS maruba;
    USE maruba;
    
    -- Create users table
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'staf',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Insert sample users
    INSERT INTO users (username, password, role) VALUES
    ('admin', 'admin123', 'admin'),
    ('manager', 'manager123', 'manager'),
    ('kasir', 'kasir123', 'kasir'),
    ('teller', 'teller123', 'teller'),
    ('surveyor', 'surveyor123', 'surveyor'),
    ('collector', 'collector123', 'collector'),
    ('akuntansi', 'akuntansi123', 'akuntansi'),
    ('staf', 'staf123', 'staf')
    ON DUPLICATE KEY UPDATE password=VALUES(password);
    "
    echo "✅ Basic database created!"
fi

# Build and start application container
echo "🐳 Building application container..."
docker build -t maruba-app .

echo "🚀 Starting application container..."
docker run -d \
    --name maruba-app \
    --network maruba-network \
    -p 8080:80 \
    maruba-app

# Wait for application to be ready
echo "⏳ Waiting for application to be ready..."
for i in {1..30}; do
    if curl -s http://localhost:8080 > /dev/null 2>&1; then
        echo "✅ Application is ready!"
        break
    fi
    echo "Waiting for application... ($i/30)"
    sleep 2
done

# Test the application
echo "🧪 Testing application..."
if curl -s http://localhost:8080 | grep -q "Login"; then
    echo "✅ Application is working!"
else
    echo "❌ Application test failed!"
    exit 1
fi

echo ""
echo "🎉 Setup Complete!"
echo "=================="
echo "🌐 Application URL: http://localhost:8080"
echo "👤 Login Credentials:"
echo "   • Admin: admin/admin123"
echo "   • Manager: manager/manager123"
echo "   • Kasir: kasir/kasir123"
echo "   • Teller: teller/teller123"
echo "   • Surveyor: surveyor/surveyor123"
echo "   • Collector: collector/collector123"
echo "   • Akuntansi: akuntansi/akuntansi123"
echo "   • Staf: staf/staf123"
echo ""
echo "🗄️ Database: MySQL on port 3306"
echo "🔧 Docker: Containers are running"
echo "📋 Status: Ready for development!"
echo ""
echo "🔄 To stop: docker stop maruba-app maruba-mysql"
echo "🔄 To restart: docker start maruba-app maruba-mysql"
echo "🔄 To rebuild: docker build -t maruba-app . && docker run -d --name maruba-app --network maruba-network -p 8080:80 maruba-app"
