#!/bin/bash

# Health check script for monitoring

cd /opt/ruangtes-api

# Detect docker compose command (V2 uses 'docker compose', V1 uses 'docker-compose')
if docker compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
elif docker-compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker-compose"
else
    echo "❌ Docker Compose not found"
    exit 1
fi

# Check if containers are running
if ! $DOCKER_COMPOSE -f docker-compose.prod.yml ps | grep -q "Up"; then
    echo "❌ Containers are not running"
    exit 1
fi

# Check if app container is healthy
if ! $DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan tinker --execute="echo 'OK';" > /dev/null 2>&1; then
    echo "❌ Application is not responding"
    exit 1
fi

# Check database connection
if ! $DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo "❌ Database connection failed"
    exit 1
fi

# Check Redis connection
if ! $DOCKER_COMPOSE -f docker-compose.prod.yml exec -T app php artisan tinker --execute="Redis::ping();" > /dev/null 2>&1; then
    echo "❌ Redis connection failed"
    exit 1
fi

echo "✅ All services are healthy"
exit 0
