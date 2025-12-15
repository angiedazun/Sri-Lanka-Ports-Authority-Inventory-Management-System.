<?php
/**
 * Query Builder
 * Fluent interface for building SQL queries
 * 
 * @package SLPA\Database
 * @version 1.0.0
 */

class QueryBuilder {
    private $db;
    private $table;
    private $select = ['*'];
    private $joins = [];
    private $wheres = [];
    private $groupBy = [];
    private $having = [];
    private $orderBy = [];
    private $limit;
    private $offset;
    private $bindings = [];
    
    public function __construct($table = null) {
        $this->db = Database::getInstance();
        $this->table = $table;
    }
    
    /**
     * Set table name
     */
    public function table($table) {
        $this->table = $table;
        return $this;
    }
    
    /**
     * Set SELECT columns
     */
    public function select(...$columns) {
        $this->select = empty($columns) ? ['*'] : $columns;
        return $this;
    }
    
    /**
     * Add SELECT columns
     */
    public function addSelect(...$columns) {
        if ($this->select === ['*']) {
            $this->select = $columns;
        } else {
            $this->select = array_merge($this->select, $columns);
        }
        return $this;
    }
    
    /**
     * Add DISTINCT to SELECT
     */
    public function distinct() {
        $this->select[0] = 'DISTINCT ' . $this->select[0];
        return $this;
    }
    
    /**
     * Add JOIN clause
     */
    public function join($table, $first, $operator = null, $second = null) {
        if ($operator === null) {
            // Assume $first is full join condition
            $this->joins[] = "INNER JOIN `$table` ON $first";
        } else {
            $this->joins[] = "INNER JOIN `$table` ON `$first` $operator `$second`";
        }
        return $this;
    }
    
    /**
     * Add LEFT JOIN clause
     */
    public function leftJoin($table, $first, $operator = null, $second = null) {
        if ($operator === null) {
            $this->joins[] = "LEFT JOIN `$table` ON $first";
        } else {
            $this->joins[] = "LEFT JOIN `$table` ON `$first` $operator `$second`";
        }
        return $this;
    }
    
    /**
     * Add RIGHT JOIN clause
     */
    public function rightJoin($table, $first, $operator = null, $second = null) {
        if ($operator === null) {
            $this->joins[] = "RIGHT JOIN `$table` ON $first";
        } else {
            $this->joins[] = "RIGHT JOIN `$table` ON `$first` $operator `$second`";
        }
        return $this;
    }
    
    /**
     * Add WHERE clause
     */
    public function where($column, $operator = null, $value = null) {
        if (is_array($column)) {
            // Array of conditions
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val);
            }
            return $this;
        }
        
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add OR WHERE clause
     */
    public function orWhere($column, $operator = null, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE IN clause
     */
    public function whereIn($column, array $values) {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE NOT IN clause
     */
    public function whereNotIn($column, array $values) {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE NULL clause
     */
    public function whereNull($column) {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE NOT NULL clause
     */
    public function whereNotNull($column) {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE BETWEEN clause
     */
    public function whereBetween($column, array $values) {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add WHERE LIKE clause
     */
    public function whereLike($column, $value) {
        return $this->where($column, 'LIKE', $value);
    }
    
    /**
     * Add raw WHERE clause
     */
    public function whereRaw($sql, array $bindings = []) {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add GROUP BY clause
     */
    public function groupBy(...$columns) {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }
    
    /**
     * Add HAVING clause
     */
    public function having($column, $operator = null, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Add ORDER BY clause
     */
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        
        return $this;
    }
    
    /**
     * Order by descending
     */
    public function orderByDesc($column) {
        return $this->orderBy($column, 'DESC');
    }
    
    /**
     * Order by multiple columns
     */
    public function orderByMultiple(array $orders) {
        foreach ($orders as $column => $direction) {
            $this->orderBy($column, $direction);
        }
        return $this;
    }
    
    /**
     * Set LIMIT
     */
    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * Set OFFSET
     */
    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * Paginate results
     */
    public function paginate($page = 1, $perPage = 15) {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }
    
    /**
     * Get query results
     */
    public function get() {
        $sql = $this->toSQL();
        $stmt = $this->prepareStatement($sql);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Get first result
     */
    public function first() {
        $this->limit(1);
        $results = $this->get();
        
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Get single value
     */
    public function value($column) {
        $result = $this->select($column)->first();
        
        return $result ? $result[$column] : null;
    }
    
    /**
     * Check if records exist
     */
    public function exists() {
        return $this->count() > 0;
    }
    
    /**
     * Count records
     */
    public function count($column = '*') {
        $original = $this->select;
        $this->select = ["COUNT($column) as count"];
        
        $result = $this->first();
        
        $this->select = $original;
        
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get MAX value
     */
    public function max($column) {
        return $this->aggregate('MAX', $column);
    }
    
    /**
     * Get MIN value
     */
    public function min($column) {
        return $this->aggregate('MIN', $column);
    }
    
    /**
     * Get AVG value
     */
    public function avg($column) {
        return $this->aggregate('AVG', $column);
    }
    
    /**
     * Get SUM value
     */
    public function sum($column) {
        return $this->aggregate('SUM', $column);
    }
    
    /**
     * Execute aggregate function
     */
    private function aggregate($function, $column) {
        $original = $this->select;
        $this->select = ["$function(`$column`) as aggregate"];
        
        $result = $this->first();
        
        $this->select = $original;
        
        return $result ? $result['aggregate'] : null;
    }
    
    /**
     * Insert record
     */
    public function insert(array $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        
        $columnList = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        
        $sql = "INSERT INTO `{$this->table}` (`$columnList`) VALUES ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $this->bindValues($stmt, $values);
        $stmt->execute();
        
        return $this->db->getConnection()->insert_id;
    }
    
    /**
     * Insert multiple records
     */
    public function insertMultiple(array $records) {
        if (empty($records)) {
            return false;
        }
        
        $columns = array_keys($records[0]);
        $columnList = implode('`, `', $columns);
        
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($records), $placeholders));
        
        $sql = "INSERT INTO `{$this->table}` (`$columnList`) VALUES $allPlaceholders";
        
        $values = [];
        foreach ($records as $record) {
            $values = array_merge($values, array_values($record));
        }
        
        $stmt = $this->db->prepare($sql);
        $this->bindValues($stmt, $values);
        
        return $stmt->execute();
    }
    
    /**
     * Update records
     */
    public function update(array $data) {
        $sets = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $sets[] = "`$column` = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $sets);
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
            $values = array_merge($values, $this->bindings);
        }
        
        $stmt = $this->db->prepare($sql);
        $this->bindValues($stmt, $values);
        
        return $stmt->execute();
    }
    
    /**
     * Increment column
     */
    public function increment($column, $amount = 1) {
        return $this->update([
            $column => $this->db->getConnection()->quote("`$column` + $amount")
        ]);
    }
    
    /**
     * Decrement column
     */
    public function decrement($column, $amount = 1) {
        return $this->update([
            $column => $this->db->getConnection()->quote("`$column` - $amount")
        ]);
    }
    
    /**
     * Delete records
     */
    public function delete() {
        $sql = "DELETE FROM `{$this->table}`";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }
        
        $stmt = $this->prepareStatement($sql);
        
        return $stmt->execute();
    }
    
    /**
     * Truncate table
     */
    public function truncate() {
        return $this->db->query("TRUNCATE TABLE `{$this->table}`");
    }
    
    /**
     * Build SQL query
     */
    public function toSQL() {
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= " FROM `{$this->table}`";
        
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }
        
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', array_map(function($col) {
                return "`$col`";
            }, $this->groupBy));
        }
        
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . $this->buildHavingClause();
        }
        
        if (!empty($this->orderBy)) {
            $orders = array_map(function($order) {
                return "`{$order['column']}` {$order['direction']}";
            }, $this->orderBy);
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }
    
    /**
     * Build WHERE clause
     */
    private function buildWhereClause() {
        $this->bindings = [];
        $clauses = [];
        $first = true;
        
        foreach ($this->wheres as $where) {
            $boolean = $first ? '' : " {$where['boolean']} ";
            $first = false;
            
            switch ($where['type']) {
                case 'basic':
                    $clauses[] = $boolean . "`{$where['column']}` {$where['operator']} ?";
                    $this->bindings[] = $where['value'];
                    break;
                    
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $boolean . "`{$where['column']}` IN ($placeholders)";
                    $this->bindings = array_merge($this->bindings, $where['values']);
                    break;
                    
                case 'not_in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $boolean . "`{$where['column']}` NOT IN ($placeholders)";
                    $this->bindings = array_merge($this->bindings, $where['values']);
                    break;
                    
                case 'null':
                    $clauses[] = $boolean . "`{$where['column']}` IS NULL";
                    break;
                    
                case 'not_null':
                    $clauses[] = $boolean . "`{$where['column']}` IS NOT NULL";
                    break;
                    
                case 'between':
                    $clauses[] = $boolean . "`{$where['column']}` BETWEEN ? AND ?";
                    $this->bindings = array_merge($this->bindings, $where['values']);
                    break;
                    
                case 'raw':
                    $clauses[] = $boolean . $where['sql'];
                    $this->bindings = array_merge($this->bindings, $where['bindings']);
                    break;
            }
        }
        
        return implode('', $clauses);
    }
    
    /**
     * Build HAVING clause
     */
    private function buildHavingClause() {
        $clauses = [];
        $first = true;
        
        foreach ($this->having as $having) {
            $boolean = $first ? '' : " {$having['boolean']} ";
            $first = false;
            
            $clauses[] = $boolean . "`{$having['column']}` {$having['operator']} ?";
            $this->bindings[] = $having['value'];
        }
        
        return implode('', $clauses);
    }
    
    /**
     * Prepare statement with bindings
     */
    private function prepareStatement($sql) {
        $stmt = $this->db->prepare($sql);
        
        if (!empty($this->bindings)) {
            $this->bindValues($stmt, $this->bindings);
        }
        
        return $stmt;
    }
    
    /**
     * Bind values to statement
     */
    private function bindValues($stmt, array $values) {
        if (empty($values)) {
            return;
        }
        
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $stmt->bind_param($types, ...$values);
    }
    
    /**
     * Clone query builder
     */
    public function clone() {
        return clone $this;
    }
    
    /**
     * Reset query builder
     */
    public function reset() {
        $this->select = ['*'];
        $this->joins = [];
        $this->wheres = [];
        $this->groupBy = [];
        $this->having = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->bindings = [];
        
        return $this;
    }
}

/**
 * Helper function to create QueryBuilder instance
 */
function query($table = null) {
    return new QueryBuilder($table);
}
