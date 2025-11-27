# 🗺️ Google Maps Scraper

Công cụ tự động thu thập dữ liệu địa điểm từ Google Maps với khả năng lọc theo vị trí địa lý chi tiết.

## ✨ Tính năng

- 🔍 **Cào dữ liệu Google Maps**: Tự động thu thập thông tin địa điểm theo từ khóa
- 📍 **Lọc theo vị trí**: Quốc gia → Tỉnh/Thành → Thành phố → Quận → Phường
- 💾 **Lưu trữ dữ liệu**: SQLite database với đầy đủ thông tin
- 🎯 **Thông tin thu thập**:
  - Tên địa điểm
  - Số điện thoại
  - Địa chỉ cụ thể
  - Tọa độ GPS (latitude, longitude)
  - Phân loại theo khu vực
- 🔎 **Tìm kiếm & lọc**: Giao diện web để xem và lọc dữ liệu
- 🆓 **100% miễn phí**: Không cần API key hay dịch vụ trả phí

## 🛠️ Công nghệ sử dụng

- **Backend**: Node.js + TypeScript + Express
- **Scraper**: Puppeteer (headless Chrome)
- **Database**: SQLite
- **Frontend**: React + Vite
- **Styling**: CSS thuần

## 📦 Cài đặt

### Yêu cầu hệ thống

- Node.js >= 18.x
- npm >= 9.x

### Các bước cài đặt

1. **Clone repository**
```bash
git clone <repository-url>
cd DONA
```

2. **Cài đặt dependencies**
```bash
npm install
cd backend && npm install
cd ../frontend && npm install
cd ..
```

3. **Tạo file .env**
```bash
cd backend
cp .env.example .env
```

Nội dung file `.env`:
```env
PORT=3000
DATABASE_PATH=./data/scraper.db
```

## 🚀 Sử dụng

### Chạy toàn bộ hệ thống

```bash
# Từ thư mục gốc
npm run dev
```

Lệnh này sẽ chạy đồng thời:
- Backend API: http://localhost:3000
- Frontend: http://localhost:5173

### Chạy riêng từng phần

**Backend:**
```bash
cd backend
npm run dev
```

**Frontend:**
```bash
cd frontend
npm run dev
```

## 📖 Hướng dẫn sử dụng

### 1. Cào dữ liệu

1. Mở trình duyệt và truy cập: http://localhost:5173
2. Nhập **từ khóa** (ví dụ: "nhà hàng", "khách sạn", "spa")
3. Chọn vị trí (tùy chọn):
   - Quốc gia (ví dụ: "Việt Nam")
   - Tỉnh/Thành phố (ví dụ: "Hồ Chí Minh")
   - Thành phố/Huyện (ví dụ: "Thành phố Thủ Đức")
   - Quận/Huyện (ví dụ: "Quận 1")
   - Phường/Xã (ví dụ: "Phường Bến Nghé")
4. Chọn số lượng kết quả tối đa (mặc định: 20, tối đa: 100)
5. Nhấn **"Bắt đầu cào"**

### 2. Xem và lọc dữ liệu

Sau khi cào xong, dữ liệu sẽ hiển thị ở bảng bên dưới với các tính năng:

- **Tìm kiếm**: Tìm theo tên, địa chỉ, số điện thoại
- **Lọc theo vị trí**: Chọn Quốc gia, Tỉnh, Thành phố, Quận
- **Phân trang**: Điều hướng qua các trang kết quả
- **Xem tọa độ**: Click vào tọa độ để mở Google Maps

### 3. Quản lý dữ liệu

- **Xóa tất cả**: Nhấn nút "Xóa tất cả" để xóa toàn bộ dữ liệu

## 🔧 API Endpoints

### GET /api/places
Lấy danh sách địa điểm với bộ lọc

**Query Parameters:**
- `keyword` - Lọc theo từ khóa
- `country` - Lọc theo quốc gia
- `province` - Lọc theo tỉnh
- `city` - Lọc theo thành phố
- `district` - Lọc theo quận
- `ward` - Lọc theo phường
- `search` - Tìm kiếm trong tên, địa chỉ, SĐT
- `limit` - Số lượng kết quả (mặc định: 100)
- `offset` - Vị trí bắt đầu (cho phân trang)

**Response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 150,
    "limit": 100,
    "offset": 0,
    "hasMore": true
  }
}
```

### GET /api/places/filters/:field
Lấy danh sách giá trị duy nhất cho bộ lọc

**Parameters:**
- `field` - country | province | city | district | ward

**Response:**
```json
{
  "success": true,
  "data": ["Hà Nội", "Hồ Chí Minh", "Đà Nẵng"]
}
```

### POST /api/places/scrape
Cào dữ liệu từ Google Maps

**Request Body:**
```json
{
  "keyword": "nhà hàng",
  "country": "Việt Nam",
  "province": "Hồ Chí Minh",
  "city": "Thành phố Thủ Đức",
  "district": "Quận 1",
  "ward": "Phường Bến Nghé",
  "maxResults": 20
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "scraped": 20,
    "saved": 20,
    "places": [...]
  }
}
```

### DELETE /api/places
Xóa toàn bộ dữ liệu

**Response:**
```json
{
  "success": true,
  "message": "All places deleted"
}
```

## 📁 Cấu trúc thư mục

```
DONA/
├── backend/
│   ├── src/
│   │   ├── database/
│   │   │   └── schema.ts          # SQLite schema
│   │   ├── models/
│   │   │   └── Place.ts           # Place model
│   │   ├── routes/
│   │   │   └── places.ts          # API routes
│   │   ├── scraper/
│   │   │   └── googleMaps.ts      # Puppeteer scraper
│   │   └── server.ts              # Express server
│   ├── data/                      # SQLite database
│   ├── package.json
│   └── tsconfig.json
├── frontend/
│   ├── src/
│   │   ├── api/
│   │   │   └── places.ts          # API client
│   │   ├── components/
│   │   │   ├── ScrapeForm.tsx     # Form cào dữ liệu
│   │   │   └── PlacesList.tsx     # Bảng hiển thị
│   │   ├── App.tsx                # Main app
│   │   ├── main.tsx               # Entry point
│   │   └── index.css              # Styles
│   ├── index.html
│   ├── package.json
│   └── vite.config.ts
├── package.json
└── README.md
```

## ⚠️ Lưu ý

1. **Tốc độ cào dữ liệu**:
   - Puppeteer sử dụng trình duyệt headless nên tương đối chậm
   - Thời gian cào ~1-2 giây/địa điểm
   - Không nên cào quá 100 kết quả cùng lúc

2. **Google Maps có thể chặn**:
   - Nếu cào quá nhiều, Google có thể yêu cầu CAPTCHA
   - Nên cào với số lượng vừa phải (20-50 kết quả)
   - Có thể thêm delay giữa các lần cào

3. **Dữ liệu không đầy đủ**:
   - Một số địa điểm không có SĐT hoặc địa chỉ đầy đủ
   - Tọa độ được lấy từ URL, có thể không chính xác 100%

4. **Hiệu năng**:
   - SQLite phù hợp cho dữ liệu nhỏ-vừa (< 100k bản ghi)
   - Nếu cần lưu hàng triệu địa điểm, nên chuyển sang PostgreSQL

## 🐛 Xử lý lỗi

### Lỗi Puppeteer
```
Error: Failed to launch the browser process!
```
**Giải pháp**: Cài đặt Chrome/Chromium dependencies
```bash
# Ubuntu/Debian
sudo apt-get install -y chromium-browser

# macOS
brew install chromium
```

### Lỗi SQLite
```
Error: SQLITE_CANTOPEN: unable to open database file
```
**Giải pháp**: Tạo thư mục data
```bash
mkdir -p backend/data
```

## 🔮 Tính năng tương lai

- [ ] Export dữ liệu sang CSV/Excel
- [ ] Hiển thị địa điểm trên bản đồ
- [ ] Lọc theo khoảng cách (radius search)
- [ ] Lên lịch cào tự động
- [ ] API authentication
- [ ] Multi-language support

## 📝 License

MIT License - Sử dụng tự do cho mục đích cá nhân

## 🤝 Đóng góp

Mọi đóng góp đều được hoan nghênh! Hãy tạo issue hoặc pull request.

## 📧 Liên hệ

Nếu có vấn đề gì, hãy tạo issue trên GitHub.

---

Made with ❤️ by Claude
