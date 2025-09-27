<?php
/**
 * Database Configuration - Enhanced for API Validation System
 */

// Database Configuration - MySQL
define('DB_HOST', '195.88.211.130');
define('DB_NAME', 'apivalid_tes');
define('DB_USER', 'apivalid_tes');
define('DB_PASS', 'apivalid_tes');
define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

// Site Configuration
define('SITE_NAME', 'API Validation System');
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);

// API Configuration
define('API_VERSION', '1.0.0');
define('DEFAULT_RATE_LIMIT', 1000);
define('DEFAULT_DURATION_DAYS', 30);

// Security Configuration
define('ADMIN_SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('BLOCK_DURATION_HOURS', 24);

// Server Configuration
define('SERVER_1_NAME', 'AriePulsa API');
define('SERVER_2_NAME', 'OrderKuota API');

// Ewallet Codes
define('EWALLET_CODES', ['shopeepay', 'dana', 'gopay', 'ovo', 'gopay_driver', 'linkaja', 'isaku']);

// Bank Codes
define('BANK_CODES', [
    '002' => 'BRI',
    '008' => 'Mandiri',
    '009' => 'BNI',
    '014' => 'BCA',
    '022' => 'CIMB Niaga',
    '213' => 'BTPN',
    '451' => 'BSI'
]);

// External API Configuration
define('ARIEPULSA_API_KEY', 'fPZKLcPwR04zMyxGZYU58rcxMTfXaFNh');
define('ARIEPULSA_API_URL', 'https://ariepulsa.my.id/api/get-nickname-ewallet');

/**
 * Get database connection
 */
function getDatabase() {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}

/**
 * Initialize database tables
 */
function initDatabase() {
    try {
        $pdo = getDatabase();
        
        // Create api_keys table with user_id
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                api_key VARCHAR(255) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                whatsapp VARCHAR(20) NOT NULL,
                user_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                daily_limit INT DEFAULT 1000,
                is_active BOOLEAN DEFAULT TRUE,
                total_hits INT DEFAULT 0,
                last_used DATETIME NULL,
                rate_limit_per_minute INT DEFAULT 60,
                INDEX idx_api_key (api_key),
                INDEX idx_user_id (user_id),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(255) NOT NULL,
                whatsapp VARCHAR(20) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME NULL,
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create api_logs table
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create other required tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS blocked_ips (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) UNIQUE NOT NULL,
                reason TEXT NOT NULL,
                blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_ip_address (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS server_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                server_name VARCHAR(255) UNIQUE NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                response_time DECIMAL(10,3),
                error_message TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ewallet_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(100) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'info',
                expires_at DATETIME NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS packages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                daily_limit INT NOT NULL,
                duration_days INT NOT NULL,
                features TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Insert default data
        $pdo->exec("
            INSERT IGNORE INTO server_status (server_name, is_active) VALUES 
            ('" . SERVER_1_NAME . "', TRUE),
            ('" . SERVER_2_NAME . "', TRUE)
        ");
        
        $pdo->exec("
            INSERT IGNORE INTO ewallet_codes (code, name) VALUES 
            ('dana', 'DANA'),
            ('gopay', 'GoPay'),
            ('ovo', 'OVO'),
            ('shopeepay', 'ShopeePay'),
            ('linkaja', 'LinkAja'),
            ('isaku', 'iSaku'),
            ('gopay_driver', 'GoPay Driver')
        ");
        
        $pdo->exec("
            INSERT IGNORE INTO api_settings (setting_key, setting_value) VALUES 
            ('rate_limit_per_minute', '60'),
            ('maintenance_mode', '0'),
            ('maintenance_message', 'System is under maintenance. Please try again later.'),
            ('ariepulsa_api_key', '" . ARIEPULSA_API_KEY . "'),
            ('ariepulsa_api_url', '" . ARIEPULSA_API_URL . "')
        ");
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception('Database initialization failed: ' . $e->getMessage());
    }
}

// Initialize database on first load
try {
    initDatabase();
} catch (Exception $e) {
    error_log('Database initialization error: ' . $e->getMessage());
}
?>