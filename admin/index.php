<?php
/**
 * Admin Panel - Main Dashboard with AJAX Navigation
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

$apiAuth = new APIAuth();

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $page = $_GET['page'] ?? 'index';
    $pageFile = $page . '.php';
    
    // Check if page exists and is in admin directory
    if (file_exists($pageFile) && $page !== 'login' && $page !== 'logout') {
        ob_start();
        include $pageFile;
        $content = ob_get_clean();
        
        // Extract title from content
        preg_match('/<title>(.*?)<\/title>/i', $content, $titleMatches);
        $title = $titleMatches[1] ?? 'Admin Panel';
        
        // Extract main content (everything between <main> tags or after sidebar)
        preg_match('/<main[^>]*>(.*?)<\/main>/is', $content, $mainMatches);
        if ($mainMatches) {
            $mainContent = $mainMatches[1];
        } else {
            // Fallback: get content after sidebar
            $parts = explode('<!-- Main content -->', $content);
            if (count($parts) > 1) {
                $mainContent = $parts[1];
                $mainContent = preg_replace('/<\/div>\s*<\/div>\s*<\/div>\s*<\/body>\s*<\/html>.*$/s', '', $mainContent);
            } else {
                $mainContent = $content;
            }
        }
        
        echo json_encode([
            'success' => true,
            'title' => $title,
            'content' => $mainContent
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Page not found'
        ]);
    }
    exit;
}

// Get statistics for dashboard
$stats = [
    'total_api_keys' => 0,
    'active_api_keys' => 0,
    'total_requests_today' => 0,
    'total_requests_this_month' => 0,
    'blocked_ips' => 0,
    'server_status' => []
];

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get API keys stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM api_keys");
    $stats['total_api_keys'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM api_keys WHERE is_active = 1");
    $stats['active_api_keys'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get requests stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM api_logs WHERE DATE(created_at) = CURDATE()");
    $stats['total_requests_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM api_logs WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stats['total_requests_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get blocked IPs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM blocked_ips WHERE is_active = 1");
    $stats['blocked_ips'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get server status
    $stmt = $pdo->query("SELECT * FROM server_status ORDER BY server_name");
    $stats['server_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

$pageTitle = 'Admin Dashboard';
include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors duration-200 flex items-center" onclick="location.reload()">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
            </div>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-primary-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total API Keys</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $stats['total_api_keys'] ?></p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-key text-3xl text-gray-300"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Active API Keys</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $stats['active_api_keys'] ?></p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-check-circle text-3xl text-gray-300"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Requests Today</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_requests_today']) ?></p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-calendar-day text-3xl text-gray-300"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Blocked IPs</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $stats['blocked_ips'] ?></p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-ban text-3xl text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Server Status -->
            <div class="bg-white rounded-xl shadow-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h6 class="text-lg font-semibold text-gray-900">Server Status</h6>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($stats['server_status'] as $server): ?>
                        <div class="flex items-center">
                            <div class="mr-4">
                                <i class="fas fa-server text-2xl text-<?= $server['is_active'] ? 'green' : 'red' ?>-500"></i>
                            </div>
                            <div class="flex-1">
                                <h6 class="text-lg font-medium text-gray-900 mb-1"><?= htmlspecialchars($server['server_name']) ?></h6>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $server['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $server['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                                <?php if ($server['response_time']): ?>
                                <p class="text-sm text-gray-500 mt-1">Response: <?= $server['response_time'] ?>ms</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h6 class="text-lg font-semibold text-gray-900">Quick Actions</h6>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="api-keys.php" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-key mr-2"></i>Manage API Keys
                        </a>
                        <a href="analytics.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-chart-line mr-2"></i>View Analytics
                        </a>
                        <a href="logs.php" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-list mr-2"></i>View Logs
                        </a>
                        <a href="settings.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-cog mr-2"></i>Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

<!-- Loading indicator -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="loading">
    <div class="bg-white rounded-lg p-8 flex flex-col items-center">
        <i class="fas fa-spinner fa-spin text-3xl text-primary-600 mb-4"></i>
        <p class="text-gray-700">Loading...</p>
    </div>
</div>

<script>
// AJAX Navigation
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.ajax-nav');
    const mainContent = document.querySelector('.main-content .content-wrapper');
    const loading = document.getElementById('loading');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const page = this.getAttribute('data-page');
            if (!page) return;
            
            // Show loading
            loading.style.display = 'block';
            mainContent.style.display = 'none';
            
            // Update active nav
            navLinks.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Load page content
            fetch(`?ajax=1&page=${page}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update page title
                        document.title = data.title + ' - API Management System';
                        
                        // Update content
                        mainContent.innerHTML = data.content;
                        
                        // Re-initialize any JavaScript in the loaded content
                        initializePageScripts();
                    } else {
                        mainContent.innerHTML = '<div class="alert alert-danger">Error loading page: ' + data.error + '</div>';
                    }
                })
                .catch(error => {
                    mainContent.innerHTML = '<div class="alert alert-danger">Error loading page: ' + error.message + '</div>';
                })
                .finally(() => {
                    loading.style.display = 'none';
                    mainContent.style.display = 'block';
                });
        });
    });
});

// Initialize page-specific scripts
function initializePageScripts() {
    // Re-initialize Bootstrap components
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Re-initialize modals
    const modalTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="modal"]'));
    modalTriggerList.map(function (modalTriggerEl) {
        return new bootstrap.Modal(modalTriggerEl);
    });
    
    // Re-initialize any custom scripts
    if (typeof window.pageInit === 'function') {
        window.pageInit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>