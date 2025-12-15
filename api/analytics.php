<?php
/**
 * Analytics API Endpoint
 * Handles analytics and reporting requests
 */

require_once '../includes/db.php';

// Check authentication
if (!Auth::check()) {
    Response::json(['success' => false, 'error' => 'Unauthorized'], 401);
}

try {
    $action = $_GET['action'] ?? 'dashboard';
    $analytics = new AnalyticsManager();
    
    switch ($action) {
        case 'dashboard':
            $stats = $analytics->getDashboardStats();
            Response::json([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'trend':
            $type = $_GET['type'] ?? 'papers';
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            
            $trend = $analytics->getInventoryTrend($type, $days);
            Response::json([
                'success' => true,
                'type' => $type,
                'trend' => $trend
            ]);
            break;
            
        case 'distribution':
            $distribution = $analytics->getStockDistribution();
            Response::json([
                'success' => true,
                'distribution' => $distribution
            ]);
            break;
            
        case 'top_items':
            $type = $_GET['type'] ?? 'papers';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            
            $items = $analytics->getTopIssuedItems($type, $limit, $days);
            Response::json([
                'success' => true,
                'items' => $items
            ]);
            break;
            
        case 'comparison':
            $type = $_GET['type'] ?? 'papers';
            $comparison = $analytics->getMonthlyComparison($type);
            Response::json([
                'success' => true,
                'comparison' => $comparison
            ]);
            break;
            
        case 'predict':
            $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
            $type = $_GET['type'] ?? 'papers';
            
            if (!$itemId) {
                Response::json(['success' => false, 'error' => 'Item ID required'], 400);
            }
            
            $prediction = $analytics->predictStockDepletion($itemId, $type);
            Response::json([
                'success' => true,
                'prediction' => $prediction
            ]);
            break;
            
        case 'heatmap':
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
            $heatmap = $analytics->getActivityHeatmap($days);
            Response::json([
                'success' => true,
                'heatmap' => $heatmap
            ]);
            break;
            
        case 'report':
            $type = $_GET['type'] ?? 'papers';
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $report = $analytics->generateReport($type, $startDate, $endDate);
            Response::json([
                'success' => true,
                'report' => $report
            ]);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    $logger = new Logger();
    $logger->error("Analytics API failed: " . $e->getMessage());
    Response::json(['success' => false, 'error' => $e->getMessage()], 500);
}
