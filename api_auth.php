<?php
/**
 * API Authentication & Rate Limiting
 */

require_once 'config.php';

class APIAuth {
    private $pdo;
    
    public function __construct() {
        $this->pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Validate API Key
     */
    public function validateApiKey($apiKey) {
        if (empty($apiKey)) {
            return ['valid' => false, 'error' => 'API key is required'];
        }
        
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
        
        return ['valid' => true, 'remaining' => $keyData['daily_limit'] - $hitCount];
    }
    
    /**
     * Log API request
     */
    public function logRequest($apiKey, $ipAddress, $endpoint, $requestData, $responseData, $statusCode, $responseTime) {
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
        
        // Update total hits
        $stmt = $this->pdo->prepare("
            UPDATE api_keys SET total_hits = total_hits + 1, last_used = NOW()
            WHERE api_key = ?
        ");
        $stmt->execute([$apiKey]);
    }
    
    /**
     * Check if IP is blocked
     */
    public function isIpBlocked($ipAddress) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM blocked_ips 
            WHERE ip_address = ? AND is_active = 1 AND expires_at > NOW()
        ");
        $stmt->execute([$ipAddress]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Block IP address
     */
    public function blockIp($ipAddress, $reason, $hours = 24) {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO blocked_ips (ip_address, reason, expires_at, is_active)
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([$ipAddress, $reason, $expiresAt]);
    }
    
    /**
     * Get server status
     */
    public function getServerStatus($serverName) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM server_status WHERE server_name = ?
        ");
        $stmt->execute([$serverName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update server status
     */
    public function updateServerStatus($serverName, $isActive, $responseTime = null, $errorMessage = null) {
        $stmt = $this->pdo->prepare("
            UPDATE server_status 
            SET is_active = ?, last_check = NOW(), response_time = ?, error_message = ?
            WHERE server_name = ?
        ");
        $stmt->execute([$isActive ? 1 : 0, $responseTime, $errorMessage, $serverName]);
    }
    
    /**
     * Get API usage statistics
     */
    public function getUsageStats($apiKey = null, $days = 30) {
        $whereClause = $apiKey ? "WHERE api_key = ?" : "";
        $params = $apiKey ? [$apiKey] : [];
        
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
            AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            GROUP BY api_key, DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top IPs by usage
     */
    public function getTopIps($apiKey = null, $limit = 10) {
        $whereClause = $apiKey ? "WHERE api_key = ?" : "";
        $params = $apiKey ? [$apiKey] : [];
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare("
            SELECT 
                ip_address,
                COUNT(*) as request_count,
                COUNT(DISTINCT api_key) as api_keys_used,
                MAX(created_at) as last_request
            FROM api_logs 
            {$whereClause}
            GROUP BY ip_address
            ORDER BY request_count DESC
            LIMIT ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Unblock IP address
     */
    public function unblockIp($ipAddress) {
        $stmt = $this->pdo->prepare("
            UPDATE blocked_ips 
            SET is_active = 0 
            WHERE ip_address = ?
        ");
        $stmt->execute([$ipAddress]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get all blocked IPs
     */
    public function getIpBlocks() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM blocked_ips 
            ORDER BY blocked_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
