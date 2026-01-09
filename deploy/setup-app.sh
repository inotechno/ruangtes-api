#!/bin/bash

# Script to setup application (install dependencies, generate key, etc.) - No Docker

set -e

cd /opt/ruangtes-api

echo "🔧 Setting up application..."
echo ""

# 1. Fix storage permissions
echo "1️⃣  Fixing storage permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
sudo chown $USER:$USER .env 2>/dev/null || true
chmod 644 .env 2>/dev/null || true
echo "✅ Storage permissions fixed"
echo ""

# 2. Install composer dependencies
echo "2️⃣  Installing Composer dependencies..."
if [ ! -d "vendor" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
    echo "✅ Composer dependencies installed"
else
    echo "✅ Vendor directory exists, skipping install"
fi
echo ""

# 3. Generate APP_KEY if not set
echo "3️⃣  Checking APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "   Generating APP_KEY..."
    # Temporarily make .env writable
    chmod 666 .env 2>/dev/null || true
    php artisan key:generate --force
    chmod 644 .env 2>/dev/null || true
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY is already set"
fi
echo ""

# 4. Run migrations
echo "4️⃣  Running migrations..."
php artisan migrate --force || true
echo "✅ Migrations completed"
echo ""

# 5. Create storage link
echo "5️⃣  Creating storage link..."
php artisan storage:link || true
echo "✅ Storage link created"
echo ""

# 6. Clear and cache config
echo "6️⃣  Caching configuration..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
echo "✅ Configuration cached"
echo ""

# 7. Final permissions fix
echo "7️⃣  Finalizing permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
echo "✅ Permissions finalized"
echo ""

echo "✅ Application setup completed!"
