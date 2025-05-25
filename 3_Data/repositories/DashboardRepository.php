<?php

require_once __DIR__ . '/../Database.php';

class DashboardRepository {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log("DashboardRepository: Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public function getUserById($userId) {
        try {
            $sql = "SELECT UserID, FirstName, LastName, Email, LastLogin FROM User WHERE UserID = ? AND IsDeleted = FALSE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception("User not found with ID: $userId");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("DashboardRepository::getUserById PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get user data: " . $e->getMessage());
        }
    }

    public function calculateSavingsProgress($userId) {
        try {
            $sql = "
                SELECT 
                    COALESCE(SUM(SavedAmount), 0) as TotalSaved,
                    COALESCE(SUM(TargetAmount), 0) as TotalTarget
                FROM Goal 
                WHERE UserID = ? AND IsDeleted = FALSE
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            $totalSaved = floatval($result['TotalSaved']);
            $totalTarget = floatval($result['TotalTarget']);
            
            if ($totalTarget <= 0) {
                return 0;
            }
            
            $percentage = ($totalSaved / $totalTarget) * 100;
            return min($percentage, 100); // Cap at 100%
            
        } catch (PDOException $e) {
            error_log("DashboardRepository::calculateSavingsProgress PDO Error: " . $e->getMessage());
            throw new Exception("Failed to calculate savings progress: " . $e->getMessage());
        }
    }

    public function calculateGoalProgress($userId) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as TotalGoals,
                    SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as CompletedGoals
                FROM Goal 
                WHERE UserID = ? AND IsDeleted = FALSE
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            $totalGoals = intval($result['TotalGoals']);
            $completedGoals = intval($result['CompletedGoals']);
            
            if ($totalGoals <= 0) {
                return 0;
            }
            
            $percentage = ($completedGoals / $totalGoals) * 100;
            return $percentage;
            
        } catch (PDOException $e) {
            error_log("DashboardRepository::calculateGoalProgress PDO Error: " . $e->getMessage());
            throw new Exception("Failed to calculate goal progress: " . $e->getMessage());
        }
    }

    public function getTotalSavedAmount($userId) {
        try {
            $sql = "
                SELECT COALESCE(SUM(SavedAmount), 0) as TotalSaved
                FROM Goal 
                WHERE UserID = ? AND IsDeleted = FALSE
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return floatval($result['TotalSaved']);
            
        } catch (PDOException $e) {
            error_log("DashboardRepository::getTotalSavedAmount PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get total saved amount: " . $e->getMessage());
        }
    }

    public function getUpcomingDeadlines($userId) {
        try {
            $sql = "
                SELECT GoalName, TargetDate, TargetAmount, SavedAmount
                FROM Goal 
                WHERE UserID = ? 
                    AND IsDeleted = FALSE 
                    AND Status = 'Active'
                    AND TargetDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER BY TargetDate ASC
                LIMIT 10
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll();
            
            return $result ? $result : [];
            
        } catch (PDOException $e) {
            error_log("DashboardRepository::getUpcomingDeadlines PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get upcoming deadlines: " . $e->getMessage());
        }
    }
    
    public function updateLastLogin($userId) {
        try {
            $sql = "UPDATE User SET LastLogin = NOW() WHERE UserID = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("DashboardRepository::updateLastLogin PDO Error: " . $e->getMessage());
            // Don't throw exception for login update failures
        }
    }
}
?>