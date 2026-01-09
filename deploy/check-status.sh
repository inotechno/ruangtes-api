#!/bin/bash

# Script to check application status and troubleshoot issues

cd /opt/ruangtes-api

# Detect docker compose command
if docker compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
elif docker-compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker-compose"
else
    echo "❌ Docker Compose not found"
    exit 1
fi

echo "🔍 Checking Application Status..."
echo ""

# Check containers
echo "📦 Container Status:"
$DOCKER_COMPOSE -f docker-compose.prod.yml ps
echo ""

# Check PHP-FPM port
echo "🔌 Checking PHP-FPM Port (9000):"
if netstat -tlnp 2>/dev/null | grep -q ":9000" || ss -tlnp 2>/dev/null | grep -q ":9000"; then
    echo "✅ Port 9000 is listening"
    netstat -tlnp 2>/dev/null | grep ":9000" || ss -tlnp 2>/dev/null | grep ":9000"
else
    echo "❌ Port 9000 is NOT listening"
    echo "   Fix: Update docker-compose.prod.yml to expose port 9000"
fi
echo ""

# Check logs for errors
echo "📋 Recent Errors (last 20 lines):"
echo "--- App Container ---"
$DOCKER_COMPOSE -f docker-compose.prod.yml logs --tail=20 app | grep -i error || echo "No errors found"
echo ""
echo "--- Queue Container ---"
$DOCKER_COMPOSE -f docker-compose.prod.yml logs --tail=20 queue | grep -i error || echo "No errors found"
echo ""

# Check .env file
echo "⚙️  Environment Configuration:"
if [ -f .env ]; then
    echo "✅ .env file exists"
    if grep -q "APP_KEY=" .env && ! grep -q "APP_KEY=$" .env; then
        echo "✅ APP_KEY is set"
    else
        echo "❌ APP_KEY is not set"
    fi
    if grep -q "DB_PASSWORD=" .env && ! grep -q "DB_PASSWORD=CHANGE_THIS_PASSWORD" .env; then
        echo "✅ DB_PASSWORD is set"
    else
        echo "❌ DB_PASSWORD needs to be changed"
    fi
else
    echo "❌ .env file not found"
fi
echo ""

# Check storage permissions
echo "📁 Storage Permissions:"
if [ -d storage ]; then
    PERMS=$(stat -c "%a" storage 2>/dev/null || stat -f "%OLp" storage 2>/dev/null)
    OWNER=$(stat -c "%U:%G" storage 2>/dev/null || stat -f "%Su:%Sg" storage 2>/dev/null)
    echo "   Storage: $PERMS, Owner: $OWNER"
    if [ "$PERMS" != "775" ] && [ "$PERMS" != "755" ]; then
        echo "   ⚠️  Permissions might need fixing: chmod -R 775 storage"
    fi
else
    echo "   ❌ Storage directory not found"
fi
echo ""

# Check database connection
echo "🗄️  Database Connection:"
if $DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" > /dev/null 2>&1; then
    echo "✅ Database connection OK"
else
    echo "❌ Database connection failed"
    echo "   Check: .env DB_* settings and postgres container status"
fi
echo ""

# Check Redis connection
echo "🔴 Redis Connection:"
if $DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan tinker --execute="Redis::ping(); echo 'OK';" > /dev/null 2>&1; then
    echo "✅ Redis connection OK"
else
    echo "❌ Redis connection failed"
    echo "   Check: .env REDIS_* settings and redis container status"
fi
echo ""

# Check Nginx
echo "🌐 Nginx Status:"
if systemctl is-active --quiet nginx; then
    echo "✅ Nginx is running"
    if nginx -t > /dev/null 2>&1; then
        echo "✅ Nginx configuration is valid"
    else
        echo "❌ Nginx configuration has errors"
        echo "   Run: sudo nginx -t"
    fi
else
    echo "❌ Nginx is not running"
    echo "   Run: sudo systemctl start nginx"
fi
echo ""

echo "✅ Status check completed!"
