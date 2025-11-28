# 🚀 Hướng dẫn Deploy lên AZdigi VPS

## Bước 1: Kết nối SSH vào VPS

### Lấy thông tin SSH từ AZdigi:
1. Đăng nhập vào **My AZdigi** (my.azdigi.com)
2. Vào **Services → My Services**
3. Click vào VPS của bạn
4. Lấy thông tin:
   - **IP Address**: VD: 103.x.x.x
   - **Username**: root
   - **Password**: (đã gửi qua email khi mua)

### Kết nối SSH:

**Trên Windows (dùng PuTTY hoặc CMD):**
```bash
ssh root@103.x.x.x
# Nhập password khi được hỏi
```

**Trên Mac/Linux:**
```bash
ssh root@103.x.x.x
# Nhập password
```

---

## Bước 2: Cài đặt môi trường (chỉ làm 1 lần)

```bash
# Update hệ thống
sudo apt-get update
sudo apt-get upgrade -y

# Cài Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Kiểm tra version
node -v    # Phải >= v18.x
npm -v

# Cài Git
sudo apt-get install -y git

# Cài Chromium (QUAN TRỌNG cho scraper!)
sudo apt-get install -y chromium-browser chromium-codecs-ffmpeg-extra

# Cài PM2 (quản lý ứng dụng chạy nền)
sudo npm install -g pm2

# Cài Nginx (web server)
sudo apt-get install -y nginx
```

---

## Bước 3: Clone project từ Git

```bash
# Tạo thư mục cho ứng dụng
cd /var/www
sudo mkdir -p google-maps-scraper
sudo chown -R $USER:$USER google-maps-scraper
cd google-maps-scraper

# Clone repository (thay YOUR_REPO_URL bằng URL repo của bạn)
git clone YOUR_REPO_URL .

# Checkout branch đúng
git checkout claude/google-maps-scraper-011JmFpYUPeT39Edsg51a5kR
```

**Lưu ý:** Nếu repo là private, cần:
```bash
# Option 1: Dùng Personal Access Token
git clone https://YOUR_TOKEN@github.com/username/DONA.git .

# Option 2: Dùng SSH key (khuyên dùng)
ssh-keygen -t ed25519 -C "your_email@example.com"
cat ~/.ssh/id_ed25519.pub
# Copy key này vào GitHub Settings → SSH Keys
```

---

## Bước 4: Build ứng dụng

```bash
# Phân quyền cho build script
chmod +x build-production.sh

# Chạy build (mất ~2-5 phút)
./build-production.sh
```

**Kết quả:** Thư mục `dist/` sẽ được tạo với toàn bộ code production.

---

## Bước 5: Cấu hình môi trường

```bash
cd dist

# Tạo file .env
nano .env
```

**Nhập nội dung sau vào file .env:**
```env
NODE_ENV=production
PORT=3000
DATABASE_PATH=/var/www/google-maps-scraper/dist/data/scraper.db
```

**Lưu file:** Nhấn `Ctrl+X`, sau đó `Y`, sau đó `Enter`

```bash
# Tạo thư mục data
mkdir -p data
chmod 755 data
```

---

## Bước 6: Chạy ứng dụng với PM2

```bash
cd /var/www/google-maps-scraper/dist/backend

# Start ứng dụng
pm2 start dist/server.js --name google-maps-scraper

# Lưu cấu hình PM2
pm2 save

# Tự động khởi động khi VPS restart
pm2 startup
# → Copy lệnh hiển thị ra và chạy lại lệnh đó

# Kiểm tra trạng thái
pm2 status
pm2 logs google-maps-scraper
```

**Kết quả:** Ứng dụng đang chạy tại `http://103.x.x.x:3000`

---

## Bước 7: Cấu hình Nginx (để dùng domain)

### 7.1. Tạo cấu hình Nginx

```bash
sudo nano /etc/nginx/sites-available/google-maps-scraper
```

**Nhập nội dung sau (thay `your-domain.com` bằng domain của bạn):**

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;

        # Tăng timeout cho scraping
        proxy_read_timeout 300s;
        proxy_connect_timeout 300s;
        proxy_send_timeout 300s;
    }
}
```

**Lưu file:** `Ctrl+X` → `Y` → `Enter`

### 7.2. Kích hoạt cấu hình

```bash
# Tạo symbolic link
sudo ln -s /etc/nginx/sites-available/google-maps-scraper /etc/nginx/sites-enabled/

# Xóa default site (nếu có)
sudo rm /etc/nginx/sites-enabled/default

# Test cấu hình
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### 7.3. Trỏ domain về VPS

**Trong AZdigi DNS Manager:**
1. Vào **My AZdigi → Domains**
2. Click **Manage DNS** cho domain của bạn
3. Thêm/Sửa 2 record:

```
Type: A
Name: @
Value: 103.x.x.x (IP VPS của bạn)
TTL: 3600

Type: A
Name: www
Value: 103.x.x.x
TTL: 3600
```

**Chờ 5-30 phút** để DNS propagate.

---

## Bước 8: Cài SSL miễn phí (Let's Encrypt)

```bash
# Cài Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Lấy SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Chọn: 2 (Redirect HTTP to HTTPS)
```

**Tự động gia hạn SSL:**
```bash
sudo certbot renew --dry-run
```

---

## ✅ Bước 9: Kiểm tra hoạt động

### Test API:
```bash
curl http://localhost:3000/health
# Kết quả: {"status":"ok",...}
```

### Truy cập web:
- **Qua IP:** http://103.x.x.x:3000
- **Qua domain:** https://your-domain.com

### Test scraping:
1. Vào web interface
2. Chọn preset: **🍜 Hàng quán**
3. Nhập location: **Quận 1, Hồ Chí Minh**
4. Bật **Ultra Deep Mode**
5. Click **Bắt đầu cào**
6. Chờ ~5-10 phút
7. Xem kết quả (nên có 800-1400 địa điểm)

---

## 🔥 Lệnh quản lý hữu ích

### Quản lý PM2:
```bash
pm2 status                    # Xem trạng thái
pm2 logs google-maps-scraper  # Xem logs real-time
pm2 restart google-maps-scraper  # Restart app
pm2 stop google-maps-scraper     # Dừng app
pm2 delete google-maps-scraper   # Xóa app
```

### Quản lý Nginx:
```bash
sudo systemctl status nginx   # Xem trạng thái
sudo systemctl restart nginx  # Restart
sudo nginx -t                 # Test config
```

### Xem logs:
```bash
# App logs
pm2 logs google-maps-scraper --lines 100

# Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```

### Xem tài nguyên:
```bash
pm2 monit                     # Monitor CPU/RAM
htop                          # System monitor
df -h                         # Disk usage
free -h                       # RAM usage
```

---

## 🐛 Troubleshooting

### Lỗi: "Port 3000 already in use"
```bash
# Tìm process đang dùng port 3000
sudo lsof -i :3000

# Kill process
sudo kill -9 PID_NUMBER

# Hoặc restart PM2
pm2 restart google-maps-scraper
```

### Lỗi: "Chromium not found"
```bash
# Cài lại Chromium
sudo apt-get update
sudo apt-get install -y chromium-browser chromium-codecs-ffmpeg-extra

# Kiểm tra
which chromium-browser
chromium-browser --version
```

### Lỗi: "Cannot find module"
```bash
cd /var/www/google-maps-scraper/dist
npm install --production
pm2 restart google-maps-scraper
```

### Lỗi: "Permission denied" khi scraping
```bash
cd /var/www/google-maps-scraper/dist
chmod -R 755 data
chown -R $USER:$USER data
```

### App bị crash khi scraping nhiều:
```bash
# Tăng RAM cho PM2
pm2 delete google-maps-scraper
pm2 start dist/backend/dist/server.js --name google-maps-scraper --max-memory-restart 2G

# Hoặc nâng cấp VPS lên gói RAM cao hơn tại AZdigi
```

---

## 🔄 Cập nhật code mới

```bash
cd /var/www/google-maps-scraper

# Pull code mới
git pull origin claude/google-maps-scraper-011JmFpYUPeT39Edsg51a5kR

# Build lại
./build-production.sh

# Restart app
pm2 restart google-maps-scraper
```

---

## 📊 Khuyến nghị VPS cho AZdigi

**Gói VPS tối thiểu:**
- **RAM:** 2GB (khuyên dùng 4GB cho Ultra Deep Mode)
- **CPU:** 2 cores
- **SSD:** 20GB+
- **Bandwidth:** 1TB+

**Gói khuyên dùng:**
- **VPS SSD 2** hoặc **Cloud VPS 2** (4GB RAM, 2 CPU)
- Giá: ~200k-300k/tháng
- Đủ chạy batch scraping + Ultra Deep Mode

---

## 🎯 Checklist hoàn thành

- [ ] Đã kết nối SSH vào VPS
- [ ] Đã cài Node.js, Git, Chromium, PM2, Nginx
- [ ] Đã clone repository và checkout đúng branch
- [ ] Đã chạy build-production.sh thành công
- [ ] Đã cấu hình .env và tạo thư mục data
- [ ] Đã start app với PM2 và thấy status "online"
- [ ] Truy cập http://IP:3000 thấy giao diện
- [ ] Đã cấu hình Nginx và trỏ domain
- [ ] Đã cài SSL (nếu có domain)
- [ ] Test scraping thành công với ít nhất 1 keyword

---

**Hỗ trợ AZdigi:**
- Hotline: 1900 2027
- Email: support@azdigi.com
- Ticket: my.azdigi.com

**Cần hỗ trợ kỹ thuật?** Liên hệ support AZdigi để được hỗ trợ cài đặt môi trường Node.js trên VPS.
