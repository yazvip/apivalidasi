<?php
/**
 * Enhanced API Authentication & Rate Limiting
 */

require_once 'config.php';

class APIAuth {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabase();
    }
    
    /**
     * Validate API Key
     */
    public function validateApiKey($apiKey) {
        if (empty($apiKey)) {
            return ['valid' => false, 'error' => 'API key is required'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM api_keys 
                WHERE api_key = ? AND is_active = 1 AND expires_at > NOW()
            ");
            $stmt->execute([$apiKey]);
            $key = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$key) {
                return ['valid' => false, 'error' => 'Invalid or expired API key'];
            }
            
            return ['valid' => true, 'key' => $key];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check rate limit
     */
    public function checkRateLimit($apiKey) {
        $key = $this->validateApiKey($apiKey);
        if (!$key['valid']) {
            return $key;
        }
        
        $keyData = $key['key'];
        
        try {
            // Check daily limit
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as hit_count FROM api_logs 
                WHERE api_key = ? AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$apiKey]);
            $hitCount = $stmt->fetch(PDO::FETCH_ASSOC)['hit_count'];
            
            if ($hitCount >= $keyData['daily_limit']) {
                return ['valid' => false, 'error' => 'Daily rate limit exceeded'];
            }
            
            // Check per-minute limit
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as hit_count FROM api_logs 
                WHERE api_key = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            $stmt->execute([$apiKey]);
            $minuteHitCount = $stmt->fetch(PDO::FETCH_ASSOC)['hit_count'];
            
            if ($minuteHitCount >= $keyData['rate_limit_per_minute']) {
                return ['valid' => false, 'error' => 'Per-minute rate limit exceeded'];
            }
            
            return [
                'valid' => true, 
                'remaining' => $keyData['daily_limit'] - $hitCount,
                'minute_remaining' => $keyData['rate_limit_per_minute'] - $minuteHitCount
            ];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'Rate limit check failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Log API request
     */
    public function logRequest($apiKey, $ipAddress, $endpoint, $requestData, $responseData, $statusCode, $responseTime) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO api_logs (api_key, ip_address, endpoint, request_data, response_data, status_code, response_time)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $apiKey,
                $ipAddress,
                $endpoint,
                json_encode($requestData),
                json_encode($responseData),
                $statusCode,
                $responseTime
            ]);
            
            // Update API key last used timestamp
            $stmt = $this->pdo->prepare("
                UPDATE api_keys SET last_used = NOW() WHERE api_key = ?
            ");
            $stmt->execute([$apiKey]);
            
        } catch (Exception $e) {
            error_log('Failed to log API request: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if IP is blocked
     */
    public function isIpBlocked($ipAddress) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM blocked_ips 
                WHERE ip_address = ? AND is_active = 1 AND expires_at > NOW()
            ");
            $stmt->execute([$ipAddress]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Block IP address
     */
    public function blockIp($ipAddress, $reason, $hours = 24) {
        try {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO blocked_ips (ip_address, reason, expires_at, is_active)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                reason = VALUES(reason),
                expires_at = VALUES(expires_at),
                is_active = 1,
                blocked_at = NOW()
            ");
            $stmt->execute([$ipAddress, $reason, $expiresAt]);
            
            return true;
        } catch (Exception $e) {
            error_log('Failed to block IP: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get server status
     */
    public function getServerStatus($serverName) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM server_status WHERE server_name = ?
            ");
            $stmt->execute([$serverName]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Update server status
     */
    public function updateServerStatus($serverName, $isActive, $responseTime = null, $errorMessage = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE server_status 
                SET is_active = ?, last_check = NOW(), response_time = ?, error_message = ?
                WHERE server_name = ?
            ");
            $stmt->execute([$isActive ? 1 : 0, $responseTime, $errorMessage, $serverName]);
            
            return true;
        } catch (Exception $e) {
            error_log('Failed to update server status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get API usage statistics
     */
    public function getUsageStats($apiKey = null, $days = 30) {
        try {
            $whereClause = $apiKey ? "WHERE api_key = ?" : "";
            $params = $apiKey ? [$apiKey, $days] : [$days];
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    api_key,
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    AVG(response_time) as avg_response_time,
                    COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) as success_requests,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_requests,
                    DATE(created_at) as date
                FROM api_logs 
                {$whereClause}
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY api_key, DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get maintenance mode status
     */
    public function getMaintenanceMode() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_value FROM api_settings WHERE setting_key = 'maintenance_mode'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (bool)$result['setting_value'] : false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get setting value
     */
    public function getSetting($key, $default = null) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_value FROM api_settings WHERE setting_key = ?
            ");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}
?>