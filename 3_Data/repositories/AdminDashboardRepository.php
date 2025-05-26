<?php

require_once __DIR__ . '/../Database.php';

class AdminDashboardRepository {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log("AdminDashboardRepository: Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public function getUserActivityStats() {
        try {
            $sql = "
                SELECT 
                    COUNT(CASE WHEN LastLogin >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as activeUsers,
                    COUNT(CASE WHEN LastLogin < DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR LastLogin IS NULL THEN 1 END) as inactiveUsers
                FROM User 
                WHERE IsDeleted = FALSE AND Role = 'user'
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            error_log("ADMIN REPO: User activity query result - " . json_encode($result));
            
            return [
                'activeUsers' => intval($result['activeUsers']),
                'inactiveUsers' => intval($result['inactiveUsers'])
            ];
        } catch (PDOException $e) {
            error_log("AdminDashboardRepository::getUserActivityStats PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get user activity stats: " . $e->getMessage());
        }
    }

    public function getAverageSavingsPerCategory() {
        try {
            $sql = "
                SELECT AVG(g.SavedAmount) as avgSavings
                FROM Goal g
                INNER JOIN Category c ON g.CategoryID = c.CategoryID
                WHERE g.IsDeleted = FALSE 
                AND c.IsDeleted = FALSE 
                AND g.SavedAmount > 0
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $avgSavings = $result['avgSavings'] ? floatval($result['avgSavings']) : 0;
            error_log("ADMIN REPO: Average savings result - " . $avgSavings);
            
            return $avgSavings;
        } catch (PDOException $e) {
            error_log("AdminDashboardRepository::getAverageSavingsPerCategory PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get average savings: " . $e->getMessage());
        }
    }

    public function getActiveUsersThisMonth() {
        try {
            $sql = "
                SELECT COUNT(DISTINCT UserID) as activeUsers
                FROM User 
                WHERE IsDeleted = FALSE 
                AND Role = 'user'
                AND (
                    MONTH(LastLogin) = MONTH(CURDATE()) AND YEAR(LastLogin) = YEAR(CURDATE())
                    OR 
                    MONTH(DateCreated) = MONTH(CURDATE()) AND YEAR(DateCreated) = YEAR(CURDATE())
                )
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $activeUsers = intval($result['activeUsers']);
            error_log("ADMIN REPO: Active users this month - " . $activeUsers);
            
            return $activeUsers;
        } catch (PDOException $e) {
            error_log("AdminDashboardRepository::getActiveUsersThisMonth PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get active users count: " . $e->getMessage());
        }
    }

    public function getMostUsedCategories() {
        try {
            $sql = "
                SELECT c.CategoryName, COUNT(g.GoalID) as usageCount
                FROM Category c
                INNER JOIN Goal g ON c.CategoryID = g.CategoryID
                WHERE c.IsDeleted = FALSE AND g.IsDeleted = FALSE
                GROUP BY c.CategoryID, c.CategoryName
                ORDER BY usageCount DESC
                LIMIT 5
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            $categories = [];
            foreach ($results as $row) {
                $categories[] = [
                    'name' => $row['CategoryName'],
                    'count' => intval($row['usageCount'])
                ];
            }
            
            error_log("ADMIN REPO: Top categories - " . json_encode($categories));
            
            return $categories;
        } catch (PDOException $e) {
            error_log("AdminDashboardRepository::getMostUsedCategories PDO Error: " . $e->getMessage());
            throw new Exception("Failed to get top categories: " . $e->getMessage());
        }
    }
}
?>