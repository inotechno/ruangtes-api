#!/bin/bash

# Final fix for Nginx 404 - Laravel works but Nginx returns 404

set -e

NGINX_CONFIG="/etc/nginx/sites-available/ruangtes-api"

echo "🔧 Final Fix for Nginx 404 Error..."
echo ""

# 1. Check current config
echo "1️⃣  Analyzing current Nginx configuration..."
echo ""

# Check if location / comes before location ~ \.php$
LOCATION_ORDER=$(grep -n "location" "$NGINX_CONFIG" | head -5)
echo "   Location blocks order:"
echo "$LOCATION_ORDER" | sed 's/^/   /'
echo ""

# 2. Check for conflicting location blocks
echo "2️⃣  Checking for conflicting location blocks..."
if grep -q "location ~ \.php\$" "$NGINX_CONFIG" && grep -q "location / {" "$NGINX_CONFIG"; then
    PHP_LOC_LINE=$(grep -n "location ~ \\\.php" "$NGINX_CONFIG" | cut -d: -f1)
    ROOT_LOC_LINE=$(grep -n "location / {" "$NGINX_CONFIG" | cut -d: -f1)
    
    if [ "$ROOT_LOC_LINE" -lt "$PHP_LOC_LINE" ]; then
        echo "✅ Location / comes before location ~ \.php$ (correct order)"
    else
        echo "❌ Location order might be wrong"
    fi
fi
echo ""

# 3. Test with direct index.php access
echo "3️⃣  Testing direct index.php access..."
INDEX_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/index.php 2>&1)
if [ "$INDEX_TEST" = "200" ]; then
    echo "✅ Direct index.php access works (HTTP $INDEX_TEST)"
    echo "   This means PHP-FPM is working, problem is in routing"
elif [ "$INDEX_TEST" = "404" ]; then
    echo "❌ Direct index.php returns 404"
    echo "   This means Nginx can't find the file or PHP-FPM can't execute it"
else
    echo "⚠️  Direct index.php returns HTTP $INDEX_TEST"
fi
echo ""

# 4. Check if file exists from Nginx perspective
echo "4️⃣  Checking file accessibility..."
if [ -f "/opt/ruangtes-api/public/index.php" ]; then
    echo "✅ File exists: /opt/ruangtes-api/public/index.php"
    PERMS=$(stat -c "%a" /opt/ruangtes-api/public/index.php 2>/dev/null || echo "unknown")
    OWNER=$(stat -c "%U:%G" /opt/ruangtes-api/public/index.php 2>/dev/null || echo "unknown")
    echo "   Permissions: $PERMS, Owner: $OWNER"
    
    # Check if readable by nginx user
    if sudo -u www-data test -r /opt/ruangtes-api/public/index.php 2>/dev/null; then
        echo "✅ File is readable by www-data (Nginx user)"
    else
        echo "❌ File is NOT readable by www-data"
        echo "   Fix: sudo chmod 644 /opt/ruangtes-api/public/index.php"
    fi
else
    echo "❌ File does not exist"
fi
echo ""

# 5. Test FastCGI with verbose output
echo "5️⃣  Testing FastCGI with detailed output..."
# Create test file
TEST_FILE="/opt/ruangtes-api/public/test_fcgi_detailed.php"
echo "<?php 
echo 'FastCGI Test\n';
echo 'SCRIPT_FILENAME: ' . \$_SERVER['SCRIPT_FILENAME'] . '\n';
echo 'DOCUMENT_ROOT: ' . \$_SERVER['DOCUMENT_ROOT'] . '\n';
echo 'REQUEST_URI: ' . \$_SERVER['REQUEST_URI'] . '\n';
?>" | sudo tee "$TEST_FILE" > /dev/null

FCGI_DETAILED=$(curl -s http://localhost/test_fcgi_detailed.php 2>&1)
if echo "$FCGI_DETAILED" | grep -q "FastCGI Test"; then
    echo "✅ FastCGI is working"
    echo "$FCGI_DETAILED"
else
    echo "❌ FastCGI test failed"
    echo "   Response: $FCGI_DETAILED"
fi
sudo rm -f "$TEST_FILE"
echo ""

# 6. Check Nginx error log for specific errors
echo "6️⃣  Recent Nginx errors (last 5 lines)..."
if [ -f /var/log/nginx/ruangtes-error.log ]; then
    RECENT=$(sudo tail -5 /var/log/nginx/ruangtes-error.log 2>/dev/null)
    if [ -n "$RECENT" ]; then
        echo "$RECENT" | sed 's/^/   /'
    else
        echo "   No recent errors"
    fi
else
    echo "   Error log not found"
fi
echo ""

# 7. Show recommended fix
echo "7️⃣  Recommended Actions:"
echo ""
echo "   If direct index.php returns 404:"
echo "   1. Check file permissions: sudo chmod 644 /opt/ruangtes-api/public/index.php"
echo "   2. Check file ownership: sudo chown \$USER:www-data /opt/ruangtes-api/public/index.php"
echo "   3. Verify fastcgi_param SCRIPT_FILENAME uses \$realpath_root"
echo ""
echo "   If direct index.php works but routes don't:"
echo "   1. Ensure try_files directive: try_files \$uri \$uri/ /index.php?\$query_string;"
echo "   2. Clear route cache: docker compose exec app php artisan route:clear"
echo "   3. Check Laravel logs: docker compose exec app tail storage/logs/laravel.log"
echo ""

echo "✅ Diagnosis completed!"
