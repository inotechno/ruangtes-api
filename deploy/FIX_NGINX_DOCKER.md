# Fix Nginx Configuration for Docker

Masalah: Nginx di server tidak bisa connect ke PHP-FPM di Docker container.

## Masalah

Nginx config saat ini menggunakan:
```nginx
upstream ruangtes_app {
    server 127.0.0.1:9000;
}
```

Tapi PHP-FPM berjalan di dalam Docker container, bukan di host. Ada 2 solusi:

## Solusi 1: Expose PHP-FPM Port ke Host (Recommended)

### Step 1: Update docker-compose.prod.yml

Tambahkan port mapping untuk app container:

```yaml
app:
  ports:
    - "127.0.0.1:9000:9000"
```

### Step 2: Restart Containers

```bash
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d
```

### Step 3: Verify

```bash
# Check if port 9000 is listening
netstat -tlnp | grep 9000
# atau
ss -tlnp | grep 9000
```

## Solusi 2: Use Docker Network (Alternative)

Jika tidak ingin expose port, bisa setup Nginx di Docker juga, atau gunakan reverse proxy.

## Troubleshooting Container Queue

Container `ruangtes-queue` restarting - cek logs:

```bash
docker compose -f docker-compose.prod.yml logs queue
```

Kemungkinan masalah:
1. Database belum ready
2. Redis belum ready
3. .env belum dikonfigurasi
4. Permission issues

## Fix Steps

1. Cek logs semua containers
2. Pastikan .env sudah dikonfigurasi
3. Pastikan storage permissions
4. Update docker-compose.prod.yml untuk expose port 9000
5. Restart containers
