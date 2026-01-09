#!/bin/bash

# Script to fix common application issues

set -e

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

echo "🔧 Fixing Application Issues..."
echo ""

# 1. Fix storage permissions on host
echo "1️⃣  Fixing storage permissions on host..."
sudo chown -R $USER:33 storage bootstrap/cache vendor 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
sudo chown $USER:$USER .env 2>/dev/null || true
chmod 644 .env 2>/dev/null || true
echo "✅ Storage permissions fixed"
echo ""

# 2. Install composer dependencies if needed
echo "2️⃣  Checking composer dependencies..."
if [ ! -d "vendor" ]; then
    echo "   Installing composer dependencies..."
    $DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    sudo chown -R $USER:$USER vendor 2>/dev/null || true
    echo "✅ Composer dependencies installed"
else
    echo "✅ Vendor directory exists"
fi
echo ""

# 3. Generate APP_KEY if not set
echo "3️⃣  Checking APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "   Generating APP_KEY..."
    # Temporarily make .env writable
    sudo chmod 666 .env 2>/dev/null || true
    $DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan key:generate --force
    # Restore .env permissions
    sudo chown $USER:$USER .env 2>/dev/null || true
    chmod 644 .env 2>/dev/null || true
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY already set"
fi
echo ""

# 4. Clear and cache config
echo "4️⃣  Clearing and caching configuration..."
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T -u root app php artisan config:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T -u root app php artisan cache:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T -u root app php artisan route:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T -u root app php artisan view:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T -u root app php artisan config:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T -u root app php artisan route:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T -u root app php artisan view:cache || true
echo "✅ Configuration cached"
echo ""

# 5. Create storage link
echo "5️⃣  Creating storage link..."
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan storage:link || true
echo "✅ Storage link created"
echo ""

# 6. Restart containers
echo "6️⃣  Restarting containers..."
$DOCKER_COMPOSE -f docker-compose.prod.yml restart app queue scheduler
echo "✅ Containers restarted"
echo ""

echo "✅ Fix completed!"
echo ""
echo "Next steps:"
echo "1. Check status: ./deploy/check-status.sh"
echo "2. Check logs: docker compose -f docker-compose.prod.yml logs -f app"
echo "3. Test API: curl https://api.ruangtes.web.id/health"
