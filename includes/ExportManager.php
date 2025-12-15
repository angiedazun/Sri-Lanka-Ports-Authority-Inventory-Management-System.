<?php
/**
 * Export Manager
 * Handles data export to various formats (Excel, PDF, CSV)
 * 
 * Features:
 * - Excel export with formatting
 * - PDF export with tables
 * - CSV export for data import
 * - Automatic column detection
 * - Custom headers and footers
 */

class ExportManager {
    private $db;
    private $logger;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Export data to Excel format
     * Uses HTML table format that Excel can parse
     */
    public function toExcel($data, $filename = 'export', $options = []) {
        try {
            $title = $options['title'] ?? 'Data Export';
            $headers = $options['headers'] ?? array_keys($data[0] ?? []);
            
            // Set headers for Excel download
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
            header('Cache-Control: max-age=0');
            
            // Start HTML table
            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<style>
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #4CAF50; color: white; font-weight: bold; padding: 10px; border: 1px solid #ddd; text-align: left; }
                td { padding: 8px; border: 1px solid #ddd; }
                tr:nth-child(even) { background-color: #f2f2f2; }
                .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; }
            </style>';
            echo '</head><body>';
            
            echo '<div class="title">' . htmlspecialchars($title) . '</div>';
            echo '<div>Generated: ' . date('Y-m-d H:i:s') . '</div>';
            echo '<div>Total Records: ' . count($data) . '</div><br>';
            
            echo '<table border="1">';
            
            // Table headers
            echo '<thead><tr>';
            foreach ($headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr></thead>';
            
            // Table data
            echo '<tbody>';
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($headers as $key => $header) {
                    $cellKey = is_numeric($key) ? $header : $key;
                    $value = $row[$cellKey] ?? '';
                    echo '<td>' . htmlspecialchars($value) . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody>';
            
            echo '</table>';
            
            if (isset($options['footer'])) {
                echo '<div class="footer">' . htmlspecialchars($options['footer']) . '</div>';
            }
            
            echo '</body></html>';
            
            $this->logger->info("Excel export: $filename", ['records' => count($data)]);
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Excel export failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Export data to PDF format
     * Uses HTML table format for PDF generation
     */
    public function toPDF($data, $filename = 'export', $options = []) {
        try {
            $title = $options['title'] ?? 'Data Export';
            $headers = $options['headers'] ?? array_keys($data[0] ?? []);
            $orientation = $options['orientation'] ?? 'P'; // P=Portrait, L=Landscape
            
            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
            header('Cache-Control: max-age=0');
            
            // Start HTML for PDF conversion
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 20mm; }
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; color: #333; }
        .meta { font-size: 10px; color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #4CAF50; color: white; padding: 8px; border: 1px solid #ddd; font-weight: bold; }
        td { padding: 6px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">' . htmlspecialchars($title) . '</div>
        <div class="meta">Generated: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . count($data) . '</div>
    </div>
    
    <table>
        <thead>
            <tr>';
            
            foreach ($headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            
            echo '</tr>
        </thead>
        <tbody>';
        
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($headers as $key => $header) {
                $cellKey = is_numeric($key) ? $header : $key;
                $value = $row[$cellKey] ?? '';
                echo '<td>' . htmlspecialchars($value) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</tbody>
    </table>
    
    <div class="footer">
        <p>SLPA Inventory Management System | © ' . date('Y') . ' Sri Lanka Ports Authority</p>
        ' . (isset($options['footer']) ? '<p>' . htmlspecialchars($options['footer']) . '</p>' : '') . '
    </div>
</body>
</html>';
            
            $this->logger->info("PDF export: $filename", ['records' => count($data)]);
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("PDF export failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Export data to CSV format
     */
    public function toCSV($data, $filename = 'export', $options = []) {
        try {
            $headers = $options['headers'] ?? array_keys($data[0] ?? []);
            $delimiter = $options['delimiter'] ?? ',';
            $enclosure = $options['enclosure'] ?? '"';
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
            header('Cache-Control: max-age=0');
            
            // Open output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write headers
            fputcsv($output, array_values($headers), $delimiter, $enclosure);
            
            // Write data
            foreach ($data as $row) {
                $csvRow = [];
                foreach ($headers as $key => $header) {
                    $cellKey = is_numeric($key) ? $header : $key;
                    $csvRow[] = $row[$cellKey] ?? '';
                }
                fputcsv($output, $csvRow, $delimiter, $enclosure);
            }
            
            fclose($output);
            
            $this->logger->info("CSV export: $filename", ['records' => count($data)]);
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("CSV export failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Export data to JSON format
     */
    public function toJSON($data, $filename = 'export', $options = []) {
        try {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '.json"');
            header('Cache-Control: max-age=0');
            
            $export = [
                'title' => $options['title'] ?? 'Data Export',
                'generated' => date('Y-m-d H:i:s'),
                'total_records' => count($data),
                'data' => $data
            ];
            
            echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            $this->logger->info("JSON export: $filename", ['records' => count($data)]);
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("JSON export failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Query and export in one step
     */
    public function exportQuery($query, $format = 'excel', $filename = 'export', $options = []) {
        try {
            $result = $this->db->query($query);
            $data = [];
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            if (empty($data)) {
                throw new Exception('No data to export');
            }
            
            $method = 'to' . strtoupper($format);
            if (!method_exists($this, $method)) {
                throw new Exception("Export format '$format' not supported");
            }
            
            return $this->$method($data, $filename, $options);
            
        } catch (Exception $e) {
            $this->logger->error("Export query failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
