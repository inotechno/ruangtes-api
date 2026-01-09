#!/bin/bash

set -e

echo "🚀 Deploying RuangTes API..."

# Check if user has docker permission
if ! docker ps > /dev/null 2>&1; then
    echo "❌ Docker permission denied. Trying with sudo..."
    echo "💡 To fix permanently, run: sudo usermod -aG docker \$USER && newgrp docker"
    
    # Check if script is run with sudo
    if [ "$EUID" -ne 0 ]; then
        echo "⚠️  Please run with sudo or add user to docker group"
        echo "   Run: sudo ./deploy/deploy.sh"
        exit 1
    fi
fi

cd /opt/ruangtes-api

# Get AWS Account ID and ECR Registry
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
AWS_REGION=${AWS_REGION:-ap-southeast-1}
ECR_REGISTRY="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
ECR_REPOSITORY="ruangtes-api"

# Login to ECR
echo "📦 Logging in to ECR..."
aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY

# Pull latest image
echo "⬇️  Pulling latest image..."
docker pull $ECR_REGISTRY/$ECR_REPOSITORY:latest || echo "Image not found in ECR, will build locally"
docker tag $ECR_REGISTRY/$ECR_REPOSITORY:latest ruangtes-app:latest || docker build -t ruangtes-app:latest .

# Detect docker compose command (V2 uses 'docker compose', V1 uses 'docker-compose')
if docker compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
elif docker-compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker-compose"
else
    echo "❌ Docker Compose not found. Please install Docker Compose."
    exit 1
fi

# Stop and remove old containers
echo "🛑 Stopping old containers..."
$DOCKER_COMPOSE -f docker-compose.prod.yml down || true

# Start new containers
echo "🚀 Starting new containers..."
$DOCKER_COMPOSE -f docker-compose.prod.yml up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 15

# Run migrations
echo "🗄️  Running migrations..."
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan migrate --force || true

# Clear and cache config
echo "⚙️  Caching configuration..."
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan config:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan route:cache || true
$DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan view:cache || true

# Restart queue and scheduler
echo "🔄 Restarting queue and scheduler..."
$DOCKER_COMPOSE -f docker-compose.prod.yml restart queue scheduler || true

# Clean up old images
echo "🧹 Cleaning up old images..."
docker image prune -af --filter "until=168h" || true

echo "✅ Deployment completed!"
