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
                (FirstName, LastName, Email, DOB, PasswordHash, Role, AccountStatus) 
                VALUES (?, ?, ?, ?, ?, 'user', 'Active')"
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

    // ADMIN STATISTICS METHODS
    public function getTotalUsers() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM User");
            $stmt->execute();
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            error_log("Database error in getTotalUsers: " . $e->getMessage());
            return 0;
        }
    }

    public function getActiveUsers() {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM User 
                WHERE AccountStatus = 'Active'"
            );
            $stmt->execute();
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            error_log("Database error in getActiveUsers: " . $e->getMessage());
            return 0;
        }
    }

    public function getActiveUsersThisMonth() {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM User 
                WHERE AccountStatus = 'Active' 
                AND MONTH(LastLogin) = MONTH(CURRENT_DATE()) 
                AND YEAR(LastLogin) = YEAR(CURRENT_DATE())"
            );
            $stmt->execute();
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            error_log("Database error in getActiveUsersThisMonth: " . $e->getMessage());
            return 0;
        }
    }

    public function getAverageSavings() {
        try {
            $stmt = $this->db->prepare(
                "SELECT AVG(Amount) as average FROM Savings"
            );
            $stmt->execute();
            return $stmt->fetch()['average'] ?? 0;
        } catch (PDOException $e) {
            error_log("Database error in getAverageSavings: " . $e->getMessage());
            return 0;
        }
    }

    public function getCommonCategories($limit = 4) {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.CategoryName as name, COUNT(t.TransactionID) as count 
                FROM Transaction t
                JOIN Category c ON t.CategoryID = c.CategoryID
                GROUP BY c.CategoryName
                ORDER BY count DESC
                LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in getCommonCategories: " . $e->getMessage());
            return [
                ['name' => 'Travel', 'count' => 0],
                ['name' => 'Education', 'count' => 0],
                ['name' => 'Emergency Funds', 'count' => 0],
                ['name' => 'Bills', 'count' => 0]
            ];
        }
    }
}
?>