<?php
require_once __DIR__ . '/../Database.php';

class AdminRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM Admin WHERE Email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function updateLastLogin($adminId) {
        $stmt = $this->db->prepare(
            "UPDATE Admin SET LastLogin = NOW() WHERE AdminID = ?"
        );
        return $stmt->execute([$adminId]);
    }
}
?>