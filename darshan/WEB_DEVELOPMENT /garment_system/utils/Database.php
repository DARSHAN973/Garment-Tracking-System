<?php
/**
 * Database Utility Class
 * Provides common database operations
 */

class DatabaseHelper {
    private $conn;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->conn = getDbConnection();
    }
    
    /**
     * Get all records from a table with pagination
     */
    public function getAll($table, $conditions = [], $orderBy = 'created_at DESC', $limit = null, $offset = 0) {
        try {
            $sql = "SELECT * FROM {$table}";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClauses = [];
                foreach ($conditions as $column => $value) {
                    $whereClauses[] = "{$column} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            
            $sql .= " ORDER BY {$orderBy}";
            
            if ($limit) {
                $sql .= " LIMIT {$limit} OFFSET {$offset}";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Database getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get single record by ID
     */
    public function getById($table, $id, $idColumn = null) {
        try {
            if (!$idColumn) {
                $idColumn = substr($table, -1) === 's' ? substr($table, 0, -1) . '_id' : $table . '_id';
            }
            
            $stmt = $this->conn->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ?");
            $stmt->execute([$id]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Database getById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Insert new record
     */
    public function insert($table, $data) {
        try {
            // Add audit fields
            $data['created_at'] = getCurrentUTC();
            if (isset($_SESSION['user_id'])) {
                $data['created_by'] = $_SESSION['user_id'];
            }
            
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            $stmt->execute();
            return $this->conn->lastInsertId();
        } catch (Exception $e) {
            error_log("Database insert error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update record by ID
     */
    public function update($table, $id, $data, $idColumn = null) {
        try {
            if (!$idColumn) {
                $idColumn = substr($table, -1) === 's' ? substr($table, 0, -1) . '_id' : $table . '_id';
            }
            
            // Add audit fields
            $data['updated_at'] = getCurrentUTC();
            if (isset($_SESSION['user_id'])) {
                $data['updated_by'] = $_SESSION['user_id'];
            }
            
            $setClause = implode(', ', array_map(fn($key) => "{$key} = :{$key}", array_keys($data)));
            
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$idColumn} = :id";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(':id', $id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Database update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete record by ID (soft delete if column exists)
     */
    public function delete($table, $id, $idColumn = null) {
        try {
            if (!$idColumn) {
                $idColumn = substr($table, -1) === 's' ? substr($table, 0, -1) . '_id' : $table . '_id';
            }
            
            // Check if table has is_active column for soft delete
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$table} LIKE 'is_active'");
            $stmt->execute();
            
            if ($stmt->fetch()) {
                // Soft delete
                return $this->update($table, $id, ['is_active' => 0], $idColumn);
            } else {
                // Hard delete
                $stmt = $this->conn->prepare("DELETE FROM {$table} WHERE {$idColumn} = ?");
                return $stmt->execute([$id]);
            }
        } catch (Exception $e) {
            error_log("Database delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get count of records
     */
    public function count($table, $conditions = []) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$table}";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClauses = [];
                foreach ($conditions as $column => $value) {
                    $whereClauses[] = "{$column} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Database count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Execute custom query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Execute custom query and return single row
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Database queryOne error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }
}
?>