# Quick Fix: Connection Refused (111) to PHP-FPM

Error: `connect() failed (111: Connection refused) while connecting to upstream, upstream: "fastcgi://127.0.0.1:9000"`

## Penyebab

PHP-FPM di container tidak bisa diakses dari host di port 9000.

## Quick Fix

### Option 1: Run Fix Script

```bash
cd /opt/ruangtes-api
./deploy/fix-nginx-connection.sh
```

### Option 2: Manual Fix

```bash
cd /opt/ruangtes-api

# 1. Check if port mapping exists
grep "127.0.0.1:9000:9000" docker-compose.prod.yml

# 2. If not exists, add to docker-compose.prod.yml under app service:
#    ports:
#      - "127.0.0.1:9000:9000"

# 3. Restart app container
docker compose -f docker-compose.prod.yml restart app

# 4. Wait and verify
sleep 5
netstat -tlnp | grep 9000

# 5. Test connection
timeout 2 bash -c "echo > /dev/tcp/127.0.0.1/9000" && echo "OK" || echo "FAILED"

# 6. Reload Nginx
sudo systemctl reload nginx
```

## Verify Port Mapping

Check docker-compose.prod.yml:

```yaml
app:
  ports:
    - "127.0.0.1:9000:9000"  # ✅ Must have this
```

## Verify Container Status

```bash
# Check if container is running
docker compose -f docker-compose.prod.yml ps app

# Check logs if not running
docker compose -f docker-compose.prod.yml logs app

# Restart if needed
docker compose -f docker-compose.prod.yml restart app
```

## Test After Fix

```bash
# Test connection
curl http://localhost/health

# Check Nginx error log (should be empty now)
sudo tail -f /var/log/nginx/ruangtes-error.log
```
