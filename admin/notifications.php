<?php
/**
 * Admin Panel - Notifications
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

$pageTitle = 'Notifications';
$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($action === 'create') {
            $title = $_POST['title'] ?? '';
            $message_text = $_POST['message'] ?? '';
            $type = $_POST['type'] ?? 'info';
            $expires_at = $_POST['expires_at'] ?? null;
            
            if (empty($title) || empty($message_text)) {
                $error = 'Title and message are required';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (title, message, type, expires_at) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$title, $message_text, $type, $expires_at]);
                $message = "Notification created successfully";
            }
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $isActive = (int)($_POST['is_active'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE notifications SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $id]);
            $message = "Notification status updated";
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Notification deleted successfully";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get notifications
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("
        SELECT * FROM notifications 
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
                <button class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center" onclick="showModal('createModal')">
                    <i class="fas fa-plus mr-2"></i>Create Notification
                </button>
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

            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-primary-600 to-primary-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Title</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Created</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Expires</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($notifications)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-bell-slash text-4xl mb-4 text-gray-300"></i>
                                    <p class="text-lg">No notifications found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($notification['title']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $notification['type'] === 'success' ? 'bg-green-100 text-green-800' : ($notification['type'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : ($notification['type'] === 'danger' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) ?>">
                                        <?= ucfirst($notification['type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $notification['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $notification['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $notification['expires_at'] ? date('Y-m-d H:i', strtotime($notification['expires_at'])) : 'Never' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button class="p-2 rounded-full text-white <?= $notification['is_active'] ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600' ?>" 
                                                onclick="toggleNotification(<?= $notification['id'] ?>, <?= $notification['is_active'] ? 0 : 1 ?>)" 
                                                title="<?= $notification['is_active'] ? 'Disable' : 'Enable' ?>">
                                            <i class="fas fa-<?= $notification['is_active'] ? 'pause' : 'play' ?>"></i>
                                        </button>
                                        <button class="p-2 rounded-full bg-red-500 hover:bg-red-600 text-white" 
                                                onclick="deleteNotification(<?= $notification['id'] ?>)" 
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Notification Modal -->
        <div class="modal" id="createModal" tabindex="-1">
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
                    <form method="POST">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="text-lg font-semibold text-gray-900">Create Notification</h5>
                        </div>
                        <div class="p-6">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="message" name="message" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="type" name="type">
                                    <option value="info">Info</option>
                                    <option value="success">Success</option>
                                    <option value="warning">Warning</option>
                                    <option value="danger">Danger</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">Expires At (optional)</label>
                                <input type="datetime-local" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" id="expires_at" name="expires_at">
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200" onclick="hideModalManually(document.getElementById('createModal'))">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors duration-200">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<script>
function toggleNotification(id, isActive) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="is_active" value="${isActive}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteNotification(id) {
    if (confirm('Are you sure you want to delete this notification?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
