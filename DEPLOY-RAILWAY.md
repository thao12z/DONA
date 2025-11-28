# 🚀 Deploy lên Railway.app (Miễn phí $5/tháng)

## ✅ Ưu điểm:
- **$5 credit miễn phí** mỗi tháng (không cần thẻ)
- Hỗ trợ Node.js + Puppeteer tốt
- Deploy siêu nhanh từ Git
- SSL + domain miễn phí
- Không sleep
- Dashboard đẹp, dễ dùng

## ⚠️ Giới hạn (gói Free):
- $5 credit/tháng = ~500 giờ chạy
- RAM: 8GB (rất đủ!)
- Nếu hết credit → app tắt đến tháng sau

---

## 📋 Bước 1: Chuẩn bị code

### 1.1. Tạo file `railway.json` (optional)

```bash
cd /home/user/DONA
nano railway.json
```

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "cd backend && node dist/server.js",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### 1.2. Cập nhật `backend/package.json`

Thêm script `postinstall`:

```json
{
  "scripts": {
    "dev": "tsx watch src/server.ts",
    "build": "tsc",
    "start": "node dist/server.js",
    "postinstall": "npm run build"
  }
}
```

### 1.3. Tạo file `Procfile`

```bash
echo "web: cd backend && node dist/server.js" > Procfile
```

---

## 📋 Bước 2: Đăng ký Railway

1. Truy cập: https://railway.app
2. Click **Login**
3. Chọn **Login with GitHub**
4. Authorize Railway

---

## 📋 Bước 3: Deploy từ Git

### 3.1. Tạo Project mới

1. Click **New Project**
2. Chọn **Deploy from GitHub repo**
3. Chọn repository: `DONA`
4. Railway sẽ tự detect Node.js

### 3.2. Cấu hình Environment Variables

1. Vào **Variables** tab
2. Thêm các biến:

```
NODE_ENV=production
PORT=3000
DATABASE_PATH=/app/data/scraper.db
PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=false
```

### 3.3. Cấu hình Build

1. Vào **Settings** tab
2. **Root Directory**: để trống
3. **Build Command**: (để Railway auto-detect)
```bash
cd backend && npm install && npm run build && cd ../frontend && npm install && npm run build
```
4. **Start Command**:
```bash
cd backend && node dist/server.js
```

### 3.4. Thêm Volume (cho database)

1. Vào project
2. Click **+ New** → **Volume**
3. Mount Path: `/app/data`
4. Size: 1GB

---

## 📋 Bước 4: Deploy!

1. Click **Deploy**
2. Railway tự động:
   - Clone code
   - Install dependencies
   - Build
   - Start server
3. Chờ 3-5 phút
4. Khi thấy "Deployed" → Thành công!

---

## 📋 Bước 5: Lấy URL

1. Vào **Settings** tab
2. Scroll xuống **Domains**
3. Click **Generate Domain**
4. Nhận URL: `https://your-app.up.railway.app`

### Hoặc dùng custom domain:

1. Vào **Settings** → **Domains**
2. Click **Custom Domain**
3. Nhập domain của bạn
4. Cấu hình DNS theo hướng dẫn

---

## 📋 Bước 6: Test scraping

1. Mở `https://your-app.up.railway.app`
2. Test với preset nhỏ trước:
   - **💊 Nhà thuốc** (4 keywords)
   - Location: **Quận 1, Hồ Chí Minh**
   - Max Results: **50**
3. Sau khi OK, test Ultra Deep Mode

---

## 📊 Monitor Usage

### Xem credit còn lại:

1. Click vào avatar (góc trên phải)
2. Chọn **Usage**
3. Xem: **$X.XX / $5.00 used**

### Ước tính chi phí:

- App chạy 24/7: ~$0.20/ngày = ~$6/tháng
- App chỉ chạy khi cần (12h/ngày): ~$0.10/ngày = ~$3/tháng

**Mẹo tiết kiệm:** Tắt app khi không dùng
```bash
# Trong Railway dashboard → Settings → Sleep
```

---

## 🔄 Auto-Deploy

Mỗi lần push code lên GitHub:
→ Railway tự động build + deploy lại

**Tắt auto-deploy:**
1. Settings → **Deployments**
2. Tắt **Auto Deploy**

---

## 🐛 Troubleshooting

### Build failed

1. Xem logs: **Deployments** → Click vào deployment failed
2. Thường do:
   - TypeScript errors
   - Missing dependencies
   - Wrong build command

**Fix:** Sửa code và push lại

### App crashed

1. Xem logs: **Deployments** → **View Logs**
2. Thường do:
   - Port không đúng (phải dùng `process.env.PORT`)
   - Chromium không tìm thấy
   - Out of memory

### Database bị mất sau mỗi deploy

**Nguyên nhân:** Chưa mount volume

**Fix:**
1. Tạo Volume (bước 3.4)
2. Redeploy

---

## 💰 Nâng cấp (nếu hết $5 credit)

### Railway Pro: $20/tháng
- $20 credit + $5 miễn phí = $25 total
- Unlimited projects
- Priority support

### Pay-as-you-go:
- Nạp $10, $20, $50...
- Chỉ trừ khi dùng

---

## 🎯 Khi nào dùng Railway?

✅ **Dùng Railway nếu:**
- Scraping vừa phải (5-20 lần/ngày)
- Cần app chạy 24/7 không sleep
- Có $5 credit là đủ mỗi tháng
- Thích UI đẹp, deploy nhanh

❌ **KHÔNG dùng Railway nếu:**
- Scraping rất nhiều (>50 lần/ngày)
- Cần Ultra Deep Mode liên tục
- Không muốn giới hạn credit

→ **Lúc đó dùng VPS AZdigi tốt hơn**

---

## 📚 Tài liệu Railway

- Docs: https://docs.railway.app
- Node.js guide: https://docs.railway.app/guides/nodejs
- Discord: https://discord.gg/railway
