#!/bin/bash

# Script to test PHP-FPM and Laravel directly

set -e

echo "🧪 Testing PHP-FPM and Laravel..."
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

# 1. Test PHP execution
echo "1️⃣  Testing PHP execution..."
PHP_TEST=$(docker compose -f docker-compose.prod.yml exec -T app php -r "echo 'PHP Version: ' . phpversion();" 2>&1)
if [ $? -eq 0 ]; then
    echo "✅ PHP is working: $PHP_TEST"
else
    echo "❌ PHP execution failed: $PHP_TEST"
fi
echo ""

# 2. Test index.php directly
echo "2️⃣  Testing index.php execution..."
if [ -f "public/index.php" ]; then
    PHP_OUTPUT=$(docker compose -f docker-compose.prod.yml exec -T app php public/index.php 2>&1 | head -5)
    if [ $? -eq 0 ]; then
        echo "✅ index.php can be executed"
        echo "   Output (first 5 lines):"
        echo "$PHP_OUTPUT" | sed 's/^/   /'
    else
        echo "❌ index.php execution failed"
        echo "   Error: $PHP_OUTPUT"
    fi
else
    echo "❌ index.php not found"
fi
echo ""

# 3. Check vendor/autoload.php
echo "3️⃣  Checking vendor/autoload.php..."
if [ -f "vendor/autoload.php" ]; then
    echo "✅ vendor/autoload.php exists"
    # Test if it can be loaded
    PHP_AUTOLOAD=$(docker compose -f docker-compose.prod.yml exec -T app php -r "require 'vendor/autoload.php'; echo 'Autoload OK';" 2>&1)
    if [ $? -eq 0 ]; then
        echo "✅ Autoload can be loaded: $PHP_AUTOLOAD"
    else
        echo "❌ Autoload failed: $PHP_AUTOLOAD"
    fi
else
    echo "❌ vendor/autoload.php NOT found"
    echo "   Run: ./deploy/setup-app.sh"
fi
echo ""

# 4. Test Laravel bootstrap
echo "4️⃣  Testing Laravel bootstrap..."
if [ -f "bootstrap/app.php" ]; then
    echo "✅ bootstrap/app.php exists"
    # Try to bootstrap Laravel
    LARAVEL_TEST=$(docker compose -f docker-compose.prod.yml exec -T app php -r "
        require 'vendor/autoload.php';
        \$app = require_once 'bootstrap/app.php';
        echo 'Laravel bootstrap OK';
    " 2>&1)
    if [ $? -eq 0 ]; then
        echo "✅ Laravel can bootstrap: $LARAVEL_TEST"
    else
        echo "❌ Laravel bootstrap failed"
        echo "   Error: $LARAVEL_TEST"
    fi
else
    echo "❌ bootstrap/app.php NOT found"
fi
echo ""

# 5. Test via FastCGI directly
echo "5️⃣  Testing FastCGI connection..."
# Create test PHP file
TEST_FILE="/opt/ruangtes-api/public/test_fcgi.php"
echo "<?php echo 'FastCGI Test: ' . phpversion(); ?>" | sudo tee "$TEST_FILE" > /dev/null

# Test via curl
FCGI_TEST=$(curl -s http://localhost/test_fcgi.php 2>&1)
if echo "$FCGI_TEST" | grep -q "FastCGI Test"; then
    echo "✅ FastCGI is working"
    echo "   Response: $FCGI_TEST"
else
    echo "❌ FastCGI test failed"
    echo "   Response: $FCGI_TEST"
fi

# Cleanup
sudo rm -f "$TEST_FILE"
echo ""

# 6. Test health endpoint
echo "6️⃣  Testing /health endpoint..."
HEALTH_TEST=$(curl -s http://localhost/health 2>&1)
if echo "$HEALTH_TEST" | grep -q "status"; then
    echo "✅ Health endpoint working"
    echo "$HEALTH_TEST" | head -3
else
    echo "❌ Health endpoint failed"
    echo "   Response: $HEALTH_TEST"
fi
echo ""

# 7. Check .env
echo "7️⃣  Checking .env configuration..."
if [ -f ".env" ]; then
    echo "✅ .env exists"
    if grep -q "APP_KEY=base64:" .env; then
        echo "✅ APP_KEY is set"
    else
        echo "❌ APP_KEY is NOT set"
    fi
    if grep -q "DB_PASSWORD=" .env && ! grep -q "DB_PASSWORD=CHANGE_THIS_PASSWORD" .env; then
        echo "✅ DB_PASSWORD is set"
    else
        echo "❌ DB_PASSWORD needs to be configured"
    fi
else
    echo "❌ .env file NOT found"
fi
echo ""

echo "✅ Testing completed!"
