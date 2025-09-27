<?php
/**
 * User Profile Management - Android-like UI
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
require_once '../api_auth.php';

$apiAuth = new APIAuth();
$message = '';
$error = '';

// Check if user is logged in
if (!isset($_SESSION['user_api_key'])) {
    header('Location: index.php');
    exit;
}

$apiKey = $_SESSION['user_api_key'];

// Get user information
try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM api_keys WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $user = null;
}

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'regenerate_api_key') {
        try {
            $pdo = getDatabase();
            
            // Generate new API key
            $newApiKey = 'user_' . bin2hex(random_bytes(16));
            
            // Update API key
            $stmt = $pdo->prepare("UPDATE api_keys SET api_key = ?, updated_at = NOW() WHERE api_key = ?");
            $stmt->execute([$newApiKey, $apiKey]);
            
            // Update session
            $_SESSION['user_api_key'] = $newApiKey;
            $apiKey = $newApiKey;
            
            $message = "API key regenerated successfully";
        } catch (Exception $e) {
            $error = "Error regenerating API key: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 15px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }

        .btn-danger {
            background: var(--gradient-danger);
            border: none;
            border-radius: 15px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);
        }

        .api-key-display {
            background: var(--light-color);
            border: 2px dashed #e2e8f0;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .api-key-text {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            word-break: break-all;
        }

        .copy-btn {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .nav-item {
            flex: 1;
            text-align: center;
            padding: 10px 5px;
        }

        .nav-link {
            color: #64748b;
            text-decoration: none;
            font-size: 0.8rem;
            transition: color 0.3s ease;
        }

        .nav-link.active {
            color: var(--primary-color);
        }

        .nav-link i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .main-content {
            padding-bottom: 80px;
        }

        .fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert {
            border: none;
            border-radius: 15px;
            padding: 15px 20px;
            margin: 20px 0;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        /* Advanced Features */
        .api-key-section {
            position: relative;
        }

        .api-key-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .qr-code-container {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            margin: 0 auto 15px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e2e8f0;
        }

        .usage-chart {
            height: 300px;
            margin: 20px 0;
        }

        .security-settings {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .security-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .security-item:last-child {
            border-bottom: none;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
            background: #ccc;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .toggle-switch.active {
            background: var(--primary-color);
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
        }

        .toggle-switch.active::after {
            transform: translateX(26px);
        }

        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }

        .activity-item {
            position: relative;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 0 0 2px var(--primary-color);
        }

        .activity-item.success::before {
            background: var(--success-color);
            box-shadow: 0 0 0 2px var(--success-color);
        }

        .activity-item.warning::before {
            background: var(--warning-color);
            box-shadow: 0 0 0 2px var(--warning-color);
        }

        .activity-item.danger::before {
            background: var(--danger-color);
            box-shadow: 0 0 0 2px var(--danger-color);
        }

        .notification-settings {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info h6 {
            margin: 0;
            color: var(--dark-color);
        }

        .setting-info small {
            color: #6b7280;
        }

        .export-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .backup-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .backup-section h5 {
            color: white;
            margin-bottom: 15px;
        }

        .backup-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .backup-actions .btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
        }

        .backup-actions .btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }

        .modal-advanced .modal-dialog {
            max-width: 800px;
        }

        .modal-advanced .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .form-floating {
            position: relative;
        }

        .form-floating .form-control {
            height: calc(3.5rem + 2px);
            line-height: 1.25;
        }

        .form-floating label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 0.75rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity 0.1s ease-in-out, transform 0.1s ease-in-out;
        }

        .form-floating .form-control:focus ~ label,
        .form-floating .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }

        @media (max-width: 768px) {
            .api-key-actions {
                flex-direction: column;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .backup-actions {
                flex-direction: column;
            }
            
            .security-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .setting-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-user-circle me-2"></i>
                User Profile
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container main-content">
        <div class="row">
            <div class="col-12">
                <div class="fade-in">
                    <h1 class="display-4 fw-bold mb-4 text-center">
                        <i class="fas fa-user-circle text-primary"></i>
                        Profile Management
                    </h1>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success fade-in">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger fade-in">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <?php if ($user): ?>
                <!-- User Information -->
                <div class="card fade-in">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-user text-primary"></i>
                            User Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Name</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($user['name']) ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">WhatsApp</label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($user['whatsapp']) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Daily Limit</label>
                                    <p class="form-control-plaintext"><?= number_format($user['daily_limit']) ?> requests/day</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Rate Limit</label>
                                    <p class="form-control-plaintext"><?= number_format($user['rate_limit_per_minute']) ?> requests/minute</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?> fs-6">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Expires At</label>
                                    <p class="form-control-plaintext"><?= date('M d, Y H:i', strtotime($user['expires_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Key Management -->
                <div class="card fade-in">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-key text-primary"></i>
                            API Key Management
                        </h5>
                        
                        <div class="api-key-section">
                            <div class="api-key-display">
                                <div class="api-key-text" id="apiKeyText"><?= htmlspecialchars($user['api_key']) ?></div>
                                <div class="api-key-actions">
                                    <button class="copy-btn" onclick="copyApiKey()">
                                        <i class="fas fa-copy me-1"></i>
                                        Copy API Key
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm" onclick="showQRCode()">
                                        <i class="fas fa-qrcode me-1"></i>
                                        Show QR Code
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" onclick="showApiKeyDetails()">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Key Details
                                    </button>
                                </div>
                            </div>
                            
                            <!-- QR Code Container -->
                            <div class="qr-code-container" id="qrCodeContainer" style="display: none;">
                                <h6>API Key QR Code</h6>
                                <div class="qr-code" id="qrCode">
                                    <i class="fas fa-qrcode fa-3x text-muted"></i>
                                </div>
                                <p class="text-muted">Scan this QR code to quickly access your API key</p>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Keep your API key secure and don't share it with others. 
                                If compromised, regenerate it immediately.
                            </div>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="regenerate_api_key">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to regenerate your API key? This will invalidate the current key.')">
                                    <i class="fas fa-sync-alt me-2"></i>
                                    Regenerate API Key
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="security-settings fade-in">
                    <h5 class="mb-3">
                        <i class="fas fa-shield-alt text-primary"></i>
                        Security Settings
                    </h5>
                    <div class="security-item">
                        <div>
                            <h6>Two-Factor Authentication</h6>
                            <small>Add an extra layer of security to your account</small>
                        </div>
                        <div class="toggle-switch" onclick="toggle2FA()"></div>
                    </div>
                    <div class="security-item">
                        <div>
                            <h6>Email Notifications</h6>
                            <small>Get notified about important account activities</small>
                        </div>
                        <div class="toggle-switch active" onclick="toggleEmailNotifications()"></div>
                    </div>
                    <div class="security-item">
                        <div>
                            <h6>API Key Rotation</h6>
                            <small>Automatically rotate API keys every 30 days</small>
                        </div>
                        <div class="toggle-switch" onclick="toggleKeyRotation()"></div>
                    </div>
                </div>

                <!-- Usage Analytics Chart -->
                <div class="card fade-in">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-chart-line text-primary"></i>
                            Usage Analytics
                        </h5>
                        <div class="usage-chart">
                            <canvas id="usageChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Export & Backup Section -->
                <div class="export-section fade-in">
                    <h5 class="mb-3">
                        <i class="fas fa-download text-primary"></i>
                        Export & Backup
                    </h5>
                    <div class="export-buttons">
                        <button class="btn btn-success btn-sm" onclick="exportProfileData('json')">
                            <i class="fas fa-file-code me-1"></i>
                            Export Profile (JSON)
                        </button>
                        <button class="btn btn-info btn-sm" onclick="exportProfileData('csv')">
                            <i class="fas fa-file-csv me-1"></i>
                            Export Usage Data (CSV)
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="exportProfileData('pdf')">
                            <i class="fas fa-file-pdf me-1"></i>
                            Export Report (PDF)
                        </button>
                    </div>
                </div>

                <!-- Backup Section -->
                <div class="backup-section fade-in">
                    <h5>
                        <i class="fas fa-database me-2"></i>
                        Data Backup
                    </h5>
                    <p class="mb-3">Create a complete backup of your profile and usage data</p>
                    <div class="backup-actions">
                        <button class="btn btn-sm" onclick="createBackup()">
                            <i class="fas fa-save me-1"></i>
                            Create Backup
                        </button>
                        <button class="btn btn-sm" onclick="restoreBackup()">
                            <i class="fas fa-upload me-1"></i>
                            Restore Backup
                        </button>
                        <button class="btn btn-sm" onclick="downloadBackup()">
                            <i class="fas fa-download me-1"></i>
                            Download Backup
                        </button>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="card fade-in">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-history text-primary"></i>
                            Recent Activity
                        </h5>
                        <div class="activity-timeline">
                            <div class="activity-item success">
                                <h6>API Key Generated</h6>
                                <p class="mb-1">Your API key was successfully generated</p>
                                <small class="text-muted"><?= date('M d, Y H:i') ?></small>
                            </div>
                            <div class="activity-item">
                                <h6>Profile Updated</h6>
                                <p class="mb-1">Your profile information was updated</p>
                                <small class="text-muted"><?= date('M d, Y H:i', strtotime('-1 day')) ?></small>
                            </div>
                            <div class="activity-item warning">
                                <h6>Rate Limit Warning</h6>
                                <p class="mb-1">You're approaching your daily rate limit</p>
                                <small class="text-muted"><?= date('M d, Y H:i', strtotime('-2 days')) ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usage Statistics -->
                <div class="card fade-in">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-chart-bar text-primary"></i>
                            Usage Statistics
                        </h5>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($user['total_hits']) ?></div>
                                <div class="stat-label">Total Requests</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($user['daily_limit'] - $user['total_hits']) ?></div>
                                <div class="stat-label">Remaining Today</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $user['last_used'] ? date('M d', strtotime($user['last_used'])) : 'Never' ?></div>
                                <div class="stat-label">Last Used</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $user['rate_limit_per_minute'] ?></div>
                                <div class="stat-label">Per Minute</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav d-lg-none">
        <div class="d-flex">
            <div class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Home
                </a>
            </div>
            <div class="nav-item">
                <a href="analytics.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="logs.php" class="nav-link">
                    <i class="fas fa-list"></i>
                    Logs
                </a>
            </div>
            <div class="nav-item">
                <a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    Notifications
                </a>
            </div>
            <div class="nav-item">
                <a href="profile.php" class="nav-link active">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Pass user data to JavaScript
        window.userData = {
            name: '<?= htmlspecialchars($user['name']) ?>',
            whatsapp: '<?= htmlspecialchars($user['whatsapp']) ?>',
            apiKey: '<?= htmlspecialchars($user['api_key']) ?>',
            dailyLimit: <?= $user['daily_limit'] ?>,
            rateLimit: <?= $user['rate_limit_per_minute'] ?>,
            isActive: <?= $user['is_active'] ? 'true' : 'false' ?>,
            expiresAt: '<?= $user['expires_at'] ?>',
            lastUsed: '<?= $user['last_used'] ?>',
            totalHits: <?= $user['total_hits'] ?>
        };
    </script>
    <script src="assets/js/advanced-profile.js"></script>
</body>
</html>