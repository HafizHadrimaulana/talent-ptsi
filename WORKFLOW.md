# 🚀 Workflow - Talent PTSI

> **Deployment:** Manual via cPanel Git Version Control  
> **Production:** https://demo-sapahc.ptsi.co.id

---

## 📋 Quick Links

- **Production:** https://demo-sapahc.ptsi.co.id
- **cPanel:** https://cpaneldrc.ptsi.co.id:2083 (Git Version Control)
- **GitHub:** https://github.com/HafizHadrimaulana/talent-ptsi

---

## 🔄 Daily Workflow

### 1️⃣ Development (Local)

```bash
# Start project
cd C:\laragon\www\talent-ptsi
npm run dev

# Browser: http://localhost
```

**Saat coding:**
- Edit files → Vite auto-reload
- Test fitur locally
- Check console errors (F12)

**Stop dev server:** Press `q` atau `Ctrl+C`

---

### 2️⃣ Build & Commit

```bash
# Build production assets
npm run build

# Git workflow
git status
git add .
git commit -m "feat: deskripsi fitur"
git push origin main
```

**Commit messages:**
- `feat:` fitur baru
- `fix:` bug fix
- `chore:` maintenance (update deps, cleanup)
- `docs:` dokumentasi

---

### 3️⃣ Merge ke Production

```bash
# Option A: Direct push
git push origin main:production

# Option B: Merge manual
git checkout production
git merge main
git push origin production
```

---

### 4️⃣ Deploy ke cPanel

**Step 1: Git Pull**

1. Login: https://cpaneldrc.ptsi.co.id:2083
2. **Git Version Control** (section Files)
3. **Manage** repo `talent-ptsi`
4. **Update from Remote** (tombol biru)
5. Wait ~10 detik
6. ✅ Done!

**Step 2: Clear Cache (Kalo Perlu)**

Buka: https://demo-sapahc.ptsi.co.id/deploy-k8m3x9p2f5h7w4j6.php?token=80ROaMyPyXt81XHiNItLnVsitLXU0lcem5JHZuOEGBFCbZL41EpJdkiuIYbTL

Atau bikin file temporary `clear.php` via File Manager:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('config:clear');
$kernel->call('cache:clear');
$kernel->call('config:cache');
$kernel->call('route:cache');
$kernel->call('view:cache');

echo "✅ Cache cleared!";
```

Akses via browser, terus hapus file-nya.

**Step 3: Verify**

- Check: https://demo-sapahc.ptsi.co.id
- Test fitur yang baru di-deploy
- Check console errors (F12)

---

## 📝 Deployment Checklist

### Before Push

- [ ] `npm run build` (production assets)
- [ ] Test locally (no errors)
- [ ] Commit `public/build/` files
- [ ] Git push to production branch

### After Deploy

- [ ] cPanel Git Version Control → Update from Remote
- [ ] Clear cache (kalo ada config/route changes)
- [ ] Test production website
- [ ] Check Laravel logs kalo ada error

---

## ⏱️ Time Estimate

| Step | Time |
|------|------|
| Build + commit | ~1 menit |
| Push to GitHub | ~10 detik |
| cPanel Git pull | ~30 detik |
| Clear cache (optional) | ~1 menit |
| **Total** | **~2-3 menit** |

---

## 🐛 Common Issues

**CSS/JS not updated:**
- Hard refresh: `Ctrl + Shift + R`
- Clear browser cache
- Check `public/build/manifest.json` updated

**Upload/Storage 403:**
- Run: https://demo-sapahc.ptsi.co.id/fix-storage-direct.php?run=true

**Git pull failed:**
- cPanel → Git Version Control → Manage → Reset HEAD

**Laravel 500 error:**
- Check logs: `storage/logs/laravel.log` (via File Manager)
- Clear cache via temporary PHP script

---

## 🔀 Branch Info

- **main:** development & integration
- **develop:** training team
- **management-prinsip:** recruitment team  
- **production:** deployment (auto-triggered GitHub Actions)

---

**Last Updated:** 29 Jan 2026
