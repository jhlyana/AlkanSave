<?php
require_once __DIR__ . '/../Database.php';

class UserRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM User WHERE Email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in findByEmail: " . $e->getMessage());
            return false;
        }
    }
    
    public function createUser($data) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO User 
                (FirstName, LastName, Email, DOB, PasswordHash, Role) 
                VALUES (?, ?, ?, ?, ?, 'user')"
            );
            $result = $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['dob'],
                password_hash($data['password'], PASSWORD_BCRYPT)
            ]);
            
            if (!$result) {
                error_log("User creation failed: " . implode(", ", $stmt->errorInfo()));
            }
            return $result;
            
        } catch (PDOException $e) {
            error_log("Database error in createUser: " . $e->getMessage());
            return false;
        }
    }
}
?>