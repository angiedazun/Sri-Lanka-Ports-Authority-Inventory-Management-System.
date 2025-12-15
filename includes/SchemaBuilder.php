<?php
/**
 * Schema Builder
 * Fluent interface for building database schemas
 * 
 * @package SLPA\Database
 * @version 1.0.0
 */

class SchemaBuilder {
    private $table;
    private $columns = [];
    private $indexes = [];
    private $foreignKeys = [];
    private $engine = 'InnoDB';
    private $charset = 'utf8mb4';
    private $collation = 'utf8mb4_unicode_ci';
    private $comment;
    
    public function __construct($table) {
        $this->table = $table;
    }
    
    /**
     * Add auto-increment primary key
     */
    public function id($column = 'id') {
        return $this->bigIncrements($column);
    }
    
    /**
     * Add big integer auto-increment primary key
     */
    public function bigIncrements($column) {
        $this->columns[$column] = [
            'type' => 'BIGINT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary' => true
        ];
        return $this;
    }
    
    /**
     * Add integer auto-increment primary key
     */
    public function increments($column) {
        $this->columns[$column] = [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary' => true
        ];
        return $this;
    }
    
    /**
     * Add string column
     */
    public function string($column, $length = 255) {
        $this->columns[$column] = [
            'type' => "VARCHAR($length)"
        ];
        return $this;
    }
    
    /**
     * Add text column
     */
    public function text($column) {
        $this->columns[$column] = [
            'type' => 'TEXT'
        ];
        return $this;
    }
    
    /**
     * Add medium text column
     */
    public function mediumText($column) {
        $this->columns[$column] = [
            'type' => 'MEDIUMTEXT'
        ];
        return $this;
    }
    
    /**
     * Add long text column
     */
    public function longText($column) {
        $this->columns[$column] = [
            'type' => 'LONGTEXT'
        ];
        return $this;
    }
    
    /**
     * Add integer column
     */
    public function integer($column) {
        $this->columns[$column] = [
            'type' => 'INT'
        ];
        return $this;
    }
    
    /**
     * Add big integer column
     */
    public function bigInteger($column) {
        $this->columns[$column] = [
            'type' => 'BIGINT'
        ];
        return $this;
    }
    
    /**
     * Add small integer column
     */
    public function smallInteger($column) {
        $this->columns[$column] = [
            'type' => 'SMALLINT'
        ];
        return $this;
    }
    
    /**
     * Add tiny integer column
     */
    public function tinyInteger($column) {
        $this->columns[$column] = [
            'type' => 'TINYINT'
        ];
        return $this;
    }
    
    /**
     * Add unsigned integer column
     */
    public function unsignedInteger($column) {
        $this->integer($column);
        $this->columns[$column]['unsigned'] = true;
        return $this;
    }
    
    /**
     * Add boolean column
     */
    public function boolean($column) {
        $this->columns[$column] = [
            'type' => 'TINYINT(1)'
        ];
        return $this;
    }
    
    /**
     * Add decimal column
     */
    public function decimal($column, $precision = 8, $scale = 2) {
        $this->columns[$column] = [
            'type' => "DECIMAL($precision, $scale)"
        ];
        return $this;
    }
    
    /**
     * Add float column
     */
    public function float($column, $precision = 8, $scale = 2) {
        $this->columns[$column] = [
            'type' => "FLOAT($precision, $scale)"
        ];
        return $this;
    }
    
    /**
     * Add double column
     */
    public function double($column, $precision = 8, $scale = 2) {
        $this->columns[$column] = [
            'type' => "DOUBLE($precision, $scale)"
        ];
        return $this;
    }
    
    /**
     * Add date column
     */
    public function date($column) {
        $this->columns[$column] = [
            'type' => 'DATE'
        ];
        return $this;
    }
    
    /**
     * Add datetime column
     */
    public function dateTime($column) {
        $this->columns[$column] = [
            'type' => 'DATETIME'
        ];
        return $this;
    }
    
    /**
     * Add timestamp column
     */
    public function timestamp($column) {
        $this->columns[$column] = [
            'type' => 'TIMESTAMP'
        ];
        return $this;
    }
    
    /**
     * Add time column
     */
    public function time($column) {
        $this->columns[$column] = [
            'type' => 'TIME'
        ];
        return $this;
    }
    
    /**
     * Add year column
     */
    public function year($column) {
        $this->columns[$column] = [
            'type' => 'YEAR'
        ];
        return $this;
    }
    
    /**
     * Add enum column
     */
    public function enum($column, array $values) {
        $valueList = "'" . implode("', '", $values) . "'";
        $this->columns[$column] = [
            'type' => "ENUM($valueList)"
        ];
        return $this;
    }
    
    /**
     * Add JSON column
     */
    public function json($column) {
        $this->columns[$column] = [
            'type' => 'JSON'
        ];
        return $this;
    }
    
    /**
     * Add created_at and updated_at timestamps
     */
    public function timestamps() {
        $this->timestamp('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->nullable()->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
        return $this;
    }
    
    /**
     * Add soft delete timestamp
     */
    public function softDeletes($column = 'deleted_at') {
        $this->timestamp($column)->nullable();
        return $this;
    }
    
    /**
     * Make column nullable
     */
    public function nullable() {
        $lastColumn = array_key_last($this->columns);
        $this->columns[$lastColumn]['nullable'] = true;
        return $this;
    }
    
    /**
     * Set default value
     */
    public function default($value) {
        $lastColumn = array_key_last($this->columns);
        $this->columns[$lastColumn]['default'] = $value;
        return $this;
    }
    
    /**
     * Make column unsigned
     */
    public function unsigned() {
        $lastColumn = array_key_last($this->columns);
        $this->columns[$lastColumn]['unsigned'] = true;
        return $this;
    }
    
    /**
     * Make column unique
     */
    public function unique() {
        $lastColumn = array_key_last($this->columns);
        $this->columns[$lastColumn]['unique'] = true;
        return $this;
    }
    
    /**
     * Add column comment
     */
    public function comment($comment) {
        $lastColumn = array_key_last($this->columns);
        $this->columns[$lastColumn]['comment'] = $comment;
        return $this;
    }
    
    /**
     * Set ON UPDATE behavior
     */
    public function onUpdate($value) {
        $lastColumn = array_key_last($this->columns);
        $this->columns[$lastColumn]['on_update'] = $value;
        return $this;
    }
    
    /**
     * Add index
     */
    public function index($columns, $name = null) {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $name = $name ?: 'idx_' . implode('_', $columns);
        
        $this->indexes[] = [
            'name' => $name,
            'columns' => $columns,
            'type' => 'INDEX'
        ];
        
        return $this;
    }
    
    /**
     * Add unique index
     */
    public function uniqueIndex($columns, $name = null) {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $name = $name ?: 'unique_' . implode('_', $columns);
        
        $this->indexes[] = [
            'name' => $name,
            'columns' => $columns,
            'type' => 'UNIQUE'
        ];
        
        return $this;
    }
    
    /**
     * Add fulltext index
     */
    public function fulltextIndex($columns, $name = null) {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $name = $name ?: 'fulltext_' . implode('_', $columns);
        
        $this->indexes[] = [
            'name' => $name,
            'columns' => $columns,
            'type' => 'FULLTEXT'
        ];
        
        return $this;
    }
    
    /**
     * Add foreign key
     */
    public function foreign($column, $referencedTable, $referencedColumn = 'id', $constraintName = null) {
        $constraintName = $constraintName ?: "fk_{$this->table}_{$column}";
        
        $this->foreignKeys[] = [
            'name' => $constraintName,
            'column' => $column,
            'referenced_table' => $referencedTable,
            'referenced_column' => $referencedColumn,
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE'
        ];
        
        return $this;
    }
    
    /**
     * Set foreign key ON DELETE behavior
     */
    public function onDelete($action) {
        $lastKey = array_key_last($this->foreignKeys);
        $this->foreignKeys[$lastKey]['on_delete'] = strtoupper($action);
        return $this;
    }
    
    /**
     * Set foreign key ON UPDATE behavior
     */
    public function onUpdateForeign($action) {
        $lastKey = array_key_last($this->foreignKeys);
        $this->foreignKeys[$lastKey]['on_update'] = strtoupper($action);
        return $this;
    }
    
    /**
     * Set table engine
     */
    public function engine($engine) {
        $this->engine = $engine;
        return $this;
    }
    
    /**
     * Set table charset
     */
    public function charset($charset) {
        $this->charset = $charset;
        return $this;
    }
    
    /**
     * Set table collation
     */
    public function collation($collation) {
        $this->collation = $collation;
        return $this;
    }
    
    /**
     * Set table comment
     */
    public function tableComment($comment) {
        $this->comment = $comment;
        return $this;
    }
    
    /**
     * Convert schema to SQL
     */
    public function toSQL($action = 'create') {
        if ($action === 'create') {
            return $this->buildCreateTableSQL();
        } elseif ($action === 'alter') {
            return $this->buildAlterTableSQL();
        }
        
        throw new InvalidArgumentException("Invalid action: $action");
    }
    
    /**
     * Build CREATE TABLE SQL
     */
    private function buildCreateTableSQL() {
        $sql = "CREATE TABLE `{$this->table}` (\n";
        
        $definitions = [];
        
        // Add columns
        foreach ($this->columns as $name => $column) {
            $definitions[] = "  " . $this->buildColumnDefinition($name, $column);
        }
        
        // Add indexes
        foreach ($this->indexes as $index) {
            $columnList = implode('`, `', $index['columns']);
            $definitions[] = "  {$index['type']} `{$index['name']}` (`$columnList`)";
        }
        
        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            $definitions[] = "  CONSTRAINT `{$fk['name']}` " .
                           "FOREIGN KEY (`{$fk['column']}`) " .
                           "REFERENCES `{$fk['referenced_table']}`(`{$fk['referenced_column']}`) " .
                           "ON DELETE {$fk['on_delete']} " .
                           "ON UPDATE {$fk['on_update']}";
        }
        
        $sql .= implode(",\n", $definitions);
        $sql .= "\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation}";
        
        if ($this->comment) {
            $sql .= " COMMENT='{$this->comment}'";
        }
        
        return $sql;
    }
    
    /**
     * Build ALTER TABLE SQL
     */
    private function buildAlterTableSQL() {
        $statements = [];
        
        // Add columns
        foreach ($this->columns as $name => $column) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD COLUMN " . 
                          $this->buildColumnDefinition($name, $column);
        }
        
        // Add indexes
        foreach ($this->indexes as $index) {
            $columnList = implode('`, `', $index['columns']);
            $statements[] = "ALTER TABLE `{$this->table}` ADD {$index['type']} `{$index['name']}` (`$columnList`)";
        }
        
        // Add foreign keys
        foreach ($this->foreignKeys as $fk) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD CONSTRAINT `{$fk['name']}` " .
                          "FOREIGN KEY (`{$fk['column']}`) " .
                          "REFERENCES `{$fk['referenced_table']}`(`{$fk['referenced_column']}`) " .
                          "ON DELETE {$fk['on_delete']} " .
                          "ON UPDATE {$fk['on_update']}";
        }
        
        return implode(";\n", $statements);
    }
    
    /**
     * Build column definition
     */
    private function buildColumnDefinition($name, $column) {
        $sql = "`$name` {$column['type']}";
        
        if (!empty($column['unsigned'])) {
            $sql .= " UNSIGNED";
        }
        
        if (!empty($column['nullable'])) {
            $sql .= " NULL";
        } else {
            $sql .= " NOT NULL";
        }
        
        if (isset($column['default'])) {
            if ($column['default'] === 'CURRENT_TIMESTAMP' || $column['default'] === 'NULL') {
                $sql .= " DEFAULT {$column['default']}";
            } elseif (is_bool($column['default'])) {
                $sql .= " DEFAULT " . ($column['default'] ? '1' : '0');
            } elseif (is_numeric($column['default'])) {
                $sql .= " DEFAULT {$column['default']}";
            } else {
                $sql .= " DEFAULT '{$column['default']}'";
            }
        }
        
        if (!empty($column['on_update'])) {
            $sql .= " ON UPDATE {$column['on_update']}";
        }
        
        if (!empty($column['auto_increment'])) {
            $sql .= " AUTO_INCREMENT";
        }
        
        if (!empty($column['primary'])) {
            $sql .= " PRIMARY KEY";
        }
        
        if (!empty($column['unique'])) {
            $sql .= " UNIQUE";
        }
        
        if (!empty($column['comment'])) {
            $sql .= " COMMENT '{$column['comment']}'";
        }
        
        return $sql;
    }
}

/**
 * Schema Manager
 * High-level database schema operations
 */
class Schema {
    private static $db;
    
    private static function getDB() {
        if (!self::$db) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }
    
    /**
     * Create new table
     */
    public static function create($tableName, callable $callback) {
        $schema = new SchemaBuilder($tableName);
        $callback($schema);
        
        $sql = $schema->toSQL('create');
        self::getDB()->query($sql);
        
        return true;
    }
    
    /**
     * Drop table if exists
     */
    public static function dropIfExists($tableName) {
        $sql = "DROP TABLE IF EXISTS `$tableName`";
        return self::getDB()->query($sql);
    }
    
    /**
     * Check if table exists
     */
    public static function hasTable($tableName) {
        $result = self::getDB()->query(
            "SHOW TABLES LIKE '$tableName'"
        );
        return $result->num_rows > 0;
    }
    
    /**
     * Check if column exists
     */
    public static function hasColumn($tableName, $columnName) {
        $result = self::getDB()->query(
            "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'"
        );
        return $result->num_rows > 0;
    }
    
    /**
     * Get table columns
     */
    public static function getColumns($tableName) {
        $result = self::getDB()->query("SHOW COLUMNS FROM `$tableName`");
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
        
        return $columns;
    }
    
    /**
     * Get table indexes
     */
    public static function getIndexes($tableName) {
        $result = self::getDB()->query("SHOW INDEX FROM `$tableName`");
        
        $indexes = [];
        while ($row = $result->fetch_assoc()) {
            $indexes[] = $row;
        }
        
        return $indexes;
    }
    
    /**
     * Rename table
     */
    public static function rename($oldName, $newName) {
        $sql = "RENAME TABLE `$oldName` TO `$newName`";
        return self::getDB()->query($sql);
    }
}
