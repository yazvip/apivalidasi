<?php
/**
 * Validate Bank & Ewallet API - Production Ready with Enhanced Security
 * File untuk validasi bank account dan ewallet dengan sistem API key yang aman
 */

// Disable error reporting in production
error_reporting(0);
ini_set('display_errors', 0);

// Memory optimization settings
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 30);

require_once 'api_auth.php';
require_once 'config.php';

// Memory-optimized data loading
$ewalletCodes = getEwalletCodes();
$ariepulsaApiKey = getAriePulsaApiKey();

// Unset unused variables to free memory
unset($ewalletCodes, $ariepulsaApiKey);

// Set security headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Initialize API Auth
$apiAuth = new APIAuth();
$startTime = microtime(true);

// Rate limiting per minute
$rateLimitPerMinute = getRateLimitPerMinute();

/**
 * Validate bank account
 */
function validateAccount(string $bankCode, string $accountNumber): array {
    // Implementation for bank account validation
    // This would contain the actual validation logic
    return [
        'status' => false, 
        'data' => [
            'pesan' => 'Bank validation not implemented',
            'bank_code' => $bankCode,
            'account_number' => $accountNumber
        ]
    ];
}

/**
 * Validate e-wallet server 1 (AriePulsa) - Secure
 */
function validateEwalletServer1(string $ewalletCode, string $accountNumber): array {
    global $ariepulsaApiKey;
    
    // Sanitize inputs
    $ewalletCode = sanitizeInput($ewalletCode);
    $accountNumber = sanitizeInput($accountNumber);
    
    $url = 'https://ariepulsa.my.id/api/get-nickname-ewallet';
    $postData = [
        'api_key' => $ariepulsaApiKey, 
        'action' => 'get-nickname-ewallet', 
        'layanan' => $ewalletCode, 
        'target' => $accountNumber
    ];
    return makeCurlRequest($url, $postData);
}

/**
 * Validate e-wallet server 2 (OrderKuota)
 */
function validateEwalletServer2(string $ewalletCode, string $accountNumber): array {
    // OrderKuota API implementation
    $checker = new OrderKuotaChecker();
    try {
        $result = $checker->checkEwalletName(strtoupper($ewalletCode), $accountNumber);
        return $result;
    } catch (Exception $e) {
        return [
            'status' => false,
            'data' => [
                'pesan' => $e->getMessage(),
                'ewallet_code' => $ewalletCode,
                'account_number' => $accountNumber
            ]
        ];
    }
}

/**
 * Make cURL request - Enhanced Security & Memory Optimized
 */
function makeCurlRequest(string $url, array $postData): array {
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return [
            'status' => false,
            'data' => [
                'pesan' => 'Invalid URL provided'
            ]
        ];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_USERAGENT, 'API-Client/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'Accept-Encoding: gzip, deflate'
    ]);
    
    // Enable response compression
    curl_setopt($ch, CURLOPT_ENCODING, '');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Free memory immediately
    unset($ch);
    
    if ($error) {
        return [
            'status' => false,
            'data' => [
                'pesan' => 'Connection error occurred'
            ]
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'status' => false,
            'data' => [
                'pesan' => 'Service temporarily unavailable'
            ]
        ];
    }
    
    $data = json_decode($response, true);
    
    // Free response memory immediately
    unset($response);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'status' => false,
            'data' => [
                'pesan' => 'Invalid response format'
            ]
        ];
    }
    
    return $data;
}

/**
 * Sanitize input data
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Memory optimization helper
 */
function optimizeMemory() {
    // Force garbage collection
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
}

/**
 * Get memory usage info
 */
function getMemoryUsage(): array {
    return [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
    ];
}

/**
 * Get ewallet codes from database
 */
function getEwalletCodes(): array {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT code FROM ewallet_codes WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return ['shopeepay', 'dana', 'gopay', 'ovo', 'gopay_driver', 'linkaja', 'isaku'];
    }
}

/**
 * Get AriePulsa API key from database
 */
function getAriePulsaApiKey(): string {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT value FROM api_settings WHERE key = 'ariepulsa_api_key'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : 'fPZKLcPwR04zMyxGZYU58rcxMTfXaFNh';
    } catch (Exception $e) {
        return 'fPZKLcPwR04zMyxGZYU58rcxMTfXaFNh';
    }
}

/**
 * Get rate limit per minute from database
 */
function getRateLimitPerMinute(): int {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT value FROM api_settings WHERE key = 'rate_limit_per_minute'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['value'] : 60;
    } catch (Exception $e) {
        return 60;
    }
}

/**
 * Get maintenance mode status
 */
function getMaintenanceMode(): bool {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT value FROM api_settings WHERE key = 'maintenance_mode'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (bool)$result['value'] : false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get maintenance message
 */
function getMaintenanceMessage(): string {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT value FROM api_settings WHERE key = 'maintenance_message'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : 'System is currently under maintenance. Please try again later.';
    } catch (Exception $e) {
        return 'System is currently under maintenance. Please try again later.';
    }
}

/**
 * OrderKuota Checker Class
 */
class OrderKuotaChecker {
    const CHECKER_HOST = 'checker.orderkuota.com';
    const API_HOST = 'app.orderkuota.com';
    const USER_AGENT = 'okhttp/4.12.0';
    const TIMEOUT = 30;
    
    private static $checkerUrl = null;
    
    private function getStaticParams() {
        return [
            'app_reg_id' => 'dD9rR-_6TDu_YRaY34daCG:APA91bGi0fQq9nfHvry6spktX3A0lYnKShs1jbu-_ZwGx01jKNBmHTcFdtiiyypd81PGvmshxvX-ELebGBYgHcGeYT1NcaO-6yvi1ieVQBHzH5bB8Sr5hzo',
            'phone_uuid' => 'dD9rR-_6TDu_YRaY34daCG',
            'phone_model' => 'RMX3771',
            'phone_android_version' => '15',
            'app_version_code' => '250718',
            'app_version_name' => '25.07.18',
            'auth_username' => 'defac',
            'auth_token' => '2476730:DEGe91HFZILQTPUmXyKvhlotfbjiMwsc',
            'ui_mode' => 'light'
        ];
    }
    
    private function getLatestCheckerUrl() {
        if (self::$checkerUrl !== null) {
            return self::$checkerUrl;
        }
        
        $url = 'https://' . self::API_HOST . '/api/v2/get';
        
        $bodyPayload = http_build_query([
            'requests[9]' => 'top_menu_v2',
            'requests[6]' => 'unread_notification_count',
            'requests[5]' => 'bottom_menu',
            'requests[8]' => 'config',
            'requests[2]' => 'payments',
            'requests[1]' => 'navigation_menu',
            'requests[4]' => 'point',
            'requests[3]' => 'total_pending_trx',
            'requests[0]' => 'account',
        ] + $this->getStaticParams());
        
        $headers = [
            'Host: ' . self::API_HOST,
            'User-Agent: ' . self::USER_AGENT,
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $response = $this->makeRequest($url, $bodyPayload, $headers);
        
        if (isset($response['config']['results']['checkers']['url'])) {
            self::$checkerUrl = $response['config']['results']['checkers']['url'];
            return self::$checkerUrl;
        }
        
        throw new Exception('Checker URL not found in config response.');
    }
    
    public function checkEwalletName($provider, $phoneNumber) {
        // Validasi input
        if (empty($provider) || empty($phoneNumber)) {
            throw new Exception('Provider and phone number are required.');
        }
        
        // Validasi format nomor telepon
        if (!preg_match('/^08\d{8,11}$/', $phoneNumber)) {
            throw new Exception('Invalid phone number format. Use format: 08xxxxxxxxxx');
        }
        
        $urlTemplate = $this->getLatestCheckerUrl();
        $url = str_replace('{ID}', $provider, $urlTemplate);
        
        $bodyPayload = http_build_query([
            'phoneNumber' => $phoneNumber,
            'customerId' => '',
            'id' => $provider,
        ] + $this->getStaticParams());
        
        $headers = [
            'Host: ' . self::CHECKER_HOST,
            'User-Agent: ' . self::USER_AGENT,
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $result = $this->makeRequest($url, $bodyPayload, $headers);
        
        return $this->formatResponse($result, $provider, $phoneNumber);
    }
    
    private function formatResponse($apiResult, $provider, $phoneNumber) {
        $isSuccess = isset($apiResult['status']) && $apiResult['status'] === 'success';
        
        if (!$isSuccess) {
            return [
                'status' => false,
                'data' => [
                    'pesan' => $apiResult['message'] ?? 'Unknown error',
                    'ewallet_code' => $provider,
                    'account_number' => $phoneNumber
                ]
            ];
        }
        
        $accountName = $apiResult['message'] ?? 'Unknown';
        
        // Check if account_name contains phone number (invalid response)
        if ($this->containsPhoneNumber($accountName, $phoneNumber)) {
            return [
                'status' => false,
                'data' => [
                    'pesan' => 'Data tidak valid - Nama akun mengandung nomor telepon',
                    'ewallet_code' => $provider,
                    'account_number' => $phoneNumber
                ]
            ];
        }
        
        return [
            'status' => true,
            'data' => [
                'pesan' => 'Data Berhasil validasi',
                'ewallet_code' => $provider,
                'account_number' => $phoneNumber,
                'account_name' => $accountName
            ]
        ];
    }
    
    private function containsPhoneNumber($accountName, $phoneNumber) {
        // Check if account name contains the phone number
        if (strpos($accountName, $phoneNumber) !== false) {
            return true;
        }
        
        // Check if account name contains partial phone number (last 4 digits)
        $lastFourDigits = substr($phoneNumber, -4);
        if (strpos($accountName, $lastFourDigits) !== false) {
            return true;
        }
        
        // Check if account name contains phone number pattern (08xxxxxxxxxx)
        if (preg_match('/08\d{8,11}/', $accountName)) {
            return true;
        }
        
        return false;
    }
    
    private function makeRequest($url, $bodyPayload, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        
        // Enable response compression
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, [
            'Accept-Encoding: gzip, deflate'
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Free cURL handle memory immediately
        unset($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode === 200) {
            // Success
        } elseif ($httpCode === 469) {
            // Special case for 469 - might be success with different response
            $data = json_decode($response, true);
            if ($data && isset($data['status']) && $data['status'] === 'success') {
                // Free response memory
                unset($response);
                return $data;
            }
        } else {
            $errorMsg = "HTTP Error: " . $httpCode . " - " . substr($response, 0, 200);
            unset($response);
            throw new Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        
        // Free response memory immediately
        unset($response);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg());
        }
        
        return $data;
    }
}

// Handle API requests
try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? 'validate';
    $type = $_GET['type'] ?? 'ewallet'; // bank or ewallet
    $code = $_GET['code'] ?? $_GET['ewalletCode'] ?? $_GET['bankCode'] ?? '';
    $accountNumber = $_GET['accountNumber'] ?? $_GET['phone'] ?? $_GET['phoneNumber'] ?? '';
    $server = $_GET['server'] ?? '1'; // 1 for AriePulsa, 2 for OrderKuota
    
    // Get API key from header or parameter
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Check if IP is blocked
    $blockedIp = $apiAuth->isIpBlocked($ipAddress);
    if ($blockedIp) {
        http_response_code(403);
        echo json_encode([
            'status' => false,
            'data' => [
                'pesan' => 'IP address is blocked: ' . $blockedIp['reason'],
                'blocked_until' => $blockedIp['expires_at']
            ]
        ]);
        exit;
    }
    
    // Handle POST data with memory optimization
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $type = $input['type'] ?? $type;
            $code = $input['code'] ?? $input['ewalletCode'] ?? $input['bankCode'] ?? $code;
            $accountNumber = $input['accountNumber'] ?? $input['phone'] ?? $input['phoneNumber'] ?? $accountNumber;
            $server = $input['server'] ?? $server;
            
            // Free input memory immediately
            unset($input);
        }
    }
    
    // Show help if no parameters
    if (empty($code) && empty($accountNumber)) {
        // Enable output compression
        if (extension_loaded('zlib') && !ob_get_level()) {
            ob_start('ob_gzhandler');
        }
        
        $helpData = [
            'message' => 'Validate Bank & Ewallet API',
            'version' => '1.0.0',
            'usage' => [
                'ewallet' => '?type=ewallet&code=GOPAY&accountNumber=087841903677&server=1&api_key=YOUR_API_KEY',
                'bank' => '?type=bank&code=BCA&accountNumber=1234567890&api_key=YOUR_API_KEY',
                'examples' => [
                    'AriePulsa' => '?type=ewallet&code=gopay&accountNumber=087841903677&server=1&api_key=YOUR_API_KEY',
                    'OrderKuota' => '?type=ewallet&code=GOPAY&accountNumber=087841903677&server=2&api_key=YOUR_API_KEY',
                    'Bank' => '?type=bank&code=BCA&accountNumber=1234567890&api_key=YOUR_API_KEY'
                ]
            ],
            'supported_ewallet_codes' => getEwalletCodes(),
            'servers' => [
                '1' => 'AriePulsa API',
                '2' => 'OrderKuota API'
            ],
            'authentication' => [
                'method' => 'API Key',
                'header' => 'X-API-Key: YOUR_API_KEY',
                'parameter' => '?api_key=YOUR_API_KEY'
            ]
        ];
        
        echo json_encode($helpData, JSON_PRETTY_PRINT);
        
        // Free memory
        unset($helpData);
        exit;
    }
    
    // Validate API key
    $authResult = $apiAuth->validateApiKey($apiKey);
    if (!$authResult['valid']) {
        http_response_code(401);
        echo json_encode([
            'status' => false,
            'data' => [
                'pesan' => $authResult['error']
            ]
        ]);
        exit;
    }
    
    // Check rate limit
    $rateLimitResult = $apiAuth->checkRateLimit($apiKey);
    if (!$rateLimitResult['valid']) {
        http_response_code(429);
        echo json_encode([
            'status' => false,
            'data' => [
                'pesan' => $rateLimitResult['error']
            ]
        ]);
        exit;
    }
    
    // Validate required parameters
    if (empty($code)) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'data' => [
                'pesan' => 'Code is required',
                'type' => $type,
                'account_number' => $accountNumber ?? ''
            ]
        ]);
        exit;
    }
    
    if (empty($accountNumber)) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'data' => [
                'pesan' => 'Account number is required',
                'type' => $type,
                'code' => $code
            ]
        ]);
        exit;
    }
    
// Check maintenance mode
$maintenanceMode = getMaintenanceMode();
if ($maintenanceMode) {
    http_response_code(503);
    echo json_encode([
        'status' => false,
        'data' => [
            'pesan' => getMaintenanceMessage(),
            'maintenance' => true,
            'type' => $type,
            'code' => $code,
            'account_number' => $accountNumber
        ]
    ]);
    exit;
}

// Check server status
$serverName = $server === '1' ? SERVER_1_NAME : SERVER_2_NAME;
$serverStatus = $apiAuth->getServerStatus($serverName);

if (!$serverStatus || !$serverStatus['is_active']) {
    http_response_code(503);
    echo json_encode([
        'status' => false,
        'data' => [
            'pesan' => 'Server ' . $serverName . ' is currently offline for maintenance',
            'server' => $serverName,
            'type' => $type,
            'code' => $code,
            'account_number' => $accountNumber
        ]
    ]);
    exit;
}
    
    // Process request based on type
    if ($type === 'bank') {
        $result = validateAccount($code, $accountNumber);
    } elseif ($type === 'ewallet') {
        // Validate ewallet code
        if (!in_array(strtolower($code), EWALLET_CODES)) {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'data' => [
                    'pesan' => 'Invalid ewallet code. Supported: ' . implode(', ', EWALLET_CODES),
                    'ewallet_code' => $code,
                    'account_number' => $accountNumber
                ]
            ]);
            exit;
        }
        
        // Choose server
        if ($server === '1') {
            $result = validateEwalletServer1($code, $accountNumber);
        } elseif ($server === '2') {
            $result = validateEwalletServer2($code, $accountNumber);
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'data' => [
                    'pesan' => 'Invalid server. Use 1 for AriePulsa or 2 for OrderKuota',
                    'ewallet_code' => $code,
                    'account_number' => $accountNumber
                ]
            ]);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'data' => [
                'pesan' => 'Invalid type. Use "bank" or "ewallet"',
                'type' => $type,
                'code' => $code,
                'account_number' => $accountNumber
            ]
        ]);
        exit;
    }
    
    // Calculate response time
    $responseTime = microtime(true) - $startTime;
    
    // Log the request
    $apiAuth->logRequest(
        $apiKey,
        $ipAddress,
        $type . '_' . $code . '_' . $server,
        [
            'type' => $type,
            'code' => $code,
            'account_number' => $accountNumber,
            'server' => $server
        ],
        $result,
        $result['status'] ? 200 : 400,
        $responseTime
    );
    
    // Add rate limit info to response
    $result['rate_limit'] = [
        'remaining' => $rateLimitResult['remaining'],
        'reset_time' => date('Y-m-d H:i:s', strtotime('+1 day'))
    ];
    
    // Enable output compression for response
    if (extension_loaded('zlib') && !ob_get_level()) {
        ob_start('ob_gzhandler');
    }
    
    // Return result
    if ($result['status']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
    // Free memory
    unset($result, $rateLimitResult);
    
    // Final memory optimization
    optimizeMemory();
    
} catch (Exception $e) {
    $responseTime = microtime(true) - $startTime;
    
    // Log error
    if (isset($apiKey) && isset($ipAddress)) {
        $apiAuth->logRequest(
            $apiKey,
            $ipAddress,
            'error',
            [
                'type' => $type ?? '',
                'code' => $code ?? '',
                'account_number' => $accountNumber ?? '',
                'server' => $server ?? ''
            ],
            ['error' => $e->getMessage()],
            500,
            $responseTime
        );
    }
    
    // Enable output compression for error response
    if (extension_loaded('zlib') && !ob_get_level()) {
        ob_start('ob_gzhandler');
    }
    
    http_response_code(500);
    $errorData = [
        'status' => false,
        'data' => [
            'pesan' => $e->getMessage(),
            'type' => $type ?? '',
            'code' => $code ?? '',
            'account_number' => $accountNumber ?? ''
        ]
    ];
    
    echo json_encode($errorData, JSON_PRETTY_PRINT);
    
    // Free memory
    unset($errorData, $e);
    
    // Final memory optimization
    optimizeMemory();
}
?>
