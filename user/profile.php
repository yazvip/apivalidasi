<?php
/**
 * User Profile Management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
require_once '../api_auth.php';

// Check user session
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user_data'];
$message = '';
$error = '';

// Handle API key regeneration
if ($_POST && isset($_POST['regenerate_key'])) {
    try {
        $pdo = getDatabase();
        
        // Generate new API key
        $newApiKey = 'api_' . bin2hex(random_bytes(24));
        
        // Update API key in database
        $stmt = $pdo->prepare("UPDATE api_keys SET api_key = ? WHERE id = ?");
        $stmt->execute([$newApiKey, $user['id']]);
        
        // Update session
        $_SESSION['user_api_key'] = $newApiKey;
        $user['api_key'] = $newApiKey;
        $_SESSION['user_data'] = $user;
        
        $message = "API key regenerated successfully. Please update your applications with the new key.";
        
    } catch (Exception $e) {
        $error = "Error regenerating API key: " . $e->getMessage();
    }
}

$pageTitle = 'Profile';
include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Profile</h1>
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

            <!-- User Information -->
            <div class="bg-white rounded-xl shadow-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">User Information</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <div class="px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg">
                                <?= htmlspecialchars($user['name']) ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                            <div class="px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg">
                                <?= htmlspecialchars($user['whatsapp']) ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Daily Limit</label>
                            <div class="px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg">
                                <?= number_format($user['daily_limit']) ?> requests/day
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rate Limit</label>
                            <div class="px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg">
                                <?= number_format($user['rate_limit_per_minute']) ?> requests/minute
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expires At</label>
                            <div class="px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg">
                                <?= date('M d, Y H:i', strtotime($user['expires_at'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Key Management -->
            <div class="bg-white rounded-xl shadow-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">API Key Management</h5>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                            <div>
                                <h6 class="font-semibold text-blue-900">Your API Key</h6>
                                <div class="font-mono text-blue-800 bg-white px-3 py-2 rounded border mt-2 break-all">
                                    <?= htmlspecialchars($user['api_key']) ?>
                                </div>
                                <p class="text-blue-700 text-sm mt-2">Use this key in the Authorization header: Bearer YOUR_API_KEY</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button onclick="copyToClipboard('<?= htmlspecialchars($user['api_key']) ?>')" 
                                class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                            <i class="fas fa-copy mr-2"></i> Copy API Key
                        </button>
                        
                        <form method="POST" class="inline-block">
                            <button type="submit" name="regenerate_key" 
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center"
                                    onclick="return confirm('Are you sure you want to regenerate your API key? This will invalidate the current key and you will need to update all your applications.')">
                                <i class="fas fa-sync-alt mr-2"></i> Regenerate Key
                            </button>
                        </form>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                            <div>
                                <h6 class="font-semibold text-yellow-900">Security Notice</h6>
                                <p class="text-yellow-800 text-sm">
                                    Keep your API key secure and don't share it with others. 
                                    If you suspect your key has been compromised, regenerate it immediately.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Documentation -->
            <div class="bg-white rounded-xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">API Documentation</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h6 class="font-semibold text-gray-900 mb-3">Supported E-Wallets</h6>
                            <div class="space-y-2">
                                <?php foreach (EWALLET_CODES as $code): ?>
                                <div class="flex items-center p-2 bg-gray-50 rounded">
                                    <i class="fas fa-mobile-alt text-blue-500 mr-3"></i>
                                    <span class="font-mono text-sm"><?= strtolower($code) ?></span>
                                    <span class="ml-auto text-sm text-gray-600"><?= strtoupper(str_replace('_', ' ', $code)) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div>
                            <h6 class="font-semibold text-gray-900 mb-3">Supported Banks</h6>
                            <div class="space-y-2">
                                <?php foreach (BANK_CODES as $code => $name): ?>
                                <div class="flex items-center p-2 bg-gray-50 rounded">
                                    <i class="fas fa-university text-purple-500 mr-3"></i>
                                    <span class="font-mono text-sm"><?= $code ?></span>
                                    <span class="ml-auto text-sm text-gray-600"><?= $name ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 text-center">
                        <a href="api-tester.php" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 inline-flex items-center">
                            <i class="fas fa-flask mr-2"></i> Try API Tester
                        </a>
                    </div>
                </div>
            </div>
        </div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show success message
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center';
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