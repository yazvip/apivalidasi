<?php
/**
 * User Panel - Notifications
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';
require_once '../api_auth.php';

// Check user session
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Notifications';

// Get user's notifications
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("
        SELECT * FROM notifications 
        WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at DESC
    ");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $notifications = [];
}

include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
                <button onclick="location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
            </div>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div class="space-y-4">
                <?php if (empty($notifications)): ?>
                <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                    <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Notifications</h3>
                    <p class="text-gray-500">You don't have any notifications at the moment.</p>
                </div>
                <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 <?= $notification['type'] === 'success' ? 'border-green-500' : ($notification['type'] === 'warning' ? 'border-yellow-500' : ($notification['type'] === 'danger' ? 'border-red-500' : 'border-blue-500')) ?>">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-<?= $notification['type'] === 'success' ? 'check-circle' : ($notification['type'] === 'warning' ? 'exclamation-triangle' : ($notification['type'] === 'danger' ? 'times-circle' : 'info-circle')) ?> text-<?= $notification['type'] === 'success' ? 'green' : ($notification['type'] === 'warning' ? 'yellow' : ($notification['type'] === 'danger' ? 'red' : 'blue')) ?>-500 text-xl"></i>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <?= htmlspecialchars($notification['title']) ?>
                            </h3>
                            <p class="text-gray-600 mb-3">
                                <?= htmlspecialchars($notification['message']) ?>
                            </p>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-clock mr-2"></i>
                                <span><?= date('M j, Y \a\t g:i A', strtotime($notification['created_at'])) ?></span>
                                <?php if ($notification['expires_at']): ?>
                                <span class="ml-4">
                                    <i class="fas fa-calendar-times mr-1"></i>
                                    Expires: <?= date('M j, Y \a\t g:i A', strtotime($notification['expires_at'])) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

<?php include 'includes/footer.php'; ?>
