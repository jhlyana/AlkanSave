<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        $host = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_NAME'];
        $username = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASS'];
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
                $username, 
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            error_log("Database connection established successfully");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // For debugging purposes
    public function getLastError() {
        if ($this->connection) {
            return $this->connection->errorInfo();
        }
        return "No connection established";
    }
}