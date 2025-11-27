# 🚀 Hướng dẫn chạy nhanh

## Bước 1: Cài đặt

```bash
# Cài đặt dependencies cho cả backend và frontend
npm install
cd backend && npm install
cd ../frontend && npm install
cd ..
```

## Bước 2: Chạy hệ thống

```bash
# Chạy cả backend và frontend cùng lúc
npm run dev
```

Hoặc chạy riêng:

```bash
# Terminal 1 - Backend
cd backend
npm run dev

# Terminal 2 - Frontend
cd frontend
npm run dev
```

## Bước 3: Truy cập

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:3000

## Bước 4: Sử dụng

1. Mở http://localhost:5173
2. Nhập từ khóa (ví dụ: "nhà hàng", "khách sạn")
3. Nhập vị trí (tùy chọn):
   - Quốc gia: Việt Nam
   - Tỉnh: Hồ Chí Minh
   - Quận: Quận 1
4. Nhấn "Bắt đầu cào"
5. Đợi kết quả hiển thị

## ⚠️ Lưu ý

- Lần đầu chạy Puppeteer sẽ tải Chrome (~200MB)
- Cào dữ liệu mất ~1-2 giây/địa điểm
- Không nên cào quá 50 kết quả cùng lúc để tránh bị Google chặn

## 🐛 Gặp lỗi?

### Lỗi: "Cannot find module"
```bash
rm -rf node_modules package-lock.json
npm install
cd backend && rm -rf node_modules package-lock.json && npm install
cd ../frontend && rm -rf node_modules package-lock.json && npm install
```

### Lỗi: "Port already in use"
```bash
# Đổi port trong backend/.env
PORT=3001
```

### Lỗi Puppeteer
```bash
# Ubuntu/Debian
sudo apt-get install -y chromium-browser

# macOS
brew install chromium
```

## 📝 Ví dụ sử dụng

### Ví dụ 1: Tìm nhà hàng ở Quận 1
- Keyword: `nhà hàng`
- Tỉnh: `Hồ Chí Minh`
- Quận: `Quận 1`
- Số lượng: `20`

### Ví dụ 2: Tìm khách sạn ở Hà Nội
- Keyword: `khách sạn`
- Tỉnh: `Hà Nội`
- Quận: `Ba Đình`
- Số lượng: `30`

### Ví dụ 3: Tìm spa trên toàn quốc
- Keyword: `spa`
- Quốc gia: `Việt Nam`
- Số lượng: `50`

## 🎯 Tips

- Chọn vị trí càng cụ thể, kết quả càng chính xác
- Bắt đầu với số lượng nhỏ (10-20) để test
- Sử dụng bộ lọc để tìm dữ liệu đã cào
- Export dữ liệu bằng cách copy từ bảng hoặc kết nối SQLite trực tiếp

Chúc bạn thành công! 🎉
