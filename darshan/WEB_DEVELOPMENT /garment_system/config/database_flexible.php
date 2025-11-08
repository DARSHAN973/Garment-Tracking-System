<?php
/**
 * Database Configuration - Multiple Host Support
 * Garment Production System
 */

class Database {
    // Try multiple possible hosts for remote database
    private $possible_hosts = [
        'localhost',  // Most common for shared hosting
        'mysql.a1623fal.com',  // Based on username pattern
        'db.a1623fal.com',
        '127.0.0.1'
    ];
    
    private $db_name = 'a1623fal_garment_production_tracking_system';
    private $username = 'a1623fal_garment_production_user';
    private $password = 'Zose.[2$hP&D';
    private $conn = null;
    private $working_host = null;
    
    public function getConnection() {
        if ($this->conn === null) {
            $this->conn = $this->tryConnections();
        }
        return $this->conn;
    }
    
    private function tryConnections() {
        $lastError = '';
        
        foreach ($this->possible_hosts as $host) {
            try {
                echo "<!-- Trying to connect to: $host -->\n";
                
                $conn = new PDO(
                    "mysql:host=" . $host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'"
                    ]
                );
                
                // Test the connection
                $conn->query("SELECT 1");
                
                $this->working_host = $host;
                echo "<!-- Successfully connected to: $host -->\n";
                return $conn;
                
            } catch(PDOException $e) {
                $lastError = $e->getMessage();
                echo "<!-- Failed to connect to $host: " . $e->getMessage() . " -->\n";
                continue;
            }
        }
        
        // If we get here, all connections failed
        error_log("Database Connection Error: All hosts failed. Last error: " . $lastError);
        throw new Exception("Database connection failed to all hosts. Last error: " . $lastError);
    }
    
    public function closeConnection() {
        $this->conn = null;
    }
    
    // Test database connection
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return ['success' => true, 'host' => $this->working_host];
        } catch(Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function getWorkingHost() {
        return $this->working_host;
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