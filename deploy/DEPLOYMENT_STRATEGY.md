# Deployment Strategy

## Migration Strategy

### Automatic Migration Check

Deployment script akan **otomatis check** apakah ada pending migrations sebelum run migrate:

```bash
# Script akan check dulu
if php artisan migrate:status | grep -q "Pending"; then
    php artisan migrate --force
else
    echo "No pending migrations, skipping..."
fi
```

### Kapan Migrate Diperlukan?

✅ **Perlu migrate** jika:
- Ada migration file baru di `database/migrations/`
- Database schema berubah
- Ada perubahan struktur tabel

❌ **Tidak perlu migrate** jika:
- Hanya update code/logic
- Hanya update dependencies
- Hanya fix bug tanpa perubahan database

### Manual Migration Control

Jika ingin **skip migrate** atau **run migrate manual**:

#### Option 1: Skip Migrate di Deploy Script

Edit `deploy/deploy.sh` dan comment bagian migrate:

```bash
# Run migrations (only if there are pending migrations)
# echo "🗄️  Checking for pending migrations..."
# if php artisan migrate:status | grep -q "Pending"; then
#     echo "   Found pending migrations, running migrate..."
#     php artisan migrate --force || true
# else
#     echo "   No pending migrations, skipping..."
# fi
```

#### Option 2: Run Migrate Manual

```bash
cd /opt/ruangtes-api

# Check migration status
php artisan migrate:status

# Run migrate manual (jika ada pending)
php artisan migrate --force

# Atau rollback jika perlu
php artisan migrate:rollback
```

#### Option 3: Environment Variable Control

Bisa tambahkan environment variable untuk control migrate:

```bash
# Di .env atau deploy script
SKIP_MIGRATE=false  # Set true untuk skip migrate
```

Lalu update deploy script:

```bash
if [ "${SKIP_MIGRATE:-false}" != "true" ]; then
    if php artisan migrate:status | grep -q "Pending"; then
        php artisan migrate --force || true
    fi
fi
```

## Best Practices

### 1. Review Migrations Before Deploy

```bash
# Check migration files yang akan di-run
php artisan migrate:status

# Preview SQL yang akan dijalankan (jika menggunakan SQLite untuk preview)
php artisan migrate --pretend
```

### 2. Backup Database Before Migrate

```bash
# Backup sebelum migrate
pg_dump -U ruangtes ruangtes > backup_$(date +%Y%m%d_%H%M%S).sql

# Run migrate
php artisan migrate --force

# Jika ada masalah, restore
psql -U ruangtes ruangtes < backup.sql
```

### 3. Test Migrations Locally First

```bash
# Test di local/staging dulu
php artisan migrate:fresh --seed  # Fresh install
php artisan migrate --force        # Test migrations
```

### 4. Use Migration Rollback Strategy

Pastikan migrations bisa di-rollback:

```bash
# Check rollback
php artisan migrate:rollback --step=1

# Rollback semua
php artisan migrate:rollback
```

## Deployment Flow

### Standard Deployment (Auto Migrate)

```bash
cd /opt/ruangtes-api
./deploy/deploy.sh
```

Script akan:
1. ✅ Pull latest code
2. ✅ Install dependencies
3. ✅ **Check pending migrations** → Run migrate jika ada
4. ✅ Cache config
5. ✅ Restart services

### Deployment Without Migrate

```bash
cd /opt/ruangtes-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.4-fpm
sudo supervisorctl restart ruangtes-queue:*
```

### Deployment With Manual Migrate

```bash
cd /opt/ruangtes-api
git pull origin main
composer install --no-dev --optimize-autoloader

# Check migrations
php artisan migrate:status

# Run migrate manual (jika perlu)
php artisan migrate --force

# Continue deployment
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.4-fpm
sudo supervisorctl restart ruangtes-queue:*
```

## GitHub Actions

GitHub Actions workflow juga menggunakan **auto-check** untuk migrations:

```yaml
# Run migrations (only if there are pending migrations)
if php artisan migrate:status | grep -q "Pending"; then
  echo "Found pending migrations, running migrate..."
  php artisan migrate --force || true
else
  echo "No pending migrations, skipping..."
fi
```

## Troubleshooting

### Migration Failed

```bash
# Check migration status
php artisan migrate:status

# Check last migration
php artisan migrate:rollback --step=1

# Fix migration file, then retry
php artisan migrate --force
```

### Skip Failed Migration

```bash
# Mark migration as run (DANGEROUS - use with caution)
php artisan migrate --pretend  # Preview
# Then manually fix database or migration file
```

### Check Migration History

```bash
# List all migrations
php artisan migrate:status

# Check migrations table
psql -U ruangtes -d ruangtes -c "SELECT * FROM migrations ORDER BY id DESC LIMIT 10;"
```
