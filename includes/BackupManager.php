<?php
/**
 * Backup Manager
 * Handles automated database backups and restoration
 * 
 * Features:
 * - Full database backup
 * - Selective table backup
 * - Backup compression
 * - Automated backup scheduling
 * - Restore from backup
 * - Backup retention management
 */

class BackupManager {
    private $db;
    private $logger;
    private $backupDir;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
        $this->backupDir = __DIR__ . '/../backups';
        
        // Create backup directory if not exists
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        // Protect backup directory with .htaccess
        $this->protectBackupDir();
    }
    
    /**
     * Protect backup directory from web access
     */
    private function protectBackupDir() {
        $htaccess = $this->backupDir . '/.htaccess';
        
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }
    
    /**
     * Create full database backup
     */
    public function createBackup($options = []) {
        try {
            $database = DB_NAME;
            $timestamp = date('Y-m-d_His');
            $filename = $options['filename'] ?? "backup_{$database}_{$timestamp}.sql";
            $compress = $options['compress'] ?? true;
            $tables = $options['tables'] ?? $this->getAllTables();
            
            $backupPath = $this->backupDir . '/' . $filename;
            $content = '';
            
            // Header
            $content .= "-- SLPA System Database Backup\n";
            $content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $content .= "-- Database: $database\n";
            $content .= "-- Tables: " . implode(', ', $tables) . "\n\n";
            $content .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $content .= "SET time_zone = \"+00:00\";\n\n";
            
            // Backup each table
            foreach ($tables as $table) {
                $content .= $this->backupTable($table);
            }
            
            $content .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
            
            // Write to file
            if ($compress) {
                $backupPath .= '.gz';
                $gz = gzopen($backupPath, 'w9');
                gzwrite($gz, $content);
                gzclose($gz);
            } else {
                file_put_contents($backupPath, $content);
            }
            
            $fileSize = filesize($backupPath);
            
            $this->logger->info("Database backup created", [
                'file' => basename($backupPath),
                'size' => $this->formatBytes($fileSize),
                'tables' => count($tables)
            ]);
            
            return [
                'success' => true,
                'file' => basename($backupPath),
                'path' => $backupPath,
                'size' => $fileSize,
                'tables' => count($tables)
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Backup failed", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Backup single table
     */
    private function backupTable($table) {
        $content = "\n-- Table: $table\n";
        $content .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Get CREATE TABLE statement
        $result = $this->db->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch_assoc();
        $content .= $row['Create Table'] . ";\n\n";
        
        // Get table data
        $result = $this->db->query("SELECT * FROM `$table`");
        
        if ($result->num_rows > 0) {
            $content .= "INSERT INTO `$table` VALUES\n";
            $rows = [];
            
            while ($row = $result->fetch_assoc()) {
                $values = array_map(function($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return "'" . $this->db->real_escape_string($value) . "'";
                }, array_values($row));
                
                $rows[] = '(' . implode(', ', $values) . ')';
            }
            
            $content .= implode(",\n", $rows) . ";\n\n";
        }
        
        return $content;
    }
    
    /**
     * Get all tables from database
     */
    private function getAllTables() {
        $tables = [];
        $result = $this->db->query("SHOW TABLES");
        
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        return $tables;
    }
    
    /**
     * Restore database from backup
     */
    public function restoreBackup($filename) {
        try {
            $backupPath = $this->backupDir . '/' . $filename;
            
            if (!file_exists($backupPath)) {
                throw new Exception("Backup file not found: $filename");
            }
            
            // Read backup file
            if (substr($filename, -3) === '.gz') {
                $content = gzfile($backupPath);
                $content = implode('', $content);
            } else {
                $content = file_get_contents($backupPath);
            }
            
            // Split into queries
            $queries = explode(";\n", $content);
            $executed = 0;
            
            // Disable foreign key checks
            $this->db->query("SET FOREIGN_KEY_CHECKS=0");
            
            // Execute each query
            foreach ($queries as $query) {
                $query = trim($query);
                
                // Skip empty queries and comments
                if (empty($query) || substr($query, 0, 2) === '--' || substr($query, 0, 2) === '/*') {
                    continue;
                }
                
                if ($this->db->query($query)) {
                    $executed++;
                } else {
                    $this->logger->warning("Query failed during restore", [
                        'error' => $this->db->error,
                        'query' => substr($query, 0, 100)
                    ]);
                }
            }
            
            // Re-enable foreign key checks
            $this->db->query("SET FOREIGN_KEY_CHECKS=1");
            
            $this->logger->info("Database restored", [
                'file' => $filename,
                'queries_executed' => $executed
            ]);
            
            return [
                'success' => true,
                'queries_executed' => $executed,
                'file' => $filename
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Restore failed", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * List all backups
     */
    public function listBackups() {
        try {
            $backups = [];
            $files = glob($this->backupDir . '/*.{sql,sql.gz}', GLOB_BRACE);
            
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'size_formatted' => $this->formatBytes(filesize($file)),
                    'created' => date('Y-m-d H:i:s', filemtime($file)),
                    'age_days' => floor((time() - filemtime($file)) / 86400)
                ];
            }
            
            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return strcmp($b['created'], $a['created']);
            });
            
            return $backups;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to list backups", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Delete old backups
     */
    public function cleanupOldBackups($keepDays = 30, $keepMinimum = 5) {
        try {
            $backups = $this->listBackups();
            $deleted = 0;
            
            // Keep at least $keepMinimum backups
            if (count($backups) <= $keepMinimum) {
                return 0;
            }
            
            foreach ($backups as $index => $backup) {
                // Keep minimum number of backups
                if ($index < $keepMinimum) {
                    continue;
                }
                
                // Delete if older than $keepDays
                if ($backup['age_days'] > $keepDays) {
                    $filePath = $this->backupDir . '/' . $backup['filename'];
                    if (unlink($filePath)) {
                        $deleted++;
                        $this->logger->info("Deleted old backup", ['file' => $backup['filename']]);
                    }
                }
            }
            
            return $deleted;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to cleanup backups", ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Delete specific backup
     */
    public function deleteBackup($filename) {
        try {
            $backupPath = $this->backupDir . '/' . basename($filename);
            
            if (!file_exists($backupPath)) {
                throw new Exception("Backup file not found");
            }
            
            if (unlink($backupPath)) {
                $this->logger->info("Backup deleted", ['file' => $filename]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to delete backup", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Download backup file
     */
    public function downloadBackup($filename) {
        try {
            $backupPath = $this->backupDir . '/' . basename($filename);
            
            if (!file_exists($backupPath)) {
                throw new Exception("Backup file not found");
            }
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($backupPath));
            header('Cache-Control: no-cache');
            
            readfile($backupPath);
            
            $this->logger->info("Backup downloaded", ['file' => $filename]);
            exit;
            
        } catch (Exception $e) {
            $this->logger->error("Failed to download backup", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Get backup statistics
     */
    public function getStatistics() {
        try {
            $backups = $this->listBackups();
            $totalSize = array_sum(array_column($backups, 'size'));
            
            return [
                'total_backups' => count($backups),
                'total_size' => $totalSize,
                'total_size_formatted' => $this->formatBytes($totalSize),
                'oldest_backup' => end($backups)['created'] ?? null,
                'newest_backup' => $backups[0]['created'] ?? null,
                'average_size' => count($backups) > 0 ? $totalSize / count($backups) : 0
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get backup statistics", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Schedule automatic backup
     * This should be called via cron job or task scheduler
     */
    public function scheduleBackup($frequency = 'daily') {
        $lastBackupFile = $this->backupDir . '/.last_backup';
        $shouldBackup = false;
        
        if (!file_exists($lastBackupFile)) {
            $shouldBackup = true;
        } else {
            $lastBackup = strtotime(file_get_contents($lastBackupFile));
            $timeSince = time() - $lastBackup;
            
            switch ($frequency) {
                case 'hourly':
                    $shouldBackup = $timeSince >= 3600;
                    break;
                case 'daily':
                    $shouldBackup = $timeSince >= 86400;
                    break;
                case 'weekly':
                    $shouldBackup = $timeSince >= 604800;
                    break;
            }
        }
        
        if ($shouldBackup) {
            $result = $this->createBackup(['compress' => true]);
            
            if ($result['success']) {
                file_put_contents($lastBackupFile, date('Y-m-d H:i:s'));
                $this->cleanupOldBackups();
            }
            
            return $result;
        }
        
        return ['success' => false, 'message' => 'Backup not due yet'];
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
