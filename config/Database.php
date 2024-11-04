<?php
// config/Database.php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Load configuration
        $config = require dirname(__FILE__) . '/config.php';
        
        $this->host = $config['db']['host'];
        $this->db_name = $config['db']['name'];
        $this->username = $config['db']['user'];
        $this->password = $config['db']['pass'];
    }

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $this->conn;
        } catch(PDOException $e) {
            throw new Exception("Connection Error: " . $e->getMessage());
        }
    }
}