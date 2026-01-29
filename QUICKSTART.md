# 🚀 QUICK START - Auto Deploy

## 📋 Setup (Sekali Aja - 5 Menit!)

### 1️⃣ Generate Token
```bash
bash generate-token.sh
# Copy output token
```

### 2️⃣ Setup cPanel
1. Edit `deploy-auto.php` (line 21):
   ```php
   define('SECRET_TOKEN', 'PASTE_TOKEN_HERE');
   ```
2. Upload ke `public_html/deploy-auto.php`
3. Test: `https://demo-sapahc.ptsi.co.id/deploy-auto.php?token=YOUR_TOKEN`

### 3️⃣ Setup GitHub
**Settings** → **Secrets and variables** → **Actions** → **New secret**

Add 2 secrets:
- `DEPLOY_URL` = `https://demo-sapahc.ptsi.co.id/deploy-auto.php`
- `DEPLOY_TOKEN` = `YOUR_TOKEN` (same as step 1)

### 4️⃣ Test
```bash
git push origin main:production
```
Check: GitHub → Actions tab → Should see deployment running ✅

---

## 🎯 Daily Workflow

```bash
# 1. Coding...
npm run dev

# 2. Build production assets (WAJIB!)
npm run build

# 3. Commit & push
git add .
git commit -m "Your changes"
git push origin main:production

# 🎉 DONE! Auto-deploy in ~30 seconds
```

---

## ✅ Checklist

- [ ] Token generated & saved
- [ ] deploy-auto.php uploaded to cPanel
- [ ] GitHub Secrets configured
- [ ] Test push successful
- [ ] Website updated

---

## 🆘 Troubleshooting

**CSS/JS tidak update?**
→ Lupa `npm run build` sebelum push!

**GitHub Actions failed?**
→ Check token di GitHub Secrets

**Manual deploy:**
→ `https://demo-sapahc.ptsi.co.id/deploy-auto.php?token=YOUR_TOKEN`

---

**Full Guide:** [AUTOMATION-SETUP.md](./AUTOMATION-SETUP.md)
