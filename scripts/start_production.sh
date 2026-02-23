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
