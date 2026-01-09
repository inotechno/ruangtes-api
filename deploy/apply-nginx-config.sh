#!/bin/bash

# Script to apply correct Nginx configuration

set -e

NGINX_CONFIG="/etc/nginx/sites-available/ruangtes-api"
BACKUP_FILE="$NGINX_CONFIG.backup.$(date +%Y%m%d_%H%M%S)"

echo "🔧 Applying correct Nginx configuration..."
echo ""

# 1. Create backup
echo "1️⃣  Creating backup..."
sudo cp "$NGINX_CONFIG" "$BACKUP_FILE"
echo "✅ Backup created: $BACKUP_FILE"
echo ""

# 2. Check if correct config exists
if [ -f "deploy/nginx-config-correct.conf" ]; then
    echo "2️⃣  Found correct config template"
    echo "   Review the config and apply manually:"
    echo ""
    echo "   sudo nano $NGINX_CONFIG"
    echo ""
    echo "   Or copy from deploy/nginx-config-correct.conf"
    echo ""
    echo "   Key points to check:"
    echo "   - location / { try_files \$uri \$uri/ /index.php?\$query_string; }"
    echo "   - location ~ \.php$ { fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name; }"
    echo ""
else
    echo "2️⃣  Config template not found, showing current config issues..."
fi

# 3. Show current problematic lines
echo "3️⃣  Checking current configuration issues..."
echo ""

# Check try_files
if ! grep -q "try_files.*index.php" "$NGINX_CONFIG"; then
    echo "❌ Missing try_files with index.php"
    echo "   Current location / block:"
    grep -A 3 "location / {" "$NGINX_CONFIG" | head -5 || echo "   Not found"
    echo ""
    echo "   Should be:"
    echo "   location / {"
    echo "       try_files \$uri \$uri/ /index.php?\$query_string;"
    echo "   }"
    echo ""
fi

# Check fastcgi_param
if ! grep -q "fastcgi_param SCRIPT_FILENAME.*realpath_root" "$NGINX_CONFIG"; then
    echo "❌ Missing or incorrect fastcgi_param SCRIPT_FILENAME"
    echo "   Current PHP location block:"
    grep -A 5 "location ~ \\\.php" "$NGINX_CONFIG" | head -8 || echo "   Not found"
    echo ""
    echo "   Should include:"
    echo "   fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;"
    echo ""
fi

# 4. Test config
echo "4️⃣  Testing Nginx configuration..."
if sudo nginx -t 2>&1 | grep -q "successful"; then
    echo "✅ Nginx configuration syntax is valid"
else
    echo "❌ Nginx configuration has syntax errors:"
    sudo nginx -t
fi
echo ""

echo "✅ Review completed!"
echo ""
echo "To fix:"
echo "1. Edit: sudo nano $NGINX_CONFIG"
echo "2. Ensure location / has: try_files \$uri \$uri/ /index.php?\$query_string;"
echo "3. Ensure location ~ \.php$ has: fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;"
echo "4. Test: sudo nginx -t"
echo "5. Reload: sudo systemctl reload nginx"
