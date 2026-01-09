#!/bin/bash

# Quick verification script for Nginx setup

echo "🔍 Verifying Nginx Setup..."
echo ""

# 1. Check document root
echo "1️⃣  Document Root:"
NGINX_ROOT=$(grep -E "^\s*root\s+" /etc/nginx/sites-available/ruangtes-api 2>/dev/null | awk '{print $2}' | tr -d ';' || echo "NOT FOUND")
echo "   Config: $NGINX_ROOT"
if [ -d "$NGINX_ROOT" ]; then
    echo "   ✅ Directory exists"
    if [ -f "$NGINX_ROOT/index.php" ]; then
        echo "   ✅ index.php exists"
        echo "   File size: $(stat -c%s "$NGINX_ROOT/index.php" 2>/dev/null || echo "unknown") bytes"
    else
        echo "   ❌ index.php NOT FOUND"
        echo "   Files in directory:"
        ls -la "$NGINX_ROOT" | head -5
    fi
else
    echo "   ❌ Directory does not exist"
fi
echo ""

# 2. Check PHP-FPM connection
echo "2️⃣  PHP-FPM Connection:"
if netstat -tlnp 2>/dev/null | grep -q ":9000" || ss -tlnp 2>/dev/null | grep -q ":9000"; then
    echo "   ✅ Port 9000 is listening"
    PORT_INFO=$(netstat -tlnp 2>/dev/null | grep ":9000" || ss -tlnp 2>/dev/null | grep ":9000")
    echo "   $PORT_INFO"
else
    echo "   ❌ Port 9000 is NOT listening"
    echo "   Run: docker compose -f docker-compose.prod.yml restart app"
fi
echo ""

# 3. Test PHP execution
echo "3️⃣  PHP Execution Test:"
cd /opt/ruangtes-api
TEST_OUTPUT=$(docker compose -f docker-compose.prod.yml exec -T app php -r "echo 'PHP OK';" 2>&1)
if [ $? -eq 0 ]; then
    echo "   ✅ PHP is working: $TEST_OUTPUT"
else
    echo "   ❌ PHP execution failed"
    echo "   $TEST_OUTPUT"
fi
echo ""

# 4. Test index.php directly
echo "4️⃣  Testing index.php:"
if [ -f "$NGINX_ROOT/index.php" ]; then
    PHP_OUTPUT=$(docker compose -f docker-compose.prod.yml exec -T app php "$NGINX_ROOT/index.php" 2>&1 | head -1)
    if [ $? -eq 0 ]; then
        echo "   ✅ index.php can be executed"
    else
        echo "   ❌ index.php execution failed"
        echo "   Error: $PHP_OUTPUT"
    fi
fi
echo ""

# 5. Check Nginx error log
echo "5️⃣  Recent Nginx Errors:"
if [ -f /var/log/nginx/ruangtes-error.log ]; then
    RECENT_ERRORS=$(sudo tail -5 /var/log/nginx/ruangtes-error.log 2>/dev/null | grep -i "error\|failed\|denied" || echo "No recent errors")
    if [ "$RECENT_ERRORS" != "No recent errors" ]; then
        echo "   ⚠️  Recent errors found:"
        echo "$RECENT_ERRORS" | sed 's/^/   /'
    else
        echo "   ✅ No recent errors"
    fi
else
    echo "   ⚠️  Error log file not found"
fi
echo ""

# 6. Quick curl test
echo "6️⃣  Local HTTP Test:"
HTTP_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health 2>/dev/null || echo "000")
if [ "$HTTP_RESPONSE" = "200" ]; then
    echo "   ✅ HTTP 200 - Server responding"
elif [ "$HTTP_RESPONSE" = "404" ]; then
    echo "   ❌ HTTP 404 - File not found"
    echo "   Check: Document root and try_files directive"
elif [ "$HTTP_RESPONSE" = "502" ]; then
    echo "   ❌ HTTP 502 - Bad Gateway"
    echo "   Check: PHP-FPM connection"
elif [ "$HTTP_RESPONSE" = "000" ]; then
    echo "   ❌ Connection failed"
else
    echo "   ⚠️  HTTP $HTTP_RESPONSE"
fi
echo ""

echo "✅ Verification completed!"
