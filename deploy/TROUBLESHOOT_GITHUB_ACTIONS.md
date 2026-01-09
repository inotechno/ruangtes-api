# Troubleshooting GitHub Actions Deployment

## Error: `dial tcp [IP]:[PORT]: i/o timeout`

Error ini terjadi ketika GitHub Actions runner tidak bisa connect ke EC2 instance.

## Penyebab & Solusi

### 1. Security Group EC2 Tidak Allow GitHub Actions IPs

**Masalah:** Security Group EC2 hanya allow IP tertentu, tapi GitHub Actions IP ranges berubah-ubah.

**Solusi A: Allow All IPs (Temporary untuk Testing)**

1. Login ke AWS Console
2. EC2 → Security Groups
3. Pilih security group untuk EC2 instance
4. Inbound Rules → Edit
5. Tambahkan rule:
   - Type: SSH
   - Port: 22
   - Source: `0.0.0.0/0` (All IPv4 addresses)
   - Description: "Allow SSH from GitHub Actions"

**Solusi B: Allow Specific GitHub Actions IP Ranges (Recommended)**

GitHub Actions menggunakan IP ranges yang bisa berubah. Untuk production, gunakan:

1. Download GitHub Actions IP ranges:
   ```bash
   curl https://api.github.com/meta | jq '.actions[]'
   ```

2. Atau gunakan GitHub Actions IP ranges:
   - https://api.github.com/meta (check `actions` array)

3. Update Security Group dengan IP ranges tersebut

**Solusi C: Use VPC Endpoint atau VPN (Most Secure)**

Untuk production, gunakan VPC endpoint atau VPN connection.

### 2. EC2 Instance Tidak Running

**Check:**
```bash
# Di AWS Console
EC2 → Instances → Check instance state
```

**Fix:**
- Start instance jika stopped
- Check instance health

### 3. SSH Port Salah

**Check:**
```bash
# Test SSH connection manual
ssh -i your-key.pem -p 22 ubuntu@your-ec2-ip
```

**Fix:**
- Pastikan `EC2_PORT` secret di GitHub benar (default: 22)
- Jika menggunakan custom port, update Security Group inbound rules

### 4. SSH Key Tidak Valid

**Check:**
1. Pastikan SSH key di GitHub Secrets adalah **private key** (bukan public key)
2. Format harus benar (dengan `-----BEGIN OPENSSH PRIVATE KEY-----` atau `-----BEGIN RSA PRIVATE KEY-----`)

**Fix:**
```bash
# Generate new SSH key pair
ssh-keygen -t rsa -b 4096 -C "github-actions" -f github-actions-key

# Copy public key to EC2
ssh-copy-id -i github-actions-key.pub ubuntu@your-ec2-ip

# Copy private key content to GitHub Secret
cat github-actions-key
# Copy output ke GitHub Secret EC2_SSH_KEY
```

### 5. Network Connectivity Issue

**Test Connection:**
```bash
# Test dari local machine
telnet your-ec2-ip 22

# Atau
nc -zv your-ec2-ip 22
```

**Fix:**
- Check firewall rules
- Check VPC/Subnet configuration
- Check route tables

### 6. EC2 Host Key Verification Failed

**Fix:**
Tambahkan `fingerprint` atau disable strict host key checking:

```yaml
- name: Deploy to EC2
  uses: appleboy/ssh-action@v1.0.0
  with:
    host: ${{ secrets.EC2_HOST }}
    username: ${{ secrets.EC2_USERNAME }}
    key: ${{ secrets.EC2_SSH_KEY }}
    port: ${{ secrets.EC2_PORT || 22 }}
    timeout: 60s
    command_timeout: 10m
    fingerprint: ${{ secrets.EC2_FINGERPRINT }}  # Optional
```

## Quick Fix Steps

### Step 1: Update Security Group

1. AWS Console → EC2 → Security Groups
2. Pilih security group untuk instance
3. Inbound Rules → Add Rule:
   - Type: SSH
   - Port: 22
   - Source: `0.0.0.0/0` (untuk testing)
   - Save

### Step 2: Verify SSH Connection

```bash
# Test SSH dari local
ssh -i your-key.pem ubuntu@your-ec2-ip

# Jika berhasil, berarti connectivity OK
```

### Step 3: Check GitHub Secrets

Pastikan di GitHub Repository → Settings → Secrets and variables → Actions:

- `EC2_HOST` - EC2 Public IP atau Domain (contoh: `54.150.202.213`)
- `EC2_USERNAME` - SSH username (biasanya `ubuntu` atau `ec2-user`)
- `EC2_SSH_KEY` - Private SSH key (full content dengan header/footer)
- `EC2_PORT` - SSH port (default: `22`)

### Step 4: Test GitHub Actions

Push perubahan dan check GitHub Actions logs untuk error detail.

## Alternative: Use AWS Systems Manager (SSM)

Jika SSH terus bermasalah, bisa gunakan AWS Systems Manager Session Manager:

### Setup SSM

1. Install SSM Agent di EC2 (usually pre-installed)
2. Attach IAM role dengan policy `AmazonSSMManagedInstanceCore`
3. Update GitHub Actions workflow:

```yaml
- name: Deploy to EC2 via SSM
  uses: aws-actions/amazon-ec2-instance-connect-send-ssh-public-key@v1
  with:
    aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
    aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
    aws-region: ap-southeast-1
    instance-id: ${{ secrets.EC2_INSTANCE_ID }}
    public-key: ${{ secrets.EC2_PUBLIC_KEY }}
```

## Debug Mode

Enable debug di GitHub Actions untuk melihat detail error:

```yaml
- name: Deploy to EC2
  uses: appleboy/ssh-action@v1.0.0
  with:
    host: ${{ secrets.EC2_HOST }}
    username: ${{ secrets.EC2_USERNAME }}
    key: ${{ secrets.EC2_SSH_KEY }}
    port: ${{ secrets.EC2_PORT || 22 }}
    timeout: 60s
    command_timeout: 10m
    debug: true  # Enable debug
    script: |
      # Your deployment script
```

## Common Issues Checklist

- [ ] Security Group allows SSH (port 22) from `0.0.0.0/0` or GitHub IP ranges
- [ ] EC2 instance is running
- [ ] EC2 has public IP or Elastic IP
- [ ] SSH key format is correct (private key, not public)
- [ ] GitHub Secrets are set correctly
- [ ] Network ACLs allow traffic
- [ ] Route tables are correct
- [ ] SSH service is running on EC2 (`sudo systemctl status ssh`)

## Test Connection Script

Create script untuk test connection:

```bash
#!/bin/bash
# test-connection.sh

HOST=$1
USER=$2
PORT=${3:-22}

echo "Testing SSH connection to $USER@$HOST:$PORT..."

# Test port
nc -zv $HOST $PORT

# Test SSH
ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no -p $PORT $USER@$HOST "echo 'Connection successful!'"
```

Run:
```bash
chmod +x test-connection.sh
./test-connection.sh your-ec2-ip ubuntu 22
```
