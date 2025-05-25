<?php
require_once __DIR__ . '/../Database.php';

class TransactionRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all transactions for a user with optional month filter
     */
    public function getUserTransactions($userId, $month = null, $year = null) {
        try {
            if ($year === null) {
                $year = date('Y');
            }
            
            $sql = "
                SELECT 
                    st.TransactionID,
                    st.Amount,
                    st.DateSaved,
                    st.CreatedAt,
                    g.GoalName,
                    c.CategoryName
                FROM SavingsTransaction st
                INNER JOIN Goal g ON st.GoalID = g.GoalID
                INNER JOIN Category c ON g.CategoryID = c.CategoryID
                WHERE g.UserID = :userId 
                AND st.IsDeleted = FALSE 
                AND g.IsDeleted = FALSE
                AND c.IsDeleted = FALSE
                AND YEAR(st.DateSaved) = :year
            ";
            
            $params = [
                'userId' => $userId,
                'year' => $year
            ];
            
            if ($month !== null) {
                $sql .= " AND MONTH(st.DateSaved) = :month";
                $params['month'] = $month;
            }
            
            $sql .= " ORDER BY st.DateSaved DESC, st.CreatedAt DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting user transactions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get transaction count by month for a user
     */
    public function getMonthlyTransactionCounts($userId, $year = null) {
        try {
            if ($year === null) {
                $year = date('Y');
            }
            
            $sql = "
                SELECT 
                    MONTH(st.DateSaved) as month_number,
                    COUNT(*) as transaction_count,
                    SUM(st.Amount) as total_amount
                FROM SavingsTransaction st
                INNER JOIN Goal g ON st.GoalID = g.GoalID
                WHERE g.UserID = :userId 
                AND st.IsDeleted = FALSE 
                AND g.IsDeleted = FALSE
                AND YEAR(st.DateSaved) = :year
                GROUP BY MONTH(st.DateSaved)
                ORDER BY MONTH(st.DateSaved)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['userId' => $userId, 'year' => $year]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monthlyCounts = [];
            foreach ($results as $row) {
                $monthlyCounts[$row['month_number']] = [
                    'count' => (int)$row['transaction_count'],
                    'total' => (float)$row['total_amount']
                ];
            }
            
            return $monthlyCounts;
            
        } catch (PDOException $e) {
            error_log("Error getting monthly transaction counts: " . $e->getMessage());
            return [];
        }
    }
}
?>