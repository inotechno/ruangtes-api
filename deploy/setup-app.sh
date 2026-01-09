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
sudo chown -R $USER:$USER storage bootstrap/cache .env vendor 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod 664 .env 2>/dev/null || true
echo "✅ Storage permissions fixed"
echo ""

# 2. Install composer dependencies
echo "2️⃣  Installing composer dependencies..."
if [ ! -d "vendor" ]; then
    echo "   Running composer install..."
    $DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    # Fix vendor ownership after install
    sudo chown -R $USER:$USER vendor 2>/dev/null || true
    echo "✅ Composer dependencies installed"
else
    echo "✅ Vendor directory already exists"
fi
echo ""

# 3. Fix .env permissions before generating key
echo "3️⃣  Fixing .env permissions..."
sudo chown $USER:$USER .env 2>/dev/null || true
chmod 664 .env 2>/dev/null || true
echo "✅ .env permissions fixed"
echo ""

# 4. Generate APP_KEY if not set
echo "4️⃣  Checking APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "   Generating APP_KEY..."
    # Run as root to avoid permission issues, then fix ownership
    $DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan key:generate --force
    sudo chown $USER:$USER .env 2>/dev/null || true
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY already set"
fi
echo ""

# 5. Fix storage permissions for www-data (container user)
echo "5️⃣  Fixing storage permissions for container..."
# Make sure storage is writable by www-data (UID 33 in Alpine)
sudo chown -R $USER:33 storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
# Also ensure www-data can write to .env temporarily
sudo chmod 666 .env 2>/dev/null || true
echo "✅ Storage permissions fixed for container"
echo ""

# 6. Run migrations
echo "6️⃣  Running migrations..."
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan migrate --force || echo "⚠️  Migration failed, check database connection"
echo ""

# 7. Create storage link
echo "7️⃣  Creating storage link..."
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan storage:link || true
echo "✅ Storage link created"
echo ""

# 8. Clear and cache config
echo "8️⃣  Clearing and caching configuration..."
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan config:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan cache:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan route:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan view:clear || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan config:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan route:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml run --rm -u root app php artisan view:cache || true
echo "✅ Configuration cached"
echo ""

# 9. Finalize permissions
echo "9️⃣  Finalizing permissions..."
# Restore .env to secure permissions
sudo chown $USER:$USER .env 2>/dev/null || true
chmod 644 .env 2>/dev/null || true
# Ensure storage is writable by both user and www-data
sudo chown -R $USER:33 storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
echo "✅ Permissions finalized"
echo ""

echo "✅ Application setup completed!"
echo ""
echo "Next steps:"
echo "1. Start containers: docker compose -f docker-compose.prod.yml up -d"
echo "2. Check status: ./deploy/check-status.sh"
echo "3. Test API: curl https://api.ruangtes.web.id/health"
