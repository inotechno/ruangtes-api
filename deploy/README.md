# Deployment Guide - RuangTes API to EC2 (No Docker)

Panduan lengkap untuk deploy RuangTes API ke EC2 menggunakan instalasi langsung di server (tanpa Docker) dengan GitHub Actions.

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

### 1. Setup EC2 Instance

#### A. Initial Server Setup

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
- Install PHP 8.2 FPM dengan semua extensions yang diperlukan
- Install PostgreSQL
- Install Redis
- Install Nginx
- Install Composer
- Install Node.js & npm
- Install Certbot
- Install Supervisor (untuk queue workers & scheduler)
- Setup PostgreSQL database
- Create Nginx configuration
- Create Supervisor configurations
- Setup firewall

#### B. Database Setup

Setup script akan otomatis create database. Catat password yang di-generate, atau buat password sendiri:

```bash
# Create database manually (optional)
sudo -u postgres psql
CREATE USER ruangtes WITH PASSWORD 'your_secure_password';
CREATE DATABASE ruangtes OWNER ruangtes;
GRANT ALL PRIVILEGES ON DATABASE ruangtes TO ruangtes;
\q
```

### 2. Setup GitHub Secrets

Di GitHub repository, tambahkan secrets berikut:

1. **EC2_HOST** - EC2 Public IP atau Domain
2. **EC2_USERNAME** - SSH username (biasanya `ubuntu`)
3. **EC2_SSH_KEY** - Private SSH key untuk EC2
4. **EC2_PORT** - SSH port (default: 22)

**Cara mendapatkan SSH key:**
```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -C "github-actions"

# Copy public key to EC2
ssh-copy-id -i ~/.ssh/id_rsa.pub user@ec2-ip

# Copy private key content to GitHub Secret
cat ~/.ssh/id_rsa
```

### 3. Clone Repository to EC2

```bash
cd /opt/ruangtes-api
git clone https://github.com/inotechno/ruangtes-api.git .
```

### 4. Configure Environment Variables

```bash
cd /opt/ruangtes-api
cp .env.example .env
nano .env
```

Update konfigurasi berikut:

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
DB_PASSWORD=your_secure_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="RuangTes"

# Frontend URL
FRONTEND_URL=https://your-frontend-domain.com
```

### 5. Setup Nginx Configuration

Edit Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/ruangtes-api
```

Update `server_name` dengan domain Anda:

```nginx
server_name api.ruangtes.web.id www.api.ruangtes.web.id;
```

Test dan reload Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 6. Setup SSL with Certbot

```bash
sudo certbot --nginx -d api.ruangtes.web.id -d www.api.ruangtes.web.id
```

Certbot akan:
- Generate SSL certificate
- Update Nginx configuration untuk HTTPS
- Setup auto-renewal

### 7. Initial Application Setup

```bash
cd /opt/ruangtes-api

# Install dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if needed)
npm install
npm run build

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed

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

### 8. Verify Deployment

```bash
# Check services status
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

## GitHub Actions Workflow

Workflow akan:
1. **Test** - Run PHPUnit/Pest tests
2. **Deploy** - SSH ke EC2 dan deploy

### Manual Deployment Script

Jika ingin deploy manual tanpa GitHub Actions:

```bash
cd /opt/ruangtes-api
./deploy.sh
```

## Maintenance

### View Logs

```bash
# Application logs
tail -f /opt/ruangtes-api/storage/logs/laravel.log

# Queue worker logs
tail -f /opt/ruangtes-api/storage/logs/queue-worker.log

# Scheduler logs
tail -f /opt/ruangtes-api/storage/logs/scheduler.log

# Nginx logs
sudo tail -f /var/log/nginx/ruangtes-access.log
sudo tail -f /var/log/nginx/ruangtes-error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log
```

### Run Artisan Commands

```bash
cd /opt/ruangtes-api
php artisan <command>
```

### Database Backup

```bash
# Backup database
sudo -u postgres pg_dump ruangtes > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database
sudo -u postgres psql ruangtes < backup.sql
```

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

### Service Management

#### PHP-FPM
```bash
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
sudo systemctl reload php8.2-fpm
```

#### Nginx
```bash
sudo systemctl status nginx
sudo systemctl restart nginx
sudo systemctl reload nginx
sudo nginx -t  # Test configuration
```

#### Queue Workers
```bash
sudo supervisorctl status
sudo supervisorctl restart ruangtes-queue:*
sudo supervisorctl stop ruangtes-queue:*
sudo supervisorctl start ruangtes-queue:*
```

#### Scheduler
```bash
sudo supervisorctl status ruangtes-scheduler
sudo supervisorctl restart ruangtes-scheduler
```

#### PostgreSQL
```bash
sudo systemctl status postgresql
sudo systemctl restart postgresql

# Access database
sudo -u postgres psql
# Or
psql -U ruangtes -d ruangtes
```

#### Redis
```bash
sudo systemctl status redis-server
sudo systemctl restart redis-server

# Test
redis-cli ping
```

## Troubleshooting

### Container won't start

N/A - Tidak menggunakan Docker

### Database connection error

```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Test connection
psql -U ruangtes -d ruangtes -h 127.0.0.1

# Check PostgreSQL logs
sudo tail -f /var/log/postgresql/postgresql-*.log
```

### Redis connection error

```bash
# Check Redis status
sudo systemctl status redis-server

# Test connection
redis-cli ping

# Check Redis logs
sudo tail -f /var/log/redis/redis-server.log
```

### Permission errors

```bash
# Fix storage permissions
sudo chown -R www-data:www-data /opt/ruangtes-api/storage
sudo chown -R www-data:www-data /opt/ruangtes-api/bootstrap/cache
sudo chmod -R 775 /opt/ruangtes-api/storage
sudo chmod -R 775 /opt/ruangtes-api/bootstrap/cache
```

### Nginx 502 Bad Gateway

```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check PHP-FPM socket
ls -la /var/run/php/php8.2-fpm.sock

# Check Nginx error logs
sudo tail -f /var/log/nginx/ruangtes-error.log

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
```

### Queue Workers Not Running

```bash
# Check Supervisor status
sudo supervisorctl status

# Reread and update
sudo supervisorctl reread
sudo supervisorctl update

# Restart workers
sudo supervisorctl restart ruangtes-queue:*

# Check logs
tail -f /opt/ruangtes-api/storage/logs/queue-worker.log
```

## Security Considerations

1. **Environment Variables**: Jangan commit `.env` ke repository
2. **Database Passwords**: Gunakan strong passwords
3. **Redis**: Set password jika diperlukan (edit `/etc/redis/redis.conf`)
4. **Firewall**: Hanya buka port yang diperlukan
5. **SSL**: Selalu gunakan HTTPS di production
6. **Updates**: Update sistem dan packages secara berkala

## Monitoring

### Health Check Endpoint

Health check endpoint tersedia di `/health`:

```bash
curl https://api.ruangtes.web.id/health
```

### Setup Monitoring (Optional)

- **CloudWatch**: Monitor EC2 metrics
- **Sentry**: Error tracking
- **New Relic**: Application performance monitoring

## Support

Untuk pertanyaan atau issues, hubungi tim development.
