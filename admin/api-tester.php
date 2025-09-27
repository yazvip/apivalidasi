<?php
/**
 * Admin Panel - API Tester
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

$pageTitle = 'API Tester';
$testResult = '';
$testError = '';

// Handle API test request
if ($_POST && isset($_POST['test_api'])) {
    $apiKey = trim($_POST['api_key'] ?? '');
    $endpoint = trim($_POST['endpoint'] ?? '');
    $method = $_POST['method'] ?? 'GET';
    $headers = $_POST['headers'] ?? [];
    $body = $_POST['body'] ?? '';
    
    if (empty($apiKey) || empty($endpoint)) {
        $testError = 'API Key and Endpoint are required';
    } else {
        try {
            // Prepare headers
            $requestHeaders = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ];
            
            // Add custom headers
            foreach ($headers as $key => $value) {
                if (!empty($key) && !empty($value)) {
                    $requestHeaders[] = $key . ': ' . $value;
                }
            }
            
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            } elseif ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            } elseif ($method === 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            }
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                $testError = 'cURL Error: ' . $error;
            } else {
                $testResult = [
                    'status_code' => $httpCode,
                    'response' => $response,
                    'headers' => $requestHeaders,
                    'method' => $method,
                    'endpoint' => $endpoint
                ];
            }
            
        } catch (Exception $e) {
            $testError = 'Error: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

        <!-- Main content -->
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">API Tester</h1>
                <div class="flex space-x-3">
                    <button onclick="loadPreset('bank_validation')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-university mr-2"></i> Bank Validation
                    </button>
                    <button onclick="loadPreset('phone_validation')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-phone mr-2"></i> Phone Validation
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Test Form -->
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-semibold text-gray-900">Test API Request</h5>
                    </div>
                    <div class="p-6">
                        <form method="POST" id="testForm">
                            <div class="mb-4">
                                <label for="api_key" class="block text-sm font-medium text-gray-700 mb-2">API Key <span class="text-red-500">*</span></label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                       id="api_key" name="api_key" required placeholder="Enter API Key">
                            </div>
                            
                            <div class="mb-4">
                                <label for="method" class="block text-sm font-medium text-gray-700 mb-2">HTTP Method</label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                        id="method" name="method">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="endpoint" class="block text-sm font-medium text-gray-700 mb-2">Endpoint URL <span class="text-red-500">*</span></label>
                                <input type="url" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                       id="endpoint" name="endpoint" required placeholder="https://example.com/api/endpoint">
                            </div>
                            
                            <div class="mb-4">
                                <label for="body" class="block text-sm font-medium text-gray-700 mb-2">Request Body (JSON)</label>
                                <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                          id="body" name="body" rows="6" placeholder='{"key": "value"}'></textarea>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Custom Headers</label>
                                <div id="headers-container">
                                    <div class="flex space-x-2 mb-2">
                                        <input type="text" name="header_key[]" placeholder="Header Name" 
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        <input type="text" name="header_value[]" placeholder="Header Value" 
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        <button type="button" onclick="removeHeader(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" onclick="addHeader()" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                    <i class="fas fa-plus mr-1"></i> Add Header
                                </button>
                            </div>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="clearForm()" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200">
                                    Clear
                                </button>
                                <button type="submit" name="test_api" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors duration-200 flex items-center">
                                    <i class="fas fa-play mr-2"></i> Test API
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Test Results -->
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-semibold text-gray-900">Test Results</h5>
                    </div>
                    <div class="p-6">
                        <?php if ($testError): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <?= htmlspecialchars($testError) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($testResult): ?>
                        <div class="space-y-4">
                            <div>
                                <h6 class="font-semibold text-gray-900 mb-2">Status Code</h6>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $testResult['status_code'] >= 200 && $testResult['status_code'] < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $testResult['status_code'] ?>
                                </span>
                            </div>
                            
                            <div>
                                <h6 class="font-semibold text-gray-900 mb-2">Response</h6>
                                <pre class="bg-gray-100 p-4 rounded-lg text-sm overflow-x-auto"><?= htmlspecialchars($testResult['response']) ?></pre>
                            </div>
                            
                            <div>
                                <h6 class="font-semibold text-gray-900 mb-2">Request Details</h6>
                                <div class="text-sm text-gray-600">
                                    <p><strong>Method:</strong> <?= htmlspecialchars($testResult['method']) ?></p>
                                    <p><strong>Endpoint:</strong> <?= htmlspecialchars($testResult['endpoint']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-flask text-4xl mb-4"></i>
                            <p>No test results yet. Run a test to see results here.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<script>
// Add header row
function addHeader() {
    const container = document.getElementById('headers-container');
    const div = document.createElement('div');
    div.className = 'flex space-x-2 mb-2';
    div.innerHTML = `
        <input type="text" name="header_key[]" placeholder="Header Name" 
               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        <input type="text" name="header_value[]" placeholder="Header Value" 
               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        <button type="button" onclick="removeHeader(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

// Remove header row
function removeHeader(button) {
    button.parentElement.remove();
}

// Clear form
function clearForm() {
    document.getElementById('testForm').reset();
    document.getElementById('headers-container').innerHTML = `
        <div class="flex space-x-2 mb-2">
            <input type="text" name="header_key[]" placeholder="Header Name" 
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            <input type="text" name="header_value[]" placeholder="Header Value" 
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            <button type="button" onclick="removeHeader(this)" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
}

// Load preset configurations
function loadPreset(type) {
    if (type === 'bank_validation') {
        document.getElementById('method').value = 'POST';
        document.getElementById('endpoint').value = '<?= SITE_URL ?>/api/validate_bank.php';
        document.getElementById('body').value = JSON.stringify({
            "bank_code": "002",
            "account_number": "1234567890"
        }, null, 2);
    } else if (type === 'phone_validation') {
        document.getElementById('method').value = 'POST';
        document.getElementById('endpoint').value = '<?= SITE_URL ?>/api/validate_phone.php';
        document.getElementById('body').value = JSON.stringify({
            "phone_number": "081234567890"
        }, null, 2);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
