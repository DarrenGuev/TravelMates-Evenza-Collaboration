<?php
require_once __DIR__ . '/Database.php';

//Abstract Model class - Base class for all models
//Provides common database operations and utilities that all model classes can inherit.
abstract class Model
{
    protected Database $db;
    protected mysqli $conn;
    protected string $table;
    protected string $primaryKey = 'id';

    /**
     * Constructor - initializes database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Get all records from the table
     * 
     * @param string $orderBy Column to order by
     * @param string $direction ASC or DESC
     * @return array
     */
    public function getAll(string $orderBy = '', string $direction = 'ASC'): array
    {
        $query = "SELECT * FROM `{$this->table}`";
        
        if (!empty($orderBy)) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            // Handle multiple columns (comma-separated)
            $columns = array_map('trim', explode(',', $orderBy));
            $orderClauses = array_map(function($col) use ($direction) {
                return "`{$col}` {$direction}";
            }, $columns);
            $query .= " ORDER BY " . implode(', ', $orderClauses);
        }

        $result = $this->db->query($query);
        return $result ? $this->db->fetchAll($result) : [];
    }

    /**
     * Find a record by its primary key
     * 
     * @param int $id Primary key value
     * @return array|null
     */
    public function find(int $id): ?array
    {
        $query = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        $result = $this->db->executeStatement($query, 'i', [$id]);
        
        if ($result && $result->num_rows > 0) {
            return $this->db->fetchOne($result);
        }
        
        return null;
    }

    /**
     * Find records by a specific column value
     * 
     * @param string $column Column name
     * @param mixed $value Value to match
     * @return array
     */
    public function findBy(string $column, $value): array
    {
        $type = is_int($value) ? 'i' : 's';
        $query = "SELECT * FROM `{$this->table}` WHERE `{$column}` = ?";
        $result = $this->db->executeStatement($query, $type, [$value]);
        
        return $result ? $this->db->fetchAll($result) : [];
    }

    /**
     * Find a single record by a specific column value
     * 
     * @param string $column Column name
     * @param mixed $value Value to match
     * @return array|null
     */
    public function findOneBy(string $column, $value): ?array
    {
        $results = $this->findBy($column, $value);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Insert a new record
     * 
     * @param array $data Associative array of column => value
     * @return int|false The inserted ID or false on failure
     */
    public function insert(array $data)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($columns), '?');
        $types = $this->getBindTypes($values);

        $columnList = implode('`, `', $columns);
        $placeholderList = implode(', ', $placeholders);

        $query = "INSERT INTO `{$this->table}` (`{$columnList}`) VALUES ({$placeholderList})";
        
        $result = $this->db->executeStatement($query, $types, $values);
        
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update a record by primary key
     * 
     * @param int $id Primary key value
     * @param array $data Associative array of column => value
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $setParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setParts[] = "`{$column}` = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $types = $this->getBindTypes($values);
        $setClause = implode(', ', $setParts);

        $query = "UPDATE `{$this->table}` SET {$setClause} WHERE `{$this->primaryKey}` = ?";
        
        return $this->db->executeStatement($query, $types, $values) !== false;
    }

    /**
     * delete a record by primary key
     * 
     * @param int $id Primary key value
     * @return bool
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        return $this->db->executeStatement($query, 'i', [$id]) !== false;
    }

    /**
     * count all records in the table
     * 
     * @return int
     */
    public function count(): int
    {
        $query = "SELECT COUNT(*) as count FROM `{$this->table}`";
        $result = $this->db->query($query);
        
        if ($result) {
            $row = $this->db->fetchOne($result);
            return (int)$row['count'];
        }
        
        return 0;
    }

    /**
     * count records matching a condition
     * 
     * @param string $column Column name
     * @param mixed $value Value to match
     * @return int
     */
    public function countBy(string $column, $value): int
    {
        $type = is_int($value) ? 'i' : 's';
        $query = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE `{$column}` = ?";
        $result = $this->db->executeStatement($query, $type, [$value]);
        
        if ($result) {
            $row = $this->db->fetchOne($result);
            return (int)$row['count'];
        }
        
        return 0;
    }

    /**
     * check if a record exists by column value
     * 
     * @param string $column Column name
     * @param mixed $value Value to match
     * @return bool
     */
    public function exists(string $column, $value): bool
    {
        return $this->countBy($column, $value) > 0;
    }

    /**
     * execute a raw query
     * 
     * @param string $query SQL query
     * @return mysqli_result|bool
     */
    protected function rawQuery(string $query)
    {
        return $this->db->query($query);
    }

    /**
     * execute a prepared statement
     * 
     * @param string $query SQL query with placeholders
     * @param string $types Parameter types
     * @param array $params Parameters to bind
     * @return mysqli_result|bool
     */
    protected function executeStatement(string $query, string $types = '', array $params = [])
    {
        return $this->db->executeStatement($query, $types, $params);
    }

    /**
     * escape a string for safe SQL use
     * 
     * @param string $string String to escape
     * @return string
     */
    protected function escape(string $string): string
    {
        return $this->db->escape($string);
    }

    /**
     * get bind types string from an array of values
     * 
     * @param array $values Values to determine types for
     * @return string
     */
    protected function getBindTypes(array $values): string
    {
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_null($value)) {
                $types .= 's';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
}
