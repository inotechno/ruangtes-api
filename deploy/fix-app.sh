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

# 1. Fix storage permissions
echo "1️⃣  Fixing storage permissions..."
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app chown -R www-data:www-data storage bootstrap/cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app chmod -R 775 storage bootstrap/cache || true
echo "✅ Storage permissions fixed"
echo ""

# 2. Generate APP_KEY if not set
echo "2️⃣  Checking APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "   Generating APP_KEY..."
    $DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan key:generate --force
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY already set"
fi
echo ""

# 3. Clear and cache config
echo "3️⃣  Clearing and caching configuration..."
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan config:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan cache:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan route:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan view:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan config:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan route:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan view:cache || true
echo "✅ Configuration cached"
echo ""

# 4. Create storage link
echo "4️⃣  Creating storage link..."
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan storage:link || true
echo "✅ Storage link created"
echo ""

# 5. Restart containers
echo "5️⃣  Restarting containers..."
$DOCKER_COMPOSE -f docker-compose.prod.yml restart app queue scheduler
echo "✅ Containers restarted"
echo ""

echo "✅ Fix completed!"
echo ""
echo "Next steps:"
echo "1. Check status: ./deploy/check-status.sh"
echo "2. Check logs: docker compose -f docker-compose.prod.yml logs -f app"
echo "3. Test API: curl https://api.ruangtes.web.id/health"
