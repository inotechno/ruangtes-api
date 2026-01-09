#!/bin/bash

set -e

echo "🚀 Deploying RuangTes API..."

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

# Stop and remove old containers
echo "🛑 Stopping old containers..."
docker-compose -f docker-compose.prod.yml down || true

# Start new containers
echo "🚀 Starting new containers..."
docker-compose -f docker-compose.prod.yml up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 15

# Run migrations
echo "🗄️  Running migrations..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force || true

# Clear and cache config
echo "⚙️  Caching configuration..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache || true
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache || true
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache || true

# Restart queue and scheduler
echo "🔄 Restarting queue and scheduler..."
docker-compose -f docker-compose.prod.yml restart queue scheduler || true

# Clean up old images
echo "🧹 Cleaning up old images..."
docker image prune -af --filter "until=168h" || true

echo "✅ Deployment completed!"
