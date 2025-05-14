<?php
// Show all errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../Database.php';

class GoalRepository {
    private $db;

    public function __construct() {
        // Get database connection from singleton instance
        $database = Database::getInstance();
        $this->db = $database->getConnection();
        
        if (!$this->db) {
            throw new Exception("Database connection failed");
        }
    }

    // Retrieve all active goals for a user including their saved amounts
    public function getGoalsByUser($userId) {
        try {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Getting goals for user ID: $userId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            // Query combines goal data with calculated savings and category name
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
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Found " . count($goals) . " goals for user $userId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return $goals;
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Error in getGoalsByUser: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return [];
        }
    }

    // Create a new savings goal in the database
    public function createGoal($userId, $categoryId, $goalName, $targetAmount, $startDate, $targetDate) {
        try {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Creating goal: userId=$userId, categoryId=$categoryId, name=$goalName, target=$targetAmount");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            $stmt = $this->db->prepare("
                INSERT INTO Goal (UserID, CategoryID, GoalName, TargetAmount, SavedAmount, StartDate, TargetDate, Status)
                VALUES (?, ?, ?, ?, 0, ?, ?, 'Active')
            ");
            $result = $stmt->execute([$userId, $categoryId, $goalName, $targetAmount, $startDate, $targetDate]);
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            if ($result) {
                $goalId = $this->db->lastInsertId();
                error_log("Goal created successfully with ID: $goalId");
            } else {
                error_log("Failed to create goal: " . print_r($stmt->errorInfo(), true));
            }
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            return $result;
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Error in createGoal: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return false;
        }
    }

    // Update an existing goal's details
    public function updateGoal($goalId, $categoryId, $goalName, $targetAmount, $targetDate) {
        try {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Updating goal: goalId=$goalId, categoryId=$categoryId, name=$goalName, target=$targetAmount");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            // Start transaction to ensure data consistency
            $this->db->beginTransaction();
            
            // Calculate current savings to determine new status
            $savedAmountStmt = $this->db->prepare("
                SELECT COALESCE(SUM(Amount), 0) AS SavedAmount 
                FROM SavingsTransaction 
                WHERE GoalID = ? AND IsDeleted = FALSE
            ");
            $savedAmountStmt->execute([$goalId]);
            $savedAmount = $savedAmountStmt->fetchColumn();
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Current saved amount: $savedAmount, New target amount: $targetAmount");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            // Update goal status based on whether target is met
            $status = ($savedAmount >= $targetAmount) ? 'Completed' : 'Active';
            $completionDate = ($status === 'Completed') ? date('Y-m-d') : null;
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("New status: $status, Completion date: " . ($completionDate ?: 'NULL'));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
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
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                error_log("Goal updated successfully");
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
                return true;
            } else {
                $this->db->rollBack();
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                error_log("Failed to update goal: " . print_r($stmt->errorInfo(), true));
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
                return false;
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Error in updateGoal: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return false;
        }
    }

    // Soft delete a goal and its related transactions
    public function deleteGoal($goalId) {
        try {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Soft deleting goal: $goalId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            $this->db->beginTransaction();
            
            // Mark all related transactions as deleted
            $stmtTransactions = $this->db->prepare("
                UPDATE SavingsTransaction 
                SET IsDeleted = TRUE, UpdatedAt = NOW() 
                WHERE GoalID = ?
            ");
            $stmtTransactions->execute([$goalId]);
            
            // Mark the goal itself as deleted
            $stmtGoal = $this->db->prepare("
                UPDATE Goal 
                SET IsDeleted = TRUE, UpdatedAt = NOW() 
                WHERE GoalID = ?
            ");
            $success = $stmtGoal->execute([$goalId]);
            
            if ($success) {
                $this->db->commit();
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                error_log("Goal deleted successfully");
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            } else {
                $this->db->rollBack();
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                error_log("Failed to delete goal: " . print_r($stmtGoal->errorInfo(), true));
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            }
            
            return $success;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Error in deleteGoal: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return false;
        }
    }

    // Add money to a goal and update its status if target is reached
    public function addSavings($goalId, $amount, $dateSaved) {
        try {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Starting addSavings transaction for goalId=$goalId, amount=$amount, date=$dateSaved");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            // Begin transaction to ensure all operations succeed or fail together
            $this->db->beginTransaction();
            
            // 1. Record the savings transaction
            $transactionStmt = $this->db->prepare("
                INSERT INTO SavingsTransaction (GoalID, Amount, DateSaved, IsDeleted)
                VALUES (?, ?, ?, FALSE)
            ");
            
            $transactionSuccess = $transactionStmt->execute([$goalId, $amount, $dateSaved]);
            if (!$transactionSuccess) {
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                error_log("Failed to insert savings transaction: " . print_r($transactionStmt->errorInfo(), true));
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
                throw new PDOException("Failed to insert savings transaction");
            }
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            $transactionId = $this->db->lastInsertId();
            error_log("Savings transaction inserted successfully with ID: $transactionId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            // 2. Calculate new total savings for the goal
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
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                error_log("Failed to calculate total savings: " . print_r($totalStmt->errorInfo(), true));
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
                throw new PDOException("Failed to calculate total savings");
            }
            
            $totalSaved = $result['TotalSaved'];
            $targetAmount = $result['TargetAmount'];
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Total saved: $totalSaved, Target: $targetAmount");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            // 3. Check if goal is now completed
            $isCompleted = ($totalSaved >= $targetAmount);
            $status = $isCompleted ? 'Completed' : 'Active';
            $completionDate = $isCompleted ? date('Y-m-d') : null;
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Goal status: $status, Completion date: " . ($completionDate ?? 'NULL'));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            // 4. Update the goal's status and saved amount
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
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                error_log("Failed to update goal status: " . print_r($updateStmt->errorInfo(), true));
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
                throw new PDOException("Failed to update goal status");
            }
            
            // All operations succeeded - commit the transaction
            $this->db->commit();
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Savings transaction completed successfully");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return true;
            
        } catch (PDOException $e) {
            // Something failed - rollback all changes
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("ERROR in addSavings: " . $e->getMessage());
            error_log("PDO Error Info: " . print_r($this->db->errorInfo(), true));
            error_log("Stack trace: " . $e->getTraceAsString());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return false;
        }
    }

    // Search goals by name or category name
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
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Error in searchGoals: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return [];
        }
    }

    // Get a single goal by its ID
    public function getGoalById($goalId) {
        try {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Getting goal by ID: $goalId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            $stmt = $this->db->prepare("
                SELECT g.*, c.CategoryName,
                    COALESCE((SELECT SUM(Amount) FROM SavingsTransaction WHERE GoalID = g.GoalID AND IsDeleted = FALSE), 0) AS SavedAmount
                FROM Goal g
                JOIN Category c ON g.CategoryID = c.CategoryID
                WHERE g.GoalID = ? AND g.IsDeleted = FALSE
            ");
            $stmt->execute([$goalId]);
            $goal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            if ($goal) {
                error_log("Found goal: " . print_r($goal, true));
            } else {
                error_log("Goal not found");
            }
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            
            return $goal;
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Error in getGoalById: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return null;
        }
    }
}