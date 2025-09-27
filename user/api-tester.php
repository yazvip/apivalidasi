<?php
/**
 * User Panel - Enhanced API Tester for Bank & Ewallet Validation
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

$pageTitle = 'API Tester';
$testResult = '';
$testError = '';
$user = $_SESSION['user_data'];

// Handle preset loading
$preset = $_GET['preset'] ?? '';

// Handle API test request
if ($_POST && isset($_POST['test_api'])) {
    $type = $_POST['type'] ?? 'ewallet';
    $code = trim($_POST['code'] ?? '');
    $accountNumber = trim($_POST['account_number'] ?? '');
    $server = $_POST['server'] ?? '1';
    
    if (empty($code)) {
        $testError = 'Code is required';
    } elseif (empty($accountNumber)) {
        $testError = 'Account number is required';
    } else {
        try {
            // Prepare request data
            $requestData = [
                'type' => $type,
                'code' => $code,
                'account_number' => $accountNumber
            ];
            
            if ($type === 'ewallet') {
                $requestData['server'] = $server;
            }
            
            // Make API request to our own API
            $url = SITE_URL . '/api/validate_bank.php';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $user['api_key']
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                $testError = 'Connection Error: ' . $error;
            } else {
                $testResult = [
                    'status_code' => $httpCode,
                    'response' => $response,
                    'request_data' => $requestData,
                    'endpoint' => $url,
                    'formatted_response' => json_decode($response, true)
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
                    <button onclick="loadPreset('ewallet_gopay')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-mobile-alt mr-2"></i> Test GoPay
                    </button>
                    <button onclick="loadPreset('ewallet_dana')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-wallet mr-2"></i> Test DANA
                    </button>
                    <button onclick="loadPreset('bank_bca')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-university mr-2"></i> Test Bank
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Test Form -->
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-semibold text-gray-900">Test Validation API</h5>
                    </div>
                    <div class="p-6">
                        <form method="POST" id="testForm">
                            <div class="mb-4">
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Validation Type</label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                        id="type" name="type" onchange="toggleTypeFields()">
                                    <option value="ewallet">E-Wallet Validation</option>
                                    <option value="bank">Bank Account Validation</option>
                                </select>
                            </div>
                            
                            <div class="mb-4" id="ewallet_fields">
                                <label for="ewallet_code" class="block text-sm font-medium text-gray-700 mb-2">E-Wallet Provider <span class="text-red-500">*</span></label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                        id="ewallet_code" name="code">
                                    <option value="">Select E-Wallet</option>
                                    <option value="gopay">GoPay</option>
                                    <option value="dana">DANA</option>
                                    <option value="ovo">OVO</option>
                                    <option value="shopeepay">ShopeePay</option>
                                    <option value="linkaja">LinkAja</option>
                                    <option value="isaku">iSaku</option>
                                    <option value="gopay_driver">GoPay Driver</option>
                                </select>
                            </div>
                            
                            <div class="mb-4 hidden" id="bank_fields">
                                <label for="bank_code" class="block text-sm font-medium text-gray-700 mb-2">Bank <span class="text-red-500">*</span></label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                        id="bank_code" name="code">
                                    <option value="">Select Bank</option>
                                    <?php foreach (BANK_CODES as $bankCode => $bankName): ?>
                                    <option value="<?= $bankCode ?>"><?= $bankName ?> (<?= $bankCode ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    <span id="account_label">Phone Number</span> <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                       id="account_number" name="account_number" placeholder="081234567890" required>
                                <p class="text-sm text-gray-500 mt-1" id="account_help">Format: 08xxxxxxxxxx for e-wallet</p>
                            </div>
                            
                            <div class="mb-4" id="server_field">
                                <label for="server" class="block text-sm font-medium text-gray-700 mb-2">Server</label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200" 
                                        id="server" name="server">
                                    <option value="1">Server 1 (AriePulsa) - Recommended</option>
                                    <option value="2">Server 2 (OrderKuota) - Coming Soon</option>
                                </select>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <div class="flex">
                                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                                    <div>
                                        <h6 class="font-semibold text-blue-900">Your API Key</h6>
                                        <code class="text-blue-800 text-sm"><?= htmlspecialchars($user['api_key']) ?></code>
                                        <p class="text-blue-700 text-sm mt-1">This will be used automatically for testing</p>
                                    </div>
                                </div>
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
                                    <?= $testResult['status_code'] ?> - <?= $testResult['status_code'] >= 200 && $testResult['status_code'] < 300 ? 'Success' : 'Error' ?>
                                </span>
                            </div>
                            
                            <?php if ($testResult['formatted_response']): ?>
                            <div>
                                <h6 class="font-semibold text-gray-900 mb-2">Validation Result</h6>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <?php 
                                    $response = $testResult['formatted_response'];
                                    if (isset($response['success']) && $response['success']): 
                                    ?>
                                    <div class="flex items-center text-green-700 mb-3">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span class="font-semibold">Validation Successful</span>
                                    </div>
                                    <?php if (isset($response['data'])): ?>
                                    <div class="space-y-2">
                                        <?php if (isset($response['data']['account_name'])): ?>
                                        <p><strong>Account Name:</strong> <?= htmlspecialchars($response['data']['account_name']) ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($response['data']['account_holder'])): ?>
                                        <p><strong>Account Holder:</strong> <?= htmlspecialchars($response['data']['account_holder']) ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($response['data']['bank_name'])): ?>
                                        <p><strong>Bank:</strong> <?= htmlspecialchars($response['data']['bank_name']) ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($response['data']['provider'])): ?>
                                        <p><strong>Provider:</strong> <?= htmlspecialchars($response['data']['provider']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <div class="flex items-center text-red-700 mb-3">
                                        <i class="fas fa-times-circle mr-2"></i>
                                        <span class="font-semibold">Validation Failed</span>
                                    </div>
                                    <p class="text-red-600"><?= htmlspecialchars($response['message'] ?? 'Unknown error') ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div>
                                <h6 class="font-semibold text-gray-900 mb-2">Raw Response</h6>
                                <pre class="bg-gray-100 p-4 rounded-lg text-sm overflow-x-auto max-h-64"><?= htmlspecialchars($testResult['response']) ?></pre>
                            </div>
                            
                            <div>
                                <h6 class="font-semibold text-gray-900 mb-2">Request Details</h6>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm"><strong>Endpoint:</strong> <?= htmlspecialchars($testResult['endpoint']) ?></p>
                                    <p class="text-sm"><strong>Method:</strong> POST</p>
                                    <p class="text-sm"><strong>Request Data:</strong></p>
                                    <pre class="text-xs mt-2 bg-white p-2 rounded border"><?= htmlspecialchars(json_encode($testResult['request_data'], JSON_PRETTY_PRINT)) ?></pre>
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
            
            <!-- API Examples -->
            <div class="mt-8 bg-white rounded-xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-900">API Usage Examples</h5>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- E-Wallet Example -->
                        <div>
                            <h6 class="font-semibold text-gray-900 mb-3">E-Wallet Validation Example</h6>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm font-medium mb-2">cURL Example:</p>
                                <pre class="text-xs bg-gray-900 text-green-400 p-3 rounded overflow-x-auto">curl -X POST "<?= SITE_URL ?>/api/validate_bank.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <?= htmlspecialchars($user['api_key']) ?>" \
  -d '{
    "type": "ewallet",
    "code": "gopay",
    "account_number": "081234567890",
    "server": "1"
  }'</pre>
                            </div>
                        </div>
                        
                        <!-- Bank Example -->
                        <div>
                            <h6 class="font-semibold text-gray-900 mb-3">Bank Account Validation Example</h6>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm font-medium mb-2">cURL Example:</p>
                                <pre class="text-xs bg-gray-900 text-green-400 p-3 rounded overflow-x-auto">curl -X POST "<?= SITE_URL ?>/api/validate_bank.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <?= htmlspecialchars($user['api_key']) ?>" \
  -d '{
    "type": "bank",
    "code": "014",
    "account_number": "1234567890"
  }'</pre>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Response Format -->
                    <div class="mt-6">
                        <h6 class="font-semibold text-gray-900 mb-3">Expected Response Format</h6>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <pre class="text-sm">{
  "success": true,
  "message": "Validation successful",
  "data": {
    "ewallet_code": "gopay",
    "account_number": "081234567890",
    "account_name": "John Doe",
    "provider": "AriePulsa",
    "is_valid": true
  },
  "metadata": {
    "request_id": "unique_id",
    "timestamp": "2024-01-01 12:00:00",
    "response_time_ms": 250.5,
    "api_version": "1.0.0",
    "rate_limit_remaining": 999
  }
}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<script>
// Toggle fields based on validation type
function toggleTypeFields() {
    const type = document.getElementById('type').value;
    const ewalletFields = document.getElementById('ewallet_fields');
    const bankFields = document.getElementById('bank_fields');
    const serverField = document.getElementById('server_field');
    const accountLabel = document.getElementById('account_label');
    const accountHelp = document.getElementById('account_help');
    const accountNumber = document.getElementById('account_number');
    
    if (type === 'ewallet') {
        ewalletFields.classList.remove('hidden');
        bankFields.classList.add('hidden');
        serverField.classList.remove('hidden');
        accountLabel.textContent = 'Phone Number';
        accountHelp.textContent = 'Format: 08xxxxxxxxxx for e-wallet';
        accountNumber.placeholder = '081234567890';
        
        // Set required attribute
        document.getElementById('ewallet_code').required = true;
        document.getElementById('bank_code').required = false;
    } else {
        ewalletFields.classList.add('hidden');
        bankFields.classList.remove('hidden');
        serverField.classList.add('hidden');
        accountLabel.textContent = 'Account Number';
        accountHelp.textContent = 'Bank account number (8-20 digits)';
        accountNumber.placeholder = '1234567890';
        
        // Set required attribute
        document.getElementById('ewallet_code').required = false;
        document.getElementById('bank_code').required = true;
    }
}

// Load preset configurations
function loadPreset(type) {
    if (type === 'ewallet_gopay') {
        document.getElementById('type').value = 'ewallet';
        toggleTypeFields();
        document.getElementById('ewallet_code').value = 'gopay';
        document.getElementById('account_number').value = '081234567890';
        document.getElementById('server').value = '1';
    } else if (type === 'ewallet_dana') {
        document.getElementById('type').value = 'ewallet';
        toggleTypeFields();
        document.getElementById('ewallet_code').value = 'dana';
        document.getElementById('account_number').value = '081234567890';
        document.getElementById('server').value = '1';
    } else if (type === 'bank_bca') {
        document.getElementById('type').value = 'bank';
        toggleTypeFields();
        document.getElementById('bank_code').value = '014';
        document.getElementById('account_number').value = '1234567890';
    }
}

// Clear form
function clearForm() {
    document.getElementById('testForm').reset();
    toggleTypeFields();
}

// Initialize form and handle presets
document.addEventListener('DOMContentLoaded', function() {
    toggleTypeFields();
    
    // Handle preset from URL
    const urlParams = new URLSearchParams(window.location.search);
    const preset = urlParams.get('preset');
    
    if (preset === 'ewallet') {
        loadPreset('ewallet_gopay');
    } else if (preset === 'bank') {
        loadPreset('bank_bca');
    }
});
</script>

<?php include 'includes/footer.php'; ?>