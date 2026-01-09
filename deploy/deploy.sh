#!/bin/bash

# Deployment script for server (no Docker)

set -e

echo "🚀 Deploying RuangTes API..."

cd /opt/ruangtes-api

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main || git pull origin master

# Install/update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force || true

# Clear and cache config
echo "⚙️  Caching configuration..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Restart PHP-FPM
echo "🔄 Restarting PHP-FPM..."
sudo systemctl reload php8.4-fpm || sudo systemctl reload php-fpm || true

# Restart queue workers
echo "🔄 Restarting queue workers..."
sudo supervisorctl restart ruangtes-queue:* || true

# Clear application cache
echo "🧹 Clearing application cache..."
php artisan cache:clear || true

echo "✅ Deployment completed!"
