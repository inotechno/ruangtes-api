# Quick Start - Deploy to EC2

Panduan cepat untuk deploy RuangTes API ke EC2.

## Prerequisites Checklist

- [ ] EC2 instance dengan Ubuntu 22.04+
- [ ] Domain name (optional, untuk SSL)
- [ ] AWS Account dengan ECR access
- [ ] GitHub repository dengan secrets configured

## Step 1: Setup EC2 Instance

```bash
# SSH ke EC2
ssh -i your-key.pem ubuntu@your-ec2-ip

# Download dan jalankan setup script
curl -O https://raw.githubusercontent.com/your-repo/ruangtes-api/main/deploy/setup-ec2.sh
chmod +x setup-ec2.sh
sudo ./setup-ec2.sh
```

## Step 2: Configure AWS

```bash
aws configure
# Masukkan AWS Access Key ID
# Masukkan AWS Secret Access Key
# Masukkan region (ap-southeast-1)
# Masukkan output format (json)
```

## Step 3: Create ECR Repository

```bash
aws ecr create-repository \
    --repository-name ruangtes-api \
    --region ap-southeast-1
```

## Step 4: Clone Repository

```bash
# Jika direktori sudah ada, hapus dulu
sudo rm -rf /opt/ruangtes-api

# Clone repository
sudo git clone https://github.com/inotechno/ruangtes-api.git /opt/ruangtes-api

# Set ownership
sudo chown -R $USER:$USER /opt/ruangtes-api

# Masuk ke direktori
cd /opt/ruangtes-api
```

**Note:** Jika mendapat error "destination path already exists", lihat `deploy/FIX_CLONE_ISSUE.md` untuk solusi lengkap.

## Step 5: Configure Environment

```bash
cp .env.production.example .env
nano .env
```

**Important:** Update:
- `APP_KEY` - Generate dengan `php artisan key:generate`
- `DB_PASSWORD` - Strong password
- `REDIS_PASSWORD` - Strong password
- `APP_URL` - Your domain
- `FRONTEND_URL` - Your frontend domain
- Mail configuration

## Step 6: Setup Nginx

```bash
# Edit Nginx config
sudo nano /etc/nginx/sites-available/ruangtes-api

# Update server_name dengan domain Anda
server_name your-domain.com www.your-domain.com;

# Test dan reload
sudo nginx -t
sudo systemctl reload nginx
```

## Step 7: Setup SSL (Optional but Recommended)

```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

## Step 7.5: Fix Docker Permissions

Jika mendapat error "permission denied" saat menjalankan docker commands:

```bash
# Tambahkan user ke docker group
sudo usermod -aG docker $USER

# Logout dan login kembali, atau:
newgrp docker

# Verify
docker ps
```

Atau jalankan script helper:
```bash
./deploy/setup-docker-permissions.sh
```

**Note:** Setelah menambahkan user ke grup, Anda perlu logout dan login kembali.

## Step 8: Initial Deployment

### Option A: Manual Build

```bash
cd /opt/ruangtes-api

# Build and start
docker-compose -f docker-compose.prod.yml up -d --build

# Generate key
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Cache config
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

### Option B: Using GitHub Actions

1. Setup GitHub Secrets:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `EC2_HOST`
   - `EC2_USERNAME`
   - `EC2_SSH_KEY`
   - `EC2_PORT` (optional, default: 22)

2. Push to `main` branch:
   ```bash
   git push origin main
   ```

3. GitHub Actions akan otomatis:
   - Run tests
   - Build Docker image
   - Push to ECR
   - Deploy to EC2

## Step 9: Verify Deployment

```bash
# Check containers
docker-compose -f docker-compose.prod.yml ps

# Check health
curl https://your-domain.com/health

# Check logs
docker-compose -f docker-compose.prod.yml logs -f app
```

## Common Commands

```bash
# View logs
docker-compose -f docker-compose.prod.yml logs -f [service]

# Restart service
docker-compose -f docker-compose.prod.yml restart [service]

# Run artisan command
docker-compose -f docker-compose.prod.yml exec app php artisan [command]

# Access container shell
docker-compose -f docker-compose.prod.yml exec app sh

# Update application
cd /opt/ruangtes-api
git pull
./deploy/deploy.sh
```

## Troubleshooting

### Container won't start
```bash
docker-compose -f docker-compose.prod.yml logs app
```

### Database connection error
```bash
docker-compose -f docker-compose.prod.yml logs postgres
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Permission errors
```bash
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache
```

## Next Steps

- [ ] Setup monitoring (CloudWatch, Sentry)
- [ ] Configure backup strategy
- [ ] Setup log rotation
- [ ] Configure auto-scaling (if needed)
