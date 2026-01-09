# Fix Docker Compose Command Not Found

Jika Anda mendapat error:
```
docker-compose: command not found
```

## Penyebab

Docker Compose V2 menggunakan command `docker compose` (tanpa dash), bukan `docker-compose` (dengan dash).

## Solusi

### Opsi 1: Gunakan Docker Compose V2 (Recommended)

Docker Compose V2 sudah terinstall bersama Docker. Gunakan:

```bash
# Gunakan 'docker compose' (tanpa dash)
docker compose version
docker compose -f docker-compose.prod.yml up -d
```

### Opsi 2: Install Docker Compose V1 (Legacy)

Jika ingin tetap menggunakan `docker-compose`:

```bash
# Download Docker Compose V1
sudo curl -L "https://github.com/docker/compose/releases/download/v1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose

# Make executable
sudo chmod +x /usr/local/bin/docker-compose

# Verify
docker-compose --version
```

### Opsi 3: Create Alias

Buat alias untuk kemudahan:

```bash
# Add to ~/.bashrc or ~/.zshrc
echo 'alias docker-compose="docker compose"' >> ~/.bashrc
source ~/.bashrc

# Now you can use both
docker-compose --version
docker compose --version
```

## Update Scripts

Scripts sudah diupdate untuk otomatis detect dan menggunakan command yang tersedia:
- `deploy/deploy.sh` - Auto-detect docker compose command
- `deploy/health-check.sh` - Auto-detect docker compose command
- `.github/workflows/deploy.yml` - Auto-detect docker compose command

## Verify

Setelah fix, test dengan:

```bash
# Check version
docker compose version

# Or if using V1
docker-compose --version

# Test deploy script
cd /opt/ruangtes-api
./deploy/deploy.sh
```

## Catatan

- Docker Compose V2 adalah default di Docker Desktop dan Docker Engine terbaru
- V2 lebih cepat dan memiliki fitur lebih banyak
- Scripts sudah diupdate untuk support kedua versi
