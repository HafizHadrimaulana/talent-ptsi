# 🚀 Deploy Guide - Talent PTSI

> **Kenapa SSH?** cPanel shared hosting block PHP `exec()`, jadi automation via HTTP ga bisa. Harus pake SSH.

---

## 📌 Quick Info

- **Website:** https://demo-sapahc.ptsi.co.id
- **cPanel:** https://cpaneldrc.ptsi.co.id:2083 (user: `demosapahcptsico`)
- **GitHub:** https://github.com/HafizHadrimaulana/talent-ptsi
- **Branch Deploy:** `production`

---

## 🎯 Setup SSH (Sekali Aja)

### Step 1: Generate SSH Key di cPanel

1. Buka cPanel → **Security** → **SSH Access** → **Manage SSH Keys**
2. Klik **Generate a New Key**
3. Isi form:
   ```
   Key Name: deploy-github
   Key Password: buat password (misal: Deploy123!)
   Reenter Password: ketik ulang password yang sama
   Key Type: RSA
   Key Size: 2048  ← pilih ini aja, cukup
   ```
   > ⚠️ **Simpan password ini!** Nanti dipake tiap kali SSH connect
4. Klik **Generate Key**

### Step 2: Authorize Key

1. Klik **Go Back**
2. Di list "Public Keys", cari key `deploy-github`
3. Klik **Manage** (di sebelah kanan)
4. Klik **Authorize** (tombol biru)
5. ✅ Sekarang SSH udah aktif!

### Step 3: Download Private Key

1. Klik **Go Back**
2. Di list "Private Keys", cari `deploy-github`
3. Klik **View/Download**
4. Klik **Download Key**
5. Save file ke: `C:\Users\hafiz\.ssh\cpanel-deploy.key`

### Step 4: Set Permission (Windows Git Bash)

```bash
# Buka Git Bash
cd ~/.ssh
chmod 600 cpanel-deploy.key
```

### Step 5: Test Koneksi

```bash
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i ~/.ssh/cpanel-deploy.key
```

**Saat connect:**
1. Pertama kali muncul konfirmasi fingerprint → ketik: **yes**
2. Minta **passphrase** → ketik password yang lu buat di Step 1 (misal: `Deploy123!`)
3. Kalo berhasil, bakal masuk ke terminal server
4. Ketik `exit` buat keluar

> 💡 **Tip:** Password ini diminta tiap kali SSH connect. Kalo mau auto deploy via GitHub Actions, password ini bakal di-handle otomatis via secrets.

---

## 🚀 Cara Deploy (Setiap Update)

### Workflow Lokal → Production

```bash
# 1. Development lokal
cd C:\laragon\www\talent-ptsi
npm run dev

# 2. Build untuk production
npm run build

# 3. Commit & push
git add .
git commit -m "update fitur X"
git push origin main

# 4. Merge ke production branch
git checkout production
git merge main
git push origin production
```

### Deploy ke Server (Manual via SSH)

**Option 1: Interaktif** (login dulu, command satu-satu)

```bash
# Login SSH (nanti minta password SSH key)
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i ~/.ssh/cpanel-deploy.key

# Jalankan commands
cd ~/talent-ptsi
git pull origin production
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# (Kalo ada migration baru)
php artisan migrate --force

# Keluar
exit
```

**Option 2: One-liner** (langsung jalan semua)

```bash
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i ~/.ssh/cpanel-deploy.key "cd ~/talent-ptsi && git pull origin production && php artisan config:clear && php artisan cache:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"
```

> Bakal minta password SSH key sekali, terus langsung jalan semua command. Selesai ~5-10 detik.

---

## ⚡ Automation (Optional - Kalo Mau Full Auto)

> **Current:** Manual SSH deploy tiap push (5 menit)  
> **With automation:** Push ke GitHub langsung auto deploy (0 menit)

### Setup GitHub Actions SSH Deploy

**1. Add SSH Private Key to GitHub**

- Buka: https://github.com/HafizHadrimaulana/talent-ptsi/settings/secrets/actions
- Klik: **New repository secret**
- Name: `SSH_PRIVATE_KEY`
- Value: Buka file `cpanel-deploy.key` dengan Notepad, copy **SEMUA** isi (dari `-----BEGIN` sampai `-----END`)
- Klik: **Add secret**

**2. Add SSH Password to GitHub**

- Klik: **New repository secret** lagi
- Name: `SSH_PASSPHRASE`
- Value: Password SSH key yang lu buat (misal: `Deploy123!`)
- Klik: **Add secret**

**3. Update Workflow File**

Edit [.github/workflows/deploy-cpanel.yml](.github/workflows/deploy-cpanel.yml):

```yaml
name: Auto Deploy via SSH

on:
  push:
    branches: [production]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: 🚀 Deploy
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: cpaneldrc.ptsi.co.id
          username: demosapahcptsico
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          passphrase: ${{ secrets.SSH_PASSPHRASE }}
          port: 2083
          script: |
            cd ~/talent-ptsi
            git pull origin production
            php artisan config:clear && php artisan cache:clear
            php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**4. Test**

```bash
git add .github/workflows/deploy-cpanel.yml
git commit -m "Enable SSH auto-deploy"
git push origin production
```

Cek: https://github.com/HafizHadrimaulana/talent-ptsi/actions

Kalo berhasil, next time tinggal `git push origin production` aja, deploy otomatis jalan.

---

## 📋 Cheatsheet

### Deploy Manual (Copy-Paste)

```bash
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i ~/.ssh/cpanel-deploy.key "cd ~/talent-ptsi && git pull origin production && php artisan config:clear && php artisan cache:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"
```

### Kalo Ada Migration

```bash
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i ~/.ssh/cpanel-deploy.key "cd ~/talent-ptsi && php artisan migrate --force"
```

### Cek Website

- Production: https://demo-sapahc.ptsi.co.id
- cPanel: https://cpaneldrc.ptsi.co.id:2083
- GitHub Actions: https://github.com/HafizHadrimaulana/talent-ptsi/actions

---

## 🛠️ Troubleshooting

**SSH ga bisa connect**
```bash
# Cek SSH key permission
chmod 600 ~/.ssh/cpanel-deploy.key

# Test koneksi dengan verbose
ssh -v demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i ~/.ssh/cpanel-deploy.key
```

**Git pull error**
```bash
# Login SSH, force reset
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i ~/.ssh/cpanel-deploy.key
cd ~/talent-ptsi
git reset --hard origin/production
git pull origin production
exit
```

**Upload image 403 Forbidden**

Jalanin: https://demo-sapahc.ptsi.co.id/fix-storage-direct.php?run=true

**Cache ga ke-clear**
```bash
ssh demosapahcptsico@cpaneldrc.ptsi.co.id -p 2083 -i ~/.ssh/cpanel-deploy.key "cd ~/talent-ptsi && php artisan optimize:clear && php artisan optimize"
```

---

**Last Updated:** 29 Jan 2026
