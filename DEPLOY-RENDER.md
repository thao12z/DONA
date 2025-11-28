# 🚀 Deploy lên Render.com (Miễn phí)

## ✅ Ưu điểm:
- **Miễn phí** hoàn toàn (có giới hạn)
- Hỗ trợ Node.js + Puppeteer
- Deploy tự động từ Git
- SSL miễn phí
- Subdomain miễn phí: `your-app.onrender.com`

## ⚠️ Giới hạn (gói Free):
- RAM: 512MB (đủ cho scraping nhỏ, ~20-50 kết quả/lần)
- App sleep sau 15 phút không dùng
- Build time: 90 giây
- Bandwidth: 100GB/tháng

---

## 📋 Bước 1: Chuẩn bị repository

### 1.1. Tạo file `render.yaml`

```bash
cd /home/user/DONA
nano render.yaml
```

Nhập nội dung:

```yaml
services:
  - type: web
    name: google-maps-scraper
    env: node
    region: singapore
    buildCommand: |
      cd backend && npm install
      cd ../frontend && npm install && npm run build
      cd ../backend && npm run build
    startCommand: cd backend && NODE_ENV=production node dist/server.js
    envVars:
      - key: NODE_ENV
        value: production
      - key: PORT
        value: 10000
      - key: DATABASE_PATH
        value: /opt/render/project/src/data/scraper.db
      - key: PUPPETEER_SKIP_CHROMIUM_DOWNLOAD
        value: false
    disk:
      name: data
      mountPath: /opt/render/project/src/data
      sizeGB: 1
```

### 1.2. Cập nhật `.gitignore`

Đảm bảo các file này KHÔNG bị ignore:

```bash
# .gitignore should NOT include:
# dist/
# build/

# But SHOULD include:
node_modules/
.env
*.db
.DS_Store
```

### 1.3. Commit và push

```bash
git add render.yaml
git commit -m "feat: Add Render deployment config"
git push origin claude/google-maps-scraper-011JmFpYUPeT39Edsg51a5kR
```

---

## 📋 Bước 2: Đăng ký Render

1. Truy cập: https://render.com
2. Click **Sign Up**
3. Chọn **Sign up with GitHub**
4. Authorize Render truy cập GitHub của bạn

---

## 📋 Bước 3: Deploy từ Git

### 3.1. Tạo Web Service

1. Click **New +** → **Blueprint**
2. Chọn repository: `DONA`
3. Branch: `claude/google-maps-scraper-011JmFpYUPeT39Edsg51a5kR`
4. Click **Apply**

### 3.2. Cấu hình (nếu không dùng render.yaml)

Nếu không dùng render.yaml, cấu hình thủ công:

```
Name: google-maps-scraper
Region: Singapore (gần VN nhất)
Branch: claude/google-maps-scraper-011JmFpYUPeT39Edsg51a5kR
Root Directory: (để trống)

Build Command:
cd backend && npm install && npm run build && cd ../frontend && npm install && npm run build

Start Command:
cd backend && node dist/server.js

Environment Variables:
- NODE_ENV = production
- PORT = 10000
- DATABASE_PATH = /opt/render/project/src/data/scraper.db
- PUPPETEER_SKIP_CHROMIUM_DOWNLOAD = false

Plan: Free
```

### 3.3. Thêm Persistent Disk (cho database)

1. Scroll xuống **Disk**
2. Click **Add Disk**
3. Name: `data`
4. Mount Path: `/opt/render/project/src/data`
5. Size: 1GB

---

## 📋 Bước 4: Deploy!

1. Click **Create Web Service**
2. Chờ deploy (5-10 phút lần đầu)
3. Xem logs real-time
4. Khi thấy "✅ Server is running..." → Deploy thành công!

---

## 📋 Bước 5: Truy cập ứng dụng

URL của bạn: `https://google-maps-scraper-xxx.onrender.com`

### Test scraping:
1. Mở web
2. Chọn preset nhỏ: **💊 Nhà thuốc** (4 keywords)
3. Location: **Quận 1, Hồ Chí Minh**
4. **KHÔNG BẬT** Ultra Deep Mode (gói Free RAM ít)
5. Max Results: **20** (đừng quá 50)
6. Click **Bắt đầu cào**

---

## ⚙️ Bước 6: Cấu hình tự động deploy

**Auto-deploy khi push code:**
- Render tự động deploy lại khi bạn push lên branch

**Giữ app không sleep:**
- Dùng UptimeRobot (miễn phí) để ping app mỗi 5 phút
- Hoặc nâng cấp lên Render paid ($7/tháng)

---

## 🐛 Troubleshooting

### Lỗi: "Out of memory" khi scraping

**Giải pháp:**
1. Giảm `maxResults` xuống 20-30
2. KHÔNG dùng Ultra Deep Mode
3. KHÔNG dùng batch mode
4. Hoặc nâng cấp lên Render Starter ($7/tháng, 512MB → 2GB RAM)

### App sleep sau 15 phút

**Miễn phí:**
- Dùng UptimeRobot để ping: https://uptimerobot.com
- Monitor: `https://your-app.onrender.com/health`
- Interval: 5 phút

**Trả phí:**
- Nâng cấp lên Starter ($7/tháng) → Không sleep

### Build failed

```bash
# Check logs trong Render dashboard
# Thường do:
1. Missing dependencies
2. TypeScript errors
3. Port conflicts

# Fix: Push code mới và Render tự deploy lại
```

---

## 💰 So sánh chi phí

| Dịch vụ | Giá | RAM | Scraping | Notes |
|---------|-----|-----|----------|-------|
| **Render Free** | $0 | 512MB | 20-50/lần | App sleep sau 15 phút |
| **Render Starter** | $7/tháng | 2GB | 100-200/lần | Không sleep, tốt hơn |
| **AZdigi VPS 1** | ~150k/tháng | 2GB | 200+/lần | Full control |
| **AZdigi VPS 2** | ~250k/tháng | 4GB | Unlimited | Ultra Deep Mode OK |

---

## 🎯 Khuyến nghị

**Nếu chỉ test/dùng thử:**
→ Dùng **Render Free**

**Nếu scraping thường xuyên (1-5 lần/ngày):**
→ Dùng **Render Starter** ($7/tháng)

**Nếu scraping nhiều + batch mode + Ultra Deep:**
→ Dùng **AZdigi VPS 2** (250k/tháng)

---

## 📚 Tài liệu Render

- Docs: https://render.com/docs
- Puppeteer guide: https://render.com/docs/puppeteer
- Support: support@render.com
