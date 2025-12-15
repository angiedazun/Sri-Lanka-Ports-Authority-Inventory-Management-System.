<?php
/**
 * Notification Manager
 * Handles system notifications, alerts, and user messages
 * 
 * Features:
 * - Low stock alerts
 * - Pending return reminders
 * - System notifications
 * - Email notifications
 * - User preferences
 */

class NotificationManager {
    private $db;
    private $logger;
    private $auditTrail;
    
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_ALERT = 'alert';
    
    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_URGENT = 4;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
        $this->auditTrail = new AuditTrail();
        $this->createTablesIfNotExist();
    }
    
    /**
     * Create notifications table if not exists
     */
    private function createTablesIfNotExist() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT DEFAULT NULL,
                type ENUM('info', 'success', 'warning', 'error', 'alert') DEFAULT 'info',
                priority TINYINT DEFAULT 2,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(500) DEFAULT NULL,
                icon VARCHAR(50) DEFAULT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                is_dismissed BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                read_at TIMESTAMP NULL,
                expires_at TIMESTAMP NULL,
                INDEX idx_user_read (user_id, is_read),
                INDEX idx_created (created_at),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            "CREATE TABLE IF NOT EXISTS notification_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                low_stock_alerts BOOLEAN DEFAULT TRUE,
                pending_returns BOOLEAN DEFAULT TRUE,
                system_updates BOOLEAN DEFAULT TRUE,
                email_notifications BOOLEAN DEFAULT FALSE,
                email_frequency ENUM('instant', 'daily', 'weekly') DEFAULT 'daily',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        
        foreach ($queries as $query) {
            $this->db->query($query);
        }
    }
    
    /**
     * Create a new notification
     */
    public function create($data) {
        try {
            $userId = $data['user_id'] ?? null;
            $type = $data['type'] ?? self::TYPE_INFO;
            $priority = $data['priority'] ?? self::PRIORITY_NORMAL;
            $title = $data['title'];
            $message = $data['message'];
            $link = $data['link'] ?? null;
            $icon = $data['icon'] ?? $this->getDefaultIcon($type);
            $expiresAt = $data['expires_at'] ?? null;
            
            $stmt = $this->db->prepare(
                "INSERT INTO notifications (user_id, type, priority, title, message, link, icon, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $stmt->bind_param('isisssss', $userId, $type, $priority, $title, $message, $link, $icon, $expiresAt);
            $stmt->execute();
            
            $notificationId = $this->db->insert_id;
            
            $this->logger->info("Notification created", [
                'id' => $notificationId,
                'user_id' => $userId,
                'type' => $type
            ]);
            
            return $notificationId;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to create notification", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Get notifications for a user
     */
    public function getUserNotifications($userId, $options = []) {
        try {
            $limit = $options['limit'] ?? 50;
            $unreadOnly = $options['unread_only'] ?? false;
            $includeExpired = $options['include_expired'] ?? false;
            
            $query = "SELECT * FROM notifications 
                      WHERE (user_id = ? OR user_id IS NULL)
                      AND is_dismissed = FALSE";
            
            if ($unreadOnly) {
                $query .= " AND is_read = FALSE";
            }
            
            if (!$includeExpired) {
                $query .= " AND (expires_at IS NULL OR expires_at > NOW())";
            }
            
            $query .= " ORDER BY priority DESC, created_at DESC LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $notifications = [];
            
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
            
            return $notifications;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get notifications", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId = null) {
        try {
            $query = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ?";
            
            if ($userId !== null) {
                $query .= " AND (user_id = ? OR user_id IS NULL)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('ii', $notificationId, $userId);
            } else {
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $notificationId);
            }
            
            $stmt->execute();
            return $stmt->affected_rows > 0;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to mark notification as read", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE notifications SET is_read = TRUE, read_at = NOW() 
                 WHERE (user_id = ? OR user_id IS NULL) AND is_read = FALSE"
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            
            return $stmt->affected_rows;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to mark all as read", ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Dismiss notification
     */
    public function dismiss($notificationId, $userId = null) {
        try {
            $query = "UPDATE notifications SET is_dismissed = TRUE WHERE id = ?";
            
            if ($userId !== null) {
                $query .= " AND (user_id = ? OR user_id IS NULL)";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('ii', $notificationId, $userId);
            } else {
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $notificationId);
            }
            
            $stmt->execute();
            return $stmt->affected_rows > 0;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to dismiss notification", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get unread count for a user
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM notifications 
                 WHERE (user_id = ? OR user_id IS NULL)
                 AND is_read = FALSE 
                 AND is_dismissed = FALSE
                 AND (expires_at IS NULL OR expires_at > NOW())"
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return (int)$row['count'];
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get unread count", ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Check and create low stock alerts
     */
    public function checkLowStock($threshold = 100) {
        try {
            $tables = [
                'papers_master' => ['name' => 'name', 'qty' => 'available_quantity'],
                'ribbons_master' => ['name' => 'name', 'qty' => 'available_quantity'],
                'toner_master' => ['name' => 'name', 'qty' => 'available_quantity']
            ];
            
            $alertCount = 0;
            
            foreach ($tables as $table => $columns) {
                $query = "SELECT id, {$columns['name']} as name, {$columns['qty']} as qty 
                          FROM $table 
                          WHERE {$columns['qty']} <= ? AND {$columns['qty']} > 0";
                
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $threshold);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    // Check if alert already exists
                    $checkStmt = $this->db->prepare(
                        "SELECT id FROM notifications 
                         WHERE type = 'warning' 
                         AND title LIKE ? 
                         AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                    );
                    $searchTitle = "%Low Stock Alert: {$row['name']}%";
                    $checkStmt->bind_param('s', $searchTitle);
                    $checkStmt->execute();
                    
                    if ($checkStmt->get_result()->num_rows === 0) {
                        $itemType = str_replace('_master', '', $table);
                        $this->create([
                            'type' => self::TYPE_WARNING,
                            'priority' => self::PRIORITY_HIGH,
                            'title' => 'Low Stock Alert: ' . $row['name'],
                            'message' => "Only {$row['qty']} units remaining in {$itemType} inventory. Please reorder soon.",
                            'link' => "pages/{$itemType}_master.php",
                            'icon' => 'fa-exclamation-triangle',
                            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
                        ]);
                        $alertCount++;
                    }
                }
            }
            
            return $alertCount;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to check low stock", ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Check and create pending return alerts
     */
    public function checkPendingReturns($daysOverdue = 7) {
        try {
            $tables = ['papers_issuing', 'ribbons_issuing', 'toner_issuing'];
            $alertCount = 0;
            
            foreach ($tables as $table) {
                $query = "SELECT id, item_name, issued_to, issued_date, return_date
                          FROM $table 
                          WHERE status = 'issued' 
                          AND return_date < DATE_SUB(NOW(), INTERVAL ? DAY)";
                
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $daysOverdue);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $daysLate = floor((strtotime('now') - strtotime($row['return_date'])) / 86400);
                    
                    $this->create([
                        'type' => self::TYPE_ERROR,
                        'priority' => self::PRIORITY_URGENT,
                        'title' => 'Overdue Return: ' . $row['item_name'],
                        'message' => "Item issued to {$row['issued_to']} is {$daysLate} days overdue (Due: {$row['return_date']})",
                        'link' => 'pages/' . str_replace('_issuing', '_return', $table) . '.php',
                        'icon' => 'fa-clock',
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
                    ]);
                    $alertCount++;
                }
            }
            
            return $alertCount;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to check pending returns", ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Clean up old notifications
     */
    public function cleanup($daysOld = 30) {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM notifications 
                 WHERE (
                     (is_read = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY))
                     OR (expires_at IS NOT NULL AND expires_at < NOW())
                 )"
            );
            $stmt->bind_param('i', $daysOld);
            $stmt->execute();
            
            $deleted = $stmt->affected_rows;
            
            if ($deleted > 0) {
                $this->logger->info("Cleaned up old notifications", ['deleted' => $deleted]);
            }
            
            return $deleted;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to cleanup notifications", ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Get default icon for notification type
     */
    private function getDefaultIcon($type) {
        $icons = [
            self::TYPE_INFO => 'fa-info-circle',
            self::TYPE_SUCCESS => 'fa-check-circle',
            self::TYPE_WARNING => 'fa-exclamation-triangle',
            self::TYPE_ERROR => 'fa-times-circle',
            self::TYPE_ALERT => 'fa-bell'
        ];
        
        return $icons[$type] ?? 'fa-bell';
    }
}
