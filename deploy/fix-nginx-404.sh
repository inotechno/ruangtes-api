#!/bin/bash

# Script to fix Nginx 404 error

set -e

echo "🔧 Fixing Nginx 404 Error..."
echo ""

NGINX_CONFIG="/etc/nginx/sites-available/ruangtes-api"

# 1. Check current config
echo "1️⃣  Checking Nginx configuration..."
if [ ! -f "$NGINX_CONFIG" ]; then
    echo "❌ Nginx config file not found: $NGINX_CONFIG"
    exit 1
fi

echo "✅ Config file exists: $NGINX_CONFIG"
echo ""

# 2. Check try_files directive
echo "2️⃣  Checking try_files directive..."
if grep -q "try_files" "$NGINX_CONFIG"; then
    echo "✅ try_files directive exists"
    grep "try_files" "$NGINX_CONFIG" | head -1
else
    echo "❌ try_files directive NOT found"
    echo "   This is required for Laravel routing"
fi
echo ""

# 3. Check PHP location block
echo "3️⃣  Checking PHP location block..."
if grep -q "location ~ \\\.php\$" "$NGINX_CONFIG"; then
    echo "✅ PHP location block exists"
else
    echo "❌ PHP location block NOT found"
fi
echo ""

# 4. Check fastcgi_param SCRIPT_FILENAME
echo "4️⃣  Checking fastcgi_param SCRIPT_FILENAME..."
if grep -q "fastcgi_param SCRIPT_FILENAME" "$NGINX_CONFIG"; then
    echo "✅ fastcgi_param SCRIPT_FILENAME exists"
    grep "fastcgi_param SCRIPT_FILENAME" "$NGINX_CONFIG"
else
    echo "❌ fastcgi_param SCRIPT_FILENAME NOT found"
fi
echo ""

# 5. Show current location / block
echo "5️⃣  Current location / block:"
grep -A 3 "location / {" "$NGINX_CONFIG" | head -5 || echo "Not found"
echo ""

# 6. Create backup
echo "6️⃣  Creating backup..."
sudo cp "$NGINX_CONFIG" "$NGINX_CONFIG.backup.$(date +%Y%m%d_%H%M%S)"
echo "✅ Backup created"
echo ""

# 7. Show recommended fix
echo "7️⃣  Recommended Nginx configuration:"
cat << 'EOF'

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
    fastcgi_hide_header X-Powered-By;
    fastcgi_read_timeout 300;
    fastcgi_buffer_size 128k;
    fastcgi_buffers 4 256k;
    fastcgi_busy_buffers_size 256k;
}

EOF

echo "8️⃣  To fix manually:"
echo "   sudo nano $NGINX_CONFIG"
echo "   Make sure location / has: try_files \$uri \$uri/ /index.php?\$query_string;"
echo "   Make sure location ~ \.php$ has: fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;"
echo ""

# 9. Test current config
echo "9️⃣  Testing Nginx configuration..."
if sudo nginx -t 2>&1 | grep -q "successful"; then
    echo "✅ Nginx configuration is valid"
else
    echo "❌ Nginx configuration has errors:"
    sudo nginx -t
fi
echo ""

# 10. Test PHP file directly
echo "🔟 Testing PHP execution..."
cd /opt/ruangtes-api
TEST_PHP="/tmp/test_nginx.php"
echo "<?php echo 'PHP OK: ' . phpversion(); ?>" | sudo tee "$TEST_PHP" > /dev/null

if docker compose -f docker-compose.prod.yml exec -T app php "$TEST_PHP" > /dev/null 2>&1; then
    echo "✅ PHP can execute files"
    docker compose -f docker-compose.prod.yml exec -T app php "$TEST_PHP"
else
    echo "❌ PHP execution failed"
fi
sudo rm -f "$TEST_PHP"
echo ""

echo "✅ Diagnosis completed!"
echo ""
echo "If still 404, check:"
echo "1. try_files directive in location / block"
echo "2. fastcgi_param SCRIPT_FILENAME uses \$realpath_root"
echo "3. Test: curl http://localhost/index.php"
