# 🚀 FULL AUTOMATION SETUP GUIDE

Complete setup untuk auto-deploy dari local → GitHub → cPanel

---

## 📋 Overview

**Workflow:**
```
Local Dev → Git Push → GitHub → Auto Deploy to cPanel
```

**Yang Otomatis:**
- ✅ Git pull di cPanel
- ✅ Copy build assets
- ✅ Sync storage files
- ✅ Run migrations
- ✅ Clear cache & optimize
- ✅ Set permissions

**Yang Manual (sekali setup):**
- ⚙️ Setup GitHub Secrets
- ⚙️ Upload deploy-auto.php ke cPanel
- ⚙️ Build assets di local (npm run build)

---

## 🔧 SETUP (Sekali Aja!)

### 1. Setup Deploy Script di cPanel

**A. Generate Secret Token (32+ karakter random)**
```bash
# Di terminal local:
openssl rand -base64 32
# Contoh output: xK9mP2vL8nQ4rT6wY1zA3bC5dE7fG9hJ0...
```

**B. Edit deploy-auto.php**
```php
// Line 21 - GANTI ini dengan token random tadi:
define('SECRET_TOKEN', 'xK9mP2vL8nQ4rT6wY1zA3bC5dE7fG9hJ0...');
```

**C. Upload deploy-auto.php ke cPanel**
```
Location: /home/demosapahcptsico/public_html/deploy-auto.php
```

Cara upload:
1. Login cPanel → File Manager
2. Masuk ke `public_html`
3. Upload file `deploy-auto.php`
4. Set permission: 644

**D. Test Manual**
```
https://demo-sapahc.ptsi.co.id/deploy-auto.php?token=YOUR_TOKEN
```
(Ganti YOUR_TOKEN dengan token yang kamu set tadi)

Jika muncul output deployment → **BERHASIL!** ✅

---

### 2. Setup GitHub Secrets

**A. Buka GitHub Repository**
```
https://github.com/HafizHadrimaulana/talent-ptsi
```

**B. Masuk ke Settings**
- Klik **Settings** tab
- Sidebar kiri → **Secrets and variables** → **Actions**
- Klik **New repository secret**

**C. Tambahkan 2 Secrets:**

**Secret 1: DEPLOY_URL**
```
Name: DEPLOY_URL
Value: https://demo-sapahc.ptsi.co.id/deploy-auto.php
```

**Secret 2: DEPLOY_TOKEN**
```
Name: DEPLOY_TOKEN
Value: xK9mP2vL8nQ4rT6wY1zA3bC5dE7fG9hJ0...
(Token yang sama dengan di deploy-auto.php)
```

---

### 3. Setup GitHub Actions Workflow

**File sudah dibuat:** `.github/workflows/deploy-cpanel.yml`

**Commit & Push ke GitHub:**
```bash
git add .github/workflows/deploy-cpanel.yml
git commit -m "Add auto-deploy workflow"
git push origin main
```

**Cek Workflow:**
1. Buka GitHub → Tab **Actions**
2. Pastikan workflow `Auto Deploy to cPanel` muncul
3. Status harus "Active" (bukan disabled)

---

## 🎯 CARA PAKAI (Sehari-hari)

### Workflow Development:

```bash
# 1. Development di local
npm run dev          # Jalankan local server
# ... coding ...

# 2. Build production assets
npm run build        # WAJIB! Ini generate public/build/

# 3. Commit changes + build assets
git add .
git commit -m "Your feature description"

# 4. Merge ke main (jika dari branch lain)
git checkout main
git merge your-feature-branch

# 5. Push ke production branch
git push origin main:production

# 🎉 SELESAI! GitHub Actions akan otomatis:
# - Trigger deploy-auto.php
# - cPanel akan pull, copy assets, migrate, optimize
# - Website langsung update!
```

**Cek Deployment:**
1. Buka GitHub → **Actions** tab
2. Lihat workflow run terbaru
3. Klik untuk detail log
4. Jika hijau ✅ → deployment berhasil!

---

## 📊 Monitoring & Troubleshooting

### Cek Deployment Status

**Via GitHub Actions:**
```
https://github.com/HafizHadrimaulana/talent-ptsi/actions
```
- Hijau ✅ = Berhasil
- Merah ❌ = Failed (klik untuk detail)

**Via cPanel (Manual trigger):**
```
https://demo-sapahc.ptsi.co.id/deploy-auto.php?token=YOUR_TOKEN
```

### Common Issues

**❌ Problem: GitHub Actions failed dengan HTTP 403**
- **Cause:** Token salah atau deploy-auto.php tidak accessible
- **Fix:** Cek DEPLOY_TOKEN di GitHub Secrets sama dengan deploy-auto.php

**❌ Problem: Website masih tampilkan versi lama**
- **Cause:** Browser cache atau CDN cache
- **Fix:** Hard refresh (Ctrl+Shift+R) atau clear browser cache

**❌ Problem: CSS/JS tidak update**
- **Cause:** Lupa `npm run build` sebelum push
- **Fix:** 
  ```bash
  npm run build
  git add public/build/
  git commit -m "Update build assets"
  git push origin main:production
  ```

**❌ Problem: Images tidak muncul setelah upload**
- **Cause:** Storage sync tidak jalan
- **Fix:** Run manual sync:
  ```
  https://demo-sapahc.ptsi.co.id/fix-storage-direct.php
  ```

**❌ Problem: Database migration error**
- **Cause:** Migration conflict atau syntax error
- **Fix:** Login cPanel, check error log:
  ```
  /home/demosapahcptsico/talent-ptsi/storage/logs/laravel.log
  ```

---

## ⚙️ Configuration Options

### deploy-auto.php Settings

**Disable Storage Sync** (jika tidak perlu):
```php
define('ENABLE_STORAGE_SYNC', false);
```

**Disable Composer Check** (jika tidak ada update dependencies):
```php
define('ENABLE_COMPOSER_CHECK', false);
```

**Manual Trigger via Cron** (optional - auto deploy setiap 5 menit jika ada update):
```bash
# cPanel → Cron Jobs
*/5 * * * * curl -s "https://demo-sapahc.ptsi.co.id/deploy-auto.php?token=YOUR_TOKEN" > /dev/null 2>&1
```

---

## 🔒 Security Best Practices

### 1. Keep Token Secret
- ❌ Jangan commit token ke Git
- ❌ Jangan share di chat/email
- ✅ Simpan di password manager
- ✅ Rotate token setiap 3-6 bulan

### 2. Delete Unused Scripts
```bash
# Hapus script debug setelah selesai setup:
rm /home/demosapahcptsico/public_html/setup-cpanel.php
rm /home/demosapahcptsico/public_html/debug-cpanel.php
rm /home/demosapahcptsico/public_html/fix-storage*.php
rm /home/demosapahcptsico/public_html/check-storage.php
```

### 3. Monitor Access Logs
```bash
# Check siapa saja yang access deploy-auto.php:
tail -f /home/demosapahcptsico/access-logs/demo-sapahc.ptsi.co.id
```

---

## 📚 Advanced: Manual Deployment

Jika GitHub Actions down atau butuh manual deploy:

**Method 1: Via Browser**
```
https://demo-sapahc.ptsi.co.id/deploy-auto.php?token=YOUR_TOKEN
```

**Method 2: Via Terminal (SSH jika ada akses)**
```bash
cd /home/demosapahcptsico/talent-ptsi
git pull origin production
php artisan migrate --force
php artisan optimize
```

**Method 3: Via cPanel Terminal** (jika tersedia)
```bash
php /home/demosapahcptsico/public_html/deploy-auto.php token=YOUR_TOKEN
```

---

## 🎓 Tips & Best Practices

### Development Workflow

**1. Branch Strategy:**
```
main          → Integration branch (semua merge ke sini)
develop       → Training team
management-   → Recruitment team
prinsip
production    → Deploy branch (auto-deploy)
```

**2. Merge & Deploy:**
```bash
# Feature selesai → Merge ke main
git checkout main
git merge your-feature

# Test di local
npm run build
npm run dev

# Deploy ke production
git push origin main:production  # Trigger auto-deploy
```

**3. Hotfix:**
```bash
# Untuk bug urgent:
git checkout production
git cherry-pick <commit-hash>  # Ambil commit spesifik
git push origin production      # Langsung deploy
```

### Storage Management

Karena storage **bukan symlink** (direct copy), ada 2 cara handle uploaded files:

**Option 1: Periodic Sync (Recommended)**
```bash
# Setup cron di cPanel (setiap 1 jam):
0 * * * * rsync -a /home/demosapahcptsico/talent-ptsi/storage/app/public/ /home/demosapahcptsico/public_html/storage/
```

**Option 2: Manual Sync (saat deploy)**
Deploy script sudah handle ini otomatis via `ENABLE_STORAGE_SYNC = true`

**Option 3: Upload Langsung ke public_html/storage**
Ubah Laravel config (tidak recommended):
```php
// config/filesystems.php - ubah 'public' disk:
'public' => [
    'driver' => 'local',
    'root' => '/home/demosapahcptsico/public_html/storage',
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

---

## ✅ Checklist Setup

Print & centang saat setup:

- [ ] Generate SECRET_TOKEN (32+ chars)
- [ ] Edit deploy-auto.php dengan token
- [ ] Upload deploy-auto.php ke public_html
- [ ] Test manual deploy via browser
- [ ] Setup GitHub Secret: DEPLOY_URL
- [ ] Setup GitHub Secret: DEPLOY_TOKEN
- [ ] Push workflow file ke GitHub
- [ ] Test GitHub Actions workflow
- [ ] Test push ke production branch
- [ ] Verify website update
- [ ] Delete unused debug scripts
- [ ] Save token di password manager
- [ ] Setup storage sync cron (optional)
- [ ] Document untuk team

---

## 🆘 Support

**Jika ada masalah:**

1. **Check GitHub Actions log** (detail error ada di sini)
2. **Check deploy-auto.php output** (manual trigger untuk debug)
3. **Check Laravel log:** `/home/demosapahcptsico/talent-ptsi/storage/logs/laravel.log`
4. **Check cPanel error log:** `/home/demosapahcptsico/public_html/error_log`

**Emergency Rollback:**
```bash
# Via cPanel Git Version Control:
# 1. Manage → Show Changes
# 2. Klik "Reset" pada commit sebelumnya
# 3. Run deploy-auto.php lagi
```

---

## 🎉 Success!

Sekarang kamu punya **FULL AUTOMATED DEPLOYMENT PIPELINE**!

```
Local → GitHub → cPanel
  ↓        ↓        ↓
Code    Actions   Live
```

**Happy Coding!** 🚀
