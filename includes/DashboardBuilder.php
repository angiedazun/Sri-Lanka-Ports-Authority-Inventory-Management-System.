<?php
/**
 * Dashboard Customization System
 * Customizable dashboards with widget library and user preferences
 * 
 * @package SLPA\Dashboard
 * @version 1.0.0
 */

class DashboardBuilder {
    private $db;
    private $userId;
    private $dashboardId;
    private $widgets = [];
    private $layout = [];
    
    public function __construct($userId = null) {
        $this->db = Database::getInstance();
        $this->userId = $userId ?? $_SESSION['user_id'];
    }
    
    /**
     * Create new dashboard
     */
    public function create($name, $description = '', $isDefault = false) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO dashboards (user_id, name, description, is_default, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issi', $this->userId, $name, $description, $isDefault);
        $stmt->execute();
        
        $this->dashboardId = $conn->insert_id;
        
        return $this;
    }
    
    /**
     * Load dashboard
     */
    public function load($dashboardId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM dashboards WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $dashboardId, $this->userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $dashboard = $result->fetch_assoc();
        
        if (!$dashboard) {
            throw new Exception("Dashboard not found");
        }
        
        $this->dashboardId = $dashboardId;
        $this->layout = json_decode($dashboard['layout'], true) ?? [];
        
        // Load widgets
        $this->loadWidgets();
        
        return $this;
    }
    
    /**
     * Load widgets
     */
    private function loadWidgets() {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM dashboard_widgets WHERE dashboard_id = ? ORDER BY position";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $this->dashboardId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $this->widgets[] = [
                'id' => $row['id'],
                'widget_type' => $row['widget_type'],
                'config' => json_decode($row['config'], true),
                'position' => $row['position'],
                'size' => json_decode($row['size'], true)
            ];
        }
    }
    
    /**
     * Add widget to dashboard
     */
    public function addWidget($widgetType, $config = [], $position = null, $size = null) {
        $conn = $this->db->getConnection();
        
        if ($position === null) {
            $position = count($this->widgets);
        }
        
        if ($size === null) {
            $size = ['width' => 6, 'height' => 4]; // Default size
        }
        
        $configJson = json_encode($config);
        $sizeJson = json_encode($size);
        
        $sql = "INSERT INTO dashboard_widgets (dashboard_id, widget_type, config, position, size, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issis', $this->dashboardId, $widgetType, $configJson, $position, $sizeJson);
        $stmt->execute();
        
        $widgetId = $conn->insert_id;
        
        $this->widgets[] = [
            'id' => $widgetId,
            'widget_type' => $widgetType,
            'config' => $config,
            'position' => $position,
            'size' => $size
        ];
        
        return $this;
    }
    
    /**
     * Remove widget
     */
    public function removeWidget($widgetId) {
        $conn = $this->db->getConnection();
        
        $sql = "DELETE FROM dashboard_widgets WHERE id = ? AND dashboard_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $widgetId, $this->dashboardId);
        $stmt->execute();
        
        $this->widgets = array_filter($this->widgets, function($w) use ($widgetId) {
            return $w['id'] !== $widgetId;
        });
        
        return $this;
    }
    
    /**
     * Update widget position
     */
    public function updateWidgetPosition($widgetId, $position) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE dashboard_widgets SET position = ? WHERE id = ? AND dashboard_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iii', $position, $widgetId, $this->dashboardId);
        $stmt->execute();
        
        return $this;
    }
    
    /**
     * Update widget size
     */
    public function updateWidgetSize($widgetId, $size) {
        $conn = $this->db->getConnection();
        
        $sizeJson = json_encode($size);
        
        $sql = "UPDATE dashboard_widgets SET size = ? WHERE id = ? AND dashboard_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sii', $sizeJson, $widgetId, $this->dashboardId);
        $stmt->execute();
        
        return $this;
    }
    
    /**
     * Update widget config
     */
    public function updateWidgetConfig($widgetId, $config) {
        $conn = $this->db->getConnection();
        
        $configJson = json_encode($config);
        
        $sql = "UPDATE dashboard_widgets SET config = ? WHERE id = ? AND dashboard_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sii', $configJson, $widgetId, $this->dashboardId);
        $stmt->execute();
        
        return $this;
    }
    
    /**
     * Save layout
     */
    public function saveLayout($layout) {
        $conn = $this->db->getConnection();
        
        $layoutJson = json_encode($layout);
        
        $sql = "UPDATE dashboards SET layout = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $layoutJson, $this->dashboardId);
        $stmt->execute();
        
        $this->layout = $layout;
        
        return $this;
    }
    
    /**
     * Get dashboard data
     */
    public function getData() {
        $widgetData = [];
        
        foreach ($this->widgets as $widget) {
            $widgetData[] = [
                'id' => $widget['id'],
                'type' => $widget['widget_type'],
                'config' => $widget['config'],
                'position' => $widget['position'],
                'size' => $widget['size'],
                'data' => $this->getWidgetData($widget['widget_type'], $widget['config'])
            ];
        }
        
        return [
            'dashboard_id' => $this->dashboardId,
            'layout' => $this->layout,
            'widgets' => $widgetData
        ];
    }
    
    /**
     * Get widget data
     */
    private function getWidgetData($widgetType, $config) {
        $widgetLibrary = new WidgetLibrary();
        return $widgetLibrary->getData($widgetType, $config);
    }
    
    /**
     * Get user dashboards
     */
    public function getUserDashboards() {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT id, name, description, is_default, created_at 
                FROM dashboards 
                WHERE user_id = ? 
                ORDER BY is_default DESC, created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $this->userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $dashboards = [];
        
        while ($row = $result->fetch_assoc()) {
            $dashboards[] = $row;
        }
        
        return $dashboards;
    }
    
    /**
     * Set default dashboard
     */
    public function setDefault($dashboardId) {
        $conn = $this->db->getConnection();
        
        // Remove default from all dashboards
        $sql = "UPDATE dashboards SET is_default = 0 WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $this->userId);
        $stmt->execute();
        
        // Set new default
        $sql = "UPDATE dashboards SET is_default = 1 WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $dashboardId, $this->userId);
        $stmt->execute();
        
        return $this;
    }
    
    /**
     * Get dashboard ID
     */
    public function getDashboardId() {
        return $this->dashboardId;
    }
    
    /**
     * Clone dashboard
     */
    public function clone($newName) {
        $conn = $this->db->getConnection();
        
        // Get current dashboard
        $sql = "SELECT * FROM dashboards WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $this->dashboardId);
        $stmt->execute();
        
        $dashboard = $stmt->get_result()->fetch_assoc();
        
        // Create new dashboard
        $sql = "INSERT INTO dashboards (user_id, name, description, layout, is_default, created_at) 
                VALUES (?, ?, ?, ?, 0, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isss', $this->userId, $newName, $dashboard['description'], $dashboard['layout']);
        $stmt->execute();
        
        $newDashboardId = $conn->insert_id;
        
        // Copy widgets
        $sql = "INSERT INTO dashboard_widgets (dashboard_id, widget_type, config, position, size, created_at)
                SELECT ?, widget_type, config, position, size, NOW()
                FROM dashboard_widgets
                WHERE dashboard_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $newDashboardId, $this->dashboardId);
        $stmt->execute();
        
        return $newDashboardId;
    }
}

/**
 * Widget Library
 */
class WidgetLibrary {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get available widgets
     */
    public function getAvailableWidgets() {
        return [
            'stats_card' => [
                'name' => 'Statistics Card',
                'description' => 'Display a single statistic with icon',
                'icon' => 'fa-chart-bar',
                'default_size' => ['width' => 3, 'height' => 2]
            ],
            'chart' => [
                'name' => 'Chart',
                'description' => 'Display data in various chart formats',
                'icon' => 'fa-chart-line',
                'default_size' => ['width' => 6, 'height' => 4]
            ],
            'table' => [
                'name' => 'Data Table',
                'description' => 'Display tabular data',
                'icon' => 'fa-table',
                'default_size' => ['width' => 12, 'height' => 6]
            ],
            'recent_activity' => [
                'name' => 'Recent Activity',
                'description' => 'Show recent system activities',
                'icon' => 'fa-history',
                'default_size' => ['width' => 6, 'height' => 4]
            ],
            'low_stock_alert' => [
                'name' => 'Low Stock Alert',
                'description' => 'Items below reorder level',
                'icon' => 'fa-exclamation-triangle',
                'default_size' => ['width' => 6, 'height' => 4]
            ],
            'pending_approvals' => [
                'name' => 'Pending Approvals',
                'description' => 'Items waiting for approval',
                'icon' => 'fa-clock',
                'default_size' => ['width' => 6, 'height' => 4]
            ],
            'calendar' => [
                'name' => 'Calendar',
                'description' => 'Display events and scheduled tasks',
                'icon' => 'fa-calendar',
                'default_size' => ['width' => 6, 'height' => 6]
            ]
        ];
    }
    
    /**
     * Get widget data
     */
    public function getData($widgetType, $config) {
        switch ($widgetType) {
            case 'stats_card':
                return $this->getStatsCardData($config);
            case 'chart':
                return $this->getChartData($config);
            case 'table':
                return $this->getTableData($config);
            case 'recent_activity':
                return $this->getRecentActivity($config);
            case 'low_stock_alert':
                return $this->getLowStockItems($config);
            case 'pending_approvals':
                return $this->getPendingApprovals($config);
            case 'calendar':
                return $this->getCalendarData($config);
            default:
                return [];
        }
    }
    
    private function getStatsCardData($config) {
        $conn = $this->db->getConnection();
        $metric = $config['metric'] ?? 'total_papers';
        
        $queries = [
            'total_papers' => "SELECT SUM(quantity) as value FROM papers_master",
            'total_ribbons' => "SELECT SUM(quantity) as value FROM ribbons_master",
            'total_toner' => "SELECT SUM(quantity) as value FROM toner_master",
            'low_stock_count' => "SELECT COUNT(*) as value FROM papers_master WHERE quantity < reorder_level"
        ];
        
        $sql = $queries[$metric] ?? $queries['total_papers'];
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        
        return ['value' => $row['value'] ?? 0];
    }
    
    private function getChartData($config) {
        $conn = $this->db->getConnection();
        $chartType = $config['chart_type'] ?? 'line';
        $dataSource = $config['data_source'] ?? 'monthly_papers';
        
        // Example: Monthly papers receiving
        $sql = "SELECT DATE_FORMAT(received_date, '%Y-%m') as month, SUM(quantity) as total 
                FROM papers_receiving 
                WHERE received_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY month 
                ORDER BY month";
        
        $result = $conn->query($sql);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = ['label' => $row['month'], 'value' => $row['total']];
        }
        
        return ['chart_type' => $chartType, 'data' => $data];
    }
    
    private function getTableData($config) {
        $conn = $this->db->getConnection();
        $table = $config['table'] ?? 'papers_master';
        $limit = $config['limit'] ?? 10;
        
        $sql = "SELECT * FROM $table ORDER BY id DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return ['rows' => $rows];
    }
    
    private function getRecentActivity($config) {
        $conn = $this->db->getConnection();
        $limit = $config['limit'] ?? 10;
        
        $sql = "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $activities = [];
        
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        
        return ['activities' => $activities];
    }
    
    private function getLowStockItems($config) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT 'papers' as type, item_name, quantity, reorder_level 
                FROM papers_master 
                WHERE quantity < reorder_level
                UNION ALL
                SELECT 'ribbons' as type, item_name, quantity, reorder_level 
                FROM ribbons_master 
                WHERE quantity < reorder_level
                UNION ALL
                SELECT 'toner' as type, item_name, quantity, reorder_level 
                FROM toner_master 
                WHERE quantity < reorder_level
                ORDER BY quantity ASC
                LIMIT 20";
        
        $result = $conn->query($sql);
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return ['items' => $items];
    }
    
    private function getPendingApprovals($config) {
        $userId = $_SESSION['user_id'];
        $workflowEngine = new WorkflowEngine();
        
        return ['approvals' => $workflowEngine->getPendingApprovals($userId)];
    }
    
    private function getCalendarData($config) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM scheduled_reports 
                WHERE next_run >= CURDATE() 
                ORDER BY next_run 
                LIMIT 10";
        
        $result = $conn->query($sql);
        $events = [];
        
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'title' => 'Report: ' . $row['report_name'],
                'date' => $row['next_run']
            ];
        }
        
        return ['events' => $events];
    }
}

/**
 * Dashboard Templates
 */
class DashboardTemplate {
    /**
     * Create admin dashboard
     */
    public static function createAdminDashboard($userId) {
        $builder = new DashboardBuilder($userId);
        $builder->create('Admin Dashboard', 'Comprehensive admin view', true)
            ->addWidget('stats_card', ['metric' => 'total_papers'], 0, ['width' => 3, 'height' => 2])
            ->addWidget('stats_card', ['metric' => 'total_ribbons'], 1, ['width' => 3, 'height' => 2])
            ->addWidget('stats_card', ['metric' => 'total_toner'], 2, ['width' => 3, 'height' => 2])
            ->addWidget('stats_card', ['metric' => 'low_stock_count'], 3, ['width' => 3, 'height' => 2])
            ->addWidget('chart', ['chart_type' => 'line', 'data_source' => 'monthly_papers'], 4, ['width' => 8, 'height' => 4])
            ->addWidget('low_stock_alert', [], 5, ['width' => 4, 'height' => 4])
            ->addWidget('recent_activity', ['limit' => 10], 6, ['width' => 6, 'height' => 4])
            ->addWidget('pending_approvals', [], 7, ['width' => 6, 'height' => 4]);
        
        return $builder->getDashboardId();
    }
    
    /**
     * Create inventory manager dashboard
     */
    public static function createInventoryDashboard($userId) {
        $builder = new DashboardBuilder($userId);
        $builder->create('Inventory Dashboard', 'Inventory management view', true)
            ->addWidget('low_stock_alert', [], 0, ['width' => 6, 'height' => 4])
            ->addWidget('chart', ['chart_type' => 'bar', 'data_source' => 'stock_levels'], 1, ['width' => 6, 'height' => 4])
            ->addWidget('table', ['table' => 'papers_master', 'limit' => 10], 2, ['width' => 12, 'height' => 6]);
        
        return $builder->getDashboardId();
    }
}
