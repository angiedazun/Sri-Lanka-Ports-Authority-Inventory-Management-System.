<?php
/**
 * Data Import/Export Tools
 * Advanced import/export with mapping, validation, and templates
 * 
 * @package SLPA\ImportExport
 * @version 1.0.0
 */

class ImportManager {
    private $db;
    private $errors = [];
    private $warnings = [];
    private $importedCount = 0;
    private $skippedCount = 0;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Import from CSV file
     */
    public function importCSV($filepath, $table, $mapping, $options = []) {
        if (!file_exists($filepath)) {
            throw new Exception("File not found: $filepath");
        }
        
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            throw new Exception("Cannot open file: $filepath");
        }
        
        $headers = fgetcsv($handle);
        $rowNumber = 1;
        
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                $data = $this->mapRow($headers, $row, $mapping);
                
                if ($this->validateRow($data, $table, $options)) {
                    $this->insertRow($table, $data, $options);
                    $this->importedCount++;
                } else {
                    $this->skippedCount++;
                    $this->addError($rowNumber, "Validation failed");
                }
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("Import failed at row $rowNumber: " . $e->getMessage());
        } finally {
            fclose($handle);
        }
        
        return $this->getImportSummary();
    }
    
    /**
     * Import from Excel file
     * @suppress PhanUndeclaredClassReference
     */
    public function importExcel($filepath, $table, $mapping, $options = []) {
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception('PHPSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet');
        }
        
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet */
        $worksheet = $spreadsheet->getActiveSheet();
        
        $headers = [];
        $highestColumn = $worksheet->getHighestColumn();
        
        // Get headers from first row
        foreach (range('A', $highestColumn) as $col) {
            $headers[] = $worksheet->getCell($col . '1')->getValue();
        }
        
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        
        try {
            $rowNumber = 1;
            foreach ($worksheet->getRowIterator(2) as $row) {
                $rowNumber++;
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                
                $data = $this->mapRow($headers, $rowData, $mapping);
                
                if ($this->validateRow($data, $table, $options)) {
                    $this->insertRow($table, $data, $options);
                    $this->importedCount++;
                } else {
                    $this->skippedCount++;
                }
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
        return $this->getImportSummary();
    }
    
    /**
     * Map row data using mapping configuration
     */
    private function mapRow($headers, $row, $mapping) {
        $data = [];
        
        foreach ($mapping as $dbField => $csvField) {
            $index = array_search($csvField, $headers);
            if ($index !== false && isset($row[$index])) {
                $data[$dbField] = $row[$index];
            }
        }
        
        return $data;
    }
    
    /**
     * Validate row data
     */
    private function validateRow($data, $table, $options) {
        $validationRules = $options['validation'] ?? [];
        
        foreach ($validationRules as $field => $rules) {
            $value = $data[$field] ?? null;
            
            if (isset($rules['required']) && $rules['required'] && empty($value)) {
                $this->addError(0, "Field $field is required");
                return false;
            }
            
            if (isset($rules['type'])) {
                if (!$this->validateType($value, $rules['type'])) {
                    $this->addError(0, "Field $field has invalid type");
                    return false;
                }
            }
            
            if (isset($rules['min']) && $value < $rules['min']) {
                $this->addError(0, "Field $field is below minimum value");
                return false;
            }
            
            if (isset($rules['max']) && $value > $rules['max']) {
                $this->addError(0, "Field $field exceeds maximum value");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate value type
     */
    private function validateType($value, $type) {
        switch ($type) {
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'date':
                return strtotime($value) !== false;
            default:
                return true;
        }
    }
    
    /**
     * Insert row into table
     */
    private function insertRow($table, $data, $options) {
        $conn = $this->db->getConnection();
        
        $updateOnDuplicate = $options['update_on_duplicate'] ?? false;
        
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($values), '?');
        
        $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        if ($updateOnDuplicate) {
            $updates = [];
            foreach ($fields as $field) {
                $updates[] = "$field = VALUES($field)";
            }
            $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
        }
        
        $stmt = $conn->prepare($sql);
        
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        
        $stmt->execute();
    }
    
    /**
     * Add error
     */
    private function addError($row, $message) {
        $this->errors[] = ['row' => $row, 'message' => $message];
    }
    
    /**
     * Get import summary
     */
    public function getImportSummary() {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }
    
    /**
     * Create import template
     */
    public function createTemplate($table, $filename) {
        $conn = $this->db->getConnection();
        
        $sql = "SHOW COLUMNS FROM $table";
        $result = $conn->query($sql);
        
        $headers = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['Field'] !== 'id' && $row['Field'] !== 'created_at' && $row['Field'] !== 'updated_at') {
                $headers[] = $row['Field'];
            }
        }
        
        $handle = fopen($filename, 'w');
        fputcsv($handle, $headers);
        fclose($handle);
        
        return $filename;
    }
}

/**
 * Export Manager
 */
class ExportManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Export to CSV
     */
    public function exportCSV($table, $filename, $options = []) {
        $conn = $this->db->getConnection();
        
        $where = $options['where'] ?? '1=1';
        $orderBy = $options['order_by'] ?? 'id';
        $limit = $options['limit'] ?? '';
        
        $sql = "SELECT * FROM $table WHERE $where ORDER BY $orderBy";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $result = $conn->query($sql);
        
        $handle = fopen($filename, 'w');
        
        // Write headers
        if ($row = $result->fetch_assoc()) {
            fputcsv($handle, array_keys($row));
            fputcsv($handle, $row);
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($handle, $row);
            }
        }
        
        fclose($handle);
        
        return $filename;
    }
    
    /**
     * Export to Excel
     * @suppress PhanUndeclaredClassReference
     */
    public function exportExcel($table, $filename, $options = []) {
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new Exception('PHPSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet');
        }
        
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet */
        $worksheet = $spreadsheet->getActiveSheet();
        
        $conn = $this->db->getConnection();
        
        $where = $options['where'] ?? '1=1';
        $orderBy = $options['order_by'] ?? 'id';
        
        $sql = "SELECT * FROM $table WHERE $where ORDER BY $orderBy";
        $result = $conn->query($sql);
        
        $rowNumber = 1;
        
        // Write headers
        if ($row = $result->fetch_assoc()) {
            $col = 'A';
            foreach (array_keys($row) as $header) {
                $worksheet->setCellValue($col . $rowNumber, $header);
                $col++;
            }
            
            $rowNumber++;
            
            // Write first row
            $col = 'A';
            foreach ($row as $value) {
                $worksheet->setCellValue($col . $rowNumber, $value);
                $col++;
            }
            $rowNumber++;
            
            // Write remaining rows
            while ($row = $result->fetch_assoc()) {
                $col = 'A';
                foreach ($row as $value) {
                    $worksheet->setCellValue($col . $rowNumber, $value);
                    $col++;
                }
                $rowNumber++;
            }
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filename);
        
        return $filename;
    }
    
    /**
     * Export to PDF
     * @suppress PhanUndeclaredClassReference
     */
    public function exportPDF($table, $filename, $options = []) {
        if (!class_exists('TCPDF')) {
            throw new Exception('TCPDF library not installed. Please run: composer require tecnickcom/tcpdf');
        }
        
        /** @var \TCPDF $pdf */
        $pdf = new \TCPDF();
        $pdf->AddPage();
        
        $conn = $this->db->getConnection();
        
        $where = $options['where'] ?? '1=1';
        $orderBy = $options['order_by'] ?? 'id';
        
        $sql = "SELECT * FROM $table WHERE $where ORDER BY $orderBy";
        $result = $conn->query($sql);
        
        $html = '<table border="1" cellpadding="5">';
        
        // Headers
        if ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            foreach (array_keys($row) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
            
            // First row
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
            
            // Remaining rows
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
        }
        
        $html .= '</table>';
        
        $pdf->writeHTML($html);
        $pdf->Output($filename, 'F');
        
        return $filename;
    }
    
    /**
     * Export to JSON
     */
    public function exportJSON($table, $filename, $options = []) {
        $conn = $this->db->getConnection();
        
        $where = $options['where'] ?? '1=1';
        $orderBy = $options['order_by'] ?? 'id';
        
        $sql = "SELECT * FROM $table WHERE $where ORDER BY $orderBy";
        $result = $conn->query($sql);
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        return $filename;
    }
    
    /**
     * Export custom query
     */
    public function exportQuery($sql, $filename, $format = 'csv') {
        switch ($format) {
            case 'csv':
                return $this->exportQueryToCSV($sql, $filename);
            case 'excel':
                return $this->exportQueryToExcel($sql, $filename);
            case 'json':
                return $this->exportQueryToJSON($sql, $filename);
            default:
                throw new Exception("Unsupported format: $format");
        }
    }
    
    /**
     * Export query to CSV
     */
    private function exportQueryToCSV($sql, $filename) {
        $conn = $this->db->getConnection();
        $result = $conn->query($sql);
        
        $handle = fopen($filename, 'w');
        
        if ($row = $result->fetch_assoc()) {
            fputcsv($handle, array_keys($row));
            fputcsv($handle, $row);
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($handle, $row);
            }
        }
        
        fclose($handle);
        return $filename;
    }
    
    /**
     * Export query to Excel
     * @suppress PhanUndeclaredClassReference
     */
    private function exportQueryToExcel($sql, $filename) {
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new Exception('PHPSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet');
        }
        
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet */
        $worksheet = $spreadsheet->getActiveSheet();
        
        $conn = $this->db->getConnection();
        $result = $conn->query($sql);
        
        $rowNumber = 1;
        
        if ($row = $result->fetch_assoc()) {
            $col = 'A';
            foreach (array_keys($row) as $header) {
                $worksheet->setCellValue($col . $rowNumber, $header);
                $col++;
            }
            $rowNumber++;
            
            $col = 'A';
            foreach ($row as $value) {
                $worksheet->setCellValue($col . $rowNumber, $value);
                $col++;
            }
            $rowNumber++;
            
            while ($row = $result->fetch_assoc()) {
                $col = 'A';
                foreach ($row as $value) {
                    $worksheet->setCellValue($col . $rowNumber, $value);
                    $col++;
                }
                $rowNumber++;
            }
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filename);
        
        return $filename;
    }
    
    /**
     * Export query to JSON
     */
    private function exportQueryToJSON($sql, $filename) {
        $conn = $this->db->getConnection();
        $result = $conn->query($sql);
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        return $filename;
    }
}

/**
 * Bulk Operations Manager
 */
class BulkOperations {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Bulk insert
     */
    public function bulkInsert($table, $data, $batchSize = 1000) {
        $conn = $this->db->getConnection();
        $inserted = 0;
        
        $batches = array_chunk($data, $batchSize);
        
        foreach ($batches as $batch) {
            $fields = array_keys($batch[0]);
            $placeholders = '(' . implode(',', array_fill(0, count($fields), '?')) . ')';
            
            $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES ";
            $sql .= implode(',', array_fill(0, count($batch), $placeholders));
            
            $stmt = $conn->prepare($sql);
            
            $values = [];
            $types = '';
            
            foreach ($batch as $row) {
                foreach ($row as $value) {
                    $values[] = $value;
                    $types .= 's';
                }
            }
            
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            
            $inserted += $stmt->affected_rows;
        }
        
        return $inserted;
    }
    
    /**
     * Bulk update
     */
    public function bulkUpdate($table, $updates, $idField = 'id') {
        $conn = $this->db->getConnection();
        $updated = 0;
        
        foreach ($updates as $id => $data) {
            $sets = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                $sets[] = "$field = ?";
                $values[] = $value;
            }
            
            $values[] = $id;
            
            $sql = "UPDATE $table SET " . implode(', ', $sets) . " WHERE $idField = ?";
            $stmt = $conn->prepare($sql);
            
            $types = str_repeat('s', count($values));
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            
            $updated += $stmt->affected_rows;
        }
        
        return $updated;
    }
    
    /**
     * Bulk delete
     */
    public function bulkDelete($table, $ids, $idField = 'id') {
        $conn = $this->db->getConnection();
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM $table WHERE $idField IN ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        
        return $stmt->affected_rows;
    }
}
