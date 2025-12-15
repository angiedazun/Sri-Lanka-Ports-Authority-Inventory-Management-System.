<?php
/**
 * Audit Trail Class
 * Tracks all user activities for security and compliance
 */

class AuditTrail {
    
    /**
     * Log user activity
     */
    public static function log($action, $details = [], $severity = 'info') {
        try {
            $db = Database::getInstance();
            
            $userId = Auth::id();
            $username = $_SESSION['username'] ?? 'guest';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $detailsJson = json_encode($details);
            
            $stmt = $db->prepare("
                INSERT INTO audit_log 
                (user_id, username, action, details, ip_address, user_agent, severity, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param(
                "issssss",
                $userId,
                $username,
                $action,
                $detailsJson,
                $ipAddress,
                $userAgent,
                $severity
            );
            
            $stmt->execute();
            $stmt->close();
            
            // Also log to file for critical actions
            if ($severity === 'critical' || $severity === 'high') {
                Logger::warning("AUDIT: {$action}", $details);
            }
            
        } catch (Exception $e) {
            Logger::error('Failed to log audit trail: ' . $e->getMessage());
        }
    }
    
    /**
     * Log login attempt
     */
    public static function logLogin($username, $success, $reason = '') {
        self::log('login_attempt', [
            'username' => $username,
            'success' => $success,
            'reason' => $reason
        ], $success ? 'info' : 'warning');
    }
    
    /**
     * Log data modification
     */
    public static function logDataChange($table, $recordId, $action, $oldData = null, $newData = null) {
        self::log('data_change', [
            'table' => $table,
            'record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData
        ], 'high');
    }
    
    /**
     * Log data access
     */
    public static function logDataAccess($table, $recordId = null, $query = null) {
        self::log('data_access', [
            'table' => $table,
            'record_id' => $recordId,
            'query' => $query
        ], 'info');
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        self::log('security_event', array_merge(['event' => $event], $details), 'critical');
    }
    
    /**
     * Get user activity log
     */
    public static function getUserActivity($userId, $limit = 50) {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("
                SELECT * FROM audit_log 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->bind_param("ii", $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            
            $stmt->close();
            return $activities;
            
        } catch (Exception $e) {
            Logger::error('Failed to get user activity: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get security events
     */
    public static function getSecurityEvents($days = 7) {
        try {
            $db = Database::getInstance();
            
            $stmt = $db->prepare("
                SELECT * FROM audit_log 
                WHERE severity IN ('critical', 'high', 'warning') 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY created_at DESC
            ");
            
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $events = [];
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
            
            $stmt->close();
            return $events;
            
        } catch (Exception $e) {
            Logger::error('Failed to get security events: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create audit_log table if not exists
     */
    public static function createTableIfNotExists() {
        try {
            $db = Database::getInstance();
            
            $sql = "CREATE TABLE IF NOT EXISTS audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                username VARCHAR(100),
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                severity ENUM('info', 'warning', 'high', 'critical') DEFAULT 'info',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_severity (severity),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $db->query($sql);
            
        } catch (Exception $e) {
            Logger::error('Failed to create audit_log table: ' . $e->getMessage());
        }
    }
}

// Create table on first load
AuditTrail::createTableIfNotExists();
