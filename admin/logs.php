<?php
/**
 * Admin Panel - API Logs
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

$pageTitle = 'API Logs';

// Get logs
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) FROM api_logs");
    $totalLogs = $stmt->fetchColumn();
    $totalPages = ceil($totalLogs / $limit);
    
    // Get logs
    $stmt = $pdo->prepare("
        SELECT al.*, ak.name as api_key_name
        FROM api_logs al
        LEFT JOIN api_keys ak ON al.api_key = ak.api_key
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

<div class="main-content">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">API Logs</h1>
            <div class="btn-group">
                <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="clearLogs()">
                    <i class="fas fa-trash me-1"></i>Clear Logs
                </button>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>API Key</th>
                                <th>IP Address</th>
                                <th>Endpoint</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No logs found
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($log['api_key_name'] ?? 'Unknown') ?></span>
                                </td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td><?= htmlspecialchars($log['endpoint']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $log['status_code'] >= 200 && $log['status_code'] < 300 ? 'success' : 'danger' ?>">
                                        <?= $log['status_code'] ?>
                                    </span>
                                </td>
                                <td><?= $log['response_time'] ?>ms</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewLog(<?= $log['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Logs pagination">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetails">
                <!-- Log details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewLog(logId) {
    // Load log details via AJAX
    fetch(`?action=view_log&id=${logId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('logDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Request Data</h6>
                            <pre class="bg-light p-3 rounded">${data.log.request_data || 'No data'}</pre>
                        </div>
                        <div class="col-md-6">
                            <h6>Response Data</h6>
                            <pre class="bg-light p-3 rounded">${data.log.response_data || 'No data'}</pre>
                        </div>
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('logModal')).show();
            }
        });
}

function clearLogs() {
    if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
        fetch('?action=clear_logs', {method: 'POST'})
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }
}
</script>

<?php
// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($_GET['action'] === 'view_log') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM api_logs WHERE id = ?");
            $stmt->execute([$id]);
            $log = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($log) {
                echo json_encode(['success' => true, 'log' => $log]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Log not found']);
            }
        } elseif ($_GET['action'] === 'clear_logs' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $pdo->exec("DELETE FROM api_logs");
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

include 'includes/footer.php';
?>
