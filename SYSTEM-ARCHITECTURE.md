# HỆ THỐNG QUẢN LÝ BÁN HÀNG - DONA PAPER
## Phân tích yêu cầu & Kiến trúc hệ thống

## 1. TỔNG QUAN HỆ THỐNG

### 1.1. Mục đích
- Hệ thống quản lý đơn hàng và doanh số bán hàng
- Quản lý nhân viên kinh doanh
- Báo cáo và thống kê hiệu suất

### 1.2. Người dùng
- **User (Nhân viên kinh doanh)**: Nhập đơn hàng, xem lịch sử
- **Admin (Quản lý)**: Duyệt đơn, quản lý nhân viên, báo cáo

### 1.3. Domains
- **User Portal**: kinhdoanh.donapaper.vn
- **Admin Portal**: quanly.donapaper.vn

---

## 2. CÔNG NGHỆ

### 2.1. Tech Stack
- **Backend**: PHP 7.4+ (cPanel compatible)
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Authentication**: PHP Sessions với secure cookies
- **Real-time**: AJAX polling (3-5 giây)

### 2.2. Hosting
- cPanel shared hosting (AZdigi)
- PHP, MySQL built-in
- SSL certificates (Let's Encrypt)

---

## 3. DATABASE SCHEMA

### 3.1. Bảng `users`
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Nam', 'Nữ', 'Khác'),
    address TEXT,
    id_card_front VARCHAR(255),
    id_card_back VARCHAR(255),
    province_id INT,
    district_id INT,
    ward_id INT,
    position VARCHAR(100),
    contact_info VARCHAR(100),
    is_admin TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_banned TINYINT(1) DEFAULT 0,
    ban_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_is_admin (is_admin),
    INDEX idx_province (province_id)
);
```

### 3.2. Bảng `orders`
```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    customer_social VARCHAR(255),
    customer_latitude DECIMAL(10, 8),
    customer_longitude DECIMAL(11, 8),
    customer_type VARCHAR(50),
    purchase_price DECIMAL(15, 2) NOT NULL,
    quantity INT NOT NULL,
    total_revenue DECIMAL(15, 2) NOT NULL,
    other_costs DECIMAL(15, 2) DEFAULT 0,
    profit DECIMAL(15, 2) GENERATED ALWAYS AS (total_revenue - purchase_price - other_costs) STORED,
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_message TEXT,
    admin_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### 3.3. Bảng `provinces` (Tỉnh/Thành phố)
```sql
CREATE TABLE provinces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    INDEX idx_code (code)
);
```

### 3.4. Bảng `districts` (Quận/Huyện)
```sql
CREATE TABLE districts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    province_id INT NOT NULL,
    FOREIGN KEY (province_id) REFERENCES provinces(id),
    INDEX idx_province_id (province_id),
    INDEX idx_code (code)
);
```

### 3.5. Bảng `wards` (Phường/Xã)
```sql
CREATE TABLE wards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    district_id INT NOT NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id),
    INDEX idx_district_id (district_id),
    INDEX idx_code (code)
);
```

### 3.6. Bảng `spam_tracking` (Chống spam)
```sql
CREATE TABLE spam_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    ip_address VARCHAR(45) NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ban_until TIMESTAMP NULL,
    permanent_ban TINYINT(1) DEFAULT 0,
    INDEX idx_user_ip (user_id, ip_address),
    INDEX idx_ip (ip_address),
    INDEX idx_ban_until (ban_until)
);
```

### 3.7. Bảng `login_attempts` (Bảo mật login)
```sql
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_username (username),
    INDEX idx_ip (ip_address)
);
```

---

## 4. KIẾN TRÚC THƯ MỤC

```
/dona-sales/
├── admin/                      # quanly.donapaper.vn
│   ├── index.php              # Dashboard
│   ├── orders.php             # Quản lý đơn hàng
│   ├── users.php              # Quản lý user
│   ├── reports.php            # Báo cáo kinh doanh
│   ├── login.php              # Admin login
│   └── logout.php
│
├── user/                       # kinhdoanh.donapaper.vn
│   ├── index.php              # Form nhập đơn
│   ├── history.php            # Lịch sử đơn hàng
│   ├── login.php              # User login
│   └── logout.php
│
├── api/                        # API endpoints
│   ├── auth.php               # Login/logout API
│   ├── orders.php             # Orders CRUD
│   ├── users.php              # Users CRUD
│   ├── locations.php          # Provinces/Districts/Wards
│   └── reports.php            # Statistics API
│
├── config/
│   ├── database.php           # Database config
│   ├── config.php             # App config
│   └── install.sql            # Database schema
│
├── includes/
│   ├── db.php                 # Database connection
│   ├── auth.php               # Authentication functions
│   ├── functions.php          # Utility functions
│   ├── spam_protection.php    # Anti-spam
│   └── header.php             # Common header
│   └── footer.php             # Common footer
│
├── assets/
│   ├── css/
│   │   ├── main.css           # Main styles
│   │   ├── admin.css          # Admin styles
│   │   └── user.css           # User styles
│   ├── js/
│   │   ├── main.js            # Main JavaScript
│   │   ├── admin.js           # Admin JavaScript
│   │   └── user.js            # User JavaScript
│   └── images/
│       └── logo.png
│
├── uploads/
│   └── id_cards/              # CCCD images
│
└── .htaccess                  # Apache config
```

---

## 5. CHỨC NĂNG CHI TIẾT

### 5.1. User Portal (kinhdoanh.donapaper.vn)

#### 5.1.1. Login
- Form đăng nhập với username/password
- Rate limiting: 5 lần thất bại → ban 15 phút
- Remember me option (7 ngày)

#### 5.1.2. Form nhập đơn hàng
**Các trường:**
- Tên khách hàng (required)
- Số điện thoại (required, validation Vietnamese phone)
- Địa chỉ (required)
- Mạng xã hội (optional, Facebook/Zalo/Instagram)
- Vị trí GPS (required, HTML5 Geolocation API)
- Loại khách hàng (dropdown: Lẻ, Sỉ, Đại lý, Khác)
- Giá nhập (required, number, VNĐ)
- Số lượng (required, integer)
- Tổng thu (required, auto-calculate or manual)
- Chi phí phát sinh (optional, number)
- Người lập đơn (auto-fill từ session)
- Ghi chú (optional, textarea)

**Features:**
- Auto-save draft (localStorage)
- Real-time validation
- GPS location picker (map integration optional)
- Profit calculation: Tổng thu - Giá nhập - Chi phí phát sinh

#### 5.1.3. Lịch sử đơn hàng
- Bảng hiển thị đơn hàng đã nhập
- Cột: STT, Ngày tạo, Khách hàng, Tổng thu, Trạng thái
- Filter: Trạng thái (Pending/Approved/Rejected), Ngày
- Pagination: 20 đơn/trang
- Xem chi tiết đơn (modal/collapse)
- Badge màu: Pending (yellow), Approved (green), Rejected (red)
- Hiển thị lời nhắn admin nếu rejected

### 5.2. Admin Portal (quanly.donapaper.vn)

#### 5.2.1. Dashboard
- Tổng số đơn hàng (hôm nay, tuần, tháng)
- Tổng doanh thu
- Tổng lợi nhuận
- Số đơn pending
- Biểu đồ doanh thu theo thời gian (Chart.js)
- Top 5 nhân viên xuất sắc

#### 5.2.2. Quản lý đơn hàng
**Bảng đơn hàng pending:**
- Checkbox chọn nhiều đơn
- STT, Ngày, Nhân viên, Khách hàng, Tổng thu, Actions
- Click vào row → expand chi tiết đơn
- Actions: Duyệt, Từ chối, Xem chi tiết
- Bulk actions: Duyệt tất cả, Từ chối tất cả
- Form từ chối: Bắt buộc nhập lý do

**Bảng tất cả đơn hàng:**
- Filter: Trạng thái, Nhân viên, Ngày tháng
- Export CSV
- Import CSV (template provided)

#### 5.2.3. Quản lý nhân viên
**Bảng danh sách:**
- STT
- Tên
- Giới tính
- Liên lạc
- Chức danh
- Tần suất online (last login)
- Tỷ lệ từ chối đơn (%)
- Actions: Chỉnh sửa, Khóa/Mở khóa

**Form thêm/sửa nhân viên:**
- Username (unique)
- Password (hash với password_hash)
- Tên đầy đủ
- Ngày sinh
- Giới tính
- Địa chỉ
- Upload CCCD (2 mặt)
- Khu vực: Dropdown cascade Tỉnh → Huyện → Xã
- Chức danh
- Thông tin liên lạc

#### 5.2.4. Báo cáo kinh doanh
- Filter: Thời gian, Nhân viên, Khu vực
- Tổng đơn hàng
- Tổng doanh thu
- Tổng lợi nhuận
- Trung bình đơn hàng/người/ngày
- Biểu đồ: Doanh thu theo nhân viên, theo khu vực
- Export báo cáo PDF/Excel

---

## 6. BẢO MẬT

### 6.1. Authentication
- Password hash: `password_hash()` với BCRYPT
- Session với httpOnly, secure cookies
- CSRF tokens cho mọi form
- Session timeout: 30 phút inactivity

### 6.2. Authorization
- Middleware kiểm tra quyền admin/user
- User chỉ xem được đơn của mình
- Admin xem được tất cả

### 6.3. Input Validation
- Server-side validation tất cả input
- Prepared statements (PDO) → chống SQL injection
- htmlspecialchars() → chống XSS
- File upload: Check mime type, size limit (5MB)

### 6.4. Anti-Spam/DDoS
**Rules:**
- Track actions per IP + user_id
- Window: 5 phút
- Threshold: 20 actions
- Penalty: Ban 10 phút
- Recurrence: 3 lần trong 3 ngày → Ban vĩnh viễn

**Implementation:**
- Middleware trên mọi POST request
- Log vào bảng `spam_tracking`
- Cleanup cron job: Xóa records cũ hơn 3 ngày

---

## 7. GIAO DIỆN (UI/UX)

### 7.1. Design System
**Colors:**
- Primary: #1b76ff (Blue)
- Background: #ffffff (White)
- Text: #000000 (Black)
- Secondary: Pastel shades of primary
- Success: #4caf50
- Warning: #ff9800
- Error: #f44336

**Typography:**
- Font: System fonts (San Francisco, Segoe UI, Roboto)
- Heading: 24px, 20px, 18px
- Body: 14px
- Small: 12px

**Components:**
- Cards: White background, subtle shadow
- Buttons: Primary color, rounded corners (8px)
- Inputs: Border 1px solid #ddd, focus #1b76ff
- Tables: Striped rows, hover effect
- Badges: Rounded pill shape

### 7.2. Responsive Breakpoints
- Desktop: 1920px - 1366px (container: 1200px)
- Tablet: 1280px - 960px (container: 960px)
- Mobile: 768px - 360px (container: 100% with padding)

**Strategy:**
- Mobile-first approach
- Flexbox/Grid for layout
- Hamburger menu on mobile
- Stack columns vertically on mobile
- Touch-friendly buttons (min 44px)

### 7.3. Components to Build
- Navigation bar (admin/user)
- Data tables with sorting/filtering
- Forms with validation
- Modals for details/confirmations
- Toast notifications
- Loading spinners
- Pagination
- Charts (Chart.js)

---

## 8. WORKFLOW

### 8.1. User Flow
1. User login → Dashboard
2. Click "Nhập đơn mới"
3. Fill form (GPS auto-detect)
4. Submit → Validation
5. Success → Đơn hàng pending
6. User xem lịch sử → Thấy status

### 8.2. Admin Flow
1. Admin login → Dashboard
2. Xem đơn pending
3. Click vào đơn → Xem chi tiết
4. Duyệt hoặc từ chối (với lý do)
5. Đơn chuyển status
6. User nhận notification (khi login lại)

---

## 9. DEPLOYMENT

### 9.1. cPanel Setup
1. Tạo 2 subdomains:
   - kinhdoanh.donapaper.vn → /public_html/user/
   - quanly.donapaper.vn → /public_html/admin/
2. MySQL database: Create via cPanel
3. Import SQL schema
4. Update config/database.php với credentials
5. Set permissions: uploads/ folder 755

### 9.2. SSL
- Let's Encrypt SSL (free) via cPanel
- Force HTTPS trong .htaccess

### 9.3. .htaccess
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Pretty URLs
RewriteRule ^api/(.*)$ api/$1.php [L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

---

## 10. TESTING CHECKLIST

- [ ] User registration/login
- [ ] User submit order
- [ ] GPS location detection
- [ ] Form validation (all fields)
- [ ] Spam protection (20 submits in 5 min)
- [ ] Admin login
- [ ] Admin approve order
- [ ] Admin reject order (with message)
- [ ] User see status update
- [ ] User see admin message
- [ ] Admin user management
- [ ] Admin reports
- [ ] Export CSV
- [ ] Import CSV
- [ ] Responsive on mobile
- [ ] Responsive on tablet
- [ ] Cross-browser (Chrome, Firefox, Safari)
- [ ] SQL injection test
- [ ] XSS test
- [ ] CSRF test

---

## 11. OPTIMIZATION

### 11.1. Performance
- Database indexes on frequently queried columns
- Pagination for large datasets
- Lazy loading images
- Minify CSS/JS
- GZIP compression
- Browser caching (1 month for static assets)

### 11.2. Code Quality
- PSR-12 coding standards
- DRY principle
- Separate concerns (MVC-like)
- Comments on complex logic
- Error logging (not displaying to users)

---

## 12. TIMELINE ESTIMATE

1. Database schema + Config: 1-2 hours
2. Authentication system: 2-3 hours
3. API endpoints: 3-4 hours
4. User portal: 4-5 hours
5. Admin portal: 5-6 hours
6. Responsive CSS: 3-4 hours
7. Anti-spam system: 1-2 hours
8. Testing + Bug fixes: 3-4 hours
9. Optimization: 1-2 hours

**Total: 23-32 hours** of development work

---

## 13. MAINTENANCE

- Daily database backup
- Monitor spam logs
- Update Vietnamese location data (yearly)
- Security patches for PHP/MySQL
- Performance monitoring

---

**Document Version**: 1.0
**Last Updated**: 2024-11-28
**Author**: System Architect
