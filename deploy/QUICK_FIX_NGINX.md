# Quick Fix: Nginx File Not Found

Jika `api.ruangtes.web.id` menampilkan "File Not Found", ikuti langkah berikut:

## Step 1: Run Troubleshooting

```bash
cd /opt/ruangtes-api
./deploy/troubleshoot-nginx.sh
```

Atau quick verification:
```bash
./deploy/verify-nginx-setup.sh
```

## Step 2: Common Fixes

### Fix 1: Verify Document Root

```bash
# Check current config
grep "root" /etc/nginx/sites-available/ruangtes-api

# Should show: root /opt/ruangtes-api/public;
# If wrong, fix it:
sudo nano /etc/nginx/sites-available/ruangtes-api
```

Pastikan:
```nginx
root /opt/ruangtes-api/public;
```

**BUKAN:**
```nginx
root /opt/ruangtes-api;  # ❌ Wrong - missing /public
```

### Fix 2: Verify index.php Exists

```bash
# Check if file exists
ls -la /opt/ruangtes-api/public/index.php

# If not exists, check if you're in right directory
cd /opt/ruangtes-api
ls -la public/
```

Jika file tidak ada:
```bash
# Pull from git
git pull origin main

# Or check if public directory is empty
ls -la public/
```

### Fix 3: Verify PHP-FPM Connection

```bash
# Check if port 9000 is listening
netstat -tlnp | grep 9000

# Check app container
docker compose -f docker-compose.prod.yml ps app

# If not running, restart
docker compose -f docker-compose.prod.yml restart app
```

### Fix 4: Check try_files Directive

Pastikan Nginx config memiliki:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Fix 5: Test PHP-FPM Manually

```bash
# Test connection
timeout 2 bash -c "echo > /dev/tcp/127.0.0.1/9000" && echo "OK" || echo "FAILED"

# Test PHP execution
docker compose -f docker-compose.prod.yml exec -T app php -r "echo 'PHP OK';"
```

## Step 3: Complete Fix Sequence

```bash
cd /opt/ruangtes-api

# 1. Verify document root in Nginx config
sudo grep "root" /etc/nginx/sites-available/ruangtes-api

# 2. Verify index.php exists
ls -la public/index.php

# 3. Restart app container (to ensure PHP-FPM is running)
docker compose -f docker-compose.prod.yml restart app

# 4. Wait a few seconds
sleep 5

# 5. Verify port 9000
netstat -tlnp | grep 9000

# 6. Test Nginx config
sudo nginx -t

# 7. Reload Nginx
sudo systemctl reload nginx

# 8. Test locally
curl http://localhost/health
```

## Step 4: Check Nginx Error Logs

```bash
# Watch error logs in real-time
sudo tail -f /var/log/nginx/ruangtes-error.log

# Then try accessing the site in another terminal
# Look for errors like:
# - "Primary script unknown" = wrong document root
# - "Connection refused" = PHP-FPM not running
# - "Permission denied" = file permissions issue
```

## Common Error Messages & Fixes

| Error | Cause | Fix |
|-------|-------|-----|
| `404 Not Found` | Wrong document root or missing index.php | Check `root /opt/ruangtes-api/public;` |
| `502 Bad Gateway` | PHP-FPM not running | `docker compose restart app` |
| `Primary script unknown` | Document root wrong | Update to `/opt/ruangtes-api/public` |
| `Permission denied` | File permissions | `chmod 755 public/index.php` |
| `Connection refused` | Port 9000 not listening | Check app container status |

## Verify Complete Setup

```bash
# All should return OK:
✅ docker compose -f docker-compose.prod.yml ps app | grep Up
✅ netstat -tlnp | grep 9000
✅ ls -la /opt/ruangtes-api/public/index.php
✅ sudo nginx -t
✅ curl http://localhost/health
```

## Still Not Working?

1. Check Nginx access logs: `sudo tail -f /var/log/nginx/ruangtes-access.log`
2. Check PHP-FPM logs: `docker compose -f docker-compose.prod.yml logs app`
3. Test PHP directly: `docker compose exec app php public/index.php`
4. Check SELinux (if enabled): `getenforce`
