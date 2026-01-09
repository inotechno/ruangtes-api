# Quick Start - Deployment Without Docker

Panduan cepat untuk deploy RuangTes API ke EC2 tanpa Docker.

## Step 1: Setup Server

```bash
# SSH ke EC2
ssh -i your-key.pem ubuntu@your-ec2-ip

# Download dan jalankan setup script
curl -O https://raw.githubusercontent.com/inotechno/ruangtes-api/main/deploy/setup-server.sh
chmod +x setup-server.sh
sudo ./setup-server.sh
```

## Step 2: Clone Repository

```bash
cd /opt/ruangtes-api
git clone https://github.com/inotechno/ruangtes-api.git .
```

## Step 3: Configure Environment

```bash
cd /opt/ruangtes-api
cp .env.example .env
nano .env
```

**Important:** Update:
- `APP_KEY` - Generate dengan `php artisan key:generate`
- `DB_PASSWORD` - Gunakan password dari setup script output
- `APP_URL` - Your domain
- `FRONTEND_URL` - Your frontend domain
- Mail configuration

## Step 4: Install Dependencies

```bash
cd /opt/ruangtes-api

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if needed)
npm install
npm run build
```

## Step 5: Setup Application

```bash
cd /opt/ruangtes-api

# Generate APP_KEY
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Step 6: Setup Nginx

```bash
# Edit Nginx config
sudo nano /etc/nginx/sites-available/ruangtes-api

# Update server_name dengan domain Anda
server_name api.ruangtes.web.id www.api.ruangtes.web.id;

# Test dan reload
sudo nginx -t
sudo systemctl reload nginx
```

## Step 7: Setup SSL

```bash
sudo certbot --nginx -d api.ruangtes.web.id -d www.api.ruangtes.web.id
```

## Step 8: Verify

```bash
# Check services
systemctl status php8.2-fpm
systemctl status nginx
systemctl status postgresql
systemctl status redis-server
systemctl status supervisor

# Check queue workers
sudo supervisorctl status

# Test API
curl https://api.ruangtes.web.id/health
```

## GitHub Actions

Setup GitHub Secrets:
- `EC2_HOST`
- `EC2_USERNAME`
- `EC2_SSH_KEY`
- `EC2_PORT` (optional)

Push ke `main` branch, GitHub Actions akan otomatis deploy.

## Manual Deployment

```bash
cd /opt/ruangtes-api
./deploy.sh
```

## Common Commands

```bash
# View logs
tail -f storage/logs/laravel.log
tail -f storage/logs/queue-worker.log
sudo tail -f /var/log/nginx/ruangtes-error.log

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo supervisorctl restart ruangtes-queue:*

# Run artisan commands
php artisan [command]

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```
