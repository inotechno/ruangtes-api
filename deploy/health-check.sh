#!/bin/bash

# Health check script for monitoring - No Docker

cd /opt/ruangtes-api

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed"
    exit 1
fi

# Check if application directory exists
if [ ! -f "artisan" ]; then
    echo "❌ Application directory not found"
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "❌ .env file not found"
    exit 1
fi

# Check database connection
if ! php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo "❌ Database connection failed"
    exit 1
fi

# Check Redis connection
if ! php artisan tinker --execute="Redis::connection()->ping();" > /dev/null 2>&1; then
    echo "❌ Redis connection failed"
    exit 1
fi

# Check if storage is writable
if [ ! -w "storage" ]; then
    echo "❌ Storage directory is not writable"
    exit 1
fi

# Check services
if ! systemctl is-active --quiet php8.4-fpm && ! systemctl is-active --quiet php-fpm; then  
    echo "❌ PHP-FPM is not running"
    exit 1
fi

if ! systemctl is-active --quiet nginx; then
    echo "❌ Nginx is not running"
    exit 1
fi

if ! systemctl is-active --quiet postgresql; then
    echo "❌ PostgreSQL is not running"
    exit 1
fi

if ! systemctl is-active --quiet redis-server; then
    echo "❌ Redis is not running"
    exit 1
fi

# Check queue workers
if ! supervisorctl status ruangtes-queue:* | grep -q RUNNING; then
    echo "⚠️  Queue workers are not running"
    # Don't exit with error, just warn
fi

echo "✅ All health checks passed"
