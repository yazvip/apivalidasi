<?php
/**
 * User Dashboard - API Key Login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
require_once '../api_auth.php';

$apiAuth = new APIAuth();
$user = null;
$error = '';

// Handle API key login
if ($_POST && isset($_POST['api_key'])) {
    $apiKey = trim($_POST['api_key']);
    
    if (empty($apiKey)) {
        $error = 'API Key is required';
    } else {
        $authResult = $apiAuth->validateApiKey($apiKey);
        if ($authResult['valid']) {
            $_SESSION['user_api_key'] = $apiKey;
            $_SESSION['user_data'] = $authResult['key'];
            $user = $authResult['key'];
        } else {
            $error = $authResult['error'];
        }
    }
}

// Check if user is logged in
if (isset($_SESSION['user_api_key'])) {
    $authResult = $apiAuth->validateApiKey($_SESSION['user_api_key']);
    if ($authResult['valid']) {
        $user = $authResult['key'];
    } else {
        unset($_SESSION['user_api_key']);
        unset($_SESSION['user_data']);
    }
}

// If not logged in, show login form
if (!$user) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Login - API Dashboard</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
            }
            .login-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .login-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 2rem;
                text-align: center;
            }
            .login-body {
                padding: 2rem;
            }
            .form-control {
                border-radius: 10px;
                border: 2px solid #e9ecef;
                padding: 12px 15px;
            }
            .form-control:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            }
            .btn-login {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                border-radius: 10px;
                padding: 12px 30px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .btn-login:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="login-card">
                        <div class="login-header">
                            <i class="fas fa-user-circle fa-3x mb-3"></i>
                            <h3>User Login</h3>
                            <p class="mb-0">API Dashboard</p>
                        </div>
                        <div class="login-body">
                            <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="api_key" class="form-label">
                                        <i class="fas fa-key"></i> API Key
                                    </label>
                                    <input type="text" class="form-control" id="api_key" name="api_key" 
                                           placeholder="Enter your API key" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-login">
                                        <i class="fas fa-sign-in-alt"></i> Login
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    Don't have an API key? Contact administrator
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Get user statistics
$stats = [
    'total_requests' => 0,
    'today_requests' => 0,
    'this_month_requests' => 0,
    'remaining_requests' => 0,
    'success_rate' => 0,
    'avg_response_time' => 0,
    'last_used' => null,
    'top_endpoints' => [],
    'recent_requests' => [],
    'notifications' => []
];

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get total requests
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_logs WHERE api_key = ?");
    $stmt->execute([$user['api_key']]);
    $stats['total_requests'] = $stmt->fetchColumn();
    
    // Get today's requests
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_logs WHERE api_key = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$user['api_key']]);
    $stats['today_requests'] = $stmt->fetchColumn();
    
    // Get this month's requests
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_logs WHERE api_key = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stmt->execute([$user['api_key']]);
    $stats['this_month_requests'] = $stmt->fetchColumn();
    
    // Calculate remaining requests
    $stats['remaining_requests'] = max(0, $user['daily_limit'] - $stats['today_requests']);
    
    // Get success rate
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) as success,
            COUNT(*) as total
        FROM api_logs 
        WHERE api_key = ?
    ");
    $stmt->execute([$user['api_key']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['success_rate'] = $result['total'] > 0 ? ($result['success'] / $result['total']) * 100 : 0;
    
    // Get average response time
    $stmt = $pdo->prepare("SELECT AVG(response_time) FROM api_logs WHERE api_key = ?");
    $stmt->execute([$user['api_key']]);
    $stats['avg_response_time'] = $stmt->fetchColumn() ?: 0;
    
    // Get last used
    $stats['last_used'] = $user['last_used'];
    
    // Get top endpoints
    $stmt = $pdo->prepare("
        SELECT endpoint, COUNT(*) as count 
        FROM api_logs 
        WHERE api_key = ? 
        GROUP BY endpoint 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stmt->execute([$user['api_key']]);
    $stats['top_endpoints'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent requests
    $stmt = $pdo->prepare("
        SELECT endpoint, status_code, response_time, created_at 
        FROM api_logs 
        WHERE api_key = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user['api_key']]);
    $stats['recent_requests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get notifications
    $stmt = $pdo->prepare("
        SELECT title, message, type, created_at, expires_at 
        FROM notifications 
        WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $stats['notifications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - API Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --light-bg: #f8fafc;
            --dark-bg: #1e293b;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--light-bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #334155;
        }

        /* Android-like Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 8px 0;
            z-index: 1000;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .bottom-nav .nav-item {
            flex: 1;
            text-align: center;
        }

        .bottom-nav .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 4px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 12px;
            margin: 0 4px;
        }

        .bottom-nav .nav-link:hover,
        .bottom-nav .nav-link.active {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }

        .bottom-nav .nav-link i {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .bottom-nav .nav-link span {
            font-size: 11px;
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            padding-bottom: 80px;
            min-height: 100vh;
        }

        /* Header */
        .app-header {
            background: white;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .app-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-bg);
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            background: white;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stat-card {
            background: var(--gradient-primary);
            color: white;
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: scale(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover::before {
            transform: scale(1);
        }

        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-card .stat-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }

        .stat-card .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2rem;
            opacity: 0.3;
        }

        /* Progress Bars */
        .progress-custom {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .progress-custom .progress-bar {
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        /* API Key Display */
        .api-key-display {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            background: #f1f5f9;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            word-break: break-all;
            font-size: 14px;
            color: #475569;
        }

        /* Charts */
        .chart-container {
            position: relative;
            height: 250px;
        }

        /* Buttons */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 24px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        /* Status Badges */
        .status-badge {
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .app-header {
                padding: 12px 16px;
            }

            .app-header h1 {
                font-size: 20px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-card .stat-number {
                font-size: 2rem;
            }

            .chart-container {
                height: 200px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
            border: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* List Items */
        .list-item {
            display: flex;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }

        .list-item:hover {
            background: #f8fafc;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Header -->
        <div class="app-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                        <small class="text-muted">API User</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container-fluid px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h4 mb-1">Welcome back!</h2>
                        <p class="text-muted mb-0">Here's your API usage overview</p>
                    </div>
                    <button type="button" class="btn btn-outline-primary" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card fade-in">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-number"><?= number_format($stats['total_requests']) ?></div>
                            <div class="stat-label">Total Requests</div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card fade-in" style="background: var(--gradient-success);">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="stat-number"><?= number_format($stats['today_requests']) ?></div>
                            <div class="stat-label">Today's Requests</div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card fade-in" style="background: var(--gradient-warning);">
                            <div class="stat-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="stat-number"><?= number_format($stats['remaining_requests']) ?></div>
                            <div class="stat-label">Remaining</div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card fade-in" style="background: var(--gradient-danger);">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number"><?= number_format($stats['success_rate'], 1) ?>%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                    </div>
                </div>

                <!-- API Key Info -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card fade-in">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-key text-primary"></i> API Key Information
                                    </h5>
                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?> status-badge">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Your API Key</label>
                                        <div class="api-key-display"><?= htmlspecialchars($user['api_key']) ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Keep your API key secure and don't share it with others
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Daily Usage</label>
                                        <div class="progress progress-custom mb-2">
                                            <div class="progress-bar bg-<?= $stats['remaining_requests'] > $user['daily_limit'] * 0.2 ? 'success' : 'warning' ?>" 
                                                 style="width: <?= min(($stats['today_requests'] / $user['daily_limit']) * 100, 100) ?>%">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted"><?= number_format($stats['today_requests']) ?></span>
                                            <span class="text-muted"><?= number_format($user['daily_limit']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar text-muted me-2"></i>
                                            <div>
                                                <small class="text-muted">Expires</small>
                                                <div class="fw-bold"><?= date('M d, Y', strtotime($user['expires_at'])) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clock text-muted me-2"></i>
                                            <div>
                                                <small class="text-muted">Last Used</small>
                                                <div class="fw-bold"><?= $user['last_used'] ? date('M d, Y H:i', strtotime($user['last_used'])) : 'Never' ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <?php if (!empty($stats['notifications'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card fade-in">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-bell text-primary"></i> Notifications
                                </h5>
                                <div class="row">
                                    <?php foreach ($stats['notifications'] as $notification): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="alert alert-<?= $notification['type'] === 'info' ? 'info' : ($notification['type'] === 'success' ? 'success' : ($notification['type'] === 'warning' ? 'warning' : 'danger')) ?> alert-dismissible fade show">
                                            <h6 class="alert-heading"><?= htmlspecialchars($notification['title']) ?></h6>
                                            <p class="mb-2"><?= htmlspecialchars($notification['message']) ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Charts and Recent Activity -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card fade-in">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-chart-line text-primary"></i> Request Trends
                                </h5>
                                <div class="chart-container">
                                    <canvas id="requestChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card fade-in">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-list text-primary"></i> Recent Activity
                                </h5>
                                <?php if (empty($stats['recent_requests'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p class="mb-0">No recent requests</p>
                                </div>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($stats['recent_requests'], 0, 5) as $request): ?>
                                    <div class="list-item">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($request['endpoint']) ?>">
                                                <?= htmlspecialchars($request['endpoint']) ?>
                                            </div>
                                            <small class="text-muted"><?= date('H:i:s', strtotime($request['created_at'])) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $request['status_code'] >= 200 && $request['status_code'] < 300 ? 'success' : 'danger' ?> status-badge">
                                                <?= $request['status_code'] ?>
                                            </span>
                                            <div class="text-muted small"><?= number_format($request['response_time'], 2) ?>s</div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Endpoints -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card fade-in">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-chart-bar text-primary"></i> Top Endpoints
                                </h5>
                                <?php if (empty($stats['top_endpoints'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-bar"></i>
                                    <p class="mb-0">No endpoint data available</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Endpoint</th>
                                                <th>Requests</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['top_endpoints'] as $endpoint): ?>
                                            <tr>
                                                <td>
                                                    <code class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($endpoint['endpoint']) ?>">
                                                        <?= htmlspecialchars($endpoint['endpoint']) ?>
                                                    </code>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= number_format($endpoint['count']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="progress progress-custom">
                                                        <div class="progress-bar bg-info" style="width: <?= ($endpoint['count'] / $stats['total_requests']) * 100 ?>%">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted"><?= number_format(($endpoint['count'] / $stats['total_requests']) * 100, 1) ?>%</small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Android-like Bottom Navigation -->
        <nav class="bottom-nav">
            <div class="d-flex">
                <div class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="analytics.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="logs.php">
                        <i class="fas fa-list"></i>
                        <span>Logs</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="notifications.php">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Request Trends Chart
        const requestCtx = document.getElementById('requestChart').getContext('2d');
        new Chart(requestCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'API Requests',
                    data: [120, 190, 300, 500, 200, 300, 450],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function refreshData() {
            location.reload();
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
