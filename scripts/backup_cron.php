<?php
/**
 * Automated Backup Script
 * Run this script via cron job or task scheduler
 * 
 * Windows Task Scheduler:
 * - Action: Start a program
 * - Program: C:\xampp\php\php.exe
 * - Arguments: C:\xampp\htdocs\slpasystem\scripts\backup_cron.php
 * - Schedule: Daily at 2:00 AM
 * 
 * Linux Cron:
 * 0 2 * * * php /path/to/slpasystem/scripts/backup_cron.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load system
require_once __DIR__ . '/../includes/db.php';

try {
    echo "=== SLPA Backup Script Started ===\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
    
    $backup = new BackupManager();
    $logger = new Logger();
    
    // Create backup
    echo "Creating database backup...\n";
    $result = $backup->createBackup([
        'compress' => true
    ]);
    
    if ($result['success']) {
        echo "✓ Backup created successfully!\n";
        echo "  File: {$result['file']}\n";
        echo "  Size: " . number_format($result['size'] / 1024 / 1024, 2) . " MB\n";
        echo "  Tables: {$result['tables']}\n\n";
        
        $logger->info("Automated backup completed", $result);
    } else {
        echo "✗ Backup failed!\n";
        echo "  Error: {$result['error']}\n\n";
        
        $logger->error("Automated backup failed", $result);
    }
    
    // Cleanup old backups
    echo "Cleaning up old backups...\n";
    $deleted = $backup->cleanupOldBackups(30, 5);
    echo "  Deleted {$deleted} old backup(s)\n\n";
    
    // Show backup statistics
    $stats = $backup->getStatistics();
    if ($stats) {
        echo "=== Backup Statistics ===\n";
        echo "Total Backups: {$stats['total_backups']}\n";
        echo "Total Size: {$stats['total_size_formatted']}\n";
        echo "Oldest Backup: {$stats['oldest_backup']}\n";
        echo "Newest Backup: {$stats['newest_backup']}\n\n";
    }
    
    // Check and create notifications for low stock
    echo "Checking for low stock alerts...\n";
    $notifyManager = new NotificationManager();
    $lowStockAlerts = $notifyManager->checkLowStock(100);
    echo "  Created {$lowStockAlerts} low stock alert(s)\n";
    
    // Check for pending returns
    echo "Checking for pending returns...\n";
    $pendingAlerts = $notifyManager->checkPendingReturns(7);
    echo "  Created {$pendingAlerts} pending return alert(s)\n\n";
    
    // Cleanup old notifications
    echo "Cleaning up old notifications...\n";
    $cleanedNotifications = $notifyManager->cleanup(30);
    echo "  Deleted {$cleanedNotifications} old notification(s)\n\n";
    
    echo "=== Backup Script Completed Successfully ===\n";
    exit(0);
    
} catch (Exception $e) {
    echo "✗ Script failed with error:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n\n";
    
    if (isset($logger)) {
        $logger->error("Backup script failed", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
    
    exit(1);
}
