<?php
/**
 * Bank & Ewallet Validation API - Enhanced Version
 * Main API endpoint for validating bank accounts and ewallet numbers
 */

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';
require_once '../api_auth.php';

$apiAuth = new APIAuth();
$startTime = microtime(true);

/**
 * Sanitize input data
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate ewallet using AriePulsa API
 */
function validateEwalletAriePulsa($ewalletCode, $accountNumber) {
    $url = ARIEPULSA_API_URL;
    $postData = [
        'api_key' => ARIEPULSA_API_KEY,
        'action' => 'get-nickname-ewallet',
        'layanan' => strtolower($ewalletCode),
        'target' => $accountNumber
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'API-Validation-System/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'message' => 'Connection error: ' . $error,
            'data' => null
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => 'Service unavailable (HTTP ' . $httpCode . ')',
            'data' => null
        ];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Invalid response format',
            'data' => null
        ];
    }
    
    // Process AriePulsa response
    if (isset($data['status']) && $data['status'] === 'success') {
        return [
            'success' => true,
            'message' => 'Validation successful',
            'data' => [
                'ewallet_code' => $ewalletCode,
                'account_number' => $accountNumber,
                'account_name' => $data['data']['nama'] ?? $data['message'] ?? 'Unknown',
                'provider' => 'AriePulsa',
                'is_valid' => true
            ]
        ];
    } else {
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Validation failed',
            'data' => [
                'ewallet_code' => $ewalletCode,
                'account_number' => $accountNumber,
                'provider' => 'AriePulsa',
                'is_valid' => false
            ]
        ];
    }
}

/**
 * Validate bank account (mock implementation)
 */
function validateBankAccount($bankCode, $accountNumber) {
    // Mock bank validation - replace with actual bank API
    $bankNames = BANK_CODES;
    
    if (!isset($bankNames[$bankCode])) {
        return [
            'success' => false,
            'message' => 'Invalid bank code',
            'data' => null
        ];
    }
    
    // Simple validation rules
    if (strlen($accountNumber) < 8 || strlen($accountNumber) > 20) {
        return [
            'success' => false,
            'message' => 'Invalid account number length',
            'data' => [
                'bank_code' => $bankCode,
                'bank_name' => $bankNames[$bankCode],
                'account_number' => $accountNumber,
                'is_valid' => false
            ]
        ];
    }
    
    // Mock successful validation
    return [
        'success' => true,
        'message' => 'Bank account validation successful',
        'data' => [
            'bank_code' => $bankCode,
            'bank_name' => $bankNames[$bankCode],
            'account_number' => $accountNumber,
            'account_holder' => 'John Doe', // Mock name
            'is_valid' => true
        ]
    ];
}

try {
    // Get request data
    $method = $_SERVER['REQUEST_METHOD'];
    $input = null;
    
    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
    } else {
        $input = $_GET;
    }
    
    // Extract parameters
    $type = sanitizeInput($input['type'] ?? 'ewallet');
    $code = sanitizeInput($input['code'] ?? $input['ewallet_code'] ?? $input['bank_code'] ?? '');
    $accountNumber = sanitizeInput($input['account_number'] ?? $input['phone_number'] ?? '');
    $server = sanitizeInput($input['server'] ?? '1');
    
    // Get API key
    $apiKey = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_KEY'] ?? $input['api_key'] ?? '';
    if (strpos($apiKey, 'Bearer ') === 0) {
        $apiKey = substr($apiKey, 7);
    }
    $apiKey = sanitizeInput($apiKey);
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Show API documentation if no parameters
    if (empty($code) && empty($accountNumber) && empty($apiKey)) {
        echo json_encode([
            'message' => 'Bank & Ewallet Validation API',
            'version' => API_VERSION,
            'endpoints' => [
                'ewallet_validation' => [
                    'url' => SITE_URL . '/api/validate_bank.php',
                    'method' => 'POST',
                    'parameters' => [
                        'type' => 'ewallet',
                        'code' => 'gopay|dana|ovo|shopeepay|linkaja|isaku|gopay_driver',
                        'account_number' => '08xxxxxxxxxx',
                        'server' => '1 (AriePulsa) or 2 (OrderKuota)'
                    ]
                ],
                'bank_validation' => [
                    'url' => SITE_URL . '/api/validate_bank.php',
                    'method' => 'POST',
                    'parameters' => [
                        'type' => 'bank',
                        'code' => '002|008|009|014|022|213|451',
                        'account_number' => 'bank account number'
                    ]
                ]
            ],
            'authentication' => [
                'header' => 'Authorization: Bearer YOUR_API_KEY',
                'alternative' => 'X-API-Key: YOUR_API_KEY'
            ],
            'supported_ewallets' => EWALLET_CODES,
            'supported_banks' => BANK_CODES
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Validate API key
    $authResult = $apiAuth->validateApiKey($apiKey);
    if (!$authResult['valid']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $authResult['error'],
            'error_code' => 'INVALID_API_KEY'
        ]);
        exit;
    }
    
    // Check rate limit
    $rateLimitResult = $apiAuth->checkRateLimit($apiKey);
    if (!$rateLimitResult['valid']) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => $rateLimitResult['error'],
            'error_code' => 'RATE_LIMIT_EXCEEDED'
        ]);
        exit;
    }
    
    // Validate required parameters
    if (empty($code)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Code parameter is required',
            'error_code' => 'MISSING_CODE'
        ]);
        exit;
    }
    
    if (empty($accountNumber)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Account number parameter is required',
            'error_code' => 'MISSING_ACCOUNT_NUMBER'
        ]);
        exit;
    }
    
    // Process validation based on type
    $result = null;
    $endpoint = $type . '_' . $code;
    
    if ($type === 'ewallet') {
        // Validate ewallet code
        if (!in_array(strtolower($code), EWALLET_CODES)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid ewallet code. Supported: ' . implode(', ', EWALLET_CODES),
                'error_code' => 'INVALID_EWALLET_CODE'
            ]);
            exit;
        }
        
        // Validate phone number format
        if (!preg_match('/^08\d{8,11}$/', $accountNumber)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid phone number format. Use: 08xxxxxxxxxx',
                'error_code' => 'INVALID_PHONE_FORMAT'
            ]);
            exit;
        }
        
        // Use AriePulsa for now (server 2 can be added later)
        $result = validateEwalletAriePulsa($code, $accountNumber);
        
    } elseif ($type === 'bank') {
        // Validate bank code
        if (!isset(BANK_CODES[$code])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid bank code. Supported: ' . implode(', ', array_keys(BANK_CODES)),
                'error_code' => 'INVALID_BANK_CODE'
            ]);
            exit;
        }
        
        $result = validateBankAccount($code, $accountNumber);
        
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid type. Use "bank" or "ewallet"',
            'error_code' => 'INVALID_TYPE'
        ]);
        exit;
    }
    
    // Calculate response time
    $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
    
    // Log the request
    $apiAuth->logRequest(
        $apiKey,
        $ipAddress,
        $endpoint,
        [
            'type' => $type,
            'code' => $code,
            'account_number' => $accountNumber,
            'server' => $server
        ],
        $result,
        $result['success'] ? 200 : 400,
        $responseTime
    );
    
    // Add metadata to response
    $result['metadata'] = [
        'request_id' => uniqid(),
        'timestamp' => date('Y-m-d H:i:s'),
        'response_time_ms' => round($responseTime, 2),
        'api_version' => API_VERSION,
        'rate_limit_remaining' => $rateLimitResult['remaining'] ?? 0
    ];
    
    // Return response
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $responseTime = (microtime(true) - $startTime) * 1000;
    
    // Log error
    if (isset($apiKey) && isset($ipAddress)) {
        $apiAuth->logRequest(
            $apiKey,
            $ipAddress,
            'error',
            [
                'type' => $type ?? '',
                'code' => $code ?? '',
                'account_number' => $accountNumber ?? ''
            ],
            ['error' => $e->getMessage()],
            500,
            $responseTime
        );
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error_code' => 'INTERNAL_ERROR',
        'metadata' => [
            'request_id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s'),
            'response_time_ms' => round($responseTime, 2)
        ]
    ]);
}
?>