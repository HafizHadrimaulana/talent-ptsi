# 🚀 TUTORIAL LENGKAP DEPLOYMENT KE CPANEL
## Talent PTSI - Git Version Control Deployment

---

## 📋 TABLE OF CONTENTS

1. [Persiapan](#persiapan)
2. [Backup Data Lama](#backup-data-lama)
3. [Clone Repository](#clone-repository)
4. [Switch Branch](#switch-branch)
5. [Setup Environment](#setup-environment)
6. [Set Permissions](#set-permissions)
7. [Run Setup Script](#run-setup-script)
8. [Testing](#testing)
9. [Deployment Berikutnya](#deployment-berikutnya)
10. [Troubleshooting](#troubleshooting)

---

## ⚙️ PERSIAPAN

### Yang Anda Butuhkan:
- ✅ Akses cPanel: `https://cpaneldrc.ptsi.co.id:2083`
- ✅ Username: `demosapahcptsico`
- ✅ Password: [password Anda]
- ✅ Repository GitHub: `https://github.com/HafizHadrimaulana/talent-ptsi.git`

### Tools yang Dipakai:
- **File Manager** - untuk manage files
- **Git™ Version Control** - untuk clone & pull repository
- **phpMyAdmin** - untuk database (optional)

---

## 📦 STEP 1: BACKUP DATA LAMA

### 1.1 Login cPanel
1. Buka browser
2. Pergi ke: `https://cpaneldrc.ptsi.co.id:2083`
3. Login dengan:
   - Username: `demosapahcptsico`
   - Password: [password Anda]

### 1.2 Buka File Manager
1. Di cPanel homepage, cari section **"FILES"**
2. Klik **"File Manager"**
3. File Manager akan terbuka di tab baru

### 1.3 Navigate ke Home Directory
1. Di sidebar kiri, klik **"/home/demosapahcptsico"** (home icon)
2. Anda akan melihat folder-folder:
   - `.caldav`
   - `.cpanel`
   - `public_html` ← web root Anda
   - `talent-ptsi` ← folder Laravel LAMA
   - `tmp`
   - dll

### 1.4 Backup .env File
**SANGAT PENTING! File .env berisi config database & API keys!**

1. Klik folder **`talent-ptsi`** untuk masuk ke dalamnya
2. Cari file **`.env`** (mungkin hidden, klik "Settings" → centang "Show Hidden Files")
3. Klik kanan pada file **`.env`**
4. Pilih **"Copy"**
5. Paste ke folder **`/home/demosapahcptsico/`** (home directory)
6. Rename hasil copy menjadi: **`env-backup-20260129.txt`**

### 1.5 Backup Storage Uploads (Jika Ada)
Kalau ada file yang di-upload user (foto, dokumen):

1. Masuk ke **`talent-ptsi/storage/app/public/`**
2. Kalau ada folder/file penting (misal: `uploads/`, `documents/`)
3. Select folder tersebut → **Compress** → Format: **Zip Archive**
4. Nama: **`storage-backup-20260129.zip`**
5. Move file zip ke **`/home/demosapahcptsico/`**

### 1.6 Rename Folder Lama
1. Kembali ke **`/home/demosapahcptsico/`**
2. Klik kanan folder **`talent-ptsi`**
3. Pilih **"Rename"**
4. Rename menjadi: **`talent-ptsi-backup-20260129`**
5. Klik **"Rename File"**

**✅ CHECKLIST BACKUP:**
- [x] File `.env` sudah di-copy
- [x] Storage uploads sudah di-backup (jika ada)
- [x] Folder `talent-ptsi` sudah direname jadi backup

---

## 🔄 STEP 2: CLONE REPOSITORY

### 2.1 Buka Git Version Control
1. Di cPanel homepage, cari search box di atas
2. Ketik: **"Git"**
3. Klik **"Git™ Version Control"**

### 2.2 Create Repository
1. Klik button **"Create"** (warna biru, pojok kanan atas)
2. Form akan muncul

### 2.3 Isi Form Clone
Isi dengan TEPAT seperti ini:

```
┌─────────────────────────────────────────────────────┐
│ Clone a Repository                                   │
│                                                      │
│ Clone URL: *                                         │
│ https://github.com/HafizHadrimaulana/talent-ptsi.git│
│                                                      │
│ Repository Path: *                                   │
│ /home/demosapahcptsico/talent-ptsi                  │
│                                                      │
│ Repository Name:                                     │
│ talent-ptsi                                         │
│                                                      │
│                              [Create]                │
└─────────────────────────────────────────────────────┘
```

**Detail Setiap Field:**

- **Clone URL:** `https://github.com/HafizHadrimaulana/talent-ptsi.git`
  - Copy-paste PERSIS dari atas
  - Jangan ada spasi di awal/akhir
  
- **Repository Path:** `/home/demosapahcptsico/talent-ptsi`
  - HARUS dimulai dengan `/home/demosapahcptsico/`
  - Akhiran: `/talent-ptsi` (nama folder baru)
  
- **Repository Name:** `talent-ptsi`
  - Nama display aja, terserah

### 2.4 Klik Create & Tunggu
1. Klik button **"Create"**
2. Loading indicator akan muncul
3. **TUNGGU** sampai proses selesai (bisa 2-5 menit)
4. Kalau muncul pesan sukses: **"Repository cloned successfully"** → LANJUT!

**Kalau Ada Error:**
- "Repository already exists" → Folder talent-ptsi masih ada, back ke Step 1.6
- "Permission denied" → Path salah, cek lagi step 2.3
- "Could not resolve host" → Internet issue, coba lagi

**✅ CHECKLIST CLONE:**
- [x] Repository berhasil di-clone
- [x] Tidak ada error message
- [x] Folder `talent-ptsi` muncul di File Manager

---

## 🌿 STEP 3: SWITCH BRANCH KE PRODUCTION

### 3.1 Buka Manage Repository
1. Di halaman **Git™ Version Control**
2. Akan ada list repository
3. Cari row dengan **Repository Name: talent-ptsi**
4. Klik button **"Manage"** (di kolom Actions)

### 3.2 Pilih Branch Production
Halaman manage akan terbuka:

```
┌─────────────────────────────────────────────────────┐
│ Repository: talent-ptsi                              │
│                                                      │
│ Current Branch: [main ▼]                            │
│                                                      │
│ [Pull or Deploy] [Update from Remote]               │
└─────────────────────────────────────────────────────┘
```

1. Klik dropdown **"Current Branch"** (tulisan "main" dengan panah kebawah)
2. Pilih **"production"** dari dropdown
3. Dialog konfirmasi muncul → Klik **"Switch Branch"**

### 3.3 Pull Latest Changes
1. Setelah switch branch, klik button **"Update from Remote"**
2. Tunggu proses pull selesai
3. Pesan sukses: **"Repository updated successfully"**

**✅ CHECKLIST BRANCH:**
- [x] Branch switched ke production
- [x] Pull berhasil tanpa error
- [x] Current Branch sekarang: production

---

## 📝 STEP 4: SETUP ENVIRONMENT (.env)

### 4.1 Buka File Manager
1. Kembali ke **File Manager**
2. Navigate ke **`/home/demosapahcptsico/talent-ptsi/`**

### 4.2 Copy .env dari Backup
1. Klik **"Up One Level"** untuk ke `/home/demosapahcptsico/`
2. Cari file **`env-backup-20260129.txt`** (yang tadi di-backup)
3. Klik kanan → **"Copy"**
4. Navigate ke folder **`talent-ptsi`**
5. **"Paste"**
6. Klik kanan file yang baru di-paste → **"Rename"**
7. Rename menjadi: **`.env`** (PENTING: titik di depan!)

### 4.3 Edit .env untuk Production
1. Klik kanan file **`.env`**
2. Pilih **"Edit"**
3. Editor akan terbuka

**Update nilai-nilai ini:**

```env
# WAJIB DIUBAH:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo-sapahc.ptsi.co.id

# CEK KEMBALI database credentials:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=talent_ptsi_production
DB_USERNAME=demosapahcptsico_talent
DB_PASSWORD=[password database]

# Pastikan ini ada:
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

4. Klik **"Save Changes"** (pojok kanan atas)
5. Klik **"Close"**

**✅ CHECKLIST ENV:**
- [x] File `.env` sudah ada di folder talent-ptsi
- [x] APP_ENV=production
- [x] APP_DEBUG=false
- [x] APP_URL sesuai domain
- [x] Database credentials benar

---

## 🔐 STEP 5: SET PERMISSIONS

### 5.1 Set Permission Storage
1. Di File Manager, dalam folder **`talent-ptsi`**
2. Klik kanan folder **`storage`**
3. Pilih **"Change Permissions"**

Dialog akan muncul:

```
┌─────────────────────────────────────────────────────┐
│ Change Permissions: storage                          │
│                                                      │
│ User:  [x] Read  [x] Write  [x] Execute             │
│ Group: [x] Read  [x] Write  [x] Execute             │
│ World: [x] Read  [ ] Write  [x] Execute             │
│                                                      │
│ Numeric value: 775                                   │
│                                                      │
│ [x] Recurse into subdirectories                      │
│                                                      │
│                              [Change Permissions]    │
└─────────────────────────────────────────────────────┘
```

**Setting yang BENAR:**
- User: **Read + Write + Execute** (centang semua)
- Group: **Read + Write + Execute** (centang semua)
- World: **Read + Execute** (centang Read & Execute, JANGAN Write)
- **Numeric value harus: 775**
- **✅ CENTANG "Recurse into subdirectories"** ← INI PENTING!

4. Klik **"Change Permissions"**
5. Tunggu sampai selesai

### 5.2 Set Permission Bootstrap/Cache
1. Masuk ke folder **`bootstrap`**
2. Klik kanan folder **`cache`**
3. Pilih **"Change Permissions"**
4. Setting sama dengan storage: **775**
5. **✅ CENTANG "Recurse into subdirectories"**
6. Klik **"Change Permissions"**

### 5.3 Set Permission Storage/logs (777)
1. Masuk ke folder **`storage`**
2. Klik kanan folder **`logs`**
3. Pilih **"Change Permissions"**
4. Setting: **777** (centang SEMUA kotak)
5. **✅ CENTANG "Recurse into subdirectories"**
6. Klik **"Change Permissions"**

**✅ CHECKLIST PERMISSIONS:**
- [x] storage/ = 775 (recursive)
- [x] bootstrap/cache/ = 775 (recursive)
- [x] storage/logs/ = 777 (recursive)

---

## 🛠️ STEP 6: RUN SETUP SCRIPT

### 6.1 Cek File setup-cpanel.php
1. Di File Manager, dalam folder **`talent-ptsi`**
2. Cari file **`setup-cpanel.php`**
3. Kalau **ADA** → Lanjut ke 6.2
4. Kalau **TIDAK ADA** → Download dari project local, upload ke folder ini

### 6.2 Copy Setup Script ke public_html
1. Klik kanan **`setup-cpanel.php`**
2. Pilih **"Copy"**
3. Navigate ke **`/home/demosapahcptsico/public_html/`**
4. Paste di sini
5. File **`setup-cpanel.php`** sekarang ada di public_html

### 6.3 Run Setup via Browser
1. Buka browser **TAB BARU**
2. Pergi ke: **`https://demo-sapahc.ptsi.co.id/setup-cpanel.php`**
3. Script akan running otomatis

**Output yang Benar:**

```
=================================
LARAVEL SETUP SCRIPT
=================================

✓ .env file exists

📦 Generating application key...
   ✅ Success

📦 Creating storage link...
   ✅ Success

📦 Running database migrations...
   ✅ Success

📦 Caching configuration...
   ✅ Success

📦 Caching routes...
   ✅ Success

📦 Caching views...
   ✅ Success

📦 Optimizing application...
   ✅ Success

=================================
SETUP COMPLETED!
=================================

⚠️  IMPORTANT: DELETE THIS FILE NOW!
```

**Kalau Ada Error:**
- "Class not found" → Composer dependencies belum terinstall (hubungi admin)
- "Could not open .env" → File .env tidak ada atau permission salah
- "Connection refused" → Database credentials salah di .env

### 6.4 Delete Setup Script (PENTING!)
1. Kembali ke **File Manager**
2. Navigate ke **`public_html`**
3. Klik kanan **`setup-cpanel.php`**
4. Pilih **"Delete"**
5. Konfirmasi **"Confirm"**

**⚠️ WAJIB HAPUS!** File ini bisa dieksploit kalau tidak dihapus!

**✅ CHECKLIST SETUP:**
- [x] Setup script berhasil dijalankan
- [x] Semua tasks success (✅)
- [x] setup-cpanel.php sudah DIHAPUS

---

## 🧪 STEP 7: TESTING

### 7.1 Test Website
1. Buka browser tab baru
2. Pergi ke: **`https://demo-sapahc.ptsi.co.id`**

**Yang Harus Terlihat:**
- ✅ Website loading (tidak error 500)
- ✅ Login page tampil
- ✅ CSS/JS ter-load (tampilan normal)

**Kalau Error:**
- **500 Internal Server Error** → Cek .env, cek permissions, cek logs
- **404 Not Found** → index.php di public_html salah path
- **Blank page** → PHP error, cek error_log

### 7.2 Test Login
1. Login dengan user test
2. Navigate ke beberapa menu
3. Pastikan semua fungsi jalan

### 7.3 Cek Storage Link
Test upload file (kalau ada fitur upload):
1. Upload gambar/file
2. Pastikan file tersimpan
3. Pastikan file bisa diakses via URL

**✅ CHECKLIST TESTING:**
- [x] Website accessible
- [x] Login berhasil
- [x] Menu-menu berfungsi
- [x] Upload file works (kalau ada)

---

## 🔄 STEP 8: DEPLOYMENT BERIKUTNYA

Setelah setup awal, deploy berikutnya SANGAT MUDAH!

### 8.1 Dari Development (Local)
```bash
# 1. Merge changes ke main
git checkout main
git merge develop
git push origin main

# 2. Merge ke production
git checkout production
git merge main
git push origin production
```

### 8.2 Di cPanel
1. **Git Version Control** → **Manage** → **Update from Remote**
2. Done! ✨

**TIDAK PERLU:**
- ❌ Upload file manual
- ❌ Run setup script lagi
- ❌ Set permission lagi

**Yang PERLU (kadang-kadang):**
- Run migrations jika ada perubahan database
- Clear cache jika ada issue

---

## 🚨 TROUBLESHOOTING

### Error: "500 Internal Server Error"

**Penyebab & Solusi:**

1. **.env tidak ada atau salah**
   - Cek: File `.env` ada di `/home/demosapahcptsico/talent-ptsi/`
   - Cek: APP_KEY ada dan tidak kosong
   - Solusi: Copy dari backup atau run `php artisan key:generate`

2. **Permission salah**
   - Cek: storage/ = 775
   - Cek: bootstrap/cache/ = 775
   - Solusi: Set ulang permissions (Step 5)

3. **Database connection error**
   - Cek: Credentials di .env benar
   - Cek: Database exists di phpMyAdmin
   - Solusi: Fix credentials atau create database

### Error: "419 Page Expired" saat Login

**Penyebab:**
- Session driver issue
- APP_KEY tidak ter-generate

**Solusi:**
1. Cek .env: `SESSION_DRIVER=database`
2. Run: migrations table `sessions` harus ada
3. Clear browser cache & cookies

### Website Tampil Tapi CSS/JS Tidak Load

**Penyebab:**
- APP_URL salah di .env
- public/build/ tidak ada

**Solusi:**
1. Set APP_URL di .env: `https://demo-sapahc.ptsi.co.id`
2. Pastikan folder public/build/ ada dan berisi file
3. Clear cache browser (Ctrl+Shift+R)

### Error: "Storage link not found"

**Solusi:**
```
Di setup-cpanel.php atau manual:
php artisan storage:link
```

### Clone Gagal di Git Version Control

**Error: "Permission denied"**
- Path salah, harus: `/home/demosapahcptsico/talent-ptsi`

**Error: "Repository already exists"**
- Folder talent-ptsi masih ada, rename dulu

**Error: "Could not resolve host"**
- Internet issue atau GitHub down, coba lagi

---

## 📞 NEED HELP?

Kalau stuck atau ada error yang tidak bisa diselesaikan:

1. **Cek error logs:**
   - File Manager → `talent-ptsi/storage/logs/laravel.log`
   - Baca error message terakhir

2. **Restore backup:**
   - Rename `talent-ptsi-backup-20260129` kembali ke `talent-ptsi`
   - Website akan balik ke versi lama

3. **Contact team:**
   - Share screenshot error
   - Share isi `storage/logs/laravel.log`

---

## ✅ FINAL CHECKLIST

Setup dianggap SELESAI kalau:

- [x] Repository ter-clone di `/home/demosapahcptsico/talent-ptsi`
- [x] Branch: production
- [x] File .env exist & configured
- [x] Permissions set correctly
- [x] Setup script berhasil dijalankan
- [x] Setup script sudah DIHAPUS
- [x] Website accessible & functional
- [x] Login berhasil
- [x] Backup lama masih ada (untuk emergency)

---

## 🎉 SELAMAT!

Deployment berhasil! Website sekarang running dari Git repository.

**Next Deploy:** Tinggal git pull di cPanel!

---

**Last Updated:** 29 January 2026
**Version:** 1.0.0
