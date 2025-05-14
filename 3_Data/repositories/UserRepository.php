<?php
// FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
error_reporting(E_ALL);
ini_set('display_errors', 1);
// FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE

require_once __DIR__ . '/../Database.php';

class UserRepository {
    private $pdo;
    
    public function __construct() {
        // Get database connection
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function findByEmail($email) {
        try {
            // Find user by their email
            $stmt = $this->pdo->prepare("SELECT * FROM User WHERE Email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Database error in findByEmail: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return false;
        }
    }
    
    public function createUser($data) {
        try {
            // Create new user account
            $stmt = $this->pdo->prepare(
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
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            if (!$result) {
                error_log("User creation failed: " . implode(", ", $stmt->errorInfo()));
            }
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return $result;
            
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Database error in createUser: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return false;
        }
    }

    // Methods for admin dashboard statistics
    
    public function getTotalUsers() {
        try {
            // Count all registered users
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM User");
            $stmt->execute();
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Database error in getTotalUsers: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return 0;
        }
    }

    public function getActiveUsers() {
        try {
            // Count active users only
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as count FROM User 
                WHERE AccountStatus = 'Active'"
            );
            $stmt->execute();
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Database error in getActiveUsers: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return 0;
        }
    }

    public function getActiveUsersThisMonth() {
        try {
            // Count users active this month
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as count FROM User 
                WHERE AccountStatus = 'Active' 
                AND MONTH(LastLogin) = MONTH(CURRENT_DATE()) 
                AND YEAR(LastLogin) = YEAR(CURRENT_DATE())"
            );
            $stmt->execute();
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Database error in getActiveUsersThisMonth: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return 0;
        }
    }

    public function getAverageSavings() {
        try {
            // Calculate average savings amount
            $stmt = $this->pdo->prepare(
                "SELECT AVG(Amount) as average FROM Savings"
            );
            $stmt->execute();
            return $stmt->fetch()['average'] ?? 0;
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Database error in getAverageSavings: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return 0;
        }
    }

    public function getCommonCategories($limit = 4) {
        try {
            // Get most used transaction categories
            $stmt = $this->pdo->prepare(
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
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Database error in getCommonCategories: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return [
                ['name' => 'Travel', 'count' => 0],
                ['name' => 'Emergency Funds', 'count' => 0],
                ['name' => 'Bills', 'count' => 0]
            ];
        }
    }
}