<?php
// FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
// Show errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);
// FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE

require_once __DIR__ . '/../Database.php';

class CategoryRepository {
    private $db;
    // Default categories available to all users
    private $systemCategories = ['Emergency', 'Travel', 'Bills'];

    // Set up database connection and ensure default categories exist
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureSystemCategoriesExist();
    }

    // Creates default system categories if they don't exist in database
    private function ensureSystemCategoriesExist() {
        try {
            foreach ($this->systemCategories as $categoryName) {
                // Check if category already exists in system
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM Category 
                    WHERE CategoryName = ? AND UserID IS NULL
                ");
                $stmt->execute([$categoryName]);
                
                // Add category if not found
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $this->db->prepare("
                        INSERT INTO Category 
                        (CategoryName, DateCreated, IsDeleted, UpdatedAt, UserID) 
                        VALUES (?, NOW(), 0, NOW(), NULL)
                    ");
                    $stmt->execute([$categoryName]);
                }
            }
        } catch (PDOException $e) {
            error_log("Error ensuring system categories: " . $e->getMessage());
        }
    }

    // Get all categories available to a user (both system and personal)
    public function getCategories($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM Category 
                WHERE (UserID IS NULL OR UserID = ?) AND IsDeleted = FALSE
                ORDER BY UserID IS NULL DESC, CategoryName ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting categories: " . $e->getMessage());
            return [];
        }
    }

    // Add a new user-specific category
    public function createCategory($userId, $categoryName) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO Category (CategoryName, UserID, DateCreated, IsDeleted, UpdatedAt)
                VALUES (?, ?, NOW(), FALSE, NOW())
            ");
            return $stmt->execute([$categoryName, $userId]);
        } catch (PDOException $e) {
            error_log("Error creating category: " . $e->getMessage());
            return false;
        }
    }

    // Check if a category name already exists for a user
    public function categoryExists($userId, $categoryName) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM Category 
                WHERE CategoryName = ? AND (UserID IS NULL OR UserID = ?) AND IsDeleted = FALSE
            ");
            $stmt->execute([$categoryName, $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking if category exists: " . $e->getMessage());
            return false;
        }
    }
    
    // Get category details by its ID
    public function getCategoryById($categoryId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM Category 
                WHERE CategoryID = ? AND IsDeleted = FALSE
            ");
            $stmt->execute([$categoryId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting category by ID: " . $e->getMessage());
            return null;
        }
    }
    
    // Mark a user's category as deleted if not in use
    public function deleteCategory($categoryId, $userId) {
        try {
            // Verify category exists and belongs to user
            $category = $this->getCategoryById($categoryId);
            if (!$category || $category['UserID'] === null) {
                return false; // Can't delete system categories
            }
            
            if ($category['UserID'] != $userId) {
                return false; // Can't delete another user's categories
            }
            
            // Check if category is being used by any active goals
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM Goal 
                WHERE CategoryID = ? AND IsDeleted = FALSE
            ");
            $stmt->execute([$categoryId]);
            
            if ($stmt->fetchColumn() > 0) {
                return false; // Category in use, can't delete
            }
            
            // Mark category as deleted
            $stmt = $this->db->prepare("
                UPDATE Category 
                SET IsDeleted = TRUE, UpdatedAt = NOW() 
                WHERE CategoryID = ?
            ");
            return $stmt->execute([$categoryId]);
        } catch (PDOException $e) {
            error_log("Error deleting category: " . $e->getMessage());
            return false;
        }
    }
}