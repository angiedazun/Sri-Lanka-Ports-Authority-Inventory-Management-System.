<?php
/**
 * Advanced Reporting Engine
 * Professional-grade reporting system with custom report builder
 * 
 * @package SLPA\Reports
 * @version 1.0.0
 */

class ReportBuilder {
    private $db;
    private $logger;
    private $columns = [];
    private $tables = [];
    private $joins = [];
    private $conditions = [];
    private $groupBy = [];
    private $orderBy = [];
    private $aggregates = [];
    private $limit;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Add column to report
     */
    public function addColumn($table, $column, $alias = null, $function = null) {
        $this->columns[] = [
            'table' => $table,
            'column' => $column,
            'alias' => $alias ?: $column,
            'function' => $function
        ];
        
        if (!in_array($table, $this->tables)) {
            $this->tables[] = $table;
        }
        
        return $this;
    }
    
    /**
     * Add join
     */
    public function addJoin($type, $table, $condition) {
        $this->joins[] = [
            'type' => strtoupper($type),
            'table' => $table,
            'condition' => $condition
        ];
        
        if (!in_array($table, $this->tables)) {
            $this->tables[] = $table;
        }
        
        return $this;
    }
    
    /**
     * Add condition
     */
    public function addCondition($column, $operator, $value, $logic = 'AND') {
        $this->conditions[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'logic' => strtoupper($logic)
        ];
        
        return $this;
    }
    
    /**
     * Add group by
     */
    public function addGroupBy($column) {
        $this->groupBy[] = $column;
        return $this;
    }
    
    /**
     * Add order by
     */
    public function addOrderBy($column, $direction = 'ASC') {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }
    
    /**
     * Add aggregate
     */
    public function addAggregate($function, $column, $alias) {
        $this->aggregates[] = [
            'function' => strtoupper($function),
            'column' => $column,
            'alias' => $alias
        ];
        return $this;
    }
    
    /**
     * Set limit
     */
    public function setLimit($limit) {
        $this->limit = (int)$limit;
        return $this;
    }
    
    /**
     * Build SQL query
     */
    public function buildQuery() {
        // SELECT clause
        $selectParts = [];
        
        foreach ($this->columns as $col) {
            $part = "`{$col['table']}`.`{$col['column']}`";
            
            if ($col['function']) {
                $part = "{$col['function']}($part)";
            }
            
            if ($col['alias'] !== $col['column']) {
                $part .= " AS `{$col['alias']}`";
            }
            
            $selectParts[] = $part;
        }
        
        foreach ($this->aggregates as $agg) {
            $selectParts[] = "{$agg['function']}(`{$agg['column']}`) AS `{$agg['alias']}`";
        }
        
        $sql = "SELECT " . implode(', ', $selectParts);
        
        // FROM clause
        if (empty($this->tables)) {
            throw new Exception("No tables specified");
        }
        
        $sql .= " FROM `{$this->tables[0]}`";
        
        // JOIN clauses
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN `{$join['table']}` ON {$join['condition']}";
        }
        
        // WHERE clause
        if (!empty($this->conditions)) {
            $whereParts = [];
            foreach ($this->conditions as $i => $cond) {
                $logic = $i > 0 ? $cond['logic'] : '';
                $whereParts[] = "$logic `{$cond['column']}` {$cond['operator']} ?";
            }
            $sql .= " WHERE " . implode(' ', $whereParts);
        }
        
        // GROUP BY clause
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', array_map(function($col) {
                return "`$col`";
            }, $this->groupBy));
        }
        
        // ORDER BY clause
        if (!empty($this->orderBy)) {
            $orderParts = [];
            foreach ($this->orderBy as $order) {
                $orderParts[] = "`{$order['column']}` {$order['direction']}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }
        
        // LIMIT clause
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        return $sql;
    }
    
    /**
     * Execute report
     */
    public function execute() {
        $sql = $this->buildQuery();
        
        $conn = $this->db->getConnection();
        
        if (empty($this->conditions)) {
            $result = $conn->query($sql);
        } else {
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            $types = '';
            $values = [];
            foreach ($this->conditions as $cond) {
                $values[] = $cond['value'];
                $types .= is_numeric($cond['value']) ? 'i' : 's';
            }
            
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Export to format
     */
    public function export($format = 'csv', $filename = null) {
        $data = $this->execute();
        
        if (!$filename) {
            $filename = 'report_' . date('Y-m-d_His');
        }
        
        $exporter = new ExportManager();
        
        switch (strtolower($format)) {
            case 'csv':
                return $exporter->exportCSV('temp_export', $filename, ['where' => '1=1']);
            case 'excel':
                return $exporter->exportExcel('temp_export', $filename, ['where' => '1=1']);
            case 'pdf':
                return $exporter->exportPDF('temp_export', $filename, ['where' => '1=1']);
            case 'json':
                return $exporter->exportJSON('temp_export', $filename, ['where' => '1=1']);
            default:
                throw new Exception("Unsupported export format: $format");
        }
    }
    
    /**
     * Get report configuration
     */
    public function getConfig() {
        return [
            'columns' => $this->columns,
            'tables' => $this->tables,
            'joins' => $this->joins,
            'conditions' => $this->conditions,
            'groupBy' => $this->groupBy,
            'orderBy' => $this->orderBy,
            'aggregates' => $this->aggregates,
            'limit' => $this->limit
        ];
    }
    
    /**
     * Save report definition
     */
    public function save($name, $description = '', $isPublic = false) {
        $definition = $this->getConfig();
        
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO report_definitions (name, description, definition, is_public, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $definitionJson = json_encode($definition);
        $userId = $_SESSION['user_id'] ?? 0;
        
        $stmt->bind_param('sssii', $name, $description, $definitionJson, $isPublic, $userId);
        $stmt->execute();
        
        return $conn->insert_id;
    }
    
    /**
     * Load report definition
     */
    public static function load($reportId) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT definition FROM report_definitions WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $reportId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            throw new Exception("Report not found: $reportId");
        }
        
        $definition = json_decode($row['definition'], true);
        
        $report = new self();
        $report->columns = $definition['columns'] ?? [];
        $report->tables = $definition['tables'] ?? [];
        $report->joins = $definition['joins'] ?? [];
        $report->conditions = $definition['conditions'] ?? [];
        $report->groupBy = $definition['groupBy'] ?? [];
        $report->orderBy = $definition['orderBy'] ?? [];
        $report->aggregates = $definition['aggregates'] ?? [];
        $report->limit = $definition['limit'] ?? null;
        
        return $report;
    }
}

/**
 * Report Scheduler
 */
class ReportScheduler {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Schedule report
     */
    public function schedule($reportId, $frequency, $recipients, $format = 'pdf', $parameters = []) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO report_schedules 
                (report_id, frequency, recipients, format, parameters, next_run, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        
        $recipientsJson = json_encode($recipients);
        $parametersJson = json_encode($parameters);
        $nextRun = $this->calculateNextRun($frequency);
        
        $stmt->bind_param('isssss', $reportId, $frequency, $recipientsJson, $format, $parametersJson, $nextRun);
        $stmt->execute();
        
        $this->logger->info("Report scheduled", [
            'report_id' => $reportId,
            'frequency' => $frequency,
            'next_run' => $nextRun
        ]);
        
        return $conn->insert_id;
    }
    
    /**
     * Calculate next run time
     */
    private function calculateNextRun($frequency) {
        $now = new DateTime();
        
        switch ($frequency) {
            case 'daily':
                $now->modify('+1 day');
                break;
            case 'weekly':
                $now->modify('+1 week');
                break;
            case 'monthly':
                $now->modify('+1 month');
                break;
            case 'quarterly':
                $now->modify('+3 months');
                break;
            default:
                throw new Exception("Invalid frequency: $frequency");
        }
        
        return $now->format('Y-m-d H:i:s');
    }
    
    /**
     * Run scheduled reports
     */
    public function runScheduled() {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM report_schedules 
                WHERE next_run <= NOW() AND is_active = 1";
        
        $result = $conn->query($sql);
        
        while ($schedule = $result->fetch_assoc()) {
            try {
                $this->executeSchedule($schedule);
                
                // Update next run
                $nextRun = $this->calculateNextRun($schedule['frequency']);
                $updateSql = "UPDATE report_schedules SET next_run = ?, last_run = NOW() WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param('si', $nextRun, $schedule['id']);
                $stmt->execute();
                
            } catch (Exception $e) {
                $this->logger->error("Scheduled report failed", [
                    'schedule_id' => $schedule['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Execute scheduled report
     */
    private function executeSchedule($schedule) {
        $report = ReportBuilder::load($schedule['report_id']);
        
        // Apply parameters if any
        $parameters = json_decode($schedule['parameters'], true);
        if ($parameters) {
            foreach ($parameters as $param => $value) {
                $report->addCondition($param, '=', $value);
            }
        }
        
        // Generate report
        $filename = $report->export($schedule['format']);
        
        // Send to recipients
        $recipients = json_decode($schedule['recipients'], true);
        $this->emailReport($recipients, $filename, $schedule['format']);
        
        return $filename;
    }
    
    /**
     * Email report to recipients
     */
    private function emailReport($recipients, $filename, $format) {
        // Email report using basic mail function
        $subject = 'Scheduled Report: ' . basename($filename);
        $message = "Please find attached the scheduled report.";
        $headers = "From: noreply@slpa.lk";
        
        foreach ($recipients as $recipient) {
            mail($recipient, $subject, $message, $headers);
        }
    }
}

/**
 * Report Templates
 */
class ReportTemplate {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get available templates
     */
    public function getTemplates($category = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM report_templates WHERE 1=1";
        
        if ($category) {
            $sql .= " AND category = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $category);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            $templates[] = $row;
        }
        
        return $templates;
    }
    
    /**
     * Create report from template
     */
    public function createFromTemplate($templateId, $parameters = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM report_templates WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $templateId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        
        if (!$template) {
            throw new Exception("Template not found: $templateId");
        }
        
        $definition = json_decode($template['definition'], true);
        
        $report = new ReportBuilder();
        
        // Apply template definition
        foreach ($definition['columns'] ?? [] as $col) {
            $report->addColumn($col['table'], $col['column'], $col['alias'], $col['function'] ?? null);
        }
        
        foreach ($definition['joins'] ?? [] as $join) {
            $report->addJoin($join['type'], $join['table'], $join['condition']);
        }
        
        // Apply user parameters
        foreach ($parameters as $param => $value) {
            $report->addCondition($param, '=', $value);
        }
        
        return $report;
    }
    
    /**
     * Save as template
     */
    public function saveAsTemplate($name, $category, $description, ReportBuilder $report) {
        $conn = $this->db->getConnection();
        
        $definition = $report->getConfig();
        
        $sql = "INSERT INTO report_templates (name, category, description, definition, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $definitionJson = json_encode($definition);
        
        $stmt->bind_param('ssss', $name, $category, $description, $definitionJson);
        $stmt->execute();
        
        return $conn->insert_id;
    }
}

/**
 * Data Visualization Helper
 */
class ReportVisualizer {
    /**
     * Generate chart data
     */
    public static function prepareChartData($data, $labelColumn, $valueColumn, $chartType = 'bar') {
        $labels = [];
        $values = [];
        
        foreach ($data as $row) {
            $labels[] = $row[$labelColumn];
            $values[] = $row[$valueColumn];
        }
        
        return [
            'type' => $chartType,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => ucfirst($valueColumn),
                    'data' => $values
                ]
            ]
        ];
    }
    
    /**
     * Generate summary statistics
     */
    public static function generateSummary($data, $numericColumns) {
        $summary = [];
        
        foreach ($numericColumns as $column) {
            $values = array_column($data, $column);
            
            $summary[$column] = [
                'count' => count($values),
                'sum' => array_sum($values),
                'avg' => count($values) > 0 ? array_sum($values) / count($values) : 0,
                'min' => count($values) > 0 ? min($values) : 0,
                'max' => count($values) > 0 ? max($values) : 0
            ];
        }
        
        return $summary;
    }
}
