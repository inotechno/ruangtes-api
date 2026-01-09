#!/bin/bash

# Script to troubleshoot Nginx and PHP-FPM connection

set -e

echo "🔍 Troubleshooting Nginx and PHP-FPM..."
echo ""

# 1. Check Nginx status
echo "1️⃣  Checking Nginx status..."
if systemctl is-active --quiet nginx; then
    echo "✅ Nginx is running"
else
    echo "❌ Nginx is not running"
    echo "   Run: sudo systemctl start nginx"
fi
echo ""

# 2. Check Nginx configuration
echo "2️⃣  Checking Nginx configuration..."
if sudo nginx -t 2>&1 | grep -q "successful"; then
    echo "✅ Nginx configuration is valid"
else
    echo "❌ Nginx configuration has errors:"
    sudo nginx -t
fi
echo ""

# 3. Check PHP-FPM port
echo "3️⃣  Checking PHP-FPM port (9000)..."
if netstat -tlnp 2>/dev/null | grep -q ":9000" || ss -tlnp 2>/dev/null | grep -q ":9000"; then
    echo "✅ Port 9000 is listening"
    netstat -tlnp 2>/dev/null | grep ":9000" || ss -tlnp 2>/dev/null | grep ":9000"
else
    echo "❌ Port 9000 is NOT listening"
    echo "   Check: docker compose -f docker-compose.prod.yml ps app"
fi
echo ""

# 4. Check app container
echo "4️⃣  Checking app container..."
cd /opt/ruangtes-api
if docker compose -f docker-compose.prod.yml ps app | grep -q "Up"; then
    echo "✅ App container is running"
else
    echo "❌ App container is not running"
    echo "   Check: docker compose -f docker-compose.prod.yml logs app"
fi
echo ""

# 5. Check document root
echo "5️⃣  Checking document root..."
NGINX_ROOT=$(grep -E "^\s*root\s+" /etc/nginx/sites-available/ruangtes-api 2>/dev/null | awk '{print $2}' | tr -d ';' || echo "")
if [ -n "$NGINX_ROOT" ]; then
    echo "   Nginx root: $NGINX_ROOT"
    if [ -d "$NGINX_ROOT" ]; then
        echo "✅ Document root exists"
        if [ -f "$NGINX_ROOT/index.php" ]; then
            echo "✅ index.php exists"
        else
            echo "❌ index.php NOT found in $NGINX_ROOT"
            echo "   Expected: $NGINX_ROOT/index.php"
        fi
    else
        echo "❌ Document root does not exist: $NGINX_ROOT"
    fi
else
    echo "⚠️  Could not find root directive in Nginx config"
fi
echo ""

# 6. Check fastcgi_pass
echo "6️⃣  Checking fastcgi_pass configuration..."
if grep -q "fastcgi_pass ruangtes_app" /etc/nginx/sites-available/ruangtes-api 2>/dev/null; then
    echo "✅ fastcgi_pass configured for ruangtes_app upstream"
    if grep -A 1 "upstream ruangtes_app" /etc/nginx/sites-available/ruangtes-api 2>/dev/null | grep -q "127.0.0.1:9000"; then
        echo "✅ Upstream points to 127.0.0.1:9000"
    else
        echo "⚠️  Upstream configuration might be incorrect"
    fi
else
    echo "❌ fastcgi_pass not configured correctly"
fi
echo ""

# 7. Test PHP-FPM connection
echo "7️⃣  Testing PHP-FPM connection..."
if timeout 2 bash -c "echo > /dev/tcp/127.0.0.1/9000" 2>/dev/null; then
    echo "✅ Can connect to 127.0.0.1:9000"
else
    echo "❌ Cannot connect to 127.0.0.1:9000"
    echo "   Check: docker compose -f docker-compose.prod.yml ps app"
    echo "   Check: netstat -tlnp | grep 9000"
fi
echo ""

# 8. Check Nginx error logs
echo "8️⃣  Recent Nginx error logs (last 10 lines):"
if [ -f /var/log/nginx/ruangtes-error.log ]; then
    sudo tail -10 /var/log/nginx/ruangtes-error.log
else
    echo "⚠️  Error log file not found"
fi
echo ""

# 9. Test PHP file directly
echo "9️⃣  Testing PHP execution..."
TEST_FILE="/tmp/test_php.php"
echo "<?php phpinfo(); ?>" | sudo tee $TEST_FILE > /dev/null
if docker compose -f docker-compose.prod.yml exec -T app php $TEST_FILE > /dev/null 2>&1; then
    echo "✅ PHP is working in container"
else
    echo "❌ PHP execution failed in container"
fi
sudo rm -f $TEST_FILE
echo ""

# 10. Check file permissions
echo "🔟 Checking file permissions..."
if [ -n "$NGINX_ROOT" ] && [ -d "$NGINX_ROOT" ]; then
    PERMS=$(stat -c "%a" "$NGINX_ROOT" 2>/dev/null || stat -f "%OLp" "$NGINX_ROOT" 2>/dev/null)
    OWNER=$(stat -c "%U:%G" "$NGINX_ROOT" 2>/dev/null || stat -f "%Su:%Sg" "$NGINX_ROOT" 2>/dev/null)
    echo "   Document root: $PERMS, Owner: $OWNER"
    if [ "$PERMS" != "755" ] && [ "$PERMS" != "775" ]; then
        echo "   ⚠️  Permissions might need fixing"
    fi
fi
echo ""

echo "✅ Troubleshooting completed!"
echo ""
echo "Common fixes:"
echo "1. If port 9000 not listening: docker compose -f docker-compose.prod.yml restart app"
echo "2. If document root wrong: Update /etc/nginx/sites-available/ruangtes-api"
echo "3. If permission issue: sudo chown -R \$USER:33 $NGINX_ROOT"
echo "4. Reload Nginx: sudo systemctl reload nginx"
