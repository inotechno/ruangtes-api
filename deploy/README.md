# Deployment Guide - RuangTes API to EC2

Panduan lengkap untuk deploy RuangTes API ke EC2 menggunakan Docker dan GitHub Actions.

## Prerequisites

1. **EC2 Instance**
   - Ubuntu 22.04 LTS atau lebih baru
   - Minimum: 2 vCPU, 4GB RAM
   - Recommended: 4 vCPU, 8GB RAM
   - Security Group: Open ports 22 (SSH), 80 (HTTP), 443 (HTTPS)

2. **AWS Account**
   - ECR (Elastic Container Registry) untuk menyimpan Docker images
   - IAM user dengan permissions untuk ECR

3. **GitHub Repository**
   - Repository dengan kode aplikasi
   - GitHub Secrets untuk deployment

## Setup Steps

### 1. Setup EC2 Instance

#### A. Initial Server Setup

SSH ke EC2 instance dan jalankan setup script:

```bash
# Download setup script
curl -O https://raw.githubusercontent.com/your-repo/ruangtes-api/main/deploy/setup-ec2.sh

# Make executable
chmod +x setup-ec2.sh

# Run as root
sudo ./setup-ec2.sh
```

Script ini akan:
- Install Docker & Docker Compose
- Install AWS CLI
- Install Nginx
- Install Certbot
- Setup firewall
- Create application directory
- Create Nginx configuration

#### B. Configure AWS CLI

```bash
aws configure
# Enter your AWS Access Key ID
# Enter your AWS Secret Access Key
# Enter default region (e.g., ap-southeast-1)
# Enter default output format (json)
```

#### C. Create ECR Repository

```bash
aws ecr create-repository \
    --repository-name ruangtes-api \
    --region ap-southeast-1 \
    --image-scanning-configuration scanOnPush=true \
    --image-tag-mutability MUTABLE
```

Catat ECR repository URI yang dihasilkan.

### 2. Setup GitHub Secrets

Di GitHub repository, tambahkan secrets berikut:

1. **AWS_ACCESS_KEY_ID** - AWS Access Key ID
2. **AWS_SECRET_ACCESS_KEY** - AWS Secret Access Key
3. **EC2_HOST** - EC2 Public IP atau Domain
4. **EC2_USERNAME** - SSH username (biasanya `ubuntu` atau `ec2-user`)
5. **EC2_SSH_KEY** - Private SSH key untuk EC2
6. **EC2_PORT** - SSH port (default: 22)

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
git clone https://github.com/your-username/ruangtes-api.git .
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
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ruangtes
DB_USERNAME=ruangtes
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password
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
server_name your-domain.com www.your-domain.com;
```

Test dan reload Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 6. Setup SSL with Certbot

```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Certbot akan:
- Generate SSL certificate
- Update Nginx configuration untuk HTTPS
- Setup auto-renewal

### 7. Initial Deployment

#### Option A: Manual Deployment (First Time)

```bash
cd /opt/ruangtes-api

# Build and start containers
docker-compose -f docker-compose.prod.yml up -d --build

# Generate application key
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Seed database (optional)
docker-compose -f docker-compose.prod.yml exec app php artisan db:seed

# Setup storage link
docker-compose -f docker-compose.prod.yml exec app php artisan storage:link

# Cache configuration
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

#### Option B: Using GitHub Actions

Push code ke branch `main` atau `master`, GitHub Actions akan otomatis:
1. Run tests
2. Build Docker image
3. Push to ECR
4. Deploy to EC2

### 8. Verify Deployment

```bash
# Check containers status
docker-compose -f docker-compose.prod.yml ps

# Check logs
docker-compose -f docker-compose.prod.yml logs -f app

# Test API
curl https://your-domain.com/api/health
```

## GitHub Actions Workflow

Workflow akan:
1. **Test** - Run PHPUnit/Pest tests
2. **Build** - Build Docker image
3. **Push** - Push image to ECR
4. **Deploy** - Deploy to EC2 via SSH

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
docker-compose -f docker-compose.prod.yml logs -f app

# Queue logs
docker-compose -f docker-compose.prod.yml logs -f queue

# Scheduler logs
docker-compose -f docker-compose.prod.yml logs -f scheduler

# Nginx logs
sudo tail -f /var/log/nginx/ruangtes-access.log
sudo tail -f /var/log/nginx/ruangtes-error.log
```

### Run Artisan Commands

```bash
docker-compose -f docker-compose.prod.yml exec app php artisan <command>
```

### Database Backup

```bash
# Backup database
docker-compose -f docker-compose.prod.yml exec postgres pg_dump -U ruangtes ruangtes > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database
docker-compose -f docker-compose.prod.yml exec -T postgres psql -U ruangtes ruangtes < backup.sql
```

### Update Application

```bash
cd /opt/ruangtes-api
git pull origin main
docker-compose -f docker-compose.prod.yml up -d --build
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml restart queue scheduler
```

### Clean Up

```bash
# Remove unused images
docker image prune -af

# Remove unused volumes
docker volume prune -f

# Remove unused networks
docker network prune -f
```

## Troubleshooting

### Container won't start

```bash
# Check logs
docker-compose -f docker-compose.prod.yml logs app

# Check container status
docker-compose -f docker-compose.prod.yml ps

# Restart containers
docker-compose -f docker-compose.prod.yml restart
```

### Database connection error

```bash
# Check PostgreSQL container
docker-compose -f docker-compose.prod.yml logs postgres

# Test connection
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Redis connection error

```bash
# Check Redis container
docker-compose -f docker-compose.prod.yml logs redis

# Test connection
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
>>> Redis::ping();
```

### Permission errors

```bash
# Fix storage permissions
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache
```

### Nginx 502 Bad Gateway

```bash
# Check PHP-FPM container
docker-compose -f docker-compose.prod.yml ps app

# Check Nginx error logs
sudo tail -f /var/log/nginx/ruangtes-error.log

# Restart containers
docker-compose -f docker-compose.prod.yml restart app nginx
```

## Security Considerations

1. **Environment Variables**: Jangan commit `.env` ke repository
2. **Database Passwords**: Gunakan strong passwords
3. **Redis Password**: Set Redis password
4. **Firewall**: Hanya buka port yang diperlukan
5. **SSL**: Selalu gunakan HTTPS di production
6. **Updates**: Update sistem dan Docker images secara berkala

## Monitoring

### Health Check Endpoint

Create health check endpoint:

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::ping() ? 'connected' : 'disconnected',
    ]);
});
```

### Setup Monitoring (Optional)

- **CloudWatch**: Monitor EC2 metrics
- **Sentry**: Error tracking
- **New Relic**: Application performance monitoring

## Support

Untuk pertanyaan atau issues, hubungi tim development.
