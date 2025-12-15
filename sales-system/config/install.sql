-- DONA PAPER SALES MANAGEMENT SYSTEM
-- Database Schema Installation Script
-- Version: 1.0
-- Date: 2024-11-28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `gender` ENUM('Nam', 'Nữ', 'Khác') DEFAULT NULL,
    `address_detail` TEXT DEFAULT NULL,
    `id_card_front` VARCHAR(255) DEFAULT NULL,
    `id_card_back` VARCHAR(255) DEFAULT NULL,
    `province_id` INT(11) DEFAULT NULL,
    `district_id` INT(11) DEFAULT NULL,
    `ward_id` INT(11) DEFAULT NULL,
    `position` VARCHAR(100) DEFAULT NULL,
    `contact_info` VARCHAR(100) DEFAULT NULL,
    `is_admin` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_banned` TINYINT(1) DEFAULT 0,
    `ban_until` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_username` (`username`),
    KEY `idx_is_admin` (`is_admin`),
    KEY `idx_province` (`province_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. PROVINCES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `provinces` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(10) UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. DISTRICTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `districts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(10) UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) DEFAULT NULL,
    `province_id` INT(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_province_id` (`province_id`),
    KEY `idx_code` (`code`),
    FOREIGN KEY (`province_id`) REFERENCES `provinces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. WARDS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `wards` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(10) UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) DEFAULT NULL,
    `district_id` INT(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_district_id` (`district_id`),
    KEY `idx_code` (`code`),
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. ORDERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_address` TEXT NOT NULL,
    `customer_social` VARCHAR(255) DEFAULT NULL,
    `customer_latitude` DECIMAL(10, 8) DEFAULT NULL,
    `customer_longitude` DECIMAL(11, 8) DEFAULT NULL,
    `customer_type` VARCHAR(50) DEFAULT NULL,
    `purchase_price` DECIMAL(15, 2) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `total_revenue` DECIMAL(15, 2) NOT NULL,
    `other_costs` DECIMAL(15, 2) DEFAULT 0,
    `profit` DECIMAL(15, 2) GENERATED ALWAYS AS (`total_revenue` - `purchase_price` - `other_costs`) STORED,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_message` TEXT DEFAULT NULL,
    `admin_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. SPAM TRACKING TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `spam_tracking` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `action_count` INT(11) DEFAULT 1,
    `window_start` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ban_until` TIMESTAMP NULL DEFAULT NULL,
    `permanent_ban` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_user_ip` (`user_id`, `ip_address`),
    KEY `idx_ip` (`ip_address`),
    KEY `idx_ban_until` (`ban_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. LOGIN ATTEMPTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `success` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_username` (`username`),
    KEY `idx_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. INSERT DEFAULT ADMIN USER
-- ============================================
-- Username: admin
-- Password: Admin@123
INSERT INTO `users` (`username`, `password`, `full_name`, `is_admin`, `is_active`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 1, 1);

-- ============================================
-- 9. INSERT SAMPLE PROVINCES (Top 10)
-- ============================================
INSERT INTO `provinces` (`code`, `name`, `name_en`) VALUES
('01', 'Hà Nội', 'Ha Noi'),
('79', 'Hồ Chí Minh', 'Ho Chi Minh'),
('48', 'Đà Nẵng', 'Da Nang'),
('92', 'Cần Thơ', 'Can Tho'),
('31', 'Hải Phòng', 'Hai Phong'),
('26', 'Vĩnh Phúc', 'Vinh Phuc'),
('27', 'Bắc Ninh', 'Bac Ninh'),
('30', 'Hải Dương', 'Hai Duong'),
('33', 'Hưng Yên', 'Hung Yen'),
('40', 'Nghệ An', 'Nghe An');

-- ============================================
-- INSTALLATION COMPLETE
-- ============================================
-- Next steps:
-- 1. Update config/database.php with your credentials
-- 2. Import full Vietnamese location data (provinces/districts/wards)
-- 3. Create additional admin users if needed
-- 4. Configure .htaccess for pretty URLs and security
-- Default admin login: admin / Admin@123

-- ============================================
-- 10. PERFORMANCE OPTIMIZATION INDEXES
-- ============================================

-- Add composite indexes for frequently used query combinations
ALTER TABLE `orders`
    ADD KEY IF NOT EXISTS `idx_user_status` (`user_id`, `status`),
    ADD KEY IF NOT EXISTS `idx_status_created` (`status`, `created_at`),
    ADD KEY IF NOT EXISTS `idx_customer_phone` (`customer_phone`),
    ADD KEY IF NOT EXISTS `idx_customer_name` (`customer_name`(50));

-- Add indexes for users table
ALTER TABLE `users`
    ADD KEY IF NOT EXISTS `idx_is_active` (`is_active`),
    ADD KEY IF NOT EXISTS `idx_last_login` (`last_login`),
    ADD KEY IF NOT EXISTS `idx_district` (`district_id`),
    ADD KEY IF NOT EXISTS `idx_ward` (`ward_id`);

-- Add indexes for spam tracking
ALTER TABLE `spam_tracking`
    ADD KEY IF NOT EXISTS `idx_window_start` (`window_start`),
    ADD KEY IF NOT EXISTS `idx_permanent_ban` (`permanent_ban`);

-- Add indexes for login attempts
ALTER TABLE `login_attempts`
    ADD KEY IF NOT EXISTS `idx_attempted_at` (`attempted_at`),
    ADD KEY IF NOT EXISTS `idx_username_ip` (`username`, `ip_address`);

-- ============================================
-- 11. DATABASE OPTIMIZATION SETTINGS
-- ============================================

-- Note: These are recommendations for the database configuration
-- Adjust based on available server resources

-- Increase InnoDB buffer pool size (in my.cnf or my.ini)
-- innodb_buffer_pool_size = 256M (or 1G for production)

-- Enable query cache
-- query_cache_type = 1
-- query_cache_size = 64M

-- Optimize tables
OPTIMIZE TABLE `users`;
OPTIMIZE TABLE `orders`;
OPTIMIZE TABLE `provinces`;
OPTIMIZE TABLE `districts`;
OPTIMIZE TABLE `wards`;
OPTIMIZE TABLE `spam_tracking`;
OPTIMIZE TABLE `login_attempts`;
