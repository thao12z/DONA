# 🚀 Hướng dẫn Deploy lên Hosting

## 📋 Yêu cầu Hosting

### Hosting phải hỗ trợ:
- ✅ Node.js (v18 trở lên)
- ✅ SSH access hoặc File Manager
- ✅ Quyền cài đặt packages npm
- ✅ Quyền chạy scripts
- ✅ RAM tối thiểu: 1GB (khuyến nghị 2GB)
- ✅ Disk space: 500MB trở lên

### Các loại hosting phù hợp:
- **VPS**: DigitalOcean, Linode, Vultr, AWS EC2
- **Shared Hosting**: Hostinger, Namecheap (có Node.js support)
- **Cloud**: Google Cloud, Azure, AWS
- **PaaS**: Heroku, Railway, Render

---

## 🏗️ Bước 1: Build Production

Trên máy local của bạn:

```bash
# Clone code về (nếu chưa có)
git clone <repository-url>
cd DONA

# Build production
chmod +x build-production.sh
./build-production.sh
```

Sau khi build xong, bạn sẽ có thư mục `dist/` chứa:
```
dist/
├── backend/          # Backend compiled
├── frontend/         # Frontend build
├── node_modules/     # Dependencies
├── data/            # Database folder
├── .env             # Environment config
├── start.sh         # Linux/Mac start script
├── start.bat        # Windows start script
└── package.json
```

---

## 📤 Bước 2: Upload lên Hosting

### Cách 1: Upload qua FTP/SFTP (FileZilla, WinSCP)

1. Kết nối tới hosting qua FTP/SFTP
2. Upload toàn bộ thư mục `dist/` lên hosting
3. Đổi tên thư mục thành `google-maps-scraper` (hoặc tên bạn muốn)

### Cách 2: Upload qua SSH (khuyến nghị)

```bash
# Nén thư mục dist
cd DONA
tar -czf dist.tar.gz dist/

# Upload lên server (thay your-server thành IP/domain của bạn)
scp dist.tar.gz user@your-server:/home/user/

# SSH vào server
ssh user@your-server

# Giải nén
tar -xzf dist.tar.gz
mv dist google-maps-scraper
cd google-maps-scraper
```

### Cách 3: Git Clone trực tiếp trên server

```bash
# SSH vào server
ssh user@your-server

# Clone repository
git clone <repository-url>
cd DONA

# Build trên server
chmod +x build-production.sh
./build-production.sh
cd dist
```

---

## ⚙️ Bước 3: Cấu hình

### 3.1. Cấu hình .env

```bash
cd google-maps-scraper
nano .env
```

Nội dung file `.env`:
```env
NODE_ENV=production
PORT=3000
DATABASE_PATH=./data/scraper.db
```

**Lưu ý Port**: Nếu hosting của bạn yêu cầu port cụ thể (ví dụ 8080, 5000), hãy thay đổi tại đây.

### 3.2. Cài đặt Chromium (cho Puppeteer)

#### Ubuntu/Debian:
```bash
sudo apt-get update
sudo apt-get install -y chromium-browser
```

#### CentOS/RHEL:
```bash
sudo yum install -y chromium
```

#### macOS:
```bash
brew install chromium
```

---

## 🎯 Bước 4: Chạy Ứng dụng

### Cách 1: Chạy trực tiếp (Test)

```bash
cd google-maps-scraper
./start.sh
```

Ứng dụng sẽ chạy tại: `http://your-server:3000`

### Cách 2: Chạy với PM2 (khuyến nghị - auto restart)

```bash
# Cài PM2 (chỉ cần 1 lần)
npm install -g pm2

# Start app với PM2
cd google-maps-scraper
pm2 start backend/dist/server.js --name google-maps-scraper

# Lưu cấu hình PM2
pm2 save

# Auto start khi server reboot
pm2 startup
```

Các lệnh PM2 hữu ích:
```bash
pm2 status                    # Xem trạng thái
pm2 logs google-maps-scraper  # Xem logs
pm2 restart google-maps-scraper  # Restart app
pm2 stop google-maps-scraper  # Stop app
pm2 delete google-maps-scraper  # Xóa app khỏi PM2
```

---

## 🌐 Bước 5: Cấu hình Domain (Optional)

### Sử dụng Nginx Reverse Proxy

```bash
# Cài Nginx
sudo apt-get install nginx

# Tạo config file
sudo nano /etc/nginx/sites-available/google-maps-scraper
```

Nội dung file:
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
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

Kích hoạt:
```bash
# Link config
sudo ln -s /etc/nginx/sites-available/google-maps-scraper /etc/nginx/sites-enabled/

# Test config
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### SSL với Let's Encrypt (HTTPS)

```bash
# Cài Certbot
sudo apt-get install certbot python3-certbot-nginx

# Lấy SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto renewal
sudo certbot renew --dry-run
```

---

## 🔒 Bước 6: Bảo mật

### 6.1. Firewall

```bash
# Cho phép port 22 (SSH), 80 (HTTP), 443 (HTTPS)
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 6.2. Backup Database

```bash
# Tạo script backup
nano backup.sh
```

Nội dung:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
cp /path/to/google-maps-scraper/data/scraper.db \
   /path/to/backups/scraper_$DATE.db
```

Thêm vào crontab (chạy hàng ngày lúc 2h sáng):
```bash
crontab -e
# Thêm dòng:
0 2 * * * /path/to/backup.sh
```

---

## 📊 Monitoring

### Xem logs

```bash
# Logs PM2
pm2 logs google-maps-scraper

# Logs real-time
pm2 logs google-maps-scraper --lines 100

# System logs
journalctl -u google-maps-scraper -f
```

### Monitor resource

```bash
# CPU, RAM usage
pm2 monit

# Hoặc dùng htop
htop
```

---

## 🐛 Xử lý Lỗi

### Lỗi: Port already in use
```bash
# Tìm process đang dùng port 3000
sudo lsof -i :3000

# Kill process
sudo kill -9 <PID>
```

### Lỗi: Puppeteer không chạy
```bash
# Kiểm tra Chromium
which chromium-browser

# Cài thêm dependencies
sudo apt-get install -y \
  gconf-service libasound2 libatk1.0-0 libc6 libcairo2 \
  libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc1 \
  libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 \
  libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 \
  libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 \
  libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 \
  libxrender1 libxss1 libxtst6 ca-certificates \
  fonts-liberation libappindicator1 libnss3 lsb-release \
  xdg-utils wget
```

### Lỗi: Database locked
```bash
# Stop app
pm2 stop google-maps-scraper

# Xóa lock file
rm /path/to/data/scraper.db-wal
rm /path/to/data/scraper.db-shm

# Restart
pm2 restart google-maps-scraper
```

### Lỗi: Out of memory
```bash
# Tăng swap (nếu RAM thấp)
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Thêm vào /etc/fstab để permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 📝 Checklist Deploy

- [ ] Build production thành công
- [ ] Upload files lên server
- [ ] Cài đặt Chromium
- [ ] Cấu hình .env
- [ ] Test chạy với ./start.sh
- [ ] Setup PM2 auto restart
- [ ] Cấu hình Nginx (nếu có)
- [ ] Setup SSL (nếu có domain)
- [ ] Cấu hình firewall
- [ ] Setup backup database
- [ ] Test cào dữ liệu
- [ ] Test export CSV/JSON

---

## 🎉 Hoàn thành!

Truy cập:
- **Ứng dụng**: http://your-server:3000 (hoặc https://your-domain.com)
- **API Health**: http://your-server:3000/health
- **Export CSV**: http://your-server:3000/api/places/export/csv
- **Export JSON**: http://your-server:3000/api/places/export/json

---

## 💡 Tips

1. **Performance**: Nếu cào nhiều dữ liệu, tăng RAM lên 2-4GB
2. **Speed**: Giảm `maxResults` xuống 20-30 để tránh timeout
3. **Reliability**: Dùng PM2 để auto restart khi crash
4. **Backup**: Backup database hàng ngày
5. **Monitor**: Dùng `pm2 monit` để theo dõi resource

---

## 📞 Support

Nếu gặp vấn đề, check logs:
```bash
pm2 logs google-maps-scraper --err
```

Hoặc tạo issue trên GitHub.

Good luck! 🚀
