#!/bin/bash

# Script to setup application (install dependencies, generate key, etc.)

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

echo "🔧 Setting up application..."
echo ""

# 1. Fix storage permissions on host
echo "1️⃣  Fixing storage permissions on host..."
sudo chown -R $USER:$USER storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true
echo "✅ Storage permissions fixed"
echo ""

# 2. Install composer dependencies
echo "2️⃣  Installing composer dependencies..."
if [ ! -d "vendor" ]; then
    echo "   Running composer install..."
    $DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    echo "✅ Composer dependencies installed"
else
    echo "✅ Vendor directory already exists"
fi
echo ""

# 3. Generate APP_KEY if not set
echo "3️⃣  Checking APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "   Generating APP_KEY..."
    $DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan key:generate --force
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY already set"
fi
echo ""

# 4. Run migrations
echo "4️⃣  Running migrations..."
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan migrate --force || echo "⚠️  Migration failed, check database connection"
echo ""

# 5. Create storage link
echo "5️⃣  Creating storage link..."
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan storage:link || true
echo "✅ Storage link created"
echo ""

# 6. Clear and cache config
echo "6️⃣  Clearing and caching configuration..."
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan config:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan cache:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan route:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan view:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan config:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan route:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm app php artisan view:cache || true
echo "✅ Configuration cached"
echo ""

# 7. Fix permissions again after setup
echo "7️⃣  Finalizing permissions..."
sudo chown -R $USER:$USER storage bootstrap/cache vendor || true
chmod -R 775 storage bootstrap/cache || true
echo "✅ Permissions finalized"
echo ""

echo "✅ Application setup completed!"
echo ""
echo "Next steps:"
echo "1. Start containers: docker compose -f docker-compose.prod.yml up -d"
echo "2. Check status: ./deploy/check-status.sh"
echo "3. Test API: curl https://api.ruangtes.web.id/health"
