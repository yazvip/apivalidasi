<?php
/**
 * Admin Panel - API Keys Management
 * Fixed version with MySQL compatibility, CSRF protection, validation, and UX improvements
 */

if (session_status() === PHP_SESSION_NONE) {
session_start();
}
require_once '../config.php';
require_once '../api_auth.php';

// Check admin session
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$apiAuth = new APIAuth();
$message = '';
$error = '';

/**
 * Validate WhatsApp number format
 */
function validateWhatsApp($whatsapp) {
    // Remove non-numeric characters
    $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
    
    // Check if starts with 62 or 08
    if (preg_match('/^(62|08)\d{8,11}$/', $whatsapp)) {
        return $whatsapp;
    }
    return false;
}

/**
 * Check if WhatsApp number already exists
 */
function checkDuplicateWhatsApp($whatsapp, $pdo, $excludeId = null) {
    $sql = "SELECT COUNT(*) FROM api_keys WHERE whatsapp = ?";
    $params = [$whatsapp];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

/**
 * Generate secure API key
 */
function generateApiKey() {
    return 'api_' . bin2hex(random_bytes(24));
}

/**
 * Log admin action
 */
function logAdminAction($pdo, $action, $details) {
    try {
        // Check if system_logs table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($stmt->rowCount() == 0) {
            // Table doesn't exist, skip logging
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (level, message, context) 
            VALUES ('info', ?, ?)
        ");
        $stmt->execute([
            "Admin action: {$action}",
            json_encode($details)
        ]);
    } catch (Exception $e) {
        // Log error but don't break the main flow
        error_log("Failed to log admin action: " . $e->getMessage());
    }
}

// Handle form submissions
if ($_POST) {
    // CSRF Protection
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please refresh the page and try again.';
    } else {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
        $duration = (int)($_POST['duration'] ?? 30);
        $dailyLimit = (int)($_POST['daily_limit'] ?? 1000);
        
            // Validation
            if (empty($name)) {
                $error = 'Name is required';
            } elseif (strlen($name) > 255) {
                $error = 'Name must be less than 255 characters';
            } elseif (empty($whatsapp)) {
                $error = 'WhatsApp number is required';
            } elseif (!validateWhatsApp($whatsapp)) {
                $error = 'Invalid WhatsApp format. Use 08xxxxxxxxxx or 62xxxxxxxxxx';
            } elseif ($duration < 1 || $duration > 365) {
                $error = 'Duration must be between 1 and 365 days';
            } elseif ($dailyLimit < 1 || $dailyLimit > 100000) {
                $error = 'Daily limit must be between 1 and 100,000';
        } else {
            try {
                $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                    // Check for duplicate WhatsApp
                    if (checkDuplicateWhatsApp($whatsapp, $pdo)) {
                        $error = 'WhatsApp number already exists';
                    } else {
                        $apiKey = generateApiKey();
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
                
                $stmt = $pdo->prepare("
                    INSERT INTO api_keys (api_key, name, whatsapp, expires_at, daily_limit, is_active) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$apiKey, $name, $whatsapp, $expiresAt, $dailyLimit]);
                        
                        // Log admin action
                        logAdminAction($pdo, 'API Key Created', [
                            'api_key' => $apiKey,
                            'name' => $name,
                            'whatsapp' => $whatsapp,
                            'duration' => $duration,
                            'daily_limit' => $dailyLimit
                        ]);
                
                $message = "API Key created successfully: {$apiKey}";
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = 'API Key already exists. Please try again.';
                    } else {
                        $error = 'Database error: ' . $e->getMessage();
                    }
            } catch (Exception $e) {
                $error = 'Unexpected error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0);
        
            if ($id <= 0) {
                $error = 'Invalid API key ID';
            } else {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("UPDATE api_keys SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $id]);
                    
                    // Log admin action
                    logAdminAction($pdo, 'API Key Status Changed', [
                        'id' => $id,
                        'is_active' => $isActive
                    ]);
            
            $message = "API Key status updated successfully";
        } catch (Exception $e) {
            $error = "Error updating API key: " . $e->getMessage();
                }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
            if ($id <= 0) {
                $error = 'Invalid API key ID';
            } else {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Get API key info before deletion for logging
                    $stmt = $pdo->prepare("SELECT api_key, name FROM api_keys WHERE id = ?");
                    $stmt->execute([$id]);
                    $keyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("DELETE FROM api_keys WHERE id = ?");
            $stmt->execute([$id]);
                    
                    // Log admin action
                    if ($keyInfo) {
                        logAdminAction($pdo, 'API Key Deleted', [
                            'id' => $id,
                            'api_key' => $keyInfo['api_key'],
                            'name' => $keyInfo['name']
                        ]);
                    }
            
            $message = "API Key deleted successfully";
        } catch (Exception $e) {
            $error = "Error deleting API key: " . $e->getMessage();
                }
        }
    } elseif ($action === 'reset_limit') {
        $id = (int)($_POST['id'] ?? 0);
        
            if ($id <= 0) {
                $error = 'Invalid API key ID';
            } else {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Reset today's hit count by deleting today's logs
            $stmt = $pdo->prepare("DELETE FROM api_logs WHERE api_key = (SELECT api_key FROM api_keys WHERE id = ?) AND DATE(created_at) = CURDATE()");
            $stmt->execute([$id]);
                    
                    // Log admin action
                    logAdminAction($pdo, 'API Key Limit Reset', [
                        'id' => $id
                    ]);
            
            $message = "Daily limit reset successfully";
        } catch (Exception $e) {
            $error = "Error resetting limit: " . $e->getMessage();
                }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
        $expiresAt = $_POST['expires_at'] ?? '';
        $dailyLimit = (int)($_POST['daily_limit'] ?? 1000);
        
            // Validation
            if ($id <= 0) {
                $error = 'Invalid API key ID';
            } elseif (empty($name)) {
                $error = 'Name is required';
            } elseif (strlen($name) > 255) {
                $error = 'Name must be less than 255 characters';
            } elseif (empty($whatsapp)) {
                $error = 'WhatsApp number is required';
            } elseif (!validateWhatsApp($whatsapp)) {
                $error = 'Invalid WhatsApp format. Use 08xxxxxxxxxx or 62xxxxxxxxxx';
            } elseif (empty($expiresAt)) {
                $error = 'Expiry date is required';
            } elseif (strtotime($expiresAt) < time()) {
                $error = 'Expiry date must be in the future';
            } elseif ($dailyLimit < 1 || $dailyLimit > 100000) {
                $error = 'Daily limit must be between 1 and 100,000';
        } else {
            try {
                $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                    // Check for duplicate WhatsApp (excluding current record)
                    if (checkDuplicateWhatsApp($whatsapp, $pdo, $id)) {
                        $error = 'WhatsApp number already exists';
                    } else {
                $stmt = $pdo->prepare("
                    UPDATE api_keys 
                    SET name = ?, whatsapp = ?, expires_at = ?, daily_limit = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $whatsapp, $expiresAt, $dailyLimit, $id]);
                        
                        // Log admin action
                        logAdminAction($pdo, 'API Key Updated', [
                            'id' => $id,
                            'name' => $name,
                            'whatsapp' => $whatsapp,
                            'expires_at' => $expiresAt,
                            'daily_limit' => $dailyLimit
                        ]);
                
                $message = "API Key updated successfully";
                    }
            } catch (Exception $e) {
                $error = "Error updating API key: " . $e->getMessage();
                }
            }
        }
    }
}

// Get API keys with FIXED MySQL query
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // FIXED: Use MySQL syntax instead of SQLite
    $stmt = $pdo->query("
        SELECT 
            ak.*,
            COUNT(al.id) as total_requests,
            COUNT(CASE WHEN DATE(al.created_at) = CURDATE() THEN 1 END) as today_requests
        FROM api_keys ak
        LEFT JOIN api_logs al ON ak.api_key = al.api_key
        GROUP BY ak.id
        ORDER BY ak.created_at DESC
    ");
    $apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $apiKeys = [];
}

$pageTitle = 'API Keys Management';
include 'includes/header.php';
?>

<style>
.api-key {
    font-family: 'Courier New', monospace;
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}
.status-badge {
    font-size: 0.75rem;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Modal fixes */
.modal {
    z-index: 1055;
}
.modal.hidden {
    display: none !important;
}
.modal-backdrop {
    z-index: 1050;
}

/* Button loading state */
.btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

/* Toast container */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

/* Form validation */
.form-control.is-invalid {
    border-color: #dc3545;
}

.form-control.is-valid {
    border-color: #198754;
}

/* Loading spinner */
.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhanced button styles */
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-warning:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.btn-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-info:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(6, 182, 212, 0.4);
}

/* Table enhancements */
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

/* Card enhancements */
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
}

/* Real-time validation feedback */
.validation-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.validation-feedback.show {
    display: block;
}
</style>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">API Keys Management</h1>
                <button type="button" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg shadow-lg transition-all duration-200 hover:shadow-xl flex items-center" onclick="showModal('createModal')">
                    <i class="fas fa-plus mr-2"></i> Create New API Key
                    </button>
                </div>

                <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span class="flex-1"><?= htmlspecialchars($message) ?></span>
                <button type="button" class="ml-4 text-green-700 hover:text-green-900" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span class="flex-1"><?= htmlspecialchars($error) ?></span>
                <button type="button" class="ml-4 text-red-700 hover:text-red-900" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
                </div>
                <?php endif; ?>

                <!-- API Keys Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-primary-600 to-primary-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Name</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">WhatsApp</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">API Key</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Expires</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Daily Limit</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Today's Hits</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Total Hits</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($apiKeys as $key): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($key['name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($key['whatsapp']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs"><?= htmlspecialchars($key['api_key']) ?></code>
                                        </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            $isExpired = strtotime($key['expires_at']) < time();
                                            $isActive = $key['is_active'] && !$isExpired;
                                            ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $isActive ? 'bg-green-100 text-green-800' : ($isExpired ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') ?>">
                                                <?= $isActive ? 'Active' : ($isExpired ? 'Expired' : 'Inactive') ?>
                                            </span>
                                        </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('Y-m-d H:i', strtotime($key['expires_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($key['daily_limit']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?= number_format($key['today_requests']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($key['total_requests']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <!-- Toggle Form -->
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="id" value="<?= $key['id'] ?>">
                                                    <input type="hidden" name="is_active" value="<?= $key['is_active'] ? 0 : 1 ?>">
                                                    <button type="submit" class="p-2 rounded-full text-white <?= $key['is_active'] ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600' ?>" title="<?= $key['is_active'] ? 'Disable' : 'Enable' ?>">
                                                        <i class="fas fa-<?= $key['is_active'] ? 'pause' : 'play' ?>"></i>
                                                    </button>
                                                </form>
                                                <!-- Edit Button -->
                                                <button type="button" class="p-2 rounded-full bg-blue-500 hover:bg-blue-600 text-white" onclick="showModal('editModal<?= $key['id'] ?>')" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Reset Limit Form -->
                                                <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to reset today\'s limit for this API key?')">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="action" value="reset_limit">
                                                    <input type="hidden" name="id" value="<?= $key['id'] ?>">
                                                    <button type="submit" class="p-2 rounded-full bg-yellow-500 hover:bg-yellow-600 text-white" title="Reset Today's Limit">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                </form>
                                                <!-- Delete Form -->
                                                <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this API key?')">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $key['id'] ?>">
                                                    <button type="submit" class="p-2 rounded-full bg-red-500 hover:bg-red-600 text-white" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
        <p class="mt-2">Processing...</p>
                </div>
                        </div>
                        
        <!-- Create API Key Modal -->
        <div class="modal hidden" id="createModal" tabindex="-1">
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
                    <form method="POST" id="createForm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="text-lg font-semibold text-gray-900">Create New API Key</h5>
                        </div>
                        <div class="p-6">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="name" name="name" required maxlength="255">
                                <div class="validation-feedback text-red-500 text-sm mt-1" id="nameFeedback"></div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">WhatsApp Number <span class="text-red-500">*</span></label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="whatsapp" name="whatsapp" placeholder="08xxxxxxxxxx or 62xxxxxxxxxx" required>
                                <div class="validation-feedback text-red-500 text-sm mt-1" id="whatsappFeedback"></div>
                                <p class="text-sm text-gray-500 mt-1">Format: 08xxxxxxxxxx or 62xxxxxxxxxx</p>
                        </div>
                        
                            <div class="mb-4">
                                <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">Duration (Days) <span class="text-red-500">*</span></label>
                                <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="duration" name="duration" value="30" min="1" max="365" required>
                                <div class="validation-feedback text-red-500 text-sm mt-1" id="durationFeedback"></div>
                        </div>
                        
                            <div class="mb-4">
                                <label for="daily_limit" class="block text-sm font-medium text-gray-700 mb-2">Daily Limit <span class="text-red-500">*</span></label>
                                <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="daily_limit" name="daily_limit" value="1000" min="1" max="100000" required>
                                <div class="validation-feedback text-red-500 text-sm mt-1" id="dailyLimitFeedback"></div>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200" onclick="hideModalManually(document.getElementById('createModal'))">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors duration-200 flex items-center" id="createSubmitBtn">
                                <i class="fas fa-plus mr-2"></i> Create API Key
                            </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modals for each API Key -->
    <?php foreach ($apiKeys as $key): ?>
        <div class="modal hidden" id="editModal<?= $key['id'] ?>" tabindex="-1">
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
                    <form method="POST" id="editForm<?= $key['id'] ?>">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="text-lg font-semibold text-gray-900">Edit API Key: <?= htmlspecialchars($key['name']) ?></h5>
                </div>
                        <div class="p-6">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $key['id'] ?>">
                        
                            <div class="mb-4">
                                <label for="name_<?= $key['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="name_<?= $key['id'] ?>" name="name" 
                                   value="<?= htmlspecialchars($key['name']) ?>" required maxlength="255">
                                <div class="validation-feedback text-red-500 text-sm mt-1" id="nameFeedback_<?= $key['id'] ?>"></div>
                        </div>
                        
                            <div class="mb-4">
                                <label for="whatsapp_<?= $key['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">WhatsApp Number <span class="text-red-500">*</span></label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="whatsapp_<?= $key['id'] ?>" name="whatsapp" 
                                   value="<?= htmlspecialchars($key['whatsapp']) ?>" placeholder="08xxxxxxxxxx or 62xxxxxxxxxx" required>
                                <div class="validation-feedback text-red-500 text-sm mt-1" id="whatsappFeedback_<?= $key['id'] ?>"></div>
                                <p class="text-sm text-gray-500 mt-1">Format: 08xxxxxxxxxx or 62xxxxxxxxxx</p>
                        </div>
                        
                            <div class="mb-4">
                                <label for="expires_at_<?= $key['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">Expiry Date <span class="text-red-500">*</span></label>
                                <input type="datetime-local" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="expires_at_<?= $key['id'] ?>" name="expires_at" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($key['expires_at'])) ?>" required>
                                <div class="validation-feedback text-red-500 text-sm mt-1" id="expiresAtFeedback_<?= $key['id'] ?>"></div>
                        </div>
                        
                            <div class="mb-4">
                                <label for="daily_limit_<?= $key['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">Daily Limit <span class="text-red-500">*</span></label>
                                <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="daily_limit_<?= $key['id'] ?>" name="daily_limit" 
                                   value="<?= $key['daily_limit'] ?>" min="1" max="100000" required>
                                <div class="validation-feedback text-red-500 text-sm mt-1" id="dailyLimitFeedback_<?= $key['id'] ?>"></div>
                        </div>
                        
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                <div class="flex">
                                    <input type="text" class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg bg-gray-50" value="<?= htmlspecialchars($key['api_key']) ?>" readonly>
                                    <button class="px-4 py-3 bg-gray-100 hover:bg-gray-200 border border-l-0 border-gray-300 rounded-r-lg transition-colors duration-200" type="button" onclick="copyToClipboard('<?= htmlspecialchars($key['api_key']) ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200" onclick="hideModalManually(document.getElementById('editModal<?= $key['id'] ?>'))">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors duration-200 flex items-center" id="editSubmitBtn_<?= $key['id'] ?>">
                                <i class="fas fa-save mr-2"></i> Update API Key
                            </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script>
// CSRF Token for AJAX requests
const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

// Show loading overlay
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

// Hide loading overlay
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        const toast = document.createElement('div');
        toast.className = 'toast-container';
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-body">
                    <i class="fas fa-check-circle text-success"></i> API Key copied to clipboard!
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 3000);
    });
}

// WhatsApp validation
function validateWhatsApp(whatsapp) {
    const cleaned = whatsapp.replace(/[^0-9]/g, '');
    return /^(62|08)\d{8,11}$/.test(cleaned);
}

// Real-time validation
function setupValidation() {
    // WhatsApp validation for create form
    const whatsappInput = document.getElementById('whatsapp');
    if (whatsappInput) {
        whatsappInput.addEventListener('input', function(e) {
            const value = e.target.value;
            const feedback = document.getElementById('whatsappFeedback');
            
            if (value.length > 0) {
                if (validateWhatsApp(value)) {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    feedback.classList.remove('show');
                } else {
                    e.target.classList.remove('is-valid');
                    e.target.classList.add('is-invalid');
                    feedback.textContent = 'Invalid WhatsApp format. Use 08xxxxxxxxxx or 62xxxxxxxxxx';
                    feedback.classList.add('show');
                }
            } else {
                e.target.classList.remove('is-invalid', 'is-valid');
                feedback.classList.remove('show');
            }
        });
    }
    
    // WhatsApp validation for edit forms
    document.querySelectorAll('input[id^="whatsapp_"]').forEach(input => {
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            const id = e.target.id.split('_')[1];
            const feedback = document.getElementById(`whatsappFeedback_${id}`);
            
            if (value.length > 0) {
                if (validateWhatsApp(value)) {
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    feedback.classList.remove('show');
                } else {
                    e.target.classList.remove('is-valid');
                    e.target.classList.add('is-invalid');
                    feedback.textContent = 'Invalid WhatsApp format. Use 08xxxxxxxxxx or 62xxxxxxxxxx';
                    feedback.classList.add('show');
                }
            } else {
                e.target.classList.remove('is-invalid', 'is-valid');
                feedback.classList.remove('show');
            }
        });
    });
    
    // Duration validation
    const durationInput = document.getElementById('duration');
    if (durationInput) {
        durationInput.addEventListener('input', function(e) {
            const value = parseInt(e.target.value);
            const feedback = document.getElementById('durationFeedback');
            
            if (value < 1 || value > 365) {
                e.target.classList.add('is-invalid');
                feedback.textContent = 'Duration must be between 1 and 365 days';
                feedback.classList.add('show');
            } else {
                e.target.classList.remove('is-invalid');
                feedback.classList.remove('show');
            }
        });
    }
    
    // Daily limit validation
    const dailyLimitInput = document.getElementById('daily_limit');
    if (dailyLimitInput) {
        dailyLimitInput.addEventListener('input', function(e) {
            const value = parseInt(e.target.value);
            const feedback = document.getElementById('dailyLimitFeedback');
            
            if (value < 1 || value > 100000) {
                e.target.classList.add('is-invalid');
                feedback.textContent = 'Daily limit must be between 1 and 100,000';
                feedback.classList.add('show');
            } else {
                e.target.classList.remove('is-invalid');
                feedback.classList.remove('show');
            }
        });
    }
}

// Form submission with loading state
function setupFormSubmission() {
    // Create form
    const createForm = document.getElementById('createForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('createSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            showLoading();
        });
    }
    
    // Edit forms
    document.querySelectorAll('form[id^="editForm"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            showLoading();
        });
    });
    
    // Toggle forms
    document.querySelectorAll('form[action=""]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            showLoading();
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupValidation();
    setupFormSubmission();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Handle modal events
document.addEventListener('shown.bs.modal', function(e) {
    // Reset validation when modal opens
    const modal = e.target;
    const inputs = modal.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.classList.remove('is-invalid', 'is-valid');
    });
    
    const feedbacks = modal.querySelectorAll('.validation-feedback');
    feedbacks.forEach(feedback => {
        feedback.classList.remove('show');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
