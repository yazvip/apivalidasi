<?php
/**
 * Installation Script - API Management System
 * MySQL Database Only
 */

// Disable error reporting during installation
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already installed
if (file_exists('install.lock')) {
    // Redirect to admin panel if already installed
    header('Location: admin/index.php');
    exit;
}

// Installation steps
$steps = [
    'welcome' => 'Selamat Datang',
    'database' => 'Konfigurasi Database MySQL',
    'admin' => 'Setup Admin',
    'settings' => 'Pengaturan Awal',
    'complete' => 'Instalasi Selesai'
];

$currentStep = $_GET['step'] ?? 'welcome';
$error = '';
$success = '';

// Handle form submissions
if ($_POST) {
    switch ($currentStep) {
        case 'database':
            $result = handleDatabaseSetup();
            if ($result['success']) {
                header('Location: ?step=admin');
                exit;
            } else {
                $error = $result['error'];
            }
            break;
            
        case 'admin':
            $result = handleAdminSetup();
            if ($result['success']) {
                header('Location: ?step=settings');
                exit;
            } else {
                $error = $result['error'];
            }
            break;
            
        case 'settings':
            $result = handleSettingsSetup();
            if ($result['success']) {
                header('Location: ?step=complete');
                exit;
            } else {
                $error = $result['error'];
            }
            break;
    }
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'delete_install') {
    header('Content-Type: application/json');
    
    try {
        // Check if install.lock exists (installation completed)
        if (!file_exists('install.lock')) {
            echo json_encode(['success' => false, 'error' => 'Installation not completed']);
            exit;
        }
        
        // Delete install.php
        if (unlink(__FILE__)) {
            echo json_encode(['success' => true, 'message' => 'install.php deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete install.php']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

function handleDatabaseSetup() {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbName = $_POST['db_name'] ?? '';
    $dbUser = $_POST['db_user'] ?? '';
    $dbPass = $_POST['db_pass'] ?? '';
    
    if (empty($dbName) || empty($dbUser)) {
        return ['success' => false, 'error' => 'Database name dan user harus diisi'];
    }
    
    try {
        // Test database connection
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create config file
        $configContent = generateConfigFile($dbHost, $dbName, $dbUser, $dbPass);
        file_put_contents('config.php', $configContent);
        
        // Create tables
        try {
            createTables($pdo);
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create tables: ' . $e->getMessage()];
        }
        
        // Store database info in session
        $_SESSION['db_config'] = [
            'host' => $dbHost,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass
        ];
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function handleAdminSetup() {
    $adminUser = $_POST['admin_user'] ?? '';
    $adminPass = $_POST['admin_pass'] ?? '';
    $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';
    
    if (empty($adminUser) || empty($adminPass)) {
        return ['success' => false, 'error' => 'Username dan password admin harus diisi'];
    }
    
    if ($adminPass !== $adminPassConfirm) {
        return ['success' => false, 'error' => 'Password dan konfirmasi password tidak sama'];
    }
    
    if (strlen($adminPass) < 6) {
        return ['success' => false, 'error' => 'Password minimal 6 karakter'];
    }
    
    try {
        // Get database connection from session
        $dbConfig = $_SESSION['db_config'] ?? null;
        if (!$dbConfig) {
            return ['success' => false, 'error' => 'Database configuration not found'];
        }
        
        $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4", 
                      $dbConfig['user'], $dbConfig['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Store admin credentials in database
        $hashedPassword = password_hash($adminPass, PASSWORD_DEFAULT);
        
        // Insert admin user into admin_users table
        $stmt = $pdo->prepare("
            INSERT INTO admin_users (username, password, email, full_name, is_active, created_at) 
            VALUES (?, ?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE 
            password = VALUES(password),
            email = VALUES(email),
            full_name = VALUES(full_name),
            updated_at = NOW()
        ");
        $stmt->execute([
            $adminUser, 
            $hashedPassword, 
            $_POST['admin_email'] ?? $adminUser . '@admin.local',
            $_POST['admin_name'] ?? 'Administrator'
        ]);
        
        // Store admin credentials in session for next step
        $_SESSION['admin_credentials'] = [
            'username' => $adminUser,
            'password' => $hashedPassword
        ];
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function handleSettingsSetup() {
    $siteName = $_POST['site_name'] ?? 'API Management System';
    $siteUrl = $_POST['site_url'] ?? '';
    $adminEmail = $_POST['admin_email'] ?? '';
    
    if (empty($siteUrl)) {
        return ['success' => false, 'error' => 'Site URL harus diisi'];
    }
    
    try {
        // Get database connection from session
        $dbConfig = $_SESSION['db_config'] ?? null;
        if (!$dbConfig) {
            return ['success' => false, 'error' => 'Database configuration not found'];
        }
        
        $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4", 
                      $dbConfig['user'], $dbConfig['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Store settings in database
        $settings = [
            'site_name' => $siteName,
            'site_url' => $siteUrl,
            'admin_email' => $adminEmail,
            'api_version' => '1.0',
            'maintenance_mode' => '0',
            'max_daily_requests' => '10000',
            'rate_limit_per_minute' => '60',
            'enable_logging' => '1',
            'log_retention_days' => '30',
            'enable_notifications' => '1',
            'default_package_duration' => '30',
            'default_daily_limit' => '1000',
            'enable_api_docs' => '1',
            'enable_testing_tool' => '1',
            'enable_analytics' => '1',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'Y-m-d H:i:s',
            'currency' => 'IDR',
            'language' => 'id',
            'theme' => 'default'
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO api_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, $value]);
        }
        
        // Store settings in session for completion
        $_SESSION['site_settings'] = [
            'site_name' => $siteName,
            'site_url' => $siteUrl,
            'admin_email' => $adminEmail
        ];
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function generateConfigFile($dbHost, $dbName, $dbUser, $dbPass) {
    return "<?php
/**
 * Database Configuration - MySQL Only
 */

// Database Configuration
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');
define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

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

// Create database if not exists
function initDatabase() {
    try {
        \$pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables
        \$pdo->exec(\"
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
        \");
        
        \$pdo->exec(\"
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
        \");
        
        \$pdo->exec(\"
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
        \");
        
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS server_status (
                id INT AUTO_INCREMENT PRIMARY KEY,
                server_name VARCHAR(255) UNIQUE NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                response_time DECIMAL(10,3),
                error_message TEXT,
                INDEX idx_is_active (is_active)
            )
        \");
        
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS ewallet_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(100) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE
            )
        \");
        
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS api_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        \");
        
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'info',
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_is_active (is_active)
            )
        \");
        
        \$pdo->exec(\"
            CREATE TABLE IF NOT EXISTS packages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                daily_limit INT NOT NULL,
                duration_days INT NOT NULL,
                features TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        \");

        // Insert default data
        \$pdo->exec(\"
            INSERT IGNORE INTO server_status (server_name, is_active) VALUES 
            ('AriePulsa API', TRUE),
            ('OrderKuota API', TRUE)
        \");
        
        \$pdo->exec(\"
            INSERT IGNORE INTO ewallet_codes (code, name) VALUES 
            ('dana', 'DANA'),
            ('gopay', 'GoPay'),
            ('ovo', 'OVO'),
            ('shopeepay', 'ShopeePay'),
            ('linkaja', 'LinkAja'),
            ('isaku', 'iSaku')
        \");
        
        \$pdo->exec(\"
            INSERT IGNORE INTO api_settings (setting_key, setting_value) VALUES 
            ('rate_limit_per_minute', '60'),
            ('maintenance_mode', '0'),
            ('maintenance_message', 'System is under maintenance. Please try again later.'),
            ('admin_url', 'admin-panel')
        \");
        
        return true;
        
    } catch (PDOException \$e) {
        throw new Exception('Database connection failed: ' . \$e->getMessage());
    }
}

// Get database connection
function getDatabase() {
    return new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

// Initialize database
initDatabase();
?>";
}

function createTables($pdo) {
    // Use simplified SQL file for installation
    $sqlFile = 'install_tables.sql';
    if (!file_exists($sqlFile)) {
        // Fallback to full database.sql
        $sqlFile = 'database.sql';
    }
    
    if (!file_exists($sqlFile)) {
        throw new Exception('Database schema file not found');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and clean up SQL
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^(CREATE DATABASE|USE)/i', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Log error but continue with other statements
                error_log("SQL Error: " . $e->getMessage() . " - Statement: " . substr($statement, 0, 100));
                // Only throw if it's a critical error (not table already exists)
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }
    }
    
    return true;
}

function createAdminUser($pdo, $username, $password) {
    // This function is now handled in handleAdminSetup()
    // Keeping for backward compatibility
    return true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - API Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }

        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .install-body {
            padding: 2rem;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step.active {
            background: var(--primary-color);
            color: white;
        }

        .step.completed {
            background: var(--success-color);
            color: white;
        }

        .step.pending {
            background: #e2e8f0;
            color: #64748b;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .btn-install {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-install:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1 class="mb-3">
                <i class="fas fa-rocket me-2"></i>
                API Management System
            </h1>
            <p class="mb-0">Instalasi Lengkap untuk cPanel Hosting</p>
        </div>

        <div class="install-body">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <?php foreach ($steps as $key => $name): ?>
                <div class="step <?= $key === $currentStep ? 'active' : (array_search($key, array_keys($steps)) < array_search($currentStep, array_keys($steps)) ? 'completed' : 'pending') ?>">
                    <?= array_search($key, array_keys($steps)) + 1 ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <!-- Step Content -->
            <?php if ($currentStep === 'welcome'): ?>
            <div class="text-center">
                <h2 class="mb-4">Selamat Datang di API Management System</h2>
                <p class="text-muted mb-4">
                    Sistem manajemen API yang powerful untuk validasi e-wallet dan bank account.
                    Mari kita mulai instalasi step by step.
                </p>
                <div class="row text-start">
                    <div class="col-md-6">
                        <h5><i class="fas fa-check text-success me-2"></i>Fitur Utama</h5>
                        <ul class="list-unstyled">
                            <li>• API Key Management</li>
                            <li>• Rate Limiting</li>
                            <li>• Real-time Monitoring</li>
                            <li>• Admin Panel</li>
                            <li>• User Dashboard</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-database text-primary me-2"></i>Database</h5>
                        <ul class="list-unstyled">
                            <li>• MySQL Database</li>
                            <li>• Auto Table Creation</li>
                            <li>• Index Optimization</li>
                            <li>• Data Migration</li>
                        </ul>
                    </div>
                </div>
                <a href="?step=database" class="btn btn-install btn-lg">
                    <i class="fas fa-arrow-right me-2"></i>
                    Mulai Instalasi
                </a>
            </div>

            <?php elseif ($currentStep === 'database'): ?>
            <div>
                <h2 class="mb-4">Konfigurasi Database MySQL</h2>
                <p class="text-muted mb-4">
                    Masukkan informasi database MySQL Anda. Pastikan database sudah dibuat di cPanel.
                </p>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Database Host</label>
                                <input type="text" class="form-control" name="db_host" value="localhost" required>
                                <small class="text-muted">Biasanya 'localhost' untuk cPanel</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Database Name</label>
                                <input type="text" class="form-control" name="db_name" placeholder="api_management" required>
                                <small class="text-muted">Nama database yang sudah dibuat</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Database User</label>
                                <input type="text" class="form-control" name="db_user" placeholder="username" required>
                                <small class="text-muted">Username database</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Database Password</label>
                                <input type="password" class="form-control" name="db_pass" placeholder="password">
                                <small class="text-muted">Password database</small>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="?step=welcome" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Kembali
                        </a>
                        <button type="submit" class="btn btn-install">
                            <i class="fas fa-database me-2"></i>
                            Test & Lanjutkan
                        </button>
                    </div>
                </form>
            </div>

            <?php elseif ($currentStep === 'admin'): ?>
            <div>
                <h2 class="mb-4">Setup Admin Account</h2>
                <p class="text-muted mb-4">
                    Buat akun admin untuk mengakses admin panel.
                </p>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Admin Username</label>
                                <input type="text" class="form-control" name="admin_user" placeholder="admin" required>
                                <small class="text-muted">Username untuk login admin panel</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Admin Password</label>
                                <input type="password" class="form-control" name="admin_pass" placeholder="password" required>
                                <small class="text-muted">Minimal 6 karakter</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="admin_name" placeholder="Administrator" required>
                                <small class="text-muted">Nama lengkap admin</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email Admin</label>
                                <input type="email" class="form-control" name="admin_email" placeholder="admin@example.com" required>
                                <small class="text-muted">Email untuk notifikasi</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" name="admin_pass_confirm" placeholder="konfirmasi password" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="?step=database" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Kembali
                        </a>
                        <button type="submit" class="btn btn-install">
                            <i class="fas fa-user-shield me-2"></i>
                            Buat Admin
                        </button>
                    </div>
                </form>
            </div>

            <?php elseif ($currentStep === 'settings'): ?>
            <div>
                <h2 class="mb-4">Pengaturan Awal</h2>
                <p class="text-muted mb-4">
                    Konfigurasi pengaturan dasar sistem.
                </p>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="site_name" value="API Management System">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Site URL</label>
                        <input type="url" class="form-control" name="site_url" placeholder="https://yourdomain.com" required>
                        <small class="text-muted">URL lengkap website Anda</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admin Email</label>
                        <input type="email" class="form-control" name="admin_email" placeholder="admin@yourdomain.com">
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="?step=admin" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Kembali
                        </a>
                        <button type="submit" class="btn btn-install">
                            <i class="fas fa-cog me-2"></i>
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            <?php elseif ($currentStep === 'complete'): ?>
            <div class="text-center">
                <h2 class="mb-4 text-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Instalasi Berhasil!
                </h2>
                <p class="text-muted mb-4">
                    API Management System telah berhasil diinstal dan siap digunakan.
                </p>
                
                <?php
                // Display installation summary
                if (isset($_SESSION['admin_credentials']) && isset($_SESSION['site_settings'])) {
                        echo "<div class='alert alert-success'>";
                    echo "<h5>Installation Summary</h5>";
                    echo "<p><strong>Site Name:</strong> " . htmlspecialchars($_SESSION['site_settings']['site_name']) . "</p>";
                    echo "<p><strong>Site URL:</strong> " . htmlspecialchars($_SESSION['site_settings']['site_url']) . "</p>";
                    echo "<p><strong>Admin Username:</strong> " . htmlspecialchars($_SESSION['admin_credentials']['username']) . "</p>";
                    echo "<p><strong>Admin Email:</strong> " . htmlspecialchars($_SESSION['site_settings']['admin_email']) . "</p>";
                        echo "</div>";
                    
                    // Create install.lock file
                    file_put_contents('install.lock', json_encode([
                        'installed_at' => date('Y-m-d H:i:s'),
                        'version' => '1.0',
                        'site_name' => $_SESSION['site_settings']['site_name'],
                        'site_url' => $_SESSION['site_settings']['site_url'],
                        'admin_username' => $_SESSION['admin_credentials']['username']
                    ]));
                        
                        // Clear session
                        session_destroy();
                        
                    // Auto delete install.php after successful installation
                    echo "<div class='alert alert-info mt-3'>";
                    echo "<i class='fas fa-info-circle me-2'></i>";
                    echo "<strong>Security Notice:</strong> File install.php akan dihapus otomatis dalam 5 detik untuk keamanan.";
                    echo "</div>";
                    
                    echo "<script>";
                    echo "setTimeout(function() {";
                    echo "  // Show countdown";
                    echo "  let countdown = 5;";
                    echo "  const countdownElement = document.createElement('div');";
                    echo "  countdownElement.className = 'alert alert-warning mt-2';";
                    echo "  countdownElement.innerHTML = '<i class=\"fas fa-clock me-2\"></i>Menghapus install.php dalam ' + countdown + ' detik...';";
                    echo "  document.querySelector('.alert-info').after(countdownElement);";
                    echo "  ";
                    echo "  const timer = setInterval(function() {";
                    echo "    countdown--;";
                    echo "    countdownElement.innerHTML = '<i class=\"fas fa-clock me-2\"></i>Menghapus install.php dalam ' + countdown + ' detik...';";
                    echo "    ";
                    echo "    if (countdown <= 0) {";
                    echo "      clearInterval(timer);";
                    echo "      countdownElement.innerHTML = '<i class=\"fas fa-trash me-2\"></i>Menghapus install.php...';";
                    echo "      ";
                    echo "      // Delete install.php via AJAX";
                    echo "      fetch('?action=delete_install', {method: 'POST'})";
                    echo "        .then(response => response.json())";
                    echo "        .then(data => {";
                    echo "          if (data.success) {";
                    echo "            countdownElement.className = 'alert alert-success mt-2';";
                    echo "            countdownElement.innerHTML = '<i class=\"fas fa-check me-2\"></i>install.php berhasil dihapus! Redirecting...';";
                    echo "            setTimeout(() => {";
                    echo "              window.location.href = 'admin/index.php';";
                    echo "            }, 2000);";
                    echo "          } else {";
                    echo "            countdownElement.className = 'alert alert-danger mt-2';";
                    echo "            countdownElement.innerHTML = '<i class=\"fas fa-exclamation-triangle me-2\"></i>Gagal menghapus install.php: ' + data.error;";
                    echo "          }";
                    echo "        })";
                    echo "        .catch(error => {";
                    echo "          countdownElement.className = 'alert alert-danger mt-2';";
                    echo "          countdownElement.innerHTML = '<i class=\"fas fa-exclamation-triangle me-2\"></i>Error: ' + error.message;";
                    echo "        });";
                    echo "    }";
                    echo "  }, 1000);";
                    echo "}, 2000);";
                    echo "</script>";
                    
                    // Add manual delete function
                    echo "<script>";
                    echo "function deleteInstallManually() {";
                    echo "  if (confirm('Yakin ingin menghapus file install.php? Tindakan ini tidak dapat dibatalkan!')) {";
                    echo "    const btn = document.getElementById('deleteBtn');";
                    echo "    btn.disabled = true;";
                    echo "    btn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-2\"></i>Menghapus...';";
                    echo "    ";
                    echo "    fetch('?action=delete_install', {method: 'POST'})";
                    echo "      .then(response => response.json())";
                    echo "      .then(data => {";
                    echo "        if (data.success) {";
                    echo "          btn.className = 'btn btn-success';";
                    echo "          btn.innerHTML = '<i class=\"fas fa-check me-2\"></i>Berhasil dihapus!';";
                    echo "          setTimeout(() => {";
                    echo "            window.location.href = 'admin/index.php';";
                    echo "          }, 2000);";
                    echo "        } else {";
                    echo "          btn.className = 'btn btn-danger';";
                    echo "          btn.innerHTML = '<i class=\"fas fa-exclamation-triangle me-2\"></i>Gagal: ' + data.error;";
                    echo "          setTimeout(() => {";
                    echo "            btn.disabled = false;";
                    echo "            btn.className = 'btn btn-outline-danger';";
                    echo "            btn.innerHTML = '<i class=\"fas fa-trash me-2\"></i>Hapus install.php';";
                    echo "          }, 3000);";
                    echo "        }";
                    echo "      })";
                    echo "      .catch(error => {";
                    echo "        btn.className = 'btn btn-danger';";
                    echo "        btn.innerHTML = '<i class=\"fas fa-exclamation-triangle me-2\"></i>Error: ' + error.message;";
                    echo "        setTimeout(() => {";
                    echo "          btn.disabled = false;";
                    echo "          btn.className = 'btn btn-outline-danger';";
                    echo "          btn.innerHTML = '<i class=\"fas fa-trash me-2\"></i>Hapus install.php';";
                    echo "        }, 3000);";
                    echo "      });";
                    echo "  }";
                    echo "}";
                    echo "</script>";
                }
                ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-cog me-2"></i>Admin Panel</h5>
                                <p class="text-muted">Kelola sistem, API keys, dan monitoring</p>
                                <a href="admin/index.php" class="btn btn-outline-primary">Buka Admin Panel</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-shield-alt me-2"></i>Keamanan</h5>
                                <p class="text-muted">Hapus file instalasi untuk keamanan</p>
                                <button onclick="deleteInstallManually()" class="btn btn-outline-danger" id="deleteBtn">
                                    <i class="fas fa-trash me-2"></i>Hapus install.php
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-home me-2"></i>Landing Page</h5>
                                <p class="text-muted">Halaman utama untuk pengguna</p>
                                <a href="/" class="btn btn-outline-primary">Buka Website</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>