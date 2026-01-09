# Fix Permission Issues

Jika mendapat error:
```
Permission denied: /var/www/html/storage/logs/laravel.log
Permission denied: /var/www/html/.env
```

## Masalah

Container menggunakan user `www-data` (UID 33), tapi file di host owned by `ubuntu` user. Ini menyebabkan permission denied saat container mencoba write ke storage atau .env.

## Solusi Cepat

### Fix Permissions Manual

```bash
cd /opt/ruangtes-api

# Fix storage permissions (www-data group = 33)
sudo chown -R $USER:33 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Fix .env permissions
sudo chown $USER:$USER .env
chmod 644 .env

# Untuk generate APP_KEY, temporarily make .env writable
sudo chmod 666 .env
docker compose -f docker-compose.prod.yml run --rm -u root app php artisan key:generate --force
sudo chmod 644 .env
```

### Run Artisan Commands as Root

Untuk setup commands, gunakan `-u root`:

```bash
# Generate APP_KEY
docker compose -f docker-compose.prod.yml run --rm -u root app php artisan key:generate --force

# Run migrations
docker compose -f docker-compose.prod.yml run --rm -u root app php artisan migrate --force

# Cache config
docker compose -f docker-compose.prod.yml run --rm -u root app php artisan config:cache
```

## Permanent Fix

### Option 1: Use Setup Script

Script `setup-app.sh` sudah diupdate untuk handle permissions:

```bash
./deploy/setup-app.sh
```

### Option 2: Fix Ownership

Set ownership ke www-data group (33):

```bash
# Storage and cache
sudo chown -R $USER:33 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Vendor (if exists)
sudo chown -R $USER:$USER vendor

# .env (readable by all, writable by owner)
sudo chown $USER:$USER .env
chmod 644 .env
```

### Option 3: Run Container as Current User

Ubah docker-compose.prod.yml untuk run sebagai current user:

```yaml
app:
  user: "${UID:-33}:${GID:-33}"
```

Tapi ini tidak recommended karena bisa menyebabkan masalah lain.

## Verify

```bash
# Check storage permissions
ls -la storage/logs/

# Should show:
# drwxrwxr-x ubuntu www-data storage/logs

# Check .env permissions
ls -la .env

# Should show:
# -rw-r--r-- ubuntu ubuntu .env
```

## Best Practice

1. **Storage & Cache**: Owned by `$USER:33` (www-data group), permissions `775`
2. **.env**: Owned by `$USER:$USER`, permissions `644` (read-only untuk security)
3. **Vendor**: Owned by `$USER:$USER`, permissions `755`
4. **Run setup commands as root**: Use `-u root` untuk artisan commands saat setup
5. **Runtime**: Container runs as `www-data` untuk security
