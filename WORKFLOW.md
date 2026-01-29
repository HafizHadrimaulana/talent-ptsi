# 🚀 Daily Workflow - Talent PTSI

> **Environment:** cPanel Shared Hosting (demo-sapahc.ptsi.co.id)  
> **Automation:** Manual deployment (SSH/Terminal not available)

---

## 📋 Quick Reference

- **Local:** http://localhost (Laragon)
- **Staging:** N/A
- **Production:** https://demo-sapahc.ptsi.co.id
- **cPanel:** https://cpaneldrc.ptsi.co.id:2083
- **GitHub:** https://github.com/HafizHadrimaulana/talent-ptsi

---

## 🛠️ Development Workflow

### 1️⃣ Start Development

```bash
# Start Laragon (Apache + MySQL)
# Buka Laragon → Start All

# Navigate to project
cd C:\laragon\www\talent-ptsi

# Start Vite dev server (hot reload)
npm run dev

# Buka browser: http://localhost
```

**Vite Running:**
- CSS/JS auto-compile saat file berubah
- Hot reload (ga perlu refresh browser)
- Press `q` untuk stop dev server

---

### 2️⃣ Database Work

#### Import Database

**Via phpMyAdmin:**
1. Buka: http://localhost/phpmyadmin
2. Select database: `talent_ptsi`
3. Import → Choose file `.sql`
4. Go

**Via Command Line:**
```bash
# Lokasi MySQL di Laragon
cd C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin

# Import database
mysql -u root -p talent_ptsi < path/to/backup.sql
```

#### Export Database

**Via Artisan:**
```bash
# Export migrations
php artisan migrate:status

# Seed data (kalo ada)
php artisan db:seed
```

**Via phpMyAdmin:**
1. Select database: `talent_ptsi`
2. Export → Quick → SQL
3. Download

---

### 3️⃣ Coding & Testing

```bash
# Run migrations (kalo ada migration baru)
php artisan migrate

# Clear cache saat develop
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Testing
php artisan test

# Atau buka PHPUnit
./vendor/bin/phpunit
```

**Common Tasks:**
```bash
# Generate model + migration
php artisan make:model NamaModel -m

# Generate controller
php artisan make:controller NamaController

# Generate request validation
php artisan make:request NamaRequest

# Check routes
php artisan route:list

# Tinker (interactive shell)
php artisan tinker
```

---

## 📦 Deployment Workflow

### 1️⃣ Build Assets for Production

```bash
# Stop dev server dulu (Ctrl+C atau 'q')

# Build production assets
npm run build

# Output: public/build/manifest.json + compiled CSS/JS
```

**Verify build:**
- Check `public/build/` ada file baru
- File size CSS/JS lebih kecil (minified)
- Manifest.json updated

---

### 2️⃣ Commit & Push to GitHub

```bash
# Check status
git status

# Stage semua changes
git add .

# Commit dengan message jelas
git commit -m "feat: tambah fitur X"
# atau
git commit -m "fix: perbaiki bug Y"
# atau
git commit -m "chore: update dependencies"

# Push ke main branch
git push origin main
```

**Git Best Practices:**
- Commit message jelas & deskriptif
- Commit sering (small commits)
- Test dulu sebelum commit

---

### 3️⃣ Merge ke Production Branch

```bash
# Switch ke production branch
git checkout production

# Merge dari main
git merge main

# Push ke production
git push origin production
```

**Alternative (Direct Push):**
```bash
# Push main langsung ke production
git push origin main:production
```

---

### 4️⃣ Deploy ke cPanel (Manual)

#### Step A: Git Pull via cPanel

1. **Login cPanel:** https://cpaneldrc.ptsi.co.id:2083
2. **Git Version Control** (di section Files)
3. Click **Manage** pada repo `talent-ptsi`
4. Click **Update from Remote** (tombol biru)
5. Wait ~5-10 detik sampai selesai
6. ✅ Code updated!

#### Step B: Clear Cache via Browser

Buka URL: https://demo-sapahc.ptsi.co.id/deploy-k8m3x9p2f5h7w4j6.php?token=80ROaMyPyXt81XHiNItLnVsitLXU0lcem5JHZuOEGBFCbZL41EpJdkiuIYbTL

**Script ini akan:**
- Log deployment notification
- Kasih instruksi manual steps

#### Step C: Manual Laravel Optimization (kalo perlu)

Kalo butuh clear cache atau migration:

**Via File Manager:**
1. cPanel → File Manager → `talent-ptsi`
2. Bikin file: `clear-cache.php`
3. Paste code:
```php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Clear & cache
$kernel->call('config:clear');
$kernel->call('cache:clear');
$kernel->call('config:cache');
$kernel->call('route:cache');
$kernel->call('view:cache');

echo "✅ Cache cleared & optimized!";
```
4. Buka: `https://demo-sapahc.ptsi.co.id/clear-cache.php`
5. Delete file `clear-cache.php` setelah selesai

**Migration:**
1. Bikin file: `migrate.php`
```php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('migrate', ['--force' => true]);
echo "✅ Migration completed!";
```
2. Buka: `https://demo-sapahc.ptsi.co.id/migrate.php`
3. Delete file setelah selesai

---

### 5️⃣ Verify Deployment

1. **Check Website:** https://demo-sapahc.ptsi.co.id
2. **Test fitur baru** yang di-deploy
3. **Check console errors** (F12 → Console)
4. **Test upload/storage** kalo ada perubahan storage

**Kalo ada issue:**
- Check Laravel logs: `storage/logs/laravel.log` (via cPanel File Manager)
- Rollback: git revert commit, push, pull via cPanel lagi

---

## 🔄 Branch Strategy

```
main (development)
├── develop (training team)
├── management-prinsip (recruitment team)
└── production (deployment)
```

**Workflow:**
- **main:** Integration branch, merge semua feature
- **develop:** Training team development
- **management-prinsip:** Recruitment team development
- **production:** Deploy ke server (include vendor/ & build/)

**Merge Flow:**
```bash
# Feature branch → main
git checkout main
git merge feature/nama-fitur

# main → production (ready to deploy)
git checkout production
git merge main
git push origin production
```

---

## 📁 File Structure

```
talent-ptsi/
├── app/                    # Laravel app code
├── resources/
│   ├── js/                 # Vite JS source
│   ├── css/                # Vite CSS source
│   └── views/              # Blade templates
├── public/
│   └── build/              # Vite compiled (di-commit ke Git)
├── storage/
│   ├── app/public/         # Upload files
│   └── logs/               # Laravel logs
├── database/
│   ├── migrations/         # DB schema
│   └── seeders/            # Sample data
├── .env                    # Local config (NOT in Git)
├── .env.production         # Production config (NOT in Git)
└── package.json            # NPM dependencies
```

**Important:**
- `public/build/` **DI-COMMIT** ke Git (karena ga bisa build di server)
- `.env` **TIDAK di-commit** (sensitive data)
- `vendor/` di-commit di branch `production` only

---

## 🐛 Troubleshooting

### Issue: Vite Build Error

```bash
# Clear node_modules & reinstall
rm -rf node_modules
npm install

# Rebuild
npm run build
```

### Issue: Laravel Error 500

1. Check logs: `storage/logs/laravel.log`
2. Common causes:
   - `.env` config salah
   - Cache config outdated
   - Permission storage/bootstrap

**Fix:**
```bash
# Clear all cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Set permission (Windows Laragon biasanya OK)
# Linux/Mac:
chmod -R 775 storage bootstrap/cache
```

### Issue: Upload Image 403 Forbidden

Jalanin: https://demo-sapahc.ptsi.co.id/fix-storage-direct.php?run=true

Script ini copy files dari `storage/app/public/` ke `public_html/storage/` (no symlinks).

### Issue: CSS/JS Not Loading

1. Check `public/build/manifest.json` ada & updated
2. Force refresh browser: `Ctrl + F5`
3. Clear Laravel config cache
4. Check .env `ASSET_URL` pointing ke correct domain

### Issue: Git Pull Failed di cPanel

**Option 1: Reset via Git Version Control**
- cPanel → Git Version Control
- Manage → Pull or Deploy → Reset HEAD

**Option 2: Manual via File Manager**
- Backup dulu folder yang ada conflict
- Delete folder
- Git Version Control → Create repo baru

---

## 📋 Checklists

### Before Push to Production

- [ ] `npm run build` (assets compiled)
- [ ] Test locally (http://localhost)
- [ ] Commit build files (`git add public/build`)
- [ ] Migration tested (kalo ada)
- [ ] `.env.production` config checked (kalo ada perubahan)
- [ ] No console errors
- [ ] No breaking changes

### After Deploy to Production

- [ ] Git pull via cPanel Git Version Control
- [ ] Run clear-cache.php (kalo ada config changes)
- [ ] Run migrate.php (kalo ada migration)
- [ ] Test website: https://demo-sapahc.ptsi.co.id
- [ ] Check Laravel logs (kalo ada error)
- [ ] Verify upload/storage working (kalo ada changes)

---

## ⏱️ Deployment Time

**Total: ~3-5 menit (manual)**

| Step | Time |
|------|------|
| Build assets (`npm run build`) | ~30 detik |
| Commit & push | ~30 detik |
| Login cPanel + Git pull | ~1-2 menit |
| Clear cache/migrate | ~1-2 menit |
| Testing | ~30 detik |

**Note:** Kalo SSH available, bisa jadi 10 detik (automated). Sementara ini manual dulu.

---

## 🔗 Useful Links

- **Production:** https://demo-sapahc.ptsi.co.id
- **cPanel:** https://cpaneldrc.ptsi.co.id:2083
- **GitHub Repo:** https://github.com/HafizHadrimaulana/talent-ptsi
- **Laravel Docs:** https://laravel.com/docs/11.x
- **Vite Docs:** https://vitejs.dev

---

**Last Updated:** 29 Jan 2026  
**Status:** Manual deployment workflow (SSH not available)
