#!/bin/bash

# Health check script for monitoring

cd /opt/ruangtes-api

# Check if containers are running
if ! docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then
    echo "❌ Containers are not running"
    exit 1
fi

# Check if app container is healthy
if ! docker-compose -f docker-compose.prod.yml exec -T app php artisan tinker --execute="echo 'OK';" > /dev/null 2>&1; then
    echo "❌ Application is not responding"
    exit 1
fi

# Check database connection
if ! docker-compose -f docker-compose.prod.yml exec -T app php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo "❌ Database connection failed"
    exit 1
fi

# Check Redis connection
if ! docker-compose -f docker-compose.prod.yml exec -T app php artisan tinker --execute="Redis::ping();" > /dev/null 2>&1; then
    echo "❌ Redis connection failed"
    exit 1
fi

echo "✅ All services are healthy"
exit 0
