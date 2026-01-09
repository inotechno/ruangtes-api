# Fix 404 Error - Detailed Guide

Nginx config sudah benar, tapi masih dapat 404. Kemungkinan masalah di Laravel atau PHP execution.

## Diagnosis Steps

### Step 1: Run Test Script

```bash
cd /opt/ruangtes-api
./deploy/test-php-fpm.sh
```

Script ini akan test:
- PHP execution
- index.php execution
- vendor/autoload.php
- Laravel bootstrap
- FastCGI connection
- Health endpoint

### Step 2: Common Issues & Fixes

#### Issue 1: vendor/autoload.php Missing

**Symptom:** Error "Failed opening required '/var/www/html/vendor/autoload.php'"

**Fix:**
```bash
cd /opt/ruangtes-api
./deploy/setup-app.sh
# Or manually:
docker compose -f docker-compose.prod.yml run --rm -u root app composer install --no-dev --optimize-autoloader
```

#### Issue 2: Laravel Bootstrap Fails

**Symptom:** index.php can execute but Laravel can't bootstrap

**Check:**
```bash
# Test bootstrap
docker compose -f docker-compose.prod.yml exec -T app php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
echo 'OK';
"
```

**Fix:**
- Check .env configuration
- Check APP_KEY is set
- Check database connection
- Check storage permissions

#### Issue 3: FastCGI Can't Execute PHP

**Symptom:** FastCGI connection OK but PHP files return 404

**Test:**
```bash
# Create test file
echo "<?php phpinfo(); ?>" | sudo tee /opt/ruangtes-api/public/test.php
curl http://localhost/test.php
sudo rm /opt/ruangtes-api/public/test.php
```

**Fix:**
- Check file permissions: `chmod 644 public/index.php`
- Check SELinux (if enabled): `getenforce`

#### Issue 4: Laravel Routing Issue

**Symptom:** index.php works but routes return 404

**Check:**
```bash
# Test index.php directly
curl http://localhost/index.php

# Test health endpoint
curl http://localhost/health
```

**Fix:**
- Ensure try_files directive: `try_files $uri $uri/ /index.php?$query_string;`
- Check route cache: `docker compose exec app php artisan route:clear`

## Complete Fix Sequence

```bash
cd /opt/ruangtes-api

# 1. Install dependencies
./deploy/setup-app.sh

# 2. Test PHP-FPM
./deploy/test-php-fpm.sh

# 3. If vendor missing
docker compose -f docker-compose.prod.yml run --rm -u root app composer install --no-dev --optimize-autoloader

# 4. Fix permissions
sudo chown -R $USER:33 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
chmod 644 public/index.php

# 5. Clear Laravel caches
docker compose -f docker-compose.prod.yml exec -T -u root app php artisan config:clear
docker compose -f docker-compose.prod.yml exec -T -u root app php artisan route:clear
docker compose -f docker-compose.prod.yml exec -T -u root app php artisan view:clear

# 6. Test
curl http://localhost/health
```

## Verify Nginx Config

Your config looks correct, but verify these critical parts:

```nginx
# ✅ Must have this in location /
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# ✅ Must use $realpath_root
location ~ \.php$ {
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    # NOT: $document_root$fastcgi_script_name
}
```

## Test Direct PHP Execution

```bash
# Test if PHP can execute index.php
docker compose -f docker-compose.prod.yml exec -T app php public/index.php

# Should output Laravel response or error message
# If error, check the error message
```

## Check Laravel Logs

```bash
# Check Laravel logs
docker compose -f docker-compose.prod.yml exec -T app tail -20 storage/logs/laravel.log

# Or from host
tail -20 /opt/ruangtes-api/storage/logs/laravel.log
```
