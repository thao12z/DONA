# 🔀 Deploy Hybrid: cPanel + Render/Railway

## 💡 Ý tưởng

**Vấn đề:** Bạn có hosting cPanel nhưng không chạy được Node.js + Puppeteer

**Giải pháp:** Tách ứng dụng thành 2 phần:
- **Frontend (giao diện)** → Upload lên cPanel qua FTP
- **Backend (API scraper)** → Deploy lên Render/Railway miễn phí

→ Frontend gọi API từ backend qua internet

---

## 📊 So sánh với các phương án khác

| Phương án | Frontend | Backend | Chi phí | Độ khó |
|-----------|----------|---------|---------|--------|
| **VPS** | VPS | VPS | 150-250k/tháng | Trung bình |
| **Render** | Render | Render | $0 | Dễ |
| **Railway** | Railway | Railway | $0-5/tháng | Dễ |
| **Hybrid (cPanel + Render)** | cPanel | Render | $0 | Dễ ⭐ |

**Ưu điểm Hybrid:**
- ✅ Tận dụng hosting cPanel đã mua
- ✅ Backend miễn phí (Render)
- ✅ Frontend load nhanh (cPanel VN)
- ✅ Dễ setup, không cần Linux

---

## 🎯 Kiến trúc

```
┌─────────────────────────────────────────────┐
│  User Browser                               │
│  https://yourdomain.com (cPanel)           │
└─────────────┬───────────────────────────────┘
              │
              │ Gọi API
              ▼
┌─────────────────────────────────────────────┐
│  Backend API                                │
│  https://yourapp.onrender.com (Render)     │
│  - Scraper (Puppeteer)                     │
│  - Database (SQLite)                       │
└─────────────────────────────────────────────┘
```

---

## 📋 BƯỚC 1: Deploy Backend lên Render

### 1.1. Đăng ký Render

1. Truy cập: https://render.com
2. **Sign up with GitHub**
3. Authorize Render

### 1.2. Deploy từ Git

1. **New +** → **Web Service**
2. Chọn repo: `DONA`
3. Branch: `claude/google-maps-scraper-011JmFpYUPeT39Edsg51a5kR`
4. **Name:** `google-maps-scraper-api`
5. **Region:** Singapore
6. **Root Directory:** để trống
7. **Build Command:**
```bash
cd backend && npm install && npm run build
```
8. **Start Command:**
```bash
cd backend && node dist/server.js
```

### 1.3. Cấu hình Environment Variables

Thêm các biến:
```
NODE_ENV=production
PORT=10000
DATABASE_PATH=/opt/render/project/src/data/scraper.db
PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=false
```

### 1.4. Thêm Persistent Disk

1. Scroll xuống **Disk**
2. **Add Disk**
   - Name: `data`
   - Mount Path: `/opt/render/project/src/data`
   - Size: 1GB

### 1.5. Deploy!

1. Click **Create Web Service**
2. Chờ 5-10 phút
3. Copy URL của bạn: `https://google-maps-scraper-api-xxx.onrender.com`

### 1.6. Test API

```bash
# Test trong browser hoặc curl
https://google-maps-scraper-api-xxx.onrender.com/health

# Kết quả:
{"status":"ok","timestamp":"2024-11-28T...","env":"production"}
```

---

## 📋 BƯỚC 2: Build Frontend với API URL

### 2.1. Cập nhật API URL trong frontend

```bash
cd /home/user/DONA
```

Tạo file mới: `frontend/.env.production`

```env
VITE_API_URL=https://google-maps-scraper-api-xxx.onrender.com
```

**Thay `xxx` bằng URL Render của bạn!**

### 2.2. Cập nhật API client

Sửa file `frontend/src/api/places.ts`:

```typescript
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3000';

const api = axios.create({
  baseURL: `${API_BASE_URL}/api`,
  timeout: 300000, // 5 minutes for scraping
  headers: {
    'Content-Type': 'application/json'
  }
});
```

### 2.3. Build frontend

```bash
cd frontend

# Build production
npm run build
```

Kết quả: Thư mục `frontend/dist/` chứa file HTML/CSS/JS

---

## 📋 BƯỚC 3: Upload Frontend lên cPanel

### 3.1. Kết nối cPanel

1. Đăng nhập **cPanel AZdigi** (yourdomain.com/cpanel)
2. Username/Password từ email AZdigi

### 3.2. Upload qua File Manager

1. Trong cPanel → **File Manager**
2. Navigate to `public_html/` (hoặc `public_html/scraper/` nếu muốn subfolder)
3. Click **Upload**
4. Chọn TẤT CẢ file trong `frontend/dist/`:
   - `index.html`
   - `assets/` folder
   - `vite.svg` (nếu có)

### 3.3. Hoặc upload qua FTP

**Dùng FileZilla:**
```
Host: ftp.yourdomain.com (hoặc IP từ cPanel)
Username: cpanel_username
Password: cpanel_password
Port: 21
```

Upload toàn bộ nội dung `frontend/dist/` vào `public_html/`

---

## 📋 BƯỚC 4: Cấu hình CORS trên Backend

**Quan trọng!** Backend phải cho phép frontend gọi API từ domain khác.

### 4.1. Update CORS trong backend

File đã có sẵn CORS, nhưng cần thêm domain của bạn:

Vào Render Dashboard → **Environment** tab → Thêm biến:

```
CORS_ORIGIN=https://yourdomain.com
```

Hoặc cho phép tất cả (test):
```
CORS_ORIGIN=*
```

### 4.2. Kiểm tra backend/src/server.ts

File này đã có:
```typescript
app.use(cors()); // Allow all origins
```

Nếu muốn giới hạn domain:
```typescript
app.use(cors({
  origin: process.env.CORS_ORIGIN || '*'
}));
```

### 4.3. Redeploy backend

Sau khi thêm biến CORS, Render tự động redeploy.

---

## 📋 BƯỚC 5: Test toàn bộ hệ thống

### 5.1. Truy cập frontend

Mở browser: `https://yourdomain.com`

### 5.2. Test scraping

1. **Keyword:** `nhà thuốc`
2. **Location:** `Quận 1, Hồ Chí Minh`
3. **Max Results:** `20` (test nhỏ trước)
4. Click **Bắt đầu cào**

### 5.3. Kiểm tra DevTools

**Nếu lỗi CORS:**
```
Access to XMLHttpRequest at 'https://...onrender.com' from origin
'https://yourdomain.com' has been blocked by CORS policy
```

→ Quay lại Bước 4, cấu hình CORS đúng

**Nếu OK:**
- Request thành công
- Hiển thị kết quả scraping
- Có thể export CSV/JSON

---

## 🎯 BƯỚC 6: Tối ưu

### 6.1. Cache frontend trên cPanel

Trong cPanel → **Speed** → **Optimize Website** → **Compress All Content**

### 6.2. Enable Gzip

Tạo file `.htaccess` trong `public_html/`:

```apache
# Enable Gzip
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### 6.3. CDN (optional)

Nếu muốn nhanh hơn, dùng Cloudflare CDN (miễn phí):
1. Đăng ký Cloudflare
2. Add domain
3. Change nameservers theo hướng dẫn
4. Enable "Auto Minify" + "Brotli"

---

## 🔄 Quy trình update code

### Khi sửa backend:

```bash
# Push code lên Git
git push

# Render tự động deploy lại
# Chờ 3-5 phút
```

### Khi sửa frontend:

```bash
# Build lại
cd frontend
npm run build

# Upload lại file trong dist/ lên cPanel
# Qua FTP hoặc File Manager
```

---

## 📊 Chi phí thực tế

| Hạng mục | Chi phí | Ghi chú |
|----------|---------|---------|
| **cPanel hosting** | Đã mua sẵn | Dùng cho frontend |
| **Render backend** | $0 | 512MB RAM, sleep 15 phút |
| **Domain** | Đã có | Từ hosting |
| **SSL** | $0 | cPanel Let's Encrypt + Render SSL |
| **TỔNG** | **$0** | Hoàn toàn miễn phí! |

---

## ⚠️ Giới hạn cần biết

### Render Free tier:
- ⏰ Backend sleep sau 15 phút
  - Lần đầu truy cập chờ ~30 giây wake up
  - Sau đó chạy bình thường
- 💾 RAM 512MB → Scrape 20-50 kết quả/lần
- ❌ Không dùng Ultra Deep Mode

### Giải pháp:

**Để không sleep:**
- Option 1: Dùng UptimeRobot ping backend mỗi 5 phút (free)
- Option 2: Nâng cấp Render Starter ($7/tháng)
- Option 3: Chuyển backend sang Railway (sleep ít hơn)

**Để scrape nhiều hơn:**
- Option 1: Nâng cấp Render Starter ($7/tháng, 2GB RAM)
- Option 2: Chuyển sang Railway ($5 credit, 8GB RAM)
- Option 3: Chuyển toàn bộ sang VPS

---

## 🐛 Troubleshooting

### Lỗi: CORS blocked

**Nguyên nhân:** Backend không cho phép domain của bạn

**Fix:**
```bash
# Trong Render → Environment Variables
CORS_ORIGIN=https://yourdomain.com

# Hoặc cho phép tất cả (test only)
CORS_ORIGIN=*
```

### Lỗi: API timeout

**Nguyên nhân:** Backend sleep, cần wake up

**Fix:**
- Chờ 30 giây và thử lại
- Hoặc dùng UptimeRobot để keep alive

### Lỗi: Cannot connect to API

**Nguyên nhân:** Sai URL trong frontend

**Fix:**
```bash
# Kiểm tra file frontend/.env.production
VITE_API_URL=https://ĐÚNG-URL-CỦA-BẠN.onrender.com

# Build lại và upload
cd frontend
npm run build
# Upload dist/ lên cPanel
```

### Frontend trắng xóa không hiện gì

**Nguyên nhân:** Upload sai thư mục

**Fix:**
- Upload NỘI DUNG của `dist/`, KHÔNG upload folder `dist/`
- Nghĩa là: `index.html` và `assets/` phải nằm trực tiếp trong `public_html/`

### Scraping chậm hoặc crash

**Nguyên nhân:** Render Free RAM ít

**Fix:**
- Giảm `maxResults` xuống 20-30
- KHÔNG dùng Ultra Deep Mode
- KHÔNG dùng Batch Mode
- Hoặc nâng cấp Render/Railway

---

## 🎯 Khi nào dùng Hybrid?

✅ **Nên dùng khi:**
- Đã mua hosting cPanel, không muốn bỏ phí
- Scraping ít đến vừa (5-20 lần/ngày)
- Chấp nhận backend sleep (hoặc dùng UptimeRobot)
- Muốn domain riêng từ hosting

❌ **KHÔNG nên dùng khi:**
- Scraping nhiều (50+ lần/ngày)
- Cần Ultra Deep Mode
- Cần hiệu năng cao, không chờ wake up
- Cần professional (khách hàng không chờ được 30s)

→ **Lúc đó nên dùng VPS hoặc Railway paid**

---

## 💡 Nâng cấp sau này

### Level 1: Hiện tại (FREE)
```
Frontend: cPanel
Backend: Render Free (512MB)
→ Chi phí: $0
```

### Level 2: Backend tốt hơn ($7/tháng)
```
Frontend: cPanel
Backend: Render Starter (2GB, no sleep)
→ Chi phí: $7/tháng
```

### Level 3: Backend mạnh hơn ($10/tháng)
```
Frontend: cPanel
Backend: Railway Hobby (8GB, no limit)
→ Chi phí: ~$10/tháng
```

### Level 4: Production (250k/tháng)
```
Frontend + Backend: AZdigi VPS 2
→ Chi phí: 250k/tháng
→ Full control, unlimited
```

---

## 📚 Tham khảo thêm

- Render Docs: https://render.com/docs
- Railway Docs: https://docs.railway.app
- cPanel Docs: https://docs.cpanel.net
- UptimeRobot: https://uptimerobot.com (keep backend awake)

---

## ✅ Checklist hoàn thành

- [ ] Deploy backend lên Render thành công
- [ ] Test API `/health` trả về OK
- [ ] Tạo file `.env.production` với API URL đúng
- [ ] Build frontend thành công (có thư mục `dist/`)
- [ ] Upload frontend lên cPanel qua FTP/File Manager
- [ ] Cấu hình CORS cho phép domain của bạn
- [ ] Truy cập `https://yourdomain.com` thấy giao diện
- [ ] Test scraping thành công (ít nhất 1 keyword)
- [ ] Export CSV/JSON hoạt động
- [ ] (Optional) Setup UptimeRobot để backend không sleep

---

**Chúc bạn deploy thành công!** 🚀

Nếu gặp khó khăn, hỏi tôi nhé!
