#!/bin/bash

# Script to fix Nginx connection to PHP-FPM

set -e

echo "🔧 Fixing Nginx to PHP-FPM Connection..."
echo ""

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

# 1. Check app container
echo "1️⃣  Checking app container..."
if $DOCKER_COMPOSE -f docker-compose.prod.yml ps app | grep -q "Up"; then
    echo "✅ App container is running"
    CONTAINER_STATUS=$($DOCKER_COMPOSE -f docker-compose.prod.yml ps app | grep app)
    echo "   $CONTAINER_STATUS"
else
    echo "❌ App container is not running"
    echo "   Starting app container..."
    $DOCKER_COMPOSE -f docker-compose.prod.yml up -d app
    echo "   Waiting for container to start..."
    sleep 10
fi
echo ""

# 2. Check port 9000
echo "2️⃣  Checking port 9000..."
if netstat -tlnp 2>/dev/null | grep -q ":9000" || ss -tlnp 2>/dev/null | grep -q ":9000"; then
    echo "✅ Port 9000 is listening"
    PORT_INFO=$(netstat -tlnp 2>/dev/null | grep ":9000" || ss -tlnp 2>/dev/null | grep ":9000")
    echo "   $PORT_INFO"
else
    echo "❌ Port 9000 is NOT listening"
    echo "   Checking docker-compose.prod.yml..."
    
    if grep -q "127.0.0.1:9000:9000" docker-compose.prod.yml; then
        echo "   ✅ Port mapping exists in docker-compose.prod.yml"
        echo "   Restarting app container to apply port mapping..."
        $DOCKER_COMPOSE -f docker-compose.prod.yml stop app
        $DOCKER_COMPOSE -f docker-compose.prod.yml up -d app
        echo "   Waiting for container to start..."
        sleep 10
        
        # Check again
        if netstat -tlnp 2>/dev/null | grep -q ":9000" || ss -tlnp 2>/dev/null | grep -q ":9000"; then
            echo "   ✅ Port 9000 is now listening"
        else
            echo "   ❌ Port 9000 still not listening"
            echo "   Checking container logs..."
            $DOCKER_COMPOSE -f docker-compose.prod.yml logs --tail=20 app
        fi
    else
        echo "   ❌ Port mapping NOT found in docker-compose.prod.yml"
        echo "   Please add under app service:"
        echo "   ports:"
        echo "     - \"127.0.0.1:9000:9000\""
    fi
fi
echo ""

# 3. Test connection
echo "3️⃣  Testing PHP-FPM connection..."
if timeout 2 bash -c "echo > /dev/tcp/127.0.0.1/9000" 2>/dev/null; then
    echo "✅ Can connect to 127.0.0.1:9000"
else
    echo "❌ Cannot connect to 127.0.0.1:9000"
    echo "   Checking container status..."
    $DOCKER_COMPOSE -f docker-compose.prod.yml ps app
    echo ""
    echo "   Checking container logs for errors..."
    $DOCKER_COMPOSE -f docker-compose.prod.yml logs --tail=10 app
fi
echo ""

# 4. Verify document root
echo "4️⃣  Verifying document root..."
NGINX_ROOT=$(grep -E "^\s*root\s+" /etc/nginx/sites-available/ruangtes-api 2>/dev/null | awk '{print $2}' | tr -d ';' || echo "")
if [ -n "$NGINX_ROOT" ]; then
    echo "   Nginx root: $NGINX_ROOT"
    if [ "$NGINX_ROOT" = "/opt/ruangtes-api/public" ]; then
        echo "   ✅ Document root is correct"
    else
        echo "   ❌ Document root is WRONG"
        echo "   Should be: /opt/ruangtes-api/public"
        echo "   Current: $NGINX_ROOT"
    fi
    
    if [ -f "$NGINX_ROOT/index.php" ]; then
        echo "   ✅ index.php exists"
    else
        echo "   ❌ index.php NOT found in $NGINX_ROOT"
        echo "   Checking if file exists in different location..."
        ls -la /opt/ruangtes-api/public/index.php 2>/dev/null || echo "   File not found"
    fi
else
    echo "   ⚠️  Could not find root directive"
fi
echo ""

# 5. Reload Nginx
echo "5️⃣  Reloading Nginx..."
if sudo nginx -t > /dev/null 2>&1; then
    sudo systemctl reload nginx
    echo "✅ Nginx reloaded"
else
    echo "❌ Nginx configuration has errors"
    sudo nginx -t
fi
echo ""

# 6. Final test
echo "6️⃣  Final connection test..."
sleep 2
if timeout 2 bash -c "echo > /dev/tcp/127.0.0.1/9000" 2>/dev/null; then
    echo "✅ PHP-FPM connection is working"
    echo ""
    echo "Test with: curl http://localhost/health"
    echo ""
    HTTP_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health 2>/dev/null || echo "000")
    if [ "$HTTP_TEST" = "200" ]; then
        echo "✅ HTTP test successful (200 OK)"
    else
        echo "⚠️  HTTP test returned: $HTTP_TEST"
    fi
else
    echo "❌ PHP-FPM connection still failing"
    echo ""
    echo "Troubleshooting steps:"
    echo "1. Check app container: docker compose -f docker-compose.prod.yml ps app"
    echo "2. Check logs: docker compose -f docker-compose.prod.yml logs app"
    echo "3. Verify port mapping in docker-compose.prod.yml"
    echo "4. Try: docker compose -f docker-compose.prod.yml down && docker compose -f docker-compose.prod.yml up -d"
fi
echo ""

echo "✅ Fix completed!"
