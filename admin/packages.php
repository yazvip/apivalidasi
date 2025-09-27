<?php
/**
 * Admin Panel - Packages Management
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
 * Log admin action
 */
function logAdminAction($pdo, $action, $details) {
    try {
        // Check if system_logs table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($stmt->rowCount() == 0) {
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
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $duration = (int)($_POST['duration'] ?? 30);
            $daily_limit = (int)($_POST['daily_limit'] ?? 1000);
            $features = $_POST['features'] ?? [];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Validation
            if (empty($name)) {
                $error = 'Package name is required';
            } elseif (strlen($name) > 255) {
                $error = 'Package name must be less than 255 characters';
            } elseif ($price < 0) {
                $error = 'Price must be non-negative';
            } elseif ($duration < 1 || $duration > 365) {
                $error = 'Duration must be between 1 and 365 days';
            } elseif ($daily_limit < 1 || $daily_limit > 100000) {
                $error = 'Daily limit must be between 1 and 100,000';
            } else {
                try {
                    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO packages (name, description, price, duration, daily_limit, features, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $name, 
                        $description, 
                        $price, 
                        $duration, 
                        $daily_limit, 
                        json_encode($features), 
                        $is_active
                    ]);
                    
                    // Log admin action
                    logAdminAction($pdo, 'Package Created', [
                        'name' => $name,
                        'price' => $price,
                        'duration' => $duration,
                        'daily_limit' => $daily_limit
                    ]);
                    
                    $message = "Package created successfully";
                } catch (Exception $e) {
                    $error = 'Error creating package: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $duration = (int)($_POST['duration'] ?? 30);
            $daily_limit = (int)($_POST['daily_limit'] ?? 1000);
            $features = $_POST['features'] ?? [];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($id <= 0) {
                $error = 'Invalid package ID';
            } elseif (empty($name)) {
                $error = 'Package name is required';
            } elseif (strlen($name) > 255) {
                $error = 'Package name must be less than 255 characters';
            } elseif ($price < 0) {
                $error = 'Price must be non-negative';
            } elseif ($duration < 1 || $duration > 365) {
                $error = 'Duration must be between 1 and 365 days';
            } elseif ($daily_limit < 1 || $daily_limit > 100000) {
                $error = 'Daily limit must be between 1 and 100,000';
            } else {
                try {
                    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $stmt = $pdo->prepare("
                        UPDATE packages 
                        SET name = ?, description = ?, price = ?, duration = ?, daily_limit = ?, features = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $name, $description, $price, $duration, $daily_limit, json_encode($features), $is_active, $id
                    ]);
                    
                    // Log admin action
                    logAdminAction($pdo, 'Package Updated', [
                        'id' => $id,
                        'name' => $name,
                        'price' => $price
                    ]);
                    
                    $message = "Package updated successfully";
                } catch (Exception $e) {
                    $error = 'Error updating package: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                $error = 'Invalid package ID';
            } else {
                try {
                    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Get package info before deletion for logging
                    $stmt = $pdo->prepare("SELECT name FROM packages WHERE id = ?");
                    $stmt->execute([$id]);
                    $packageInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    // Log admin action
                    if ($packageInfo) {
                        logAdminAction($pdo, 'Package Deleted', [
                            'id' => $id,
                            'name' => $packageInfo['name']
                        ]);
                    }
                    
                    $message = "Package deleted successfully";
                } catch (Exception $e) {
                    $error = 'Error deleting package: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $isActive = (int)($_POST['is_active'] ?? 0);
            
            if ($id <= 0) {
                $error = 'Invalid package ID';
            } else {
                try {
                    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $stmt = $pdo->prepare("UPDATE packages SET is_active = ? WHERE id = ?");
                    $stmt->execute([$isActive, $id]);
                    
                    // Log admin action
                    logAdminAction($pdo, 'Package Status Changed', [
                        'id' => $id,
                        'is_active' => $isActive
                    ]);
                    
                    $message = "Package status updated successfully";
                } catch (Exception $e) {
                    $error = 'Error updating package status: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get packages
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("
        SELECT * FROM packages 
        ORDER BY created_at DESC
    ");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $packages = [];
}

$pageTitle = 'Packages Management';
include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Packages Management</h1>
                <button type="button" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg shadow-lg transition-all duration-200 hover:shadow-xl flex items-center" onclick="showModal('createModal')">
                    <i class="fas fa-plus mr-2"></i> Create New Package
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

            <!-- Packages Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($packages as $package): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden <?= $package['is_active'] ? 'ring-2 ring-primary-500' : 'opacity-75' ?>">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($package['name']) ?></h3>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $package['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $package['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($package['description']) ?></p>
                        
                        <div class="mb-4">
                            <div class="text-3xl font-bold text-primary-600">$<?= number_format($package['price'], 2) ?></div>
                            <div class="text-sm text-gray-500">per package</div>
                        </div>
                        
                        <div class="space-y-2 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Duration:</span>
                                <span class="font-medium"><?= $package['duration'] ?> days</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Daily Limit:</span>
                                <span class="font-medium"><?= number_format($package['daily_limit']) ?> requests</span>
                            </div>
                        </div>
                        
                        <?php 
                        $features = json_decode($package['features'], true) ?: [];
                        if (!empty($features)): 
                        ?>
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Features:</h4>
                            <ul class="space-y-1">
                                <?php foreach ($features as $feature): ?>
                                <li class="text-sm text-gray-600 flex items-center">
                                    <i class="fas fa-check text-green-500 mr-2"></i>
                                    <?= htmlspecialchars($feature) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex space-x-2">
                            <button onclick="showModal('editModal<?= $package['id'] ?>')" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </button>
                            <form method="POST" class="inline-block">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $package['id'] ?>">
                                <input type="hidden" name="is_active" value="<?= $package['is_active'] ? 0 : 1 ?>">
                                <button type="submit" class="px-4 py-2 text-sm rounded-lg transition-colors duration-200 <?= $package['is_active'] ? 'bg-yellow-500 hover:bg-yellow-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white' ?>">
                                    <i class="fas fa-<?= $package['is_active'] ? 'pause' : 'play' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this package?')">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $package['id'] ?>">
                                <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-200 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Create Package Modal -->
        <div class="modal hidden" id="createModal" tabindex="-1">
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
                    <form method="POST" id="createForm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="text-lg font-semibold text-gray-900">Create New Package</h5>
                        </div>
                        <div class="p-6">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Package Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                           id="name" name="name" required maxlength="255">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                              id="description" name="description" rows="3"></textarea>
                                </div>
                                
                                <div>
                                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price ($) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                           id="price" name="price" required>
                                </div>
                                
                                <div>
                                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">Duration (Days) <span class="text-red-500">*</span></label>
                                    <input type="number" min="1" max="365" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                           id="duration" name="duration" value="30" required>
                                </div>
                                
                                <div>
                                    <label for="daily_limit" class="block text-sm font-medium text-gray-700 mb-2">Daily Limit <span class="text-red-500">*</span></label>
                                    <input type="number" min="1" max="100000" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                           id="daily_limit" name="daily_limit" value="1000" required>
                                </div>
                                
                                <div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_active" name="is_active" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">Active Package</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                                <div id="features-container">
                                    <div class="flex space-x-2 mb-2">
                                        <input type="text" name="features[]" placeholder="Feature name" 
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        <button type="button" onclick="removeFeature(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" onclick="addFeature()" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                    <i class="fas fa-plus mr-1"></i> Add Feature
                                </button>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200" onclick="hideModalManually(document.getElementById('createModal'))">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors duration-200 flex items-center">
                                <i class="fas fa-plus mr-2"></i> Create Package
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Modals for each Package -->
        <?php foreach ($packages as $package): ?>
        <div class="modal hidden" id="editModal<?= $package['id'] ?>" tabindex="-1">
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
                    <form method="POST" id="editForm<?= $package['id'] ?>">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h5 class="text-lg font-semibold text-gray-900">Edit Package: <?= htmlspecialchars($package['name']) ?></h5>
                        </div>
                        <div class="p-6">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= $package['id'] ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="name_<?= $package['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">Package Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                           id="name_<?= $package['id'] ?>" name="name" value="<?= htmlspecialchars($package['name']) ?>" required maxlength="255">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="description_<?= $package['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                              id="description_<?= $package['id'] ?>" name="description" rows="3"><?= htmlspecialchars($package['description']) ?></textarea>
                                </div>
                                
                                <div>
                                    <label for="price_<?= $package['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">Price ($) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                           id="price_<?= $package['id'] ?>" name="price" value="<?= $package['price'] ?>" required>
                                </div>
                                
                                <div>
                                    <label for="duration_<?= $package['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">Duration (Days) <span class="text-red-500">*</span></label>
                                    <input type="number" min="1" max="365" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                           id="duration_<?= $package['id'] ?>" name="duration" value="<?= $package['duration'] ?>" required>
                                </div>
                                
                                <div>
                                    <label for="daily_limit_<?= $package['id'] ?>" class="block text-sm font-medium text-gray-700 mb-2">Daily Limit <span class="text-red-500">*</span></label>
                                    <input type="number" min="1" max="100000" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                           id="daily_limit_<?= $package['id'] ?>" name="daily_limit" value="<?= $package['daily_limit'] ?>" required>
                                </div>
                                
                                <div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_active_<?= $package['id'] ?>" name="is_active" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" <?= $package['is_active'] ? 'checked' : '' ?>>
                                        <label for="is_active_<?= $package['id'] ?>" class="ml-2 text-sm font-medium text-gray-700">Active Package</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                                <div id="features-container_<?= $package['id'] ?>">
                                    <?php 
                                    $features = json_decode($package['features'], true) ?: [];
                                    foreach ($features as $index => $feature): 
                                    ?>
                                    <div class="flex space-x-2 mb-2">
                                        <input type="text" name="features[]" value="<?= htmlspecialchars($feature) ?>" placeholder="Feature name" 
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        <button type="button" onclick="removeFeature(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" onclick="addFeature('<?= $package['id'] ?>')" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                    <i class="fas fa-plus mr-1"></i> Add Feature
                                </button>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200" onclick="hideModalManually(document.getElementById('editModal<?= $package['id'] ?>'))">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors duration-200 flex items-center">
                                <i class="fas fa-save mr-2"></i> Update Package
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

<script>
// Add feature row
function addFeature(packageId = '') {
    const container = document.getElementById('features-container' + (packageId ? '_' + packageId : ''));
    const div = document.createElement('div');
    div.className = 'flex space-x-2 mb-2';
    div.innerHTML = `
        <input type="text" name="features[]" placeholder="Feature name" 
               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        <button type="button" onclick="removeFeature(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

// Remove feature row
function removeFeature(button) {
    button.parentElement.remove();
}
</script>

<?php include 'includes/footer.php'; ?>
