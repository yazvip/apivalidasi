<?php
/**
 * Admin Panel - Settings
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

$pageTitle = 'Settings';
$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $settings = [
            'site_name' => $_POST['site_name'] ?? '',
            'site_url' => $_POST['site_url'] ?? '',
            'admin_email' => $_POST['admin_email'] ?? '',
            'max_daily_requests' => (int)($_POST['max_daily_requests'] ?? 10000),
            'rate_limit_per_minute' => (int)($_POST['rate_limit_per_minute'] ?? 60),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'enable_logging' => isset($_POST['enable_logging']) ? '1' : '0',
            'log_retention_days' => (int)($_POST['log_retention_days'] ?? 30)
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO api_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, $value]);
        }
        
        $message = "Settings updated successfully";
        
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM api_settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $error = "Error loading settings: " . $e->getMessage();
    $settings = [];
}

include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
            </div>

            <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white rounded-xl shadow-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="text-lg font-semibold text-gray-900">General Settings</h5>
                        </div>
                        <div class="p-6">
                            <div class="mb-6">
                                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="site_name" name="site_name" 
                                       value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-6">
                                <label for="site_url" class="block text-sm font-medium text-gray-700 mb-2">Site URL</label>
                                <input type="url" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="site_url" name="site_url" 
                                       value="<?= htmlspecialchars($settings['site_url'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-6">
                                <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-2">Admin Email</label>
                                <input type="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="admin_email" name="admin_email" 
                                       value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="text-lg font-semibold text-gray-900">API Settings</h5>
                        </div>
                        <div class="p-6">
                            <div class="mb-6">
                                <label for="max_daily_requests" class="block text-sm font-medium text-gray-700 mb-2">Max Daily Requests</label>
                                <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="max_daily_requests" name="max_daily_requests" 
                                       value="<?= htmlspecialchars($settings['max_daily_requests'] ?? '10000') ?>" required>
                            </div>
                            
                            <div class="mb-6">
                                <label for="rate_limit_per_minute" class="block text-sm font-medium text-gray-700 mb-2">Rate Limit (per minute)</label>
                                <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="rate_limit_per_minute" name="rate_limit_per_minute" 
                                       value="<?= htmlspecialchars($settings['rate_limit_per_minute'] ?? '60') ?>" required>
                            </div>
                            
                            <div class="mb-6">
                                <label for="log_retention_days" class="block text-sm font-medium text-gray-700 mb-2">Log Retention (days)</label>
                                <input type="number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="log_retention_days" name="log_retention_days" 
                                       value="<?= htmlspecialchars($settings['log_retention_days'] ?? '30') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-semibold text-gray-900">System Settings</h5>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center mb-6">
                            <input class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                   <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="ml-3 text-sm font-medium text-gray-700" for="maintenance_mode">
                                Maintenance Mode
                            </label>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" type="checkbox" id="enable_logging" name="enable_logging" 
                                   <?= ($settings['enable_logging'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <label class="ml-3 text-sm font-medium text-gray-700" for="enable_logging">
                                Enable Logging
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>

<?php include 'includes/footer.php'; ?>
