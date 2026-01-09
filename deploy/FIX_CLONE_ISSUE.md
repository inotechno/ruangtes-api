# Fix Git Clone Issue

Jika Anda mendapatkan error:
```
fatal: destination path 'ruangtes-api' already exists and is not an empty directory.
```

## Solusi Cepat (Recommended)

### Opsi 1: Clone ke Lokasi yang Benar (Untuk EC2)

Jika Anda di EC2 dan ingin clone ke `/opt/ruangtes-api`:

```bash
# Hapus direktori lama jika ada
sudo rm -rf /opt/ruangtes-api

# Clone repository
sudo git clone https://github.com/inotechno/ruangtes-api.git /opt/ruangtes-api

# Set ownership
sudo chown -R $USER:$USER /opt/ruangtes-api
```

### Opsi 2: Backup File Penting, Hapus, dan Clone Ulang

Jika ada file penting di direktori lama (seperti `deploy.sh`):

```bash
# Backup file penting
mkdir ~/backup-ruangtes
cp -r ruangtes-api/deploy.sh ~/backup-ruangtes/ 2>/dev/null || true
cp -r ruangtes-api/.env ~/backup-ruangtes/ 2>/dev/null || true

# Hapus direktori lama
rm -rf ruangtes-api

# Clone repository baru
git clone https://github.com/inotechno/ruangtes-api.git ruangtes-api

# Restore file penting jika perlu (file deploy.sh sudah ada di repo, jadi tidak perlu restore)
# cp ~/backup-ruangtes/.env ruangtes-api/.env  # hanya jika .env ada dan penting
```

### Opsi 3: Clone ke Direktori Lain

```bash
# Clone ke direktori dengan nama berbeda
git clone https://github.com/inotechno/ruangtes-api.git ruangtes-api-new

# Atau langsung ke lokasi yang diinginkan
git clone https://github.com/inotechno/ruangtes-api.git /opt/ruangtes-api
```

### Opsi 4: Update Repository yang Sudah Ada (Jika Sudah Terhubung Git)

Jika direktori sudah ada dan sudah terhubung dengan git:

```bash
cd ruangtes-api

# Check apakah sudah ada git repository
if [ -d .git ]; then
    # Update remote jika perlu
    git remote set-url origin https://github.com/inotechno/ruangtes-api.git
    
    # Pull latest changes
    git pull origin main || git pull origin master
else
    # Jika belum ada git, init dan clone
    cd ..
    rm -rf ruangtes-api
    git clone https://github.com/inotechno/ruangtes-api.git ruangtes-api
fi
```

## Untuk EC2 Deployment (Recommended)

Jika Anda sedang setup di EC2, gunakan ini:

```bash
# Pastikan Anda di home directory atau direktori yang tepat
cd ~

# Hapus direktori lama jika ada
sudo rm -rf /opt/ruangtes-api

# Clone langsung ke lokasi production
sudo git clone https://github.com/inotechno/ruangtes-api.git /opt/ruangtes-api

# Set ownership (ganti 'ubuntu' dengan username Anda jika berbeda)
sudo chown -R ubuntu:ubuntu /opt/ruangtes-api

# Masuk ke direktori
cd /opt/ruangtes-api

# Setup environment
cp .env.production.example .env
nano .env  # Edit dengan konfigurasi Anda
```

## Troubleshooting

### Jika Permission Denied

```bash
# Check ownership
ls -la ruangtes-api

# Change ownership
sudo chown -R $USER:$USER ruangtes-api

# Hapus
rm -rf ruangtes-api
```

### Jika File Sedang Digunakan

```bash
# Check processes
lsof +D ruangtes-api

# Kill processes jika perlu
kill -9 <PID>

# Hapus
rm -rf ruangtes-api
```

## Catatan Penting

1. **File `deploy.sh` sudah ada di repository** di folder `deploy/deploy.sh`, jadi tidak perlu backup file tersebut.

2. **File `.env` tidak ada di repository** (karena di-ignore), jadi jika Anda sudah punya `.env` yang dikonfigurasi, backup dulu:
   ```bash
   cp ruangtes-api/.env ~/backup-env.txt
   # Setelah clone, restore:
   cp ~/backup-env.txt /opt/ruangtes-api/.env
   ```

3. **Untuk production**, selalu clone ke `/opt/ruangtes-api` seperti yang direkomendasikan di dokumentasi.
