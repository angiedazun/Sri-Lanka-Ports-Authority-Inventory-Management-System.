<?php
/**
 * Analytics Manager
 * Provides data analytics and insights for the inventory system
 * 
 * Features:
 * - Dashboard statistics
 * - Trend analysis
 * - Usage reports
 * - Predictive analytics
 * - Chart data generation
 */

class AnalyticsManager {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Get dashboard overview statistics
     */
    public function getDashboardStats() {
        try {
            $stats = [
                'papers' => $this->getInventoryStats('papers'),
                'ribbons' => $this->getInventoryStats('ribbons'),
                'toner' => $this->getInventoryStats('toner'),
                'summary' => $this->getSummaryStats()
            ];
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get dashboard stats", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Get inventory statistics for specific type
     */
    private function getInventoryStats($type) {
        $masterTable = "{$type}_master";
        $issuingTable = "{$type}_issuing";
        $receivingTable = "{$type}_receiving";
        
        // Total inventory
        $totalQuery = $this->db->query("SELECT COUNT(*) as count, SUM(available_quantity) as total_qty FROM $masterTable");
        $total = $totalQuery->fetch_assoc();
        
        // Low stock items (less than 100)
        $lowStockQuery = $this->db->query("SELECT COUNT(*) as count FROM $masterTable WHERE available_quantity < 100 AND available_quantity > 0");
        $lowStock = $lowStockQuery->fetch_assoc();
        
        // Out of stock
        $outOfStockQuery = $this->db->query("SELECT COUNT(*) as count FROM $masterTable WHERE available_quantity = 0");
        $outOfStock = $outOfStockQuery->fetch_assoc();
        
        // Issued this month
        $issuedQuery = $this->db->query("SELECT COUNT(*) as count, SUM(quantity) as total_qty FROM $issuingTable WHERE MONTH(issued_date) = MONTH(CURRENT_DATE()) AND YEAR(issued_date) = YEAR(CURRENT_DATE())");
        $issued = $issuedQuery->fetch_assoc();
        
        // Received this month
        $receivedQuery = $this->db->query("SELECT COUNT(*) as count, SUM(quantity) as total_qty FROM $receivingTable WHERE MONTH(received_date) = MONTH(CURRENT_DATE()) AND YEAR(received_date) = YEAR(CURRENT_DATE())");
        $received = $receivedQuery->fetch_assoc();
        
        // Pending returns
        $pendingQuery = $this->db->query("SELECT COUNT(*) as count FROM $issuingTable WHERE status = 'issued' AND return_date < CURRENT_DATE()");
        $pending = $pendingQuery->fetch_assoc();
        
        return [
            'total_items' => (int)$total['count'],
            'total_quantity' => (int)$total['total_qty'],
            'low_stock' => (int)$lowStock['count'],
            'out_of_stock' => (int)$outOfStock['count'],
            'issued_this_month' => (int)$issued['count'],
            'issued_quantity' => (int)$issued['total_qty'],
            'received_this_month' => (int)$received['count'],
            'received_quantity' => (int)$received['total_qty'],
            'pending_returns' => (int)$pending['count']
        ];
    }
    
    /**
     * Get summary statistics
     */
    private function getSummaryStats() {
        // Total users
        $usersQuery = $this->db->query("SELECT COUNT(*) as count FROM users");
        $users = $usersQuery->fetch_assoc();
        
        // Total transactions today
        $today = date('Y-m-d');
        $transactionsQuery = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM papers_issuing WHERE DATE(issued_date) = '$today') +
                (SELECT COUNT(*) FROM ribbons_issuing WHERE DATE(issued_date) = '$today') +
                (SELECT COUNT(*) FROM toner_issuing WHERE DATE(issued_date) = '$today') as count
        ");
        $transactions = $transactionsQuery->fetch_assoc();
        
        return [
            'total_users' => (int)$users['count'],
            'transactions_today' => (int)$transactions['count']
        ];
    }
    
    /**
     * Get inventory trend data for charts
     */
    public function getInventoryTrend($type, $days = 30) {
        try {
            $masterTable = "{$type}_master";
            $issuingTable = "{$type}_issuing";
            $receivingTable = "{$type}_receiving";
            
            $data = [
                'labels' => [],
                'issued' => [],
                'received' => [],
                'stock' => []
            ];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $data['labels'][] = date('M d', strtotime($date));
                
                // Issued quantity
                $issuedQuery = $this->db->query("SELECT SUM(quantity) as qty FROM $issuingTable WHERE DATE(issued_date) = '$date'");
                $issued = $issuedQuery->fetch_assoc();
                $data['issued'][] = (int)($issued['qty'] ?? 0);
                
                // Received quantity
                $receivedQuery = $this->db->query("SELECT SUM(quantity) as qty FROM $receivingTable WHERE DATE(received_date) = '$date'");
                $received = $receivedQuery->fetch_assoc();
                $data['received'][] = (int)($received['qty'] ?? 0);
                
                // Current stock (last day only for simplicity)
                if ($i === 0) {
                    $stockQuery = $this->db->query("SELECT SUM(available_quantity) as qty FROM $masterTable");
                    $stock = $stockQuery->fetch_assoc();
                    $data['current_stock'] = (int)($stock['qty'] ?? 0);
                }
            }
            
            return $data;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get inventory trend", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Get stock distribution for pie chart
     */
    public function getStockDistribution() {
        try {
            $data = [
                'labels' => [],
                'values' => [],
                'colors' => ['#4CAF50', '#2196F3', '#FF9800']
            ];
            
            $types = ['papers', 'ribbons', 'toner'];
            
            foreach ($types as $type) {
                $query = $this->db->query("SELECT SUM(available_quantity) as qty FROM {$type}_master");
                $result = $query->fetch_assoc();
                
                $data['labels'][] = ucfirst($type);
                $data['values'][] = (int)($result['qty'] ?? 0);
            }
            
            return $data;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get stock distribution", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Get top issued items
     */
    public function getTopIssuedItems($type, $limit = 10, $days = 30) {
        try {
            $issuingTable = "{$type}_issuing";
            $date = date('Y-m-d', strtotime("-$days days"));
            
            $query = $this->db->query("
                SELECT item_name, SUM(quantity) as total_qty, COUNT(*) as transaction_count
                FROM $issuingTable
                WHERE issued_date >= '$date'
                GROUP BY item_name
                ORDER BY total_qty DESC
                LIMIT $limit
            ");
            
            $items = [];
            while ($row = $query->fetch_assoc()) {
                $items[] = [
                    'name' => $row['item_name'],
                    'quantity' => (int)$row['total_qty'],
                    'transactions' => (int)$row['transaction_count']
                ];
            }
            
            return $items;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get top issued items", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get monthly comparison
     */
    public function getMonthlyComparison($type) {
        try {
            $issuingTable = "{$type}_issuing";
            
            $currentMonth = $this->db->query("
                SELECT SUM(quantity) as qty
                FROM $issuingTable
                WHERE MONTH(issued_date) = MONTH(CURRENT_DATE())
                AND YEAR(issued_date) = YEAR(CURRENT_DATE())
            ")->fetch_assoc();
            
            $lastMonth = $this->db->query("
                SELECT SUM(quantity) as qty
                FROM $issuingTable
                WHERE MONTH(issued_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                AND YEAR(issued_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
            ")->fetch_assoc();
            
            $currentQty = (int)($currentMonth['qty'] ?? 0);
            $lastQty = (int)($lastMonth['qty'] ?? 0);
            
            $change = 0;
            if ($lastQty > 0) {
                $change = (($currentQty - $lastQty) / $lastQty) * 100;
            }
            
            return [
                'current_month' => $currentQty,
                'last_month' => $lastQty,
                'change_percentage' => round($change, 2),
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get monthly comparison", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Predict stock depletion date
     */
    public function predictStockDepletion($itemId, $type) {
        try {
            $masterTable = "{$type}_master";
            $issuingTable = "{$type}_issuing";
            
            // Get current stock
            $stockQuery = $this->db->query("SELECT available_quantity, name FROM $masterTable WHERE id = $itemId");
            $stock = $stockQuery->fetch_assoc();
            
            if (!$stock || $stock['available_quantity'] <= 0) {
                return null;
            }
            
            // Calculate average daily usage (last 30 days)
            $usageQuery = $this->db->query("
                SELECT AVG(daily_usage) as avg_daily
                FROM (
                    SELECT DATE(issued_date) as date, SUM(quantity) as daily_usage
                    FROM $issuingTable
                    WHERE item_name = '{$stock['name']}'
                    AND issued_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(issued_date)
                ) as daily_totals
            ");
            $usage = $usageQuery->fetch_assoc();
            
            $avgDaily = (float)($usage['avg_daily'] ?? 0);
            
            if ($avgDaily <= 0) {
                return null;
            }
            
            $daysRemaining = floor($stock['available_quantity'] / $avgDaily);
            $depletionDate = date('Y-m-d', strtotime("+$daysRemaining days"));
            
            return [
                'item_name' => $stock['name'],
                'current_stock' => (int)$stock['available_quantity'],
                'avg_daily_usage' => round($avgDaily, 2),
                'days_remaining' => $daysRemaining,
                'predicted_depletion_date' => $depletionDate,
                'urgency' => $daysRemaining < 7 ? 'critical' : ($daysRemaining < 30 ? 'warning' : 'normal')
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Failed to predict stock depletion", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Get activity heatmap data
     */
    public function getActivityHeatmap($days = 30) {
        try {
            $data = [];
            $date = date('Y-m-d', strtotime("-$days days"));
            
            $query = $this->db->query("
                SELECT DATE(date) as activity_date, COUNT(*) as count
                FROM (
                    SELECT issued_date as date FROM papers_issuing WHERE issued_date >= '$date'
                    UNION ALL
                    SELECT issued_date as date FROM ribbons_issuing WHERE issued_date >= '$date'
                    UNION ALL
                    SELECT issued_date as date FROM toner_issuing WHERE issued_date >= '$date'
                    UNION ALL
                    SELECT received_date as date FROM papers_receiving WHERE received_date >= '$date'
                    UNION ALL
                    SELECT received_date as date FROM ribbons_receiving WHERE received_date >= '$date'
                    UNION ALL
                    SELECT received_date as date FROM toner_receiving WHERE received_date >= '$date'
                ) as all_activities
                GROUP BY DATE(date)
                ORDER BY activity_date
            ");
            
            while ($row = $query->fetch_assoc()) {
                $data[] = [
                    'date' => $row['activity_date'],
                    'count' => (int)$row['count']
                ];
            }
            
            return $data;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get activity heatmap", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Generate comprehensive report
     */
    public function generateReport($type, $startDate, $endDate) {
        try {
            $masterTable = "{$type}_master";
            $issuingTable = "{$type}_issuing";
            $receivingTable = "{$type}_receiving";
            
            $report = [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'issued' => [],
                'received' => [],
                'current_stock' => [],
                'summary' => []
            ];
            
            // Issued items
            $issuedQuery = $this->db->query("
                SELECT item_name, SUM(quantity) as total_qty, COUNT(*) as count
                FROM $issuingTable
                WHERE issued_date BETWEEN '$startDate' AND '$endDate'
                GROUP BY item_name
                ORDER BY total_qty DESC
            ");
            
            while ($row = $issuedQuery->fetch_assoc()) {
                $report['issued'][] = $row;
            }
            
            // Received items
            $receivedQuery = $this->db->query("
                SELECT item_name, SUM(quantity) as total_qty, COUNT(*) as count
                FROM $receivingTable
                WHERE received_date BETWEEN '$startDate' AND '$endDate'
                GROUP BY item_name
                ORDER BY total_qty DESC
            ");
            
            while ($row = $receivedQuery->fetch_assoc()) {
                $report['received'][] = $row;
            }
            
            // Current stock
            $stockQuery = $this->db->query("SELECT name, available_quantity FROM $masterTable ORDER BY available_quantity ASC");
            while ($row = $stockQuery->fetch_assoc()) {
                $report['current_stock'][] = $row;
            }
            
            // Summary
            $report['summary'] = [
                'total_issued' => array_sum(array_column($report['issued'], 'total_qty')),
                'total_received' => array_sum(array_column($report['received'], 'total_qty')),
                'total_current_stock' => array_sum(array_column($report['current_stock'], 'available_quantity')),
                'unique_items_issued' => count($report['issued']),
                'unique_items_received' => count($report['received'])
            ];
            
            return $report;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to generate report", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
