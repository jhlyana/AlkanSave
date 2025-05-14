<?php
require_once __DIR__ . '/../Database.php';

class AdminRepository {
    private $db;
    
    public function __construct() {
        // Gets the actual database connection (PDO object)
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function findByEmail($email) {
        // Find admin by their email address
        $stmt = $this->db->prepare("SELECT * FROM Admin WHERE Email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function updateLastLogin($adminId) {
        // Update when this admin last logged in
        $stmt = $this->db->prepare(
            "UPDATE Admin SET LastLogin = NOW() WHERE AdminID = ?"
        );
        return $stmt->execute([$adminId]);
    }
}