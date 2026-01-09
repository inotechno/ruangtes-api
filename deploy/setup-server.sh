#!/bin/bash

# Script to setup server without Docker (PHP, PostgreSQL, Redis, Nginx)

set -e

echo "🚀 Setting up RuangTes API Server (No Docker)..."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root${NC}"
    exit 1
fi

# Update system
echo -e "${YELLOW}Updating system packages...${NC}"
apt-get update
apt-get upgrade -y

# Install PHP 8.4 and extensions
echo -e "${YELLOW}Installing PHP 8.4 and extensions...${NC}"
apt-get install -y \
    software-properties-common \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

add-apt-repository -y ppa:ondrej/php
apt-get update

apt-get install -y \
    php8.4-fpm \
    php8.4-cli \
    php8.4-common \
    php8.4-mysql \
    php8.4-pgsql \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-curl \
    php8.4-zip \
    php8.4-gd \
    php8.4-intl \
    php8.4-bcmath \
    php8.4-redis \
    php8.4-opcache

# Install Composer
echo -e "${YELLOW}Installing Composer...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
else
    echo -e "${GREEN}Composer is already installed${NC}"
fi

# Install PostgreSQL
echo -e "${YELLOW}Installing PostgreSQL...${NC}"
if ! command -v psql &> /dev/null; then
    apt-get install -y postgresql postgresql-contrib
    systemctl enable postgresql
    systemctl start postgresql
else
    echo -e "${GREEN}PostgreSQL is already installed${NC}"
fi

# Install Redis
echo -e "${YELLOW}Installing Redis...${NC}"
if ! command -v redis-cli &> /dev/null; then
    apt-get install -y redis-server
    systemctl enable redis-server
    systemctl start redis-server
else
    echo -e "${GREEN}Redis is already installed${NC}"
fi

# Install Node.js and npm (for frontend assets)
echo -e "${YELLOW}Installing Node.js...${NC}"
if ! command -v node &> /dev/null; then
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
    \. "$HOME/.nvm/nvm.sh"
    nvm install 25
else
    echo -e "${GREEN}Node.js is already installed${NC}"
fi

# Install Supervisor (for queue workers)
echo -e "${YELLOW}Installing Supervisor...${NC}"
if ! command -v supervisorctl &> /dev/null; then
    apt-get install -y supervisor
    systemctl enable supervisor
    systemctl start supervisor
else
    echo -e "${GREEN}Supervisor is already installed${NC}"
fi

# Install Git
echo -e "${YELLOW}Installing Git...${NC}"
if ! command -v git &> /dev/null; then
    apt-get install -y git
else
    echo -e "${GREEN}Git is already installed${NC}"
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
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ruangtes
DB_USERNAME=ruangtes
DB_PASSWORD=CHANGE_THIS_PASSWORD

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
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

FRONTEND_URL=https://your-domain.com
EOF
    chown $SUDO_USER:$SUDO_USER $APP_DIR/.env
    echo -e "${GREEN}.env file created. Please edit it with your configuration.${NC}"
else
    echo -e "${GREEN}.env file already exists${NC}"
fi

# Setup PostgreSQL database
echo -e "${YELLOW}Setting up PostgreSQL database...${NC}"
DB_NAME="ruangtes"
DB_USER="ruangtes"
DB_PASSWORD="ruangtes_password_$(openssl rand -hex 8)"

sudo -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';" 2>/dev/null || echo "User might already exist"
sudo -u postgres psql -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;" 2>/dev/null || echo "Database might already exist"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;" 2>/dev/null || true

echo -e "${GREEN}PostgreSQL database created${NC}"
echo -e "${YELLOW}Database credentials:${NC}"
echo "   Database: $DB_NAME"
echo "   User: $DB_USER"
echo "   Password: $DB_PASSWORD"
echo "   Update .env file with these credentials"
echo ""

# Setup Redis
echo -e "${YELLOW}Configuring Redis...${NC}"
# Redis is already installed and running
# Optionally set password in /etc/redis/redis.conf
echo -e "${GREEN}Redis is ready${NC}"
echo ""

# Create Nginx configuration
NGINX_CONF="/etc/nginx/sites-available/ruangtes-api"
if [ ! -f "$NGINX_CONF" ]; then
    echo -e "${YELLOW}Creating Nginx configuration...${NC}"
    cat > $NGINX_CONF << 'EOF'
upstream ruangtes_app {
    server unix:/var/run/php/php8.4-fpm.sock;
}

server {
    listen 80;
    server_name api.ruangtes.web.id www.api.ruangtes.web.id;
    
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

# Setup PHP-FPM pool (optional optimization)
PHP_FPM_POOL="/etc/php/8.4/fpm/pool.d/ruangtes.conf"
if [ ! -f "$PHP_FPM_POOL" ]; then
    echo -e "${YELLOW}Creating PHP-FPM pool configuration...${NC}"
    cat > $PHP_FPM_POOL << 'EOF'
[ruangtes]
user = www-data
group = www-data
listen = /var/run/php/php8.4-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
EOF
    echo -e "${GREEN}PHP-FPM pool created${NC}"
else
    echo -e "${GREEN}PHP-FPM pool already exists${NC}"
fi

# Setup Supervisor for queue worker
SUPERVISOR_CONF="/etc/supervisor/conf.d/ruangtes-queue.conf"
if [ ! -f "$SUPERVISOR_CONF" ]; then
    echo -e "${YELLOW}Creating Supervisor configuration for queue worker...${NC}"
    cat > $SUPERVISOR_CONF << 'EOF'
[program:ruangtes-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /opt/ruangtes-api/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/opt/ruangtes-api/storage/logs/queue-worker.log
stopwaitsecs=3600
EOF
    supervisorctl reread
    supervisorctl update
    echo -e "${GREEN}Supervisor configuration created${NC}"
else
    echo -e "${GREEN}Supervisor configuration already exists${NC}"
fi

# Setup Supervisor for scheduler
SUPERVISOR_SCHEDULER="/etc/supervisor/conf.d/ruangtes-scheduler.conf"
if [ ! -f "$SUPERVISOR_SCHEDULER" ]; then
    echo -e "${YELLOW}Creating Supervisor configuration for scheduler...${NC}"
    cat > $SUPERVISOR_SCHEDULER << 'EOF'
[program:ruangtes-scheduler]
command=/bin/bash -c "while [ true ]; do (php /opt/ruangtes-api/artisan schedule:run --verbose --no-interaction &); sleep 60; done"
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/opt/ruangtes-api/storage/logs/scheduler.log
EOF
    supervisorctl reread
    supervisorctl update
    echo -e "${GREEN}Scheduler configuration created${NC}"
else
    echo -e "${GREEN}Scheduler configuration already exists${NC}"
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

echo "🚀 Deploying RuangTes API..."

# Pull latest code
git pull origin main || git pull origin master

# Install/update dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Run migrations
php artisan migrate --force || true

# Clear and cache config
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Restart PHP-FPM
sudo systemctl reload php8.4-fpm || sudo systemctl reload php-fpm || true

# Restart queue workers
sudo supervisorctl restart ruangtes-queue:* || true

# Clear application cache
php artisan cache:clear || true

echo "✅ Deployment completed!"
EOF
    chmod +x $DEPLOY_SCRIPT
    chown $SUDO_USER:$SUDO_USER $DEPLOY_SCRIPT
    echo -e "${GREEN}Deploy script created at $DEPLOY_SCRIPT${NC}"
fi

echo ""
echo -e "${GREEN}✅ Server setup completed!${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Edit $APP_DIR/.env with your configuration"
echo "2. Update database password in .env (use: $DB_PASSWORD)"
echo "3. Update Nginx configuration at $NGINX_CONF with your domain"
echo "4. Clone your repository to $APP_DIR"
echo "5. Run: cd $APP_DIR && composer install"
echo "6. Run: php artisan key:generate"
echo "7. Run: php artisan migrate"
echo "8. Run: sudo systemctl reload nginx"
echo "9. Run: certbot --nginx -d your-domain.com to setup SSL"
