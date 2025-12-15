# HỆ THỐNG QUẢN LÝ BÁN HÀNG - DONA PAPER

Hệ thống quản lý đơn hàng và doanh số bán hàng cho DONA Paper.

## TÍNH NĂNG

### User Portal (kinhdoanh.donapaper.vn)
- Đăng nhập/đăng xuất
- Nhập đơn hàng mới với GPS location
- Xem lịch sử đơn hàng
- Filter theo trạng thái và thời gian

### Admin Portal (quanly.donapaper.vn)
- Dashboard với thống kê real-time
- Duyệt/từ chối đơn hàng
- Quản lý nhân viên
- Báo cáo kinh doanh
- Export dữ liệu CSV

### Bảo mật
- Authentication với bcrypt password hashing
- CSRF protection
- XSS protection
- SQL injection prevention (PDO)
- Spam protection (20 actions/5min → ban)
- Session security (30 min timeout)
- Login rate limiting (5 attempts/15 min)

## CÀI ĐẶT

### 1. Yêu cầu hệ thống
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+
- Apache với mod_rewrite
- cPanel (cho shared hosting)

### 2. Cài đặt Database

```sql
-- Tạo database
CREATE DATABASE dona_sales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p dona_sales < config/install.sql
```

### 3. Cấu hình

```php
// Cập nhật config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dona_sales');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Cập nhật config/config.php
define('BASE_URL', 'https://donapaper.vn');
define('ADMIN_URL', 'https://quanly.donapaper.vn');
define('USER_URL', 'https://kinhdoanh.donapaper.vn');
```

### 4. Upload lên cPanel

#### Via FTP:
1. Upload toàn bộ thư mục `sales-system/` vào `public_html/`
2. Set permissions: `chmod 755 uploads/id_cards/`

#### Via Git:
```bash
cd public_html
git clone <your-repo-url> .
chmod 755 uploads/id_cards/
```

### 5. Cấu hình Subdomains trong cPanel

**User Portal (kinhdoanh.donapaper.vn):**
- Document Root: `/public_html/user`

**Admin Portal (quanly.donapaper.vn):**
- Document Root: `/public_html/admin`

### 6. Tài khoản mặc định

```
Username: admin
Password: Admin@123
```

**QUAN TRỌNG:** Đổi mật khẩu ngay sau lần đăng nhập đầu tiên!

## CẤU TRÚC THƯ MỤC

```
sales-system/
├── admin/                  # Admin portal
│   ├── index.php          # Dashboard
│   ├── login.php          # Admin login
│   └── logout.php
├── user/                   # User portal
│   ├── index.php          # Order form
│   ├── history.php        # Order history
│   ├── login.php          # User login
│   └── logout.php
├── api/                    # REST APIs
│   ├── auth.php           # Authentication
│   ├── orders.php         # Orders CRUD
│   ├── users.php          # Users management
│   ├── locations.php      # VN locations
│   └── reports.php        # Statistics
├── assets/                 # Frontend assets
│   ├── css/
│   ├── js/
│   └── images/
├── config/                 # Configuration
│   ├── config.php
│   ├── database.php
│   └── install.sql
├── includes/               # Core libraries
│   ├── auth.php
│   ├── db.php
│   ├── functions.php
│   └── spam_protection.php
├── uploads/                # File uploads
│   └── id_cards/
└── .htaccess              # Apache config
```

## SỬ DỤNG

### Tạo user mới

```php
// Qua API hoặc trực tiếp trong database
INSERT INTO users (username, password, full_name, is_admin)
VALUES ('user1', '$2y$10$...', 'Nguyen Van A', 0);
```

### Export dữ liệu

```bash
# Via API
GET /api/orders.php?action=export&status=approved

# Via command line
mysql -u root -p dona_sales -e "SELECT * FROM orders" > orders.csv
```

## BẢO TRÌ

### Cleanup spam tracking (chạy hàng ngày via cron)

```bash
0 2 * * * php /path/to/cleanup.php
```

```php
// cleanup.php
<?php
require 'config/config.php';
require 'includes/spam_protection.php';
SpamProtection::cleanup();
```

### Backup database

```bash
mysqldump -u root -p dona_sales > backup_$(date +%Y%m%d).sql
```

## TROUBLESHOOTING

### Lỗi 500 Internal Server Error
- Kiểm tra file permissions
- Kiểm tra PHP error log: `tail -f /path/to/error.log`
- Kiểm tra `.htaccess` syntax

### Session không hoạt động
- Kiểm tra `session.save_path` trong PHP
- Đảm bảo thư mục session có quyền ghi

### Database connection failed
- Kiểm tra credentials trong `config/database.php`
- Kiểm tra MySQL service đang chạy
- Kiểm tra firewall cho phép kết nối MySQL

### CSS/JS không load
- Kiểm tra đường dẫn trong HTML
- Kiểm tra `.htaccess` rewrite rules
- Clear browser cache

## SUPPORT

- Email: support@donapaper.vn
- Hotline: 1900 xxxx

## LICENSE

Proprietary - DONA Paper © 2024
