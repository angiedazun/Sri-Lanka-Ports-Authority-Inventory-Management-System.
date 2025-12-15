<?php
/**
 * Notifications API Endpoint
 * Handles notification requests
 */

require_once '../includes/db.php';

// Check authentication
if (!Auth::check()) {
    Response::json(['success' => false, 'error' => 'Unauthorized'], 401);
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';
    $notificationManager = new NotificationManager();
    $userId = Auth::id();
    
    switch ($action) {
        case 'list':
            $unreadOnly = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            
            $notifications = $notificationManager->getUserNotifications($userId, [
                'unread_only' => $unreadOnly,
                'limit' => $limit
            ]);
            
            Response::json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $notificationManager->getUnreadCount($userId)
            ]);
            break;
            
        case 'mark_read':
            $notificationId = $_POST['notification_id'] ?? null;
            
            if ($notificationId) {
                $success = $notificationManager->markAsRead($notificationId, $userId);
                Response::json(['success' => $success]);
            } else {
                Response::json(['success' => false, 'error' => 'Notification ID required'], 400);
            }
            break;
            
        case 'mark_all_read':
            $count = $notificationManager->markAllAsRead($userId);
            Response::json(['success' => true, 'marked' => $count]);
            break;
            
        case 'dismiss':
            $notificationId = $_POST['notification_id'] ?? null;
            
            if ($notificationId) {
                $success = $notificationManager->dismiss($notificationId, $userId);
                Response::json(['success' => $success]);
            } else {
                Response::json(['success' => false, 'error' => 'Notification ID required'], 400);
            }
            break;
            
        case 'unread_count':
            $count = $notificationManager->getUnreadCount($userId);
            Response::json(['success' => true, 'count' => $count]);
            break;
            
        case 'check_low_stock':
            // Admin only
            if (!Auth::hasRole('admin')) {
                Response::json(['success' => false, 'error' => 'Admin access required'], 403);
            }
            
            $threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 100;
            $count = $notificationManager->checkLowStock($threshold);
            Response::json(['success' => true, 'alerts_created' => $count]);
            break;
            
        case 'check_pending_returns':
            // Admin only
            if (!Auth::hasRole('admin')) {
                Response::json(['success' => false, 'error' => 'Admin access required'], 403);
            }
            
            $daysOverdue = isset($_GET['days_overdue']) ? (int)$_GET['days_overdue'] : 7;
            $count = $notificationManager->checkPendingReturns($daysOverdue);
            Response::json(['success' => true, 'alerts_created' => $count]);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    $logger = new Logger();
    $logger->error("Notification API failed: " . $e->getMessage());
    Response::json(['success' => false, 'error' => $e->getMessage()], 500);
}
