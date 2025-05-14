<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../Database.php';

class GoalRepository {
    private $db;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
        if (!$this->db) {
            throw new Exception("Database connection failed");
        }
    }

    // Get all goals for a specific user
    public function getGoalsByUser($userId) {
        try {
            error_log("Getting goals for user ID: $userId");
            
            // Get all goals with their saved amounts from savings transactions
            $stmt = $this->db->prepare("
                SELECT g.*, c.CategoryName,
                    COALESCE((SELECT SUM(Amount) FROM SavingsTransaction WHERE GoalID = g.GoalID AND IsDeleted = FALSE), 0) AS SavedAmount
                FROM Goal g
                JOIN Category c ON g.CategoryID = c.CategoryID
                WHERE g.UserID = :userId AND g.IsDeleted = FALSE
                ORDER BY g.TargetDate ASC
            ");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($goals) . " goals for user $userId");
            return $goals;
        } catch (PDOException $e) {
            error_log("Error in getGoalsByUser: " . $e->getMessage());
            return [];
        }
    }

    // Create a new goal
    public function createGoal($userId, $categoryId, $goalName, $targetAmount, $startDate, $targetDate) {
        try {
            error_log("Creating goal: userId=$userId, categoryId=$categoryId, name=$goalName, target=$targetAmount");
            
            $stmt = $this->db->prepare("
                INSERT INTO Goal (UserID, CategoryID, GoalName, TargetAmount, SavedAmount, StartDate, TargetDate, Status)
                VALUES (?, ?, ?, ?, 0, ?, ?, 'Active')
            ");
            $result = $stmt->execute([$userId, $categoryId, $goalName, $targetAmount, $startDate, $targetDate]);
            
            if ($result) {
                $goalId = $this->db->lastInsertId();
                error_log("Goal created successfully with ID: $goalId");
            } else {
                error_log("Failed to create goal: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error in createGoal: " . $e->getMessage());
            return false;
        }
    }

    // Update a goal
    public function updateGoal($goalId, $categoryId, $goalName, $targetAmount, $targetDate) {
        try {
            error_log("Updating goal: goalId=$goalId, categoryId=$categoryId, name=$goalName, target=$targetAmount");
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // First get current saved amount
            $savedAmountStmt = $this->db->prepare("
                SELECT COALESCE(SUM(Amount), 0) AS SavedAmount 
                FROM SavingsTransaction 
                WHERE GoalID = ? AND IsDeleted = FALSE
            ");
            $savedAmountStmt->execute([$goalId]);
            $savedAmount = $savedAmountStmt->fetchColumn();
            
            error_log("Current saved amount: $savedAmount, New target amount: $targetAmount");
            
            // Determine if goal is completed based on new target amount
            $status = ($savedAmount >= $targetAmount) ? 'Completed' : 'Active';
            $completionDate = ($status === 'Completed') ? date('Y-m-d') : null;
            
            error_log("New status: $status, Completion date: " . ($completionDate ?: 'NULL'));
            
            $stmt = $this->db->prepare("
                UPDATE Goal
                SET CategoryID = ?, 
                    GoalName = ?, 
                    TargetAmount = ?,
                    Status = ?,
                    CompletionDate = ?,
                    TargetDate = ?, 
                    UpdatedAt = NOW()
                WHERE GoalID = ?
            ");
            
            $result = $stmt->execute([
                $categoryId, 
                $goalName, 
                $targetAmount,
                $status,
                $completionDate,
                $targetDate, 
                $goalId
            ]);
            
            if ($result) {
                $this->db->commit();
                error_log("Goal updated successfully");
                return true;
            } else {
                $this->db->rollBack();
                error_log("Failed to update goal: " . print_r($stmt->errorInfo(), true));
                return false;
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in updateGoal: " . $e->getMessage());
            return false;
        }
    }

    // Delete a goal (soft delete)
    public function deleteGoal($goalId) {
        try {
            error_log("Soft deleting goal: $goalId");
            
            $this->db->beginTransaction();
            
            // Soft delete all savings transactions for this goal
            $stmtTransactions = $this->db->prepare("
                UPDATE SavingsTransaction 
                SET IsDeleted = TRUE, UpdatedAt = NOW() 
                WHERE GoalID = ?
            ");
            $stmtTransactions->execute([$goalId]);
            
            // Soft delete the goal itself
            $stmtGoal = $this->db->prepare("
                UPDATE Goal 
                SET IsDeleted = TRUE, UpdatedAt = NOW() 
                WHERE GoalID = ?
            ");
            $success = $stmtGoal->execute([$goalId]);
            
            if ($success) {
                $this->db->commit();
                error_log("Goal deleted successfully");
            } else {
                $this->db->rollBack();
                error_log("Failed to delete goal: " . print_r($stmtGoal->errorInfo(), true));
            }
            
            return $success;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in deleteGoal: " . $e->getMessage());
            return false;
        }
    }

    // Add savings to a goal
    public function addSavings($goalId, $amount, $dateSaved) {
        try {
            error_log("Starting addSavings transaction for goalId=$goalId, amount=$amount, date=$dateSaved");
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // 1. Insert the savings transaction
            $transactionStmt = $this->db->prepare("
                INSERT INTO SavingsTransaction (GoalID, Amount, DateSaved, IsDeleted)
                VALUES (?, ?, ?, FALSE)
            ");
            
            $transactionSuccess = $transactionStmt->execute([$goalId, $amount, $dateSaved]);
            if (!$transactionSuccess) {
                error_log("Failed to insert savings transaction: " . print_r($transactionStmt->errorInfo(), true));
                throw new PDOException("Failed to insert savings transaction");
            }
            
            $transactionId = $this->db->lastInsertId();
            error_log("Savings transaction inserted successfully with ID: $transactionId");
            
            // 2. Calculate the total saved amount for this goal
            $totalStmt = $this->db->prepare("
                SELECT COALESCE(SUM(Amount), 0) AS TotalSaved, g.TargetAmount
                FROM SavingsTransaction s
                JOIN Goal g ON s.GoalID = g.GoalID
                WHERE s.GoalID = ? AND s.IsDeleted = FALSE
                GROUP BY g.GoalID, g.TargetAmount
            ");
            
            $totalStmt->execute([$goalId]);
            $result = $totalStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                error_log("Failed to calculate total savings: " . print_r($totalStmt->errorInfo(), true));
                throw new PDOException("Failed to calculate total savings");
            }
            
            $totalSaved = $result['TotalSaved'];
            $targetAmount = $result['TargetAmount'];
            
            error_log("Total saved: $totalSaved, Target: $targetAmount");
            
            // 3. Determine if goal is now completed
            $isCompleted = ($totalSaved >= $targetAmount);
            $status = $isCompleted ? 'Completed' : 'Active';
            $completionDate = $isCompleted ? date('Y-m-d') : null;
            
            error_log("Goal status: $status, Completion date: " . ($completionDate ?? 'NULL'));
            
            // 4. Update the goal with new saved amount and status
            $updateStmt = $this->db->prepare("
                UPDATE Goal 
                SET SavedAmount = ?,
                    Status = ?,
                    CompletionDate = ?,
                    UpdatedAt = NOW()
                WHERE GoalID = ?
            ");
            
            $updateSuccess = $updateStmt->execute([$totalSaved, $status, $completionDate, $goalId]);
            
            if (!$updateSuccess) {
                error_log("Failed to update goal status: " . print_r($updateStmt->errorInfo(), true));
                throw new PDOException("Failed to update goal status");
            }
            
            // Commit the transaction
            $this->db->commit();
            error_log("Savings transaction completed successfully");
            return true;
            
        } catch (PDOException $e) {
            // Rollback the transaction on error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("ERROR in addSavings: " . $e->getMessage());
            error_log("PDO Error Info: " . print_r($this->db->errorInfo(), true));
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // Search goals by name or category
    public function searchGoals($userId, $searchTerm) {
        try {
            $searchTerm = "%$searchTerm%";
            $stmt = $this->db->prepare("
                SELECT g.*, c.CategoryName,
                    COALESCE((SELECT SUM(Amount) FROM SavingsTransaction WHERE GoalID = g.GoalID AND IsDeleted = FALSE), 0) AS SavedAmount
                FROM Goal g
                JOIN Category c ON g.CategoryID = c.CategoryID
                WHERE g.UserID = ? AND g.IsDeleted = FALSE
                AND (g.GoalName LIKE ? OR c.CategoryName LIKE ?)
                ORDER BY g.TargetDate ASC
            ");
            $stmt->execute([$userId, $searchTerm, $searchTerm]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in searchGoals: " . $e->getMessage());
            return [];
        }
    }

    // Get goal by ID
    public function getGoalById($goalId) {
        try {
            error_log("Getting goal by ID: $goalId");
            
            $stmt = $this->db->prepare("
                SELECT g.*, c.CategoryName,
                    COALESCE((SELECT SUM(Amount) FROM SavingsTransaction WHERE GoalID = g.GoalID AND IsDeleted = FALSE), 0) AS SavedAmount
                FROM Goal g
                JOIN Category c ON g.CategoryID = c.CategoryID
                WHERE g.GoalID = ? AND g.IsDeleted = FALSE
            ");
            $stmt->execute([$goalId]);
            $goal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($goal) {
                error_log("Found goal: " . print_r($goal, true));
            } else {
                error_log("Goal not found");
            }
            
            return $goal;
        } catch (PDOException $e) {
            error_log("Error in getGoalById: " . $e->getMessage());
            return null;
        }
    }
}