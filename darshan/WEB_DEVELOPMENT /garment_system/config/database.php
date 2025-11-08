<?php
/**
 * Database Configuration
 * Garment Production System
 */

class Database {
    private $host = '162.241.85.70';
    private $db_name = 'a1623fal_garment_production_tracking_system';
    private $username = 'a1623fal_garment_production_user';
    private $password = 'Zose.[2$hP&D';
    private $conn = null;

    
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'"
                    ]
                );
            } catch(PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        return $this->conn;
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
    
    // Test database connection
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
}

// Database connection instance
function getDbConnection() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database->getConnection();
}
?>