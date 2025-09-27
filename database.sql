-- =====================================================
-- API Management System - MySQL Database Schema
-- =====================================================
-- Created for: Ewallet Checker API System
-- Database: MySQL/MariaDB
-- Version: 1.0
-- =====================================================

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS api_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE api_management;

-- =====================================================
-- 1. API KEYS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    daily_limit INT DEFAULT 1000,
    is_active BOOLEAN DEFAULT TRUE,
    total_hits INT DEFAULT 0,
    last_used DATETIME NULL,
    rate_limit_per_minute INT DEFAULT 60,
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. API LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_data TEXT,
    response_data TEXT,
    status_code INT,
    response_time DECIMAL(10,3),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_created_at (created_at),
    INDEX idx_status_code (status_code),
    INDEX idx_ip_address (ip_address),
    FOREIGN KEY (api_key) REFERENCES api_keys(api_key) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. BLOCKED IPS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    reason TEXT NOT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_ip_address (ip_address),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. SERVER STATUS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS server_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_name VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_time DECIMAL(10,3),
    error_message TEXT,
    INDEX idx_is_active (is_active),
    INDEX idx_server_name (server_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. EWALLET CODES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS ewallet_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. API SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS api_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    INDEX idx_is_active (is_active),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. PACKAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    daily_limit INT NOT NULL,
    duration_days INT NOT NULL,
    features TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. ADMIN CREDENTIALS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. SYSTEM LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    context TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default server statuses
INSERT INTO server_status (server_name, is_active) VALUES
('Server 1 (AriePulsa)', TRUE),
('Server 2 (Backup)', TRUE)
ON DUPLICATE KEY UPDATE server_name = VALUES(server_name);

-- Insert default ewallet codes
INSERT INTO ewallet_codes (code, name, is_active) VALUES
('shopeepay', 'ShopeePay', TRUE),
('dana', 'DANA', TRUE),
('gopay', 'GoPay', TRUE),
('ovo', 'OVO', TRUE),
('gopay_driver', 'GoPay Driver', TRUE),
('linkaja', 'LinkAja', TRUE),
('isaku', 'iSaku', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert default API settings
INSERT INTO api_settings (setting_key, setting_value) VALUES
('rate_limit_per_minute', '60'),
('maintenance_mode', '0'),
('maintenance_message', 'System is under maintenance. Please try again later.'),
('admin_url', 'admin-panel'),
('api_version', '1.0'),
('max_daily_limit', '10000'),
('default_duration_days', '30'),
('external_api_key', 'fPZKLcPwR04zMyxGZYU58rcxMTfXaFNh'),
('external_api_url', 'https://ariepulsa.my.id/api/get-nickname-ewallet')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert default admin credentials (password: admin123)
INSERT INTO admin_credentials (username, password_hash, email, is_active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', TRUE)
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Insert default API key for testing
INSERT INTO api_keys (api_key, name, whatsapp, expires_at, daily_limit, is_active) VALUES
('test_api_key_12345', 'Test User', '081234567890', DATE_ADD(NOW(), INTERVAL 30 DAY), 1000, TRUE)
ON DUPLICATE KEY UPDATE api_key = VALUES(api_key);

-- Insert default packages
INSERT INTO packages (name, price, daily_limit, duration_days, features, is_active) VALUES
('Basic Plan', 50000.00, 1000, 30, '1000 requests per day, 30 days validity, Basic support', TRUE),
('Pro Plan', 100000.00, 5000, 30, '5000 requests per day, 30 days validity, Priority support, Analytics', TRUE),
('Enterprise Plan', 250000.00, 15000, 30, '15000 requests per day, 30 days validity, Premium support, Advanced analytics, Custom features', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert sample notifications
INSERT INTO notifications (title, message, type, is_active, expires_at) VALUES
('Welcome to API Management System', 'Thank you for using our API service. Please read the documentation for proper usage.', 'info', TRUE, DATE_ADD(NOW(), INTERVAL 7 DAY)),
('System Maintenance Notice', 'Scheduled maintenance will be performed on Sunday 2:00 AM - 4:00 AM WIB.', 'warning', TRUE, DATE_ADD(NOW(), INTERVAL 3 DAY)),
('New Features Available', 'Check out our new real-time dashboard and analytics features!', 'success', TRUE, DATE_ADD(NOW(), INTERVAL 14 DAY))
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- =====================================================
-- CREATE VIEWS FOR ANALYTICS
-- =====================================================

-- Daily API usage view
CREATE OR REPLACE VIEW daily_api_usage AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_requests,
    COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) as successful_requests,
    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
    AVG(response_time) as avg_response_time
FROM api_logs 
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- API key usage summary view
CREATE OR REPLACE VIEW api_key_usage_summary AS
SELECT 
    ak.api_key,
    ak.name,
    ak.whatsapp,
    ak.daily_limit,
    ak.total_hits,
    ak.last_used,
    COUNT(al.id) as today_requests,
    COUNT(CASE WHEN al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_requests,
    COUNT(CASE WHEN al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_requests
FROM api_keys ak
LEFT JOIN api_logs al ON ak.api_key = al.api_key
GROUP BY ak.id, ak.api_key, ak.name, ak.whatsapp, ak.daily_limit, ak.total_hits, ak.last_used;

-- Server performance view
CREATE OR REPLACE VIEW server_performance AS
SELECT 
    ss.server_name,
    ss.is_active,
    ss.last_check,
    ss.response_time,
    ss.error_message,
    COUNT(al.id) as total_requests,
    AVG(al.response_time) as avg_response_time,
    COUNT(CASE WHEN al.status_code >= 200 AND al.status_code < 300 THEN 1 END) as successful_requests
FROM server_status ss
LEFT JOIN api_logs al ON ss.server_name = 'Server 1 (AriePulsa)' AND al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY ss.id, ss.server_name, ss.is_active, ss.last_check, ss.response_time, ss.error_message;

-- =====================================================
-- CREATE STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Procedure to clean old logs
CREATE PROCEDURE CleanOldLogs(IN days_to_keep INT)
BEGIN
    DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    SELECT ROW_COUNT() as deleted_records;
END //

-- Procedure to get API statistics
CREATE PROCEDURE GetAPIStatistics()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM api_keys WHERE is_active = 1) as active_api_keys,
        (SELECT COUNT(*) FROM api_logs WHERE DATE(created_at) = CURDATE()) as today_requests,
        (SELECT COUNT(*) FROM api_logs WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())) as month_requests,
        (SELECT COUNT(*) FROM blocked_ips WHERE is_active = 1 AND expires_at > NOW()) as blocked_ips,
        (SELECT AVG(response_time) FROM api_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as avg_response_time;
END //

-- Procedure to update API key usage
CREATE PROCEDURE UpdateAPIKeyUsage(IN p_api_key VARCHAR(255))
BEGIN
    UPDATE api_keys 
    SET 
        total_hits = total_hits + 1,
        last_used = NOW()
    WHERE api_key = p_api_key;
END //

DELIMITER ;

-- =====================================================
-- CREATE TRIGGERS
-- =====================================================

-- Trigger to update total_hits when new log is inserted
DELIMITER //
CREATE TRIGGER update_api_key_hits
AFTER INSERT ON api_logs
FOR EACH ROW
BEGIN
    UPDATE api_keys 
    SET total_hits = total_hits + 1,
        last_used = NEW.created_at
    WHERE api_key = NEW.api_key;
END //
DELIMITER ;

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX idx_api_logs_date_status ON api_logs(created_at, status_code);
CREATE INDEX idx_api_logs_api_key_date ON api_logs(api_key, created_at);
CREATE INDEX idx_blocked_ips_active_expires ON blocked_ips(is_active, expires_at);
CREATE INDEX idx_notifications_active_expires ON notifications(is_active, expires_at);

-- =====================================================
-- GRANT PERMISSIONS (Adjust as needed)
-- =====================================================

-- Create user for application (uncomment and adjust as needed)
-- CREATE USER IF NOT EXISTS 'api_user'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON api_management.* TO 'api_user'@'localhost';
-- FLUSH PRIVILEGES;

-- =====================================================
-- DATABASE OPTIMIZATION
-- =====================================================

-- Analyze tables for optimization
ANALYZE TABLE api_keys, api_logs, blocked_ips, server_status, ewallet_codes, api_settings, notifications, packages, admin_credentials, system_logs;

-- =====================================================
-- END OF SCRIPT
-- =====================================================

-- Success message
SELECT 'Database schema created successfully!' as message;
SELECT 'Default data inserted successfully!' as message;
SELECT 'Views and procedures created successfully!' as message;
SELECT 'System is ready for production!' as message;


