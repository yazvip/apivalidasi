<?php
/**
 * User Dashboard - Enhanced with API Key Management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
require_once '../api_auth.php';

$apiAuth = new APIAuth();
$user = null;
$error = '';
$message = '';

// Handle API key login
if ($_POST && isset($_POST['api_key'])) {
    $apiKey = trim($_POST['api_key']);
    
    if (empty($apiKey)) {
        $error = 'API Key is required';
    } else {
        $authResult = $apiAuth->validateApiKey($apiKey);
        if ($authResult['valid']) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_api_key'] = $apiKey;
            $_SESSION['user_data'] = $authResult['key'];
            $_SESSION['user_id'] = $authResult['key']['id'];
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
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_data'] = $user;
        $_SESSION['user_id'] = $user['id'];
    } else {
        unset($_SESSION['user_api_key']);
        unset($_SESSION['user_data']);
        unset($_SESSION['user_logged_in']);
        unset($_SESSION['user_id']);
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
        <title>User Login - API Validation System</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: {
                                50: '#eff6ff',
                                100: '#dbeafe',
                                200: '#bfdbfe',
                                300: '#93c5fd',
                                400: '#60a5fa',
                                500: '#3b82f6',
                                600: '#2563eb',
                                700: '#1d4ed8',
                                800: '#1e40af',
                                900: '#1e3a8a',
                            }
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-primary-600 to-primary-800 flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-primary-600 to-primary-700 text-white p-8 text-center">
                    <i class="fas fa-user-circle text-4xl mb-4"></i>
                    <h3 class="text-2xl font-bold">User Login</h3>
                    <p class="text-primary-100">API Validation System</p>
                </div>
                <div class="p-8">
                    <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="api_key" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-key mr-2"></i> API Key
                            </label>
                            <input type="text" id="api_key" name="api_key" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200"
                                   placeholder="Enter your API key">
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-primary-700 hover:to-primary-800 transform hover:-translate-y-0.5 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </button>
                    </form>
                    
                    <div class="text-center mt-6">
                        <small class="text-gray-500">
                            Don't have an API key? Contact administrator at 
                            <a href="https://wa.me/6282279698099" class="text-primary-600 hover:text-primary-700">WhatsApp</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
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
    $pdo = getDatabase();
    
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

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
                    <p class="text-gray-600">Here's your API usage overview</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i> Refresh
                    </button>
                    <a href="api-tester.php" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                        <i class="fas fa-flask mr-2"></i> Test API
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-primary-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Requests</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_requests']) ?></p>
                            <p class="text-sm text-gray-500">All time</p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-chart-line text-3xl text-primary-500"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Today's Usage</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['today_requests']) ?></p>
                            <p class="text-sm text-gray-500">of <?= number_format($user['daily_limit']) ?> limit</p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-calendar-day text-3xl text-green-500"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Remaining</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['remaining_requests']) ?></p>
                            <p class="text-sm text-gray-500">requests today</p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-hourglass-half text-3xl text-yellow-500"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Success Rate</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['success_rate'], 1) ?>%</p>
                            <p class="text-sm text-gray-500">last 30 days</p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-check-circle text-3xl text-blue-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Key Information -->
            <div class="bg-white rounded-xl shadow-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">API Key Information</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Your API Key</label>
                            <div class="flex">
                                <input type="text" class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg bg-gray-50 font-mono text-sm" 
                                       value="<?= htmlspecialchars($user['api_key']) ?>" readonly>
                                <button class="px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white border border-l-0 border-primary-600 rounded-r-lg transition-colors duration-200" 
                                        onclick="copyToClipboard('<?= htmlspecialchars($user['api_key']) ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Keep your API key secure and don't share it with others
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Daily Usage Progress</label>
                            <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                                <div class="bg-primary-600 h-4 rounded-full transition-all duration-300" 
                                     style="width: <?= min(($stats['today_requests'] / $user['daily_limit']) * 100, 100) ?>%"></div>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600">
                                <span><?= number_format($stats['today_requests']) ?> used</span>
                                <span><?= number_format($stats['remaining_requests']) ?> remaining</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900"><?= date('M d, Y', strtotime($user['expires_at'])) ?></div>
                            <div class="text-sm text-gray-500">Expires</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900"><?= number_format($user['rate_limit_per_minute']) ?></div>
                            <div class="text-sm text-gray-500">Per Minute Limit</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900"><?= $user['last_used'] ? date('M d, H:i', strtotime($user['last_used'])) : 'Never' ?></div>
                            <div class="text-sm text-gray-500">Last Used</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Test Section -->
            <div class="bg-white rounded-xl shadow-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">Quick API Test</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                            <h6 class="text-lg font-semibold mb-3">
                                <i class="fas fa-mobile-alt mr-2"></i> Test E-Wallet
                            </h6>
                            <p class="text-blue-100 mb-4">Validate GoPay, DANA, OVO, and other e-wallet accounts</p>
                            <button onclick="quickTest('ewallet')" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-colors duration-200">
                                Test Now
                            </button>
                        </div>
                        
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                            <h6 class="text-lg font-semibold mb-3">
                                <i class="fas fa-university mr-2"></i> Test Bank Account
                            </h6>
                            <p class="text-purple-100 mb-4">Validate bank account numbers for major Indonesian banks</p>
                            <button onclick="quickTest('bank')" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-purple-50 transition-colors duration-200">
                                Test Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-semibold text-gray-900">Recent Requests</h5>
                    </div>
                    <div class="p-6">
                        <?php if (empty($stats['recent_requests'])): ?>
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-list text-4xl mb-4 text-gray-300"></i>
                            <p>No recent requests</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach (array_slice($stats['recent_requests'], 0, 5) as $request): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($request['endpoint']) ?></div>
                                    <div class="text-sm text-gray-500"><?= date('M d, H:i', strtotime($request['created_at'])) ?></div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $request['status_code'] >= 200 && $request['status_code'] < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $request['status_code'] ?>
                                    </span>
                                    <span class="text-xs text-gray-500"><?= number_format($request['response_time'], 0) ?>ms</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Endpoints -->
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-semibold text-gray-900">Most Used Endpoints</h5>
                    </div>
                    <div class="p-6">
                        <?php if (empty($stats['top_endpoints'])): ?>
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-chart-bar text-4xl mb-4 text-gray-300"></i>
                            <p>No endpoint data available</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($stats['top_endpoints'] as $endpoint): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($endpoint['endpoint']) ?></div>
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                        <div class="bg-primary-600 h-2 rounded-full" 
                                             style="width: <?= ($endpoint['count'] / $stats['total_requests']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="ml-4 text-right">
                                    <div class="font-semibold text-gray-900"><?= number_format($endpoint['count']) ?></div>
                                    <div class="text-xs text-gray-500"><?= number_format(($endpoint['count'] / $stats['total_requests']) * 100, 1) ?>%</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <?php if (!empty($stats['notifications'])): ?>
            <div class="mt-8 bg-white rounded-xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">System Notifications</h5>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($stats['notifications'] as $notification): ?>
                        <div class="border-l-4 <?= $notification['type'] === 'success' ? 'border-green-500 bg-green-50' : ($notification['type'] === 'warning' ? 'border-yellow-500 bg-yellow-50' : ($notification['type'] === 'danger' ? 'border-red-500 bg-red-50' : 'border-blue-500 bg-blue-50')) ?> p-4 rounded-r-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-<?= $notification['type'] === 'success' ? 'check-circle' : ($notification['type'] === 'warning' ? 'exclamation-triangle' : ($notification['type'] === 'danger' ? 'times-circle' : 'info-circle')) ?> text-<?= $notification['type'] === 'success' ? 'green' : ($notification['type'] === 'warning' ? 'yellow' : ($notification['type'] === 'danger' ? 'red' : 'blue')) ?>-500"></i>
                                </div>
                                <div class="ml-3">
                                    <h6 class="text-sm font-medium text-gray-900"><?= htmlspecialchars($notification['title']) ?></h6>
                                    <p class="text-sm text-gray-700 mt-1"><?= htmlspecialchars($notification['message']) ?></p>
                                    <p class="text-xs text-gray-500 mt-2"><?= date('M d, Y H:i', strtotime($notification['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

<script>
function quickTest(type) {
    if (type === 'ewallet') {
        window.location.href = 'api-tester.php?preset=ewallet';
    } else if (type === 'bank') {
        window.location.href = 'api-tester.php?preset=bank';
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show success message
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        toast.innerHTML = '<i class="fas fa-check mr-2"></i>API Key copied to clipboard!';
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }).catch(() => {
        alert('Failed to copy to clipboard');
    });
}
</script>

<?php include 'includes/footer.php'; ?>