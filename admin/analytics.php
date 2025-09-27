<?php
/**
 * Admin Panel - Analytics Dashboard
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

$pageTitle = 'Analytics Dashboard';

// Get analytics data
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get API usage statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_requests,
            COUNT(DISTINCT api_key) as active_keys,
            COUNT(DISTINCT DATE(created_at)) as active_days
        FROM api_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get daily requests for chart
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as requests
        FROM api_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $stats = ['total_requests' => 0, 'active_keys' => 0, 'active_days' => 0];
    $dailyData = [];
}

include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
                <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors duration-200 flex items-center" onclick="refreshData()">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-primary-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Requests</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_requests']) ?></p>
                            <p class="text-sm text-gray-500">Last 30 days</p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-chart-line text-3xl text-primary-500"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Active Keys</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['active_keys']) ?></p>
                            <p class="text-sm text-gray-500">Last 30 days</p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-key text-3xl text-green-500"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Active Days</p>
                            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['active_days']) ?></p>
                            <p class="text-sm text-gray-500">Last 30 days</p>
                        </div>
                        <div class="ml-4">
                            <i class="fas fa-calendar text-3xl text-blue-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white rounded-xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">Daily API Requests</h5>
                </div>
                <div class="p-6">
                    <canvas id="requestsChart" height="100"></canvas>
                </div>
            </div>
        </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dailyData = <?= json_encode($dailyData) ?>;

const ctx = document.getElementById('requestsChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: 'API Requests',
            data: dailyData.map(d => d.requests),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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
</script>

<?php include 'includes/footer.php'; ?>
