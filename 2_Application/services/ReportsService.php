<?php
require_once __DIR__ . '/../../3_Data/Database.php';

class ReportsService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get savings growth data by category for a specific user
     */
    public function getSavingsGrowthData($userID) {
        try {
            $sql = "SELECT c.CategoryName, COALESCE(SUM(g.SavedAmount), 0) as TotalSaved
                    FROM Category c
                    LEFT JOIN Goal g ON c.CategoryID = g.CategoryID 
                        AND g.UserID = ? 
                        AND g.IsDeleted = FALSE
                    WHERE c.IsDeleted = FALSE 
                    GROUP BY c.CategoryID, c.CategoryName
                    HAVING TotalSaved > 0
                    ORDER BY TotalSaved DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userID]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $categories = [];
            $amounts = [];
            
            foreach ($results as $row) {
                $categories[] = $row['CategoryName'];
                $amounts[] = (float)$row['TotalSaved'];
            }
            
            return [
                'categories' => $categories,
                'amounts' => $amounts,
                'success' => true
            ];
            
        } catch (PDOException $e) {
            error_log("Error fetching savings growth data: " . $e->getMessage());
            return [
                'categories' => [],
                'amounts' => [],
                'success' => false,
                'error' => 'Failed to fetch savings data'
            ];
        }
    }
    
    /**
     * Get goal completion ratio for a specific user
     */
    public function getGoalCompletionData($userID) {
        try {
            $sql = "SELECT 
                        SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as CompletedGoals,
                        SUM(CASE WHEN Status = 'Active' THEN 1 ELSE 0 END) as ActiveGoals,
                        COUNT(*) as TotalGoals
                    FROM Goal 
                    WHERE UserID = ? AND IsDeleted = FALSE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userID]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $completed = (int)($result['CompletedGoals'] ?? 0);
            $active = (int)($result['ActiveGoals'] ?? 0);
            $total = (int)($result['TotalGoals'] ?? 0);
            
            return [
                'completed' => $completed,
                'remaining' => $active,
                'total' => $total,
                'success' => true
            ];
            
        } catch (PDOException $e) {
            error_log("Error fetching goal completion data: " . $e->getMessage());
            return [
                'completed' => 0,
                'remaining' => 0,
                'total' => 0,
                'success' => false,
                'error' => 'Failed to fetch goal completion data'
            ];
        }
    }
    
    /**
     * Get completed goals filtered by month and year for a specific user
     */
    public function getCompletedGoalsByMonth($userID, $month = null, $year = null) {
        try {
            $sql = "SELECT 
                        g.GoalName,
                        g.TargetAmount,
                        g.SavedAmount,
                        g.CompletionDate,
                        c.CategoryName
                    FROM Goal g
                    LEFT JOIN Category c ON g.CategoryID = c.CategoryID
                    WHERE g.UserID = ? 
                        AND g.Status = 'Completed' 
                        AND g.IsDeleted = FALSE
                        AND g.CompletionDate IS NOT NULL";
            
            $params = [$userID];
            
            if ($month && $year) {
                $sql .= " AND MONTH(g.CompletionDate) = ? AND YEAR(g.CompletionDate) = ?";
                $params[] = $month;
                $params[] = $year;
            } elseif ($year) {
                $sql .= " AND YEAR(g.CompletionDate) = ?";
                $params[] = $year;
            }
            
            $sql .= " ORDER BY g.CompletionDate DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'goals' => $results,
                'success' => true
            ];
            
        } catch (PDOException $e) {
            error_log("Error fetching completed goals: " . $e->getMessage());
            return [
                'goals' => [],
                'success' => false,
                'error' => 'Failed to fetch completed goals'
            ];
        }
    }
    
    /**
     * Get months that have completed goals for a specific user
     */
    public function getMonthsWithCompletedGoals($userID, $year = null) {
        try {
            $currentYear = $year ?? date('Y');
            
            $sql = "SELECT DISTINCT 
                        MONTH(CompletionDate) as month,
                        MONTHNAME(CompletionDate) as monthName,
                        COUNT(*) as goalCount
                    FROM Goal 
                    WHERE UserID = ? 
                        AND Status = 'Completed' 
                        AND IsDeleted = FALSE
                        AND CompletionDate IS NOT NULL
                        AND YEAR(CompletionDate) = ?
                    GROUP BY MONTH(CompletionDate), MONTHNAME(CompletionDate)
                    ORDER BY month";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userID, $currentYear]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'months' => $results,
                'success' => true
            ];
            
        } catch (PDOException $e) {
            error_log("Error fetching months with completed goals: " . $e->getMessage());
            return [
                'months' => [],
                'success' => false,
                'error' => 'Failed to fetch months data'
            ];
        }
    }
}
?>