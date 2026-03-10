<?php
// =====================================================
// DATABASE CONFIGURATION - XAMPP SETUP
// =====================================================

class Database {
    private $host = 'localhost';
    private $db_name = 'solo_leveling';
    private $username = 'root';  // XAMPP default
    private $password = '';      // XAMPP default (empty)
    private $conn;
    
    // Database connection
    public function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }
        
        return $this->conn;
    }
    
    // Get database connection (singleton pattern)
    public function getConnection() {
        if ($this->conn === null) {
            $this->conn = $this->connect();
        }
        return $this->conn;
    }
}
?>