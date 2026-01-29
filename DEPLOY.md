# Deployment Guide - Git Version Control cPanel

## 🎯 Cara Deployment

### Strategi:
1. **GitHub Actions** - Build assets & commit ke branch production
2. **cPanel Git** - Clone repository & pull updates
3. **Deploy Script** - Automate deployment tasks

---

## 📋 SETUP STEP BY STEP

### STEP 1: Clone Repository di cPanel

1. Login cPanel → Cari **"Git™ Version Control"**
2. Klik **"Create"**
3. Isi form:
   ```
   Clone URL: https://github.com/HafizHadrimaulana/talent-ptsi.git
   Repository Path: /home/demosapahcptsico/talent-ptsi
   Repository Name: talent-ptsi
   ```
4. Klik **"Create"**
5. Tunggu proses clone selesai

### STEP 2: Switch ke Branch Production

1. Di Git Version Control, klik **"Manage"** pada repository talent-ptsi
2. Di dropdown branch, pilih **"production"**
3. Klik **"Update from Remote"**

### STEP 3: Upload Deploy Script

1. Di cPanel → **File Manager**
2. Navigate ke `/home/demosapahcptsico/`
3. Upload file **`deploy.sh`** (dari root project ini)
4. Klik kanan file `deploy.sh` → **Permissions** → Set ke **755** (rwxr-xr-x)

### STEP 4: Setup .env Production

1. Di File Manager, navigate ke `/home/demosapahcptsico/talent-ptsi/`
2. Copy `.env.example` → rename ke `.env`
3. Edit `.env` dengan config production:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

### STEP 5: First Time Setup

Di cPanel → **Terminal**, jalankan:

```bash
cd /home/demosapahcptsico/talent-ptsi

# Generate app key
php artisan key:generate

# Set permissions
chmod -R 775 storage bootstrap/cache
chmod -R 777 storage/logs

# Create storage link
php artisan storage:link

# Run migrations
php artisan migrate --force

# Optimize
php artisan optimize
```

### STEP 6: Link ke public_html

**Option A: Symlink ke public/** (Recommended)
```bash
cd /home/demosapahcptsico/public_html
# Backup index.php lama jika ada
mv index.php index.php.backup

# Link ke Laravel public
ln -s /home/demosapahcptsico/talent-ptsi/public/* .
```

**Option B: Update index.php** (Sudah ada di attachment)
Upload file `index.php` ke `/home/demosapahcptsico/public_html/`

---

## 🚀 CARA DEPLOY SETELAH SETUP

### Dari Development (Local):

```bash
# 1. Merge changes ke main
git checkout main
git merge develop  # atau management-prinsip
git push origin main

# 2. Merge main ke production
git checkout production
git merge main
git push origin production

# 3. GitHub Actions akan:
#    - Build assets
#    - Commit build files
#    - Push ke production
```

### Di cPanel:

**Option A: Manual Pull (Simple)**
1. Git Version Control → Klik **"Manage"** pada talent-ptsi
2. Klik **"Pull or Deploy"**
3. Klik **"Update from Remote"**
4. Di Terminal, jalankan: `bash /home/demosapahcptsico/deploy.sh`

**Option B: Setup Cron Job (Otomatis)**
1. cPanel → **Cron Jobs**
2. Add new cron:
   ```
   */5 * * * * cd /home/demosapahcptsico/talent-ptsi && git fetch origin production && git diff --quiet HEAD origin/production || bash /home/demosapahcptsico/deploy.sh > /dev/null 2>&1
   ```
   (Cek update setiap 5 menit)

**Option C: Webhook (Advanced)**
Setup webhook endpoint di Laravel untuk trigger deployment otomatis saat push ke GitHub.

---

## 📝 Workflow Summary

```
Local Development
    ↓ (git push)
GitHub Repository
    ↓ (GitHub Actions: build assets)
Branch Production (with built assets)
    ↓ (git pull manual/cron/webhook)
cPanel Repository
    ↓ (deploy.sh)
Laravel Optimized & Running
```

---

## 🔍 Troubleshooting

### Build assets gagal di GitHub Actions
- Cek node_modules sudah di .gitignore
- Pastikan package.json valid

### Git pull error: "local changes would be overwritten"
```bash
cd /home/demosapahcptsico/talent-ptsi
git reset --hard origin/production
```

### Permission denied
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 777 storage/logs
```

### .env tidak ada
```bash
cp .env.example .env
nano .env
php artisan key:generate
```

---

## ⚠️ IMPORTANT NOTES

1. **JANGAN commit .env** ke Git
2. **JANGAN commit node_modules** dan **vendor**
3. **public/build/** akan di-commit otomatis oleh GitHub Actions
4. Database credentials berbeda antara local dan production
5. Set `APP_DEBUG=false` di production
6. Pastikan `APP_URL` sesuai domain production

---

## 🎉 Deploy Checklist

- [ ] Repository cloned di cPanel
- [ ] Branch switched ke production
- [ ] deploy.sh uploaded & chmod 755
- [ ] .env created & configured
- [ ] App key generated
- [ ] Permissions set
- [ ] Storage linked
- [ ] Migrations run
- [ ] First deploy success
- [ ] Website accessible

---

**Setelah setup awal, deploy cukup:**
1. Push ke production branch
2. Pull di cPanel (manual/cron)
3. Done! ✨
