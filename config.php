<?php
/**
 * Database Configuration
 */

// Database Configuration - MySQL
define('DB_HOST', '195.88.211.130');
define('DB_NAME', 'apivalid_tes');
define('DB_USER', 'apivalid_tes');
define('DB_PASS', 'apivalid_tes');
define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

// API Configuration
define('API_VERSION', '1.0.0');
define('DEFAULT_RATE_LIMIT', 1000); // Default limit per day
define('DEFAULT_DURATION_DAYS', 30); // Default API key duration

// Security Configuration
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('BLOCK_DURATION_HOURS', 24); // IP block duration

// Server Configuration
define('SERVER_1_NAME', 'AriePulsa API');
define('SERVER_2_NAME', 'OrderKuota API');

// Create database if not exists
function initDatabase() {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables
        $pdo->exec("
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
                rate_limit_per_minute INT DEFAULT 60
            )
        ");
        
        $pdo->exec("
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
                INDEX idx_status_code (status_code)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS blocked_ips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) UNIQUE NOT NULL,
                reason TEXT NOT NULL,
                blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_ip_address (ip_address),
                INDEX idx_is_active (is_active)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS server_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                server_name VARCHAR(255) UNIQUE NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                response_time DECIMAL(10,3),
                error_message TEXT,
                INDEX idx_is_active (is_active)
            )
        ");
        
        // Create additional tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ewallet_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(100) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'info',
                expires_at DATETIME NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_is_active (is_active)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS packages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                daily_limit INT NOT NULL,
                minute_limit INT NOT NULL,
                duration_days INT NOT NULL,
                features TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert default server status
        $pdo->exec("
            INSERT IGNORE INTO server_status (server_name, is_active) VALUES 
            ('" . SERVER_1_NAME . "', TRUE),
            ('" . SERVER_2_NAME . "', TRUE)
        ");
        
        // Insert default ewallet codes
        $pdo->exec("
            INSERT IGNORE INTO ewallet_codes (code, name) VALUES 
            ('dana', 'DANA'),
            ('gopay', 'GoPay'),
            ('ovo', 'OVO'),
            ('shopeepay', 'ShopeePay'),
            ('linkaja', 'LinkAja'),
            ('isaku', 'iSaku')
        ");
        
        // Insert default API settings
        $pdo->exec("
            INSERT IGNORE INTO api_settings (setting_key, setting_value) VALUES 
            ('rate_limit_per_minute', '60'),
            ('maintenance_mode', '0'),
            ('maintenance_message', 'System is under maintenance. Please try again later.'),
            ('admin_url', 'admin-panel')
        ");
        
        // Check if admin API key exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_keys WHERE name = 'Admin'");
        $stmt->execute();
        $adminExists = $stmt->fetchColumn();
        
        if (!$adminExists) {
            // Create default admin API key
            $adminKey = 'admin_' . bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO api_keys (api_key, name, whatsapp, expires_at, daily_limit, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $adminKey,
                'Admin',
                '6281234567890',
                date('Y-m-d H:i:s', strtotime('+1 year')),
                999999,
                1
            ]);
            
            echo "Database initialized successfully. Admin API Key: " . $adminKey;
        } else {
            echo "Database already initialized.";
        }
        
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage();
        echo "\nPlease create database 'api_management' and ensure MySQL is running.";
    }
}

// Initialize database
initDatabase();
?>
