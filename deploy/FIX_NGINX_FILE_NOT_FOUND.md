# Fix Nginx "File Not Found" Error

Jika mengakses `api.ruangtes.web.id` muncul "File Not Found" atau "404 Not Found", berikut troubleshooting steps:

## Quick Diagnosis

Jalankan script troubleshooting:

```bash
cd /opt/ruangtes-api
./deploy/troubleshoot-nginx.sh
```

## Common Issues & Fixes

### 1. Document Root Salah

**Check:**
```bash
# Check Nginx config
grep "root" /etc/nginx/sites-available/ruangtes-api

# Should show:
# root /opt/ruangtes-api/public;
```

**Fix:**
```bash
sudo nano /etc/nginx/sites-available/ruangtes-api
# Update root directive to: root /opt/ruangtes-api/public;
sudo nginx -t
sudo systemctl reload nginx
```

### 2. PHP-FPM Tidak Bisa Connect

**Check:**
```bash
# Check if port 9000 is listening
netstat -tlnp | grep 9000

# Check app container
docker compose -f docker-compose.prod.yml ps app
```

**Fix:**
```bash
# Restart app container
docker compose -f docker-compose.prod.yml restart app

# Verify port
netstat -tlnp | grep 9000
```

### 3. index.php Tidak Ada

**Check:**
```bash
ls -la /opt/ruangtes-api/public/index.php
```

**Fix:**
```bash
# If file doesn't exist, check if you're in the right directory
cd /opt/ruangtes-api
ls -la public/

# If public directory is empty, you might need to pull from git
git pull origin main
```

### 4. FastCGI Pass Configuration

**Check Nginx config:**
```nginx
upstream ruangtes_app {
    server 127.0.0.1:9000;
}

location ~ \.php$ {
    fastcgi_pass ruangtes_app;
    # ...
}
```

**Verify:**
```bash
# Test connection
timeout 2 bash -c "echo > /dev/tcp/127.0.0.1/9000" && echo "Connection OK" || echo "Connection Failed"
```

### 5. File Permissions

**Check:**
```bash
ls -la /opt/ruangtes-api/public/
```

**Fix:**
```bash
# Fix permissions
sudo chown -R $USER:www-data /opt/ruangtes-api/public
chmod -R 755 /opt/ruangtes-api/public
```

### 6. Nginx Error Logs

**Check logs:**
```bash
sudo tail -f /var/log/nginx/ruangtes-error.log
```

Common errors:
- `connect() failed (111: Connection refused)` - PHP-FPM not running
- `Primary script unknown` - Document root wrong
- `Permission denied` - File permissions issue

## Complete Fix Steps

### Step 1: Verify Document Root

```bash
# Check Nginx config
sudo cat /etc/nginx/sites-available/ruangtes-api | grep root

# Should be: root /opt/ruangtes-api/public;
# If not, update it
```

### Step 2: Verify PHP-FPM

```bash
# Check container
docker compose -f docker-compose.prod.yml ps app

# Check port
netstat -tlnp | grep 9000

# If not running/listening:
docker compose -f docker-compose.prod.yml restart app
```

### Step 3: Verify Files

```bash
# Check if index.php exists
ls -la /opt/ruangtes-api/public/index.php

# Check if public directory has correct files
ls -la /opt/ruangtes-api/public/
```

### Step 4: Test PHP-FPM Connection

```bash
# Test from host
echo "<?php phpinfo(); ?>" | docker compose -f docker-compose.prod.yml exec -T app php
```

### Step 5: Reload Nginx

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Test After Fix

```bash
# Test from server
curl http://localhost/health

# Test from outside (if domain configured)
curl https://api.ruangtes.web.id/health
```

## Debug Mode

Enable debug di Nginx untuk lebih detail:

```nginx
# Add to server block
error_log /var/log/nginx/ruangtes-error.log debug;
```

Then check logs:
```bash
sudo tail -f /var/log/nginx/ruangtes-error.log
```

## Common Nginx Config Issues

### Wrong Root Path
```nginx
# Wrong
root /opt/ruangtes-api;

# Correct
root /opt/ruangtes-api/public;
```

### Wrong FastCGI Pass
```nginx
# Wrong
fastcgi_pass app:9000;

# Correct (for host Nginx)
fastcgi_pass 127.0.0.1:9000;
# or
fastcgi_pass ruangtes_app;  # if using upstream
```

### Missing try_files
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Verify Complete Setup

```bash
# 1. Container running
docker compose -f docker-compose.prod.yml ps

# 2. Port 9000 listening
netstat -tlnp | grep 9000

# 3. Nginx running
systemctl status nginx

# 4. Files exist
ls -la /opt/ruangtes-api/public/index.php

# 5. Test connection
curl http://localhost/health
```
