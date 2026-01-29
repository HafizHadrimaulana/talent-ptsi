# 🚀 Deployment Guide - SSH Method

## ⚠️ cPanel Shared Hosting Limitation

**Status:** HTTP-based automation **NOT POSSIBLE** due to severe `exec()` restrictions.

**Solution:** Deploy via SSH (manual or automated via GitHub Actions)

---

## 📋 Current Setup

- **Repository:** https://github.com/HafizHadrimaulana/talent-ptsi.git
- **Branch:** `production` (for deployment)
- **cPanel:** cpaneldrc.ptsi.co.id:2083
- **User:** demosapahcptsico
- **Website:** https://demo-sapahc.ptsi.co.id

---

## 🔑 Setup SSH Access (One-Time)

### 1. Enable SSH in cPanel

1. Login to cPanel: https://cpaneldrc.ptsi.co.id:2083
2. Go to **Security → SSH Access → Manage SSH Keys**
3. Click **Generate a New Key**
   - Key Name: `deploy-github` (or any name)
   - Key Type: RSA
   - Key Size: 4096 bits
   - Click **Generate Key**
4. Click **Go Back**
5. Find the key you just created
6. Click **Manage** → **Authorize**
7. Click **Go Back** → **View/Download** → Download **Private Key**

### 2. Test SSH Connection

```bash
# Windows (Git Bash / WSL / PowerShell)
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i path/to/private-key

# Linux/Mac
chmod 600 path/to/private-key
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i path/to/private-key
```

First time akan minta konfirmasi fingerprint, ketik `yes`.

---

## 🚀 Manual Deployment via SSH

### Full Deployment Steps

```bash
# Connect via SSH
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083

# Navigate to project
cd ~/talent-ptsi

# Pull latest code
git pull origin production

# Clear caches
php artisan config:clear
php artisan cache:clear

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# (Optional) Run migrations
php artisan migrate --force

# (Optional) Sync storage files if needed
# Run: https://demo-sapahc.ptsi.co.id/fix-storage-direct.php?run=true

# Exit SSH
exit
```

### Quick Deploy (One-Liner)

```bash
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 "cd ~/talent-ptsi && git pull origin production && php artisan config:clear && php artisan cache:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"
```

---

## 🤖 Automated Deployment via GitHub Actions

### Option A: SSH Direct Deploy (Recommended)

**Advantages:**
- ✅ Fully automated (no manual steps)
- ✅ Runs commands directly via SSH (no exec restrictions)
- ✅ Fast (~10-15 seconds)
- ✅ Logs available in GitHub Actions

**Setup:**

1. **Add SSH Private Key to GitHub Secrets**
   - Go to: https://github.com/HafizHadrimaulana/talent-ptsi/settings/secrets/actions
   - Click **New repository secret**
   - Name: `SSH_PRIVATE_KEY`
   - Value: Paste entire private key content (including `-----BEGIN RSA PRIVATE KEY-----`)

2. **Update GitHub Actions Workflow**

Edit `.github/workflows/deploy-cpanel.yml`:

```yaml
name: Deploy to cPanel via SSH

on:
  push:
    branches:
      - production

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: 🚀 Deploy via SSH
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: cpaneldrc.ptsi.co.id
          username: demosapahcptsico
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          port: 2083
          script: |
            cd ~/talent-ptsi
            git pull origin production
            php artisan config:clear
            php artisan cache:clear
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            echo "✅ Deployment completed!"
```

3. **Push workflow update**

```bash
git add .github/workflows/deploy-cpanel.yml
git commit -m "Update to SSH-based deployment"
git push origin main:production
```

4. **Test deployment**

Push any change to `production` branch and check GitHub Actions tab.

---

### Option B: HTTP Notification + Manual SSH (Current)

**Current setup:**
- GitHub Actions triggers: `deploy-k8m3x9p2f5h7w4j6.php`
- Script only logs notification (no commands run)
- Manual SSH deployment still needed

**Why:** cPanel shared hosting blocks PHP `exec()` even for Laravel commands.

---

## 📝 Daily Workflow

### Local Development

```bash
# 1. Develop locally
cd C:\laragon\www\talent-ptsi
npm run dev  # for development

# 2. Build assets for production
npm run build

# 3. Test locally
php artisan serve

# 4. Commit & push
git add .
git commit -m "Your changes"
git push origin main

# 5. Merge to production
git checkout production
git merge main
git push origin production
```

### Deployment

**Option A (Automated via SSH):**
- Just push to `production` branch
- GitHub Actions will deploy automatically
- Check: https://demo-sapahc.ptsi.co.id

**Option B (Manual via SSH):**
- Push to `production` branch
- Run SSH deploy command (see above)
- Check: https://demo-sapahc.ptsi.co.id

---

## 🛠️ Troubleshooting

### Issue: SSH Connection Refused

**Solution:**
- Make sure SSH key is authorized in cPanel
- Check port is 2083 (not 22)
- Verify username: `demosapahcptsico`

### Issue: Git Pull Fails

**Solution:**
```bash
# Via SSH
cd ~/talent-ptsi
git status
git reset --hard origin/production
git pull origin production
```

Or use cPanel **Git Version Control** → **Update from Remote**

### Issue: Permission Denied

**Solution:**
```bash
# Via SSH
chmod -R 775 ~/talent-ptsi/storage
chmod -R 775 ~/talent-ptsi/bootstrap/cache
```

### Issue: Images Not Accessible (403 Forbidden)

**Solution:**
- Run: https://demo-sapahc.ptsi.co.id/fix-storage-direct.php?run=true
- This copies files directly to `public_html/storage/` (no symlinks)

### Issue: Old Cache Not Cleared

**Solution:**
```bash
# Via SSH - Force clear everything
cd ~/talent-ptsi
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
rm -rf bootstrap/cache/*.php
php artisan optimize
```

---

## 📊 File Structure

```
talent-ptsi/
├── .github/
│   └── workflows/
│       └── deploy-cpanel.yml       # GitHub Actions workflow
├── public_html/                     # (cPanel symlink to public/)
│   ├── storage/                     # Direct file copy (no symlink)
│   ├── build/                       # Vite compiled assets
│   ├── deploy-k8m3x9p2f5h7w4j6.php # Deploy notification script
│   └── fix-storage-direct.php       # Storage file sync script
├── app/
├── resources/
├── storage/
└── DEPLOY-SSH.md                    # This file
```

---

## 🎯 Recommendations

### For Shared Hosting (Current):
✅ **Use SSH deployment** (manual or automated via GitHub Actions)

### For Better Performance:
Consider upgrading to:
- VPS (DigitalOcean, Linode, Vultr)
- Managed hosting (Laravel Forge, Ploi)
- Docker containers (AWS ECS, Google Cloud Run)

These allow full automation without restrictions.

---

## 🔗 Quick Links

- **Website:** https://demo-sapahc.ptsi.co.id
- **cPanel:** https://cpaneldrc.ptsi.co.id:2083
- **GitHub:** https://github.com/HafizHadrimaulana/talent-ptsi
- **GitHub Actions:** https://github.com/HafizHadrimaulana/talent-ptsi/actions

---

**Last Updated:** 2026-01-29  
**Status:** SSH deployment working, HTTP automation blocked by hosting restrictions
