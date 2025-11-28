# ✅ CHECKLIST TRƯỚC KHI DEPLOY

## 🎯 Mục tiêu: Deploy lên hosting và bắt đầu cào dữ liệu

---

## 📋 CHECKLIST CƠ BẢN

### 1. ✅ Files Cần Thiết
- [x] `backend/` - Backend API
- [x] `frontend/` - Frontend React
- [x] `build-production.sh` - Build script
- [x] `DEPLOYMENT.md` - Hướng dẫn deploy
- [x] `README.md` - Tài liệu đầy đủ
- [x] `QUICKSTART.md` - Quick start guide
- [x] `.gitignore` - Git ignore
- [x] `package.json` - Root package

### 2. ✅ Backend Hoàn Chỉnh
- [x] Database schema với quality validation
- [x] Scraper với Ultra Deep Mode (200 results)
- [x] API endpoints đầy đủ
- [x] Export CSV/JSON
- [x] Quality report
- [x] Retry mechanism
- [x] Error handling

### 3. ✅ Frontend Hoàn Chỉnh
- [x] Business presets (9 categories)
- [x] Batch scraping
- [x] Ultra Deep Mode
- [x] Quality metrics display
- [x] Export buttons
- [x] Filters & search
- [x] Pagination

### 4. ✅ Production Features
- [x] Environment configs
- [x] Production build scripts
- [x] Static file serving
- [x] Health check endpoint
- [x] CORS configured
- [x] Error messages

---

## 🏗️ BUILD & DEPLOY

### Bước 1: Build Production

```bash
# Từ thư mục DONA/
./build-production.sh
```

**Expected Output:**
```
✅ Frontend built successfully
✅ Backend built successfully
✅ Production files in dist/
```

**Kiểm tra:**
- [ ] Folder `dist/` được tạo
- [ ] Folder `dist/backend/` có code compiled
- [ ] Folder `dist/frontend/` có static files
- [ ] Folder `dist/node_modules/` có dependencies
- [ ] File `dist/start.sh` được tạo

---

### Bước 2: Upload lên Hosting

**Option A: FTP/SFTP**
```bash
# Nén
tar -czf dist.tar.gz dist/

# Upload file dist.tar.gz lên hosting qua FTP
# Sau đó trên hosting:
tar -xzf dist.tar.gz
mv dist google-maps-scraper
```

**Option B: Git (Recommended)**
```bash
# Trên hosting
git clone <your-repo>
cd DONA
git checkout claude/google-maps-scraper-011JmFpYUPeT39Edsg51a5kR
chmod +x build-production.sh
./build-production.sh
cd dist
```

**Kiểm tra:**
- [ ] Files đã upload đầy đủ
- [ ] Permissions đúng (755 cho folders, 644 cho files)
- [ ] start.sh có quyền execute (chmod +x start.sh)

---

### Bước 3: Cài Đặt Dependencies

```bash
# Cài Node.js (nếu chưa có)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Cài Chromium (CHO PUPPETEER - RẤT QUAN TRỌNG!)
sudo apt-get update
sudo apt-get install -y chromium-browser

# Hoặc trên CentOS/RHEL
sudo yum install -y chromium

# Verify
chromium-browser --version  # Phải có output
node --version             # v18.x trở lên
npm --version              # 9.x trở lên
```

**Kiểm tra:**
- [ ] Node.js installed
- [ ] NPM installed
- [ ] Chromium installed ⚠️ QUAN TRỌNG!

---

### Bước 4: Cấu Hình Environment

```bash
cd dist
cp .env.example .env
nano .env
```

**File .env:**
```env
NODE_ENV=production
PORT=3000
DATABASE_PATH=/path/to/dist/data/scraper.db
```

**Kiểm tra:**
- [ ] PORT đúng (3000 hoặc theo hosting)
- [ ] DATABASE_PATH absolute path

---

### Bước 5: Start Application

**Test Run Trước:**
```bash
cd dist
./start.sh
```

**Expected Output:**
```
🚀 Server is running on http://localhost:3000
📊 API: http://localhost:3000/api/places
🌍 Environment: production
📱 Frontend: http://localhost:3000
```

**Kiểm tra:**
- [ ] Server chạy không lỗi
- [ ] Truy cập được http://localhost:3000
- [ ] API health: http://localhost:3000/health returns OK

**Nếu OK → Dừng server (Ctrl+C) và setup PM2**

---

### Bước 6: Setup PM2 (Auto Restart)

```bash
# Cài PM2
npm install -g pm2

# Start với PM2
cd dist/backend
pm2 start dist/server.js --name google-maps-scraper

# Save config
pm2 save

# Auto start on reboot
pm2 startup
# Copy paste command output và chạy

# Check status
pm2 status
```

**Expected Output:**
```
┌────┬─────────────────────┬─────────┬─────────┐
│ id │ name                │ status  │ cpu     │
├────┼─────────────────────┼─────────┼─────────┤
│ 0  │ google-maps-scraper │ online  │ 0%      │
└────┴─────────────────────┴─────────┴─────────┘
```

**Kiểm tra:**
- [ ] PM2 status = online
- [ ] CPU < 10% khi idle
- [ ] Memory < 200MB khi idle

---

### Bước 7: Test Scraping

**Test 1: Simple Scrape**
```bash
curl -X POST http://localhost:3000/api/places/scrape \
  -H "Content-Type: application/json" \
  -d '{
    "keyword": "coffee",
    "city": "Hanoi",
    "maxResults": 5
  }'
```

**Expected:**
```json
{
  "success": true,
  "data": {
    "scraped": 5,
    "saved": 5,
    "quality": {
      "avgQuality": 75,
      "phoneRate": 80,
      "addressRate": 100,
      "coordsRate": 100
    }
  }
}
```

**Kiểm tra:**
- [ ] Response success: true
- [ ] Scraped > 0
- [ ] Quality metrics hiển thị

**Test 2: Check Data**
```bash
curl http://localhost:3000/api/places
```

**Expected:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 5,
    ...
  }
}
```

**Kiểm tra:**
- [ ] Data đã lưu vào database
- [ ] Quality score được tính
- [ ] Có phone, address, coords

---

### Bước 8: Setup Domain/Nginx (Optional)

**Nếu có domain:**

```bash
# Cài Nginx
sudo apt-get install nginx

# Config
sudo nano /etc/nginx/sites-available/scraper
```

**Nginx Config:**
```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

```bash
# Enable
sudo ln -s /etc/nginx/sites-available/scraper /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

**Kiểm tra:**
- [ ] Nginx running
- [ ] Domain trỏ về server
- [ ] Truy cập được qua domain

---

## 🧪 FUNCTIONAL TEST

### Test Checklist:

**1. UI Access:**
- [ ] Truy cập được giao diện web
- [ ] Form hiển thị đầy đủ
- [ ] Dropdown presets hoạt động

**2. Scraping:**
- [ ] Normal scrape (50 results) hoạt động
- [ ] Ultra Deep (200 results) hoạt động
- [ ] Batch scraping hoạt động
- [ ] Quality metrics hiển thị đúng

**3. Data Display:**
- [ ] Bảng hiển thị dữ liệu
- [ ] Filters hoạt động
- [ ] Search hoạt động
- [ ] Pagination hoạt động

**4. Export:**
- [ ] Export CSV download được
- [ ] Export JSON download được
- [ ] Dữ liệu trong file đúng

**5. Quality:**
- [ ] Quality score được tính
- [ ] Warning khi quality thấp
- [ ] Sort by quality hoạt động

---

## 🎯 PRODUCTION SCRAPING TEST

### Test Case: Cào Hàng Quán Quận 1

```
Settings:
- Preset: 🍜 Hàng quán
- Location: Quận 1, Hồ Chí Minh
- Ultra Deep: ✅
- Expected time: ~60-80 phút
- Expected results: 800-1400 địa điểm
```

**Checklist:**
- [ ] Start scraping thành công
- [ ] Progress tracking hiển thị
- [ ] Không bị timeout/crash
- [ ] Quality > 70/100
- [ ] Data lưu vào database
- [ ] Export CSV thành công

---

## ⚠️ TROUBLESHOOTING

### Lỗi Thường Gặp:

**1. Puppeteer không chạy**
```
Error: Failed to launch browser
```
**Fix:**
```bash
sudo apt-get install -y chromium-browser
# Hoặc cài thêm dependencies
sudo apt-get install -y libnss3 libatk1.0-0 libatk-bridge2.0-0
```

**2. Port đã dùng**
```
Error: Port 3000 already in use
```
**Fix:**
```bash
# Kill process
sudo lsof -ti:3000 | xargs kill -9
# Hoặc đổi port trong .env
```

**3. Database locked**
```
Error: Database is locked
```
**Fix:**
```bash
pm2 stop google-maps-scraper
rm data/scraper.db-wal data/scraper.db-shm
pm2 restart google-maps-scraper
```

**4. Out of memory**
```
Error: JavaScript heap out of memory
```
**Fix:**
```bash
# Tăng swap
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

---

## 📊 MONITORING

### Check Logs:
```bash
# PM2 logs
pm2 logs google-maps-scraper

# Recent errors
pm2 logs google-maps-scraper --err

# Real-time
pm2 logs google-maps-scraper --lines 100
```

### Check Resources:
```bash
# PM2 monitoring
pm2 monit

# System resources
htop
```

### Check Database:
```bash
# Database size
ls -lh data/scraper.db

# Record count
sqlite3 data/scraper.db "SELECT COUNT(*) FROM places"

# Quality report
curl http://localhost:3000/api/places/quality
```

---

## ✅ FINAL CHECKLIST

Trước khi bắt đầu cào production:

- [ ] ✅ Server online và stable
- [ ] ✅ PM2 auto-restart configured
- [ ] ✅ Chromium installed
- [ ] ✅ Test scrape thành công
- [ ] ✅ Quality validation hoạt động
- [ ] ✅ Export CSV/JSON hoạt động
- [ ] ✅ Database backup setup (optional)
- [ ] ✅ Monitoring setup
- [ ] ✅ Firewall configured (ports 22, 80, 443, 3000)

---

## 🚀 BẮT ĐẦU CÀO

Khi tất cả ✅ → Bắt đầu cào dữ liệu:

1. Truy cập: `http://your-server:3000`
2. Chọn preset hoặc nhập keyword
3. Nhập vị trí (Quận/Tỉnh)
4. Tích Ultra Deep nếu cần cào sát
5. Click "Bắt đầu cào"
6. Đợi... ☕
7. Export CSV/JSON
8. Done! 🎉

---

**Good luck with your deployment! 🚀**
