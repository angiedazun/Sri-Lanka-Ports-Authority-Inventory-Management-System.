<?php
/**
 * Database Migration System
 * Provides version control for database schema changes
 * 
 * @package SLPA\Database
 * @version 1.0.0
 */

abstract class Migration {
    protected $db;
    protected $logger;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Run the migration (upgrade)
     */
    abstract public function up();
    
    /**
     * Reverse the migration (downgrade)
     */
    abstract public function down();
    
    /**
     * Get migration name
     */
    public function getName() {
        return get_class($this);
    }
    
    /**
     * Execute raw SQL
     */
    protected function execute($sql) {
        $result = $this->db->query($sql);
        
        if ($result === false) {
            throw new DatabaseException("Migration failed: " . $this->db->getConnection()->error);
        }
        
        return $result;
    }
    
    /**
     * Create table
     */
    protected function createTable($tableName, callable $callback) {
        $schema = new SchemaBuilder($tableName);
        $callback($schema);
        
        $sql = $schema->toSQL('create');
        $this->execute($sql);
        
        $this->logger->info("Created table: $tableName");
    }
    
    /**
     * Drop table
     */
    protected function dropTable($tableName) {
        $sql = "DROP TABLE IF EXISTS `$tableName`";
        $this->execute($sql);
        
        $this->logger->info("Dropped table: $tableName");
    }
    
    /**
     * Alter table
     */
    protected function alterTable($tableName, callable $callback) {
        $schema = new SchemaBuilder($tableName);
        $callback($schema);
        
        $sql = $schema->toSQL('alter');
        $this->execute($sql);
        
        $this->logger->info("Altered table: $tableName");
    }
    
    /**
     * Rename table
     */
    protected function renameTable($oldName, $newName) {
        $sql = "RENAME TABLE `$oldName` TO `$newName`";
        $this->execute($sql);
        
        $this->logger->info("Renamed table: $oldName -> $newName");
    }
    
    /**
     * Add column
     */
    protected function addColumn($tableName, $columnName, $type, $options = []) {
        $sql = "ALTER TABLE `$tableName` ADD COLUMN " . $this->buildColumnDefinition($columnName, $type, $options);
        $this->execute($sql);
        
        $this->logger->info("Added column: $tableName.$columnName");
    }
    
    /**
     * Drop column
     */
    protected function dropColumn($tableName, $columnName) {
        $sql = "ALTER TABLE `$tableName` DROP COLUMN `$columnName`";
        $this->execute($sql);
        
        $this->logger->info("Dropped column: $tableName.$columnName");
    }
    
    /**
     * Modify column
     */
    protected function modifyColumn($tableName, $columnName, $type, $options = []) {
        $sql = "ALTER TABLE `$tableName` MODIFY COLUMN " . $this->buildColumnDefinition($columnName, $type, $options);
        $this->execute($sql);
        
        $this->logger->info("Modified column: $tableName.$columnName");
    }
    
    /**
     * Rename column
     */
    protected function renameColumn($tableName, $oldName, $newName, $type, $options = []) {
        $sql = "ALTER TABLE `$tableName` CHANGE COLUMN `$oldName` " . $this->buildColumnDefinition($newName, $type, $options);
        $this->execute($sql);
        
        $this->logger->info("Renamed column: $tableName.$oldName -> $newName");
    }
    
    /**
     * Add index
     */
    protected function addIndex($tableName, $columns, $indexName = null, $type = 'INDEX') {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $indexName = $indexName ?: 'idx_' . implode('_', $columns);
        $columnList = implode('`, `', $columns);
        
        $sql = "ALTER TABLE `$tableName` ADD $type `$indexName` (`$columnList`)";
        $this->execute($sql);
        
        $this->logger->info("Added index: $tableName.$indexName");
    }
    
    /**
     * Drop index
     */
    protected function dropIndex($tableName, $indexName) {
        $sql = "ALTER TABLE `$tableName` DROP INDEX `$indexName`";
        $this->execute($sql);
        
        $this->logger->info("Dropped index: $tableName.$indexName");
    }
    
    /**
     * Add foreign key
     */
    protected function addForeignKey($tableName, $column, $referencedTable, $referencedColumn, $constraintName = null) {
        $constraintName = $constraintName ?: "fk_{$tableName}_{$column}";
        
        $sql = "ALTER TABLE `$tableName` 
                ADD CONSTRAINT `$constraintName` 
                FOREIGN KEY (`$column`) 
                REFERENCES `$referencedTable`(`$referencedColumn`)
                ON DELETE CASCADE
                ON UPDATE CASCADE";
        
        $this->execute($sql);
        
        $this->logger->info("Added foreign key: $tableName.$column -> $referencedTable.$referencedColumn");
    }
    
    /**
     * Drop foreign key
     */
    protected function dropForeignKey($tableName, $constraintName) {
        $sql = "ALTER TABLE `$tableName` DROP FOREIGN KEY `$constraintName`";
        $this->execute($sql);
        
        $this->logger->info("Dropped foreign key: $tableName.$constraintName");
    }
    
    /**
     * Build column definition
     */
    private function buildColumnDefinition($columnName, $type, $options = []) {
        $sql = "`$columnName` $type";
        
        if (!empty($options['unsigned'])) {
            $sql .= " UNSIGNED";
        }
        
        if (!empty($options['nullable'])) {
            $sql .= " NULL";
        } else {
            $sql .= " NOT NULL";
        }
        
        if (isset($options['default'])) {
            if ($options['default'] === null) {
                $sql .= " DEFAULT NULL";
            } elseif (is_bool($options['default'])) {
                $sql .= " DEFAULT " . ($options['default'] ? '1' : '0');
            } elseif (is_numeric($options['default'])) {
                $sql .= " DEFAULT " . $options['default'];
            } else {
                $sql .= " DEFAULT '" . $this->db->escape($options['default']) . "'";
            }
        }
        
        if (!empty($options['auto_increment'])) {
            $sql .= " AUTO_INCREMENT";
        }
        
        if (!empty($options['primary'])) {
            $sql .= " PRIMARY KEY";
        }
        
        if (!empty($options['unique'])) {
            $sql .= " UNIQUE";
        }
        
        if (!empty($options['comment'])) {
            $sql .= " COMMENT '" . $this->db->escape($options['comment']) . "'";
        }
        
        return $sql;
    }
}

/**
 * Migration Manager
 * Handles migration execution and tracking
 */
class MigrationManager {
    private $db;
    private $logger;
    private $migrationsPath;
    private $migrationsTable = 'migrations';
    
    public function __construct($migrationsPath = null) {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
        $this->migrationsPath = $migrationsPath ?: __DIR__ . '/../database/migrations';
        
        $this->ensureMigrationsTable();
    }
    
    /**
     * Create migrations tracking table
     */
    private function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `batch` INT NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_batch` (`batch`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->query($sql);
    }
    
    /**
     * Run pending migrations
     */
    public function migrate() {
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            echo "No pending migrations.\n";
            return;
        }
        
        $batch = $this->getNextBatchNumber();
        
        $this->db->beginTransaction();
        
        try {
            foreach ($pending as $migrationFile) {
                $this->runMigration($migrationFile, $batch);
            }
            
            $this->db->commit();
            
            echo "Migrated " . count($pending) . " migration(s) successfully.\n";
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            $this->logger->error("Migration failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new DatabaseException("Migration failed: " . $e->getMessage());
        }
    }
    
    /**
     * Rollback last batch of migrations
     */
    public function rollback($steps = 1) {
        $batches = $this->getExecutedBatches($steps);
        
        if (empty($batches)) {
            echo "Nothing to rollback.\n";
            return;
        }
        
        $this->db->beginTransaction();
        
        try {
            foreach ($batches as $batch) {
                $this->rollbackBatch($batch);
            }
            
            $this->db->commit();
            
            echo "Rolled back $steps batch(es) successfully.\n";
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            $this->logger->error("Rollback failed", [
                'error' => $e->getMessage()
            ]);
            
            throw new DatabaseException("Rollback failed: " . $e->getMessage());
        }
    }
    
    /**
     * Reset all migrations
     */
    public function reset() {
        $batches = $this->getExecutedBatches();
        
        $this->db->beginTransaction();
        
        try {
            foreach ($batches as $batch) {
                $this->rollbackBatch($batch);
            }
            
            $this->db->commit();
            
            echo "Reset all migrations successfully.\n";
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new DatabaseException("Reset failed: " . $e->getMessage());
        }
    }
    
    /**
     * Refresh migrations (reset + migrate)
     */
    public function refresh() {
        $this->reset();
        $this->migrate();
        
        echo "Refreshed migrations successfully.\n";
    }
    
    /**
     * Get migration status
     */
    public function status() {
        $executed = $this->getExecutedMigrations();
        $all = $this->getAllMigrationFiles();
        
        $status = [];
        
        foreach ($all as $migration) {
            $status[] = [
                'migration' => $migration,
                'status' => isset($executed[$migration]) ? 'Executed' : 'Pending',
                'batch' => $executed[$migration]['batch'] ?? null,
                'executed_at' => $executed[$migration]['executed_at'] ?? null
            ];
        }
        
        return $status;
    }
    
    /**
     * Run single migration
     */
    private function runMigration($migrationFile, $batch) {
        require_once $this->migrationsPath . '/' . $migrationFile;
        
        $className = $this->getMigrationClassName($migrationFile);
        $migration = new $className();
        
        echo "Migrating: $migrationFile...";
        
        $migration->up();
        
        $this->recordMigration($migrationFile, $batch);
        
        echo " Done.\n";
    }
    
    /**
     * Rollback single batch
     */
    private function rollbackBatch($batch) {
        $migrations = $this->getMigrationsByBatch($batch);
        
        // Rollback in reverse order
        $migrations = array_reverse($migrations);
        
        foreach ($migrations as $migrationFile) {
            $this->rollbackMigration($migrationFile);
        }
    }
    
    /**
     * Rollback single migration
     */
    private function rollbackMigration($migrationFile) {
        require_once $this->migrationsPath . '/' . $migrationFile;
        
        $className = $this->getMigrationClassName($migrationFile);
        $migration = new $className();
        
        echo "Rolling back: $migrationFile...";
        
        $migration->down();
        
        $this->removeMigrationRecord($migrationFile);
        
        echo " Done.\n";
    }
    
    /**
     * Record executed migration
     */
    private function recordMigration($migration, $batch) {
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->migrationsTable}` (`migration`, `batch`) VALUES (?, ?)"
        );
        $stmt->bind_param('si', $migration, $batch);
        $stmt->execute();
    }
    
    /**
     * Remove migration record
     */
    private function removeMigrationRecord($migration) {
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->migrationsTable}` WHERE `migration` = ?"
        );
        $stmt->bind_param('s', $migration);
        $stmt->execute();
    }
    
    /**
     * Get pending migrations
     */
    private function getPendingMigrations() {
        $executed = array_keys($this->getExecutedMigrations());
        $all = $this->getAllMigrationFiles();
        
        return array_diff($all, $executed);
    }
    
    /**
     * Get executed migrations
     */
    private function getExecutedMigrations() {
        $result = $this->db->query(
            "SELECT `migration`, `batch`, `executed_at` FROM `{$this->migrationsTable}` ORDER BY `id`"
        );
        
        $migrations = [];
        
        while ($row = $result->fetch_assoc()) {
            $migrations[$row['migration']] = [
                'batch' => $row['batch'],
                'executed_at' => $row['executed_at']
            ];
        }
        
        return $migrations;
    }
    
    /**
     * Get all migration files
     */
    private function getAllMigrationFiles() {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        
        $files = scandir($this->migrationsPath);
        $migrations = [];
        
        foreach ($files as $file) {
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.php$/', $file)) {
                $migrations[] = $file;
            }
        }
        
        sort($migrations);
        
        return $migrations;
    }
    
    /**
     * Get next batch number
     */
    private function getNextBatchNumber() {
        $result = $this->db->query(
            "SELECT MAX(`batch`) as max_batch FROM `{$this->migrationsTable}`"
        );
        
        $row = $result->fetch_assoc();
        
        return ($row['max_batch'] ?? 0) + 1;
    }
    
    /**
     * Get executed batches
     */
    private function getExecutedBatches($limit = null) {
        $sql = "SELECT DISTINCT `batch` FROM `{$this->migrationsTable}` ORDER BY `batch` DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $result = $this->db->query($sql);
        
        $batches = [];
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row['batch'];
        }
        
        return $batches;
    }
    
    /**
     * Get migrations by batch
     */
    private function getMigrationsByBatch($batch) {
        $stmt = $this->db->prepare(
            "SELECT `migration` FROM `{$this->migrationsTable}` WHERE `batch` = ? ORDER BY `id`"
        );
        $stmt->bind_param('i', $batch);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $migrations = [];
        while ($row = $result->fetch_assoc()) {
            $migrations[] = $row['migration'];
        }
        
        return $migrations;
    }
    
    /**
     * Get migration class name from file
     */
    private function getMigrationClassName($file) {
        // Extract class name from filename
        // Format: YYYY_MM_DD_HHMMSS_ClassName.php
        $parts = explode('_', $file);
        $className = str_replace('.php', '', implode('', array_slice($parts, 4)));
        
        return $className;
    }
}
