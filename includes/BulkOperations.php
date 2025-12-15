<?php
/**
 * Bulk Operations Manager
 * Handles batch processing of inventory items
 * 
 * Features:
 * - Bulk delete
 * - Bulk update
 * - Bulk import from CSV
 * - Bulk status changes
 * - Transaction support
 * - Validation and error handling
 */

class BulkOperations {
    private $db;
    private $logger;
    private $auditTrail;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
        $this->auditTrail = new AuditTrail();
    }
    
    /**
     * Bulk delete items
     */
    public function bulkDelete($table, $ids, $softDelete = false) {
        try {
            if (empty($ids)) {
                throw new Exception('No IDs provided for deletion');
            }
            
            $this->db->begin_transaction();
            
            $deleted = 0;
            $failed = [];
            
            foreach ($ids as $id) {
                try {
                    if ($softDelete && $this->hasColumn($table, 'deleted_at')) {
                        $stmt = $this->db->prepare("UPDATE $table SET deleted_at = NOW() WHERE id = ?");
                    } else {
                        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = ?");
                    }
                    
                    $stmt->bind_param('i', $id);
                    
                    if ($stmt->execute()) {
                        $deleted++;
                        
                        $this->auditTrail->log([
                            'action' => $softDelete ? 'soft_delete' : 'delete',
                            'table_name' => $table,
                            'record_id' => $id,
                            'description' => 'Bulk delete operation'
                        ]);
                    } else {
                        $failed[] = $id;
                    }
                } catch (Exception $e) {
                    $failed[] = $id;
                    $this->logger->warning("Failed to delete ID $id", ['error' => $e->getMessage()]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->info("Bulk delete completed", [
                'table' => $table,
                'deleted' => $deleted,
                'failed' => count($failed)
            ]);
            
            return [
                'success' => true,
                'deleted' => $deleted,
                'failed' => $failed,
                'total' => count($ids)
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error("Bulk delete failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Bulk update items
     */
    public function bulkUpdate($table, $ids, $updates) {
        try {
            if (empty($ids)) {
                throw new Exception('No IDs provided for update');
            }
            
            if (empty($updates)) {
                throw new Exception('No update data provided');
            }
            
            $this->db->begin_transaction();
            
            // Build SET clause
            $setClauses = [];
            $values = [];
            $types = '';
            
            foreach ($updates as $column => $value) {
                $setClauses[] = "$column = ?";
                $values[] = $value;
                $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
            }
            
            $setClause = implode(', ', $setClauses);
            
            $updated = 0;
            $failed = [];
            
            foreach ($ids as $id) {
                try {
                    $sql = "UPDATE $table SET $setClause WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    
                    $params = array_merge($values, [$id]);
                    $paramTypes = $types . 'i';
                    
                    $stmt->bind_param($paramTypes, ...$params);
                    
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $updated++;
                        
                        $this->auditTrail->log([
                            'action' => 'update',
                            'table_name' => $table,
                            'record_id' => $id,
                            'new_values' => json_encode($updates),
                            'description' => 'Bulk update operation'
                        ]);
                    } else {
                        $failed[] = $id;
                    }
                } catch (Exception $e) {
                    $failed[] = $id;
                    $this->logger->warning("Failed to update ID $id", ['error' => $e->getMessage()]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->info("Bulk update completed", [
                'table' => $table,
                'updated' => $updated,
                'failed' => count($failed)
            ]);
            
            return [
                'success' => true,
                'updated' => $updated,
                'failed' => $failed,
                'total' => count($ids)
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error("Bulk update failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Bulk import from CSV
     */
    public function bulkImportCSV($table, $csvFile, $options = []) {
        try {
            if (!file_exists($csvFile)) {
                throw new Exception('CSV file not found');
            }
            
            $delimiter = $options['delimiter'] ?? ',';
            $skipFirstRow = $options['skip_first_row'] ?? true;
            $updateExisting = $options['update_existing'] ?? false;
            $uniqueColumn = $options['unique_column'] ?? 'id';
            
            $handle = fopen($csvFile, 'r');
            if (!$handle) {
                throw new Exception('Failed to open CSV file');
            }
            
            $this->db->begin_transaction();
            
            $headers = null;
            $imported = 0;
            $updated = 0;
            $failed = [];
            $rowNum = 0;
            
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;
                
                // First row as headers
                if ($rowNum === 1 && $skipFirstRow) {
                    $headers = $row;
                    continue;
                }
                
                try {
                    if ($headers === null) {
                        $headers = array_keys($this->getTableColumns($table));
                    }
                    
                    $data = array_combine($headers, $row);
                    
                    // Check if record exists
                    if ($updateExisting && isset($data[$uniqueColumn])) {
                        $checkStmt = $this->db->prepare("SELECT id FROM $table WHERE $uniqueColumn = ?");
                        $checkStmt->bind_param('s', $data[$uniqueColumn]);
                        $checkStmt->execute();
                        $exists = $checkStmt->get_result()->num_rows > 0;
                        
                        if ($exists) {
                            // Update existing
                            $updates = array_diff_key($data, [$uniqueColumn => '']);
                            $result = $this->bulkUpdate($table, [$data[$uniqueColumn]], $updates);
                            if ($result['success']) {
                                $updated++;
                            } else {
                                $failed[] = ['row' => $rowNum, 'data' => $data];
                            }
                            continue;
                        }
                    }
                    
                    // Insert new record
                    $columns = array_keys($data);
                    $placeholders = implode(',', array_fill(0, count($columns), '?'));
                    $columnsList = implode(',', $columns);
                    
                    $stmt = $this->db->prepare("INSERT INTO $table ($columnsList) VALUES ($placeholders)");
                    
                    $types = str_repeat('s', count($data));
                    $values = array_values($data);
                    
                    $stmt->bind_param($types, ...$values);
                    
                    if ($stmt->execute()) {
                        $imported++;
                    } else {
                        $failed[] = ['row' => $rowNum, 'data' => $data, 'error' => $stmt->error];
                    }
                    
                } catch (Exception $e) {
                    $failed[] = ['row' => $rowNum, 'error' => $e->getMessage()];
                }
            }
            
            fclose($handle);
            $this->db->commit();
            
            $this->logger->info("Bulk import completed", [
                'table' => $table,
                'imported' => $imported,
                'updated' => $updated,
                'failed' => count($failed)
            ]);
            
            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'failed' => $failed,
                'total_rows' => $rowNum - ($skipFirstRow ? 1 : 0)
            ];
            
        } catch (Exception $e) {
            if (isset($handle)) {
                fclose($handle);
            }
            $this->db->rollback();
            $this->logger->error("Bulk import failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Bulk status change
     */
    public function bulkStatusChange($table, $ids, $status, $statusColumn = 'status') {
        try {
            return $this->bulkUpdate($table, $ids, [$statusColumn => $status]);
            
        } catch (Exception $e) {
            $this->logger->error("Bulk status change failed", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Bulk export to CSV
     */
    public function bulkExportCSV($table, $ids = null, $filename = null) {
        try {
            $filename = $filename ?? "{$table}_export_" . date('Y-m-d_His') . ".csv";
            
            // Build query
            $sql = "SELECT * FROM $table";
            
            if ($ids !== null && !empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql .= " WHERE id IN ($placeholders)";
            }
            
            // Execute query
            if ($ids !== null && !empty($ids)) {
                $stmt = $this->db->prepare($sql);
                $types = str_repeat('i', count($ids));
                $stmt->bind_param($types, ...$ids);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $this->db->query($sql);
            }
            
            // Set CSV headers
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Write BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            $firstRow = true;
            
            while ($row = $result->fetch_assoc()) {
                // Write headers
                if ($firstRow) {
                    fputcsv($output, array_keys($row));
                    $firstRow = false;
                }
                
                // Write data
                fputcsv($output, array_values($row));
            }
            
            fclose($output);
            
            $this->logger->info("Bulk export completed", [
                'table' => $table,
                'filename' => $filename
            ]);
            
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Bulk export failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Bulk copy/duplicate items
     */
    public function bulkDuplicate($table, $ids, $fieldsToReset = []) {
        try {
            if (empty($ids)) {
                throw new Exception('No IDs provided for duplication');
            }
            
            $this->db->begin_transaction();
            
            $duplicated = 0;
            $failed = [];
            
            foreach ($ids as $id) {
                try {
                    // Get original record
                    $stmt = $this->db->prepare("SELECT * FROM $table WHERE id = ?");
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        // Remove ID and reset specified fields
                        unset($row['id']);
                        
                        foreach ($fieldsToReset as $field) {
                            if (isset($row[$field])) {
                                $row[$field] = null;
                            }
                        }
                        
                        // Insert duplicate
                        $columns = array_keys($row);
                        $placeholders = implode(',', array_fill(0, count($columns), '?'));
                        $columnsList = implode(',', $columns);
                        
                        $insertStmt = $this->db->prepare("INSERT INTO $table ($columnsList) VALUES ($placeholders)");
                        
                        $types = str_repeat('s', count($row));
                        $values = array_values($row);
                        
                        $insertStmt->bind_param($types, ...$values);
                        
                        if ($insertStmt->execute()) {
                            $duplicated++;
                        } else {
                            $failed[] = $id;
                        }
                    }
                } catch (Exception $e) {
                    $failed[] = $id;
                    $this->logger->warning("Failed to duplicate ID $id", ['error' => $e->getMessage()]);
                }
            }
            
            $this->db->commit();
            
            $this->logger->info("Bulk duplicate completed", [
                'table' => $table,
                'duplicated' => $duplicated,
                'failed' => count($failed)
            ]);
            
            return [
                'success' => true,
                'duplicated' => $duplicated,
                'failed' => $failed,
                'total' => count($ids)
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error("Bulk duplicate failed", ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if table has column
     */
    private function hasColumn($table, $column) {
        $result = $this->db->query("SHOW COLUMNS FROM $table LIKE '$column'");
        return $result->num_rows > 0;
    }
    
    /**
     * Get table columns
     */
    private function getTableColumns($table) {
        $columns = [];
        $result = $this->db->query("SHOW COLUMNS FROM $table");
        
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row['Type'];
        }
        
        return $columns;
    }
}
