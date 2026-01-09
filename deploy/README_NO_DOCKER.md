# Deployment Guide - Without Docker

Panduan deployment RuangTes API ke EC2 tanpa Docker, menggunakan instalasi langsung di server.

## Prerequisites

1. **EC2 Instance**
   - Ubuntu 22.04 LTS atau lebih baru
   - Minimum: 2 vCPU, 4GB RAM
   - Recommended: 4 vCPU, 8GB RAM
   - Security Group: Open ports 22 (SSH), 80 (HTTP), 443 (HTTPS)

2. **GitHub Repository**
   - Repository dengan kode aplikasi
   - GitHub Secrets untuk deployment

## Setup Steps

### 1. Setup Server

SSH ke EC2 instance dan jalankan setup script:

```bash
# Download setup script
curl -O https://raw.githubusercontent.com/inotechno/ruangtes-api/main/deploy/setup-server.sh

# Make executable
chmod +x setup-server.sh

# Run as root
sudo ./setup-server.sh
```

Script ini akan:
- Install PHP 8.2 FPM dengan semua extensions
- Install PostgreSQL
- Install Redis
- Install Nginx
- Install Composer
- Install Node.js & npm
- Install Certbot
- Install Supervisor (untuk queue workers)
- Setup database PostgreSQL
- Create Nginx configuration
- Create Supervisor configurations
- Setup firewall

### 2. Clone Repository

```bash
cd /opt/ruangtes-api
git clone https://github.com/inotechno/ruangtes-api.git .
```

### 3. Configure Environment

```bash
cd /opt/ruangtes-api
cp .env.example .env
nano .env
```

Update konfigurasi:
```env
APP_NAME=RuangTes
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.ruangtes.web.id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ruangtes
DB_USERNAME=ruangtes
DB_PASSWORD=your_password_from_setup_script

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 4. Install Dependencies

```bash
cd /opt/ruangtes-api

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if needed)
npm install
npm run build
```

### 5. Setup Application

```bash
cd /opt/ruangtes-api

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Setup Nginx

Edit Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/ruangtes-api
```

Update `server_name` dengan domain Anda, kemudian:

```bash
# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### 7. Setup SSL

```bash
sudo certbot --nginx -d api.ruangtes.web.id -d www.api.ruangtes.web.id
```

### 8. Verify

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

## GitHub Actions Deployment

GitHub Actions akan otomatis:
1. Run tests
2. SSH ke EC2
3. Pull latest code
4. Install dependencies
5. Run migrations
6. Cache config
7. Restart services

### Setup GitHub Secrets

Di GitHub repository → Settings → Secrets and variables → Actions:

- `EC2_HOST` - EC2 Public IP atau Domain
- `EC2_USERNAME` - SSH username (biasanya `ubuntu`)
- `EC2_SSH_KEY` - Private SSH key untuk EC2
- `EC2_PORT` - SSH port (default: 22)

## Manual Deployment

Jika ingin deploy manual:

```bash
cd /opt/ruangtes-api
./deploy.sh
```

Atau step by step:

```bash
cd /opt/ruangtes-api

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl reload php8.2-fpm
sudo supervisorctl restart ruangtes-queue:*
```

## Service Management

### PHP-FPM
```bash
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
sudo systemctl reload php8.2-fpm
```

### Nginx
```bash
sudo systemctl status nginx
sudo systemctl restart nginx
sudo systemctl reload nginx
sudo nginx -t  # Test configuration
```

### Queue Workers
```bash
sudo supervisorctl status
sudo supervisorctl restart ruangtes-queue:*
sudo supervisorctl stop ruangtes-queue:*
sudo supervisorctl start ruangtes-queue:*
```

### Scheduler
```bash
sudo supervisorctl status ruangtes-scheduler
sudo supervisorctl restart ruangtes-scheduler
```

### PostgreSQL
```bash
sudo systemctl status postgresql
sudo systemctl restart postgresql

# Access database
sudo -u postgres psql
# Or
psql -U ruangtes -d ruangtes
```

### Redis
```bash
sudo systemctl status redis-server
sudo systemctl restart redis-server

# Test
redis-cli ping
```

## Logs

### Application Logs
```bash
tail -f /opt/ruangtes-api/storage/logs/laravel.log
```

### Queue Worker Logs
```bash
tail -f /opt/ruangtes-api/storage/logs/queue-worker.log
```

### Scheduler Logs
```bash
tail -f /opt/ruangtes-api/storage/logs/scheduler.log
```

### Nginx Logs
```bash
sudo tail -f /var/log/nginx/ruangtes-access.log
sudo tail -f /var/log/nginx/ruangtes-error.log
```

### PHP-FPM Logs
```bash
sudo tail -f /var/log/php8.2-fpm.log
```

## Troubleshooting

### Permission Issues
```bash
sudo chown -R www-data:www-data /opt/ruangtes-api/storage
sudo chown -R www-data:www-data /opt/ruangtes-api/bootstrap/cache
sudo chmod -R 775 /opt/ruangtes-api/storage
sudo chmod -R 775 /opt/ruangtes-api/bootstrap/cache
```

### PHP-FPM Not Running
```bash
sudo systemctl status php8.2-fpm
sudo systemctl start php8.2-fpm
sudo journalctl -u php8.2-fpm -f
```

### Queue Workers Not Running
```bash
sudo supervisorctl status
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart ruangtes-queue:*
```

### Database Connection Issues
```bash
# Test connection
psql -U ruangtes -d ruangtes -h 127.0.0.1

# Check PostgreSQL status
sudo systemctl status postgresql

# Check PostgreSQL logs
sudo tail -f /var/log/postgresql/postgresql-*.log
```

## Maintenance

### Update Application
```bash
cd /opt/ruangtes-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.2-fpm
sudo supervisorctl restart ruangtes-queue:*
```

### Backup Database
```bash
# Backup
sudo -u postgres pg_dump ruangtes > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore
sudo -u postgres psql ruangtes < backup.sql
```

### Clear All Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```
