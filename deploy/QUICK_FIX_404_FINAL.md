# Quick Fix: 404 Error - Laravel Works But Nginx Returns 404

**Status:** Laravel bisa bootstrap, PHP-FPM connection OK, tapi Nginx masih return 404.

## Diagnosis

Jalankan:
```bash
cd /opt/ruangtes-api
./deploy/fix-nginx-404-final.sh
```

## Common Fixes

### Fix 1: Test Direct index.php Access

```bash
# Test direct access
curl -v http://localhost/index.php

# If this returns 404, problem is in PHP-FPM execution
# If this returns 200, problem is in routing (try_files)
```

### Fix 2: Check File Permissions

```bash
# Check permissions
ls -la /opt/ruangtes-api/public/index.php

# Fix if needed
sudo chmod 644 /opt/ruangtes-api/public/index.php
sudo chown $USER:www-data /opt/ruangtes-api/public/index.php
```

### Fix 3: Verify try_files Directive

Pastikan di Nginx config:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**NOT:**
```nginx
location / {
    try_files $uri $uri/ /index.php;
}
```

### Fix 4: Test PHP File Directly

```bash
# Create simple test
echo "<?php echo 'Test'; ?>" | sudo tee /opt/ruangtes-api/public/test.php
curl http://localhost/test.php
sudo rm /opt/ruangtes-api/public/test.php
```

Jika ini return 404, masalahnya di PHP-FPM execution.
Jika ini bekerja, masalahnya di Laravel routing.

### Fix 5: Check SSL Redirect

Karena config ada SSL, pastikan test via HTTPS:
```bash
# Test via HTTPS
curl -k https://api.ruangtes.web.id/health

# Or test via HTTP (should redirect)
curl -L http://api.ruangtes.web.id/health
```

### Fix 6: Clear Laravel Route Cache

```bash
docker compose -f docker-compose.prod.yml exec -T -u root app php artisan route:clear
docker compose -f docker-compose.prod.yml exec -T -u root app php artisan config:clear
```

## Complete Fix Sequence

```bash
cd /opt/ruangtes-api

# 1. Fix permissions
sudo chmod 644 public/index.php
sudo chown $USER:www-data public/index.php

# 2. Test direct access
curl http://localhost/index.php

# 3. If 404, check Nginx config
sudo nginx -t
sudo cat /etc/nginx/sites-available/ruangtes-api | grep -A 2 "location /"

# 4. Clear Laravel caches
docker compose -f docker-compose.prod.yml exec -T -u root app php artisan route:clear
docker compose -f docker-compose.prod.yml exec -T -u root app php artisan config:clear

# 5. Reload Nginx
sudo systemctl reload nginx

# 6. Test
curl http://localhost/health
curl https://api.ruangtes.web.id/health
```

## Debug Mode

Enable debug logging di Nginx:

```bash
sudo nano /etc/nginx/sites-available/ruangtes-api
# Change: error_log /var/log/nginx/ruangtes-error.log;
# To: error_log /var/log/nginx/ruangtes-error.log debug;

sudo nginx -t
sudo systemctl reload nginx

# Then test and check logs
curl http://localhost/health
sudo tail -f /var/log/nginx/ruangtes-error.log
```
