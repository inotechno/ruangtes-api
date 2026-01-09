#!/bin/bash

set -e

echo "🚀 Setting up RuangTes API on EC2..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root${NC}"
    exit 1
fi

# Update system
echo -e "${YELLOW}Updating system packages...${NC}"
apt-get update
apt-get upgrade -y

# Install Docker
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}Installing Docker...${NC}"
    apt-get install -y \
        ca-certificates \
        curl \
        gnupg \
        lsb-release
    
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
    
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    
    systemctl enable docker
    systemctl start docker
else
    echo -e "${GREEN}Docker is already installed${NC}"
fi

# Install AWS CLI
if ! command -v aws &> /dev/null; then
    echo -e "${YELLOW}Installing AWS CLI...${NC}"
    curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
    apt-get install -y unzip
    unzip awscliv2.zip
    ./aws/install
    rm -rf aws awscliv2.zip
else
    echo -e "${GREEN}AWS CLI is already installed${NC}"
fi

# Create application directory
APP_DIR="/opt/ruangtes-api"
if [ ! -d "$APP_DIR" ]; then
    echo -e "${YELLOW}Creating application directory...${NC}"
    mkdir -p $APP_DIR
    chown -R $SUDO_USER:$SUDO_USER $APP_DIR
else
    echo -e "${GREEN}Application directory already exists${NC}"
fi

# Create .env file if it doesn't exist
if [ ! -f "$APP_DIR/.env" ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    cat > $APP_DIR/.env << EOF
APP_NAME=RuangTes
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ruangtes
DB_USERNAME=ruangtes
DB_PASSWORD=CHANGE_THIS_PASSWORD

REDIS_HOST=redis
REDIS_PASSWORD=CHANGE_THIS_PASSWORD
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

FRONTEND_URL=https://your-domain.com
EOF
    chown $SUDO_USER:$SUDO_USER $APP_DIR/.env
    echo -e "${GREEN}.env file created. Please edit it with your configuration.${NC}"
else
    echo -e "${GREEN}.env file already exists${NC}"
fi

# Setup Nginx (if not using Docker nginx)
echo -e "${YELLOW}Setting up Nginx...${NC}"
if ! command -v nginx &> /dev/null; then
    apt-get install -y nginx
    systemctl enable nginx
else
    echo -e "${GREEN}Nginx is already installed${NC}"
fi

# Setup Certbot (for SSL)
echo -e "${YELLOW}Setting up Certbot...${NC}"
if ! command -v certbot &> /dev/null; then
    apt-get install -y certbot python3-certbot-nginx
else
    echo -e "${GREEN}Certbot is already installed${NC}"
fi

# Create Nginx configuration
NGINX_CONF="/etc/nginx/sites-available/ruangtes-api"
if [ ! -f "$NGINX_CONF" ]; then
    echo -e "${YELLOW}Creating Nginx configuration...${NC}"
    cat > $NGINX_CONF << 'EOF'
upstream ruangtes_app {
    server 127.0.0.1:9000;
}

server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    
    root /opt/ruangtes-api/public;
    index index.php index.html;

    charset utf-8;

    # Logging
    access_log /var/log/nginx/ruangtes-access.log;
    error_log /var/log/nginx/ruangtes-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass ruangtes_app;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to storage and bootstrap cache
    location ~ ^/(storage|bootstrap/cache) {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Client max body size (for file uploads)
    client_max_body_size 10M;
}
EOF
    
    # Enable site
    ln -sf $NGINX_CONF /etc/nginx/sites-enabled/ruangtes-api
    rm -f /etc/nginx/sites-enabled/default
    
    # Test Nginx configuration
    nginx -t
    
    echo -e "${GREEN}Nginx configuration created${NC}"
    echo -e "${YELLOW}Please update server_name in $NGINX_CONF with your domain${NC}"
else
    echo -e "${GREEN}Nginx configuration already exists${NC}"
fi

# Create systemd service for PHP-FPM (if not using Docker)
PHP_FPM_SERVICE="/etc/systemd/system/ruangtes-php-fpm.service"
if [ ! -f "$PHP_FPM_SERVICE" ]; then
    echo -e "${YELLOW}Creating PHP-FPM service...${NC}"
    # Note: This is optional if using Docker. You can skip this if using Docker for PHP-FPM
    echo -e "${YELLOW}PHP-FPM service creation skipped (using Docker)${NC}"
fi

# Setup firewall
echo -e "${YELLOW}Setting up firewall...${NC}"
if command -v ufw &> /dev/null; then
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw --force enable
    echo -e "${GREEN}Firewall configured${NC}"
else
    echo -e "${YELLOW}UFW not found, skipping firewall setup${NC}"
fi

# Create deploy script
DEPLOY_SCRIPT="$APP_DIR/deploy.sh"
if [ ! -f "$DEPLOY_SCRIPT" ]; then
    echo -e "${YELLOW}Creating deploy script...${NC}"
    cat > $DEPLOY_SCRIPT << 'EOF'
#!/bin/bash
set -e

cd /opt/ruangtes-api

# Login to ECR
aws ecr get-login-password --region ap-southeast-1 | docker login --username AWS --password-stdin $(aws sts get-caller-identity --query Account --output text).dkr.ecr.ap-southeast-1.amazonaws.com

# Pull latest image
docker pull ruangtes-app:latest

# Stop and remove old containers
docker-compose -f docker-compose.prod.yml down

# Start new containers
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# Clear and cache config
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache

# Restart queue and scheduler
docker-compose -f docker-compose.prod.yml restart queue scheduler

# Clean up old images
docker image prune -af --filter "until=168h"

echo "✅ Deployment completed!"
EOF
    chmod +x $DEPLOY_SCRIPT
    chown $SUDO_USER:$SUDO_USER $DEPLOY_SCRIPT
    echo -e "${GREEN}Deploy script created at $DEPLOY_SCRIPT${NC}"
fi

echo -e "${GREEN}✅ Setup completed!${NC}"
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Edit $APP_DIR/.env with your configuration"
echo "2. Update Nginx configuration at $NGINX_CONF with your domain"
echo "3. Run 'certbot --nginx -d your-domain.com' to setup SSL"
echo "4. Clone your repository to $APP_DIR"
echo "5. Copy docker-compose.prod.yml to $APP_DIR"
echo "6. Run 'docker-compose -f docker-compose.prod.yml up -d'"
