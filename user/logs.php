<?php
/**
 * User Panel - API Logs
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

$pageTitle = 'API Logs';

// Get user's API logs
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    // Get total count for user's API keys
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM api_logs al
        JOIN api_keys ak ON al.api_key = ak.api_key
        WHERE ak.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $totalLogs = $stmt->fetchColumn();
    $totalPages = ceil($totalLogs / $limit);
    
    // Get logs for user's API keys
    $stmt = $pdo->prepare("
        SELECT al.*, ak.name as api_key_name
        FROM api_logs al
        JOIN api_keys ak ON al.api_key = ak.api_key
        WHERE ak.user_id = ?
        ORDER BY al.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $logs = [];
    $totalPages = 0;
}

include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">API Logs</h1>
                <div class="flex space-x-3">
                    <button onclick="location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i> Refresh
                    </button>
                </div>
            </div>

            <?php if (isset($error)): ?>
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
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Time</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">API Key</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">IP Address</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Endpoint</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider">Response Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-list text-4xl mb-4 text-gray-300"></i>
                                    <p class="text-lg">No logs found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($log['api_key_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($log['ip_address']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs"><?= htmlspecialchars($log['endpoint']) ?></code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $log['status_code'] >= 200 && $log['status_code'] < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $log['status_code'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $log['response_time'] ?>ms
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-8">
                <nav class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-3 py-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-3 py-2 <?= $i === $page ? 'bg-primary-600 text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' ?> rounded-lg transition-colors duration-200">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-3 py-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>

<?php include 'includes/footer.php'; ?>
