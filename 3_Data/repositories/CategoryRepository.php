<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../Database.php';

class CategoryRepository {
    private $db;
    private $systemCategories = ['Emergency', 'Travel', 'Bills']; // System-wide categories

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureSystemCategoriesExist();
    }

    // Create system categories if they don't exist
    private function ensureSystemCategoriesExist() {
        try {
            foreach ($this->systemCategories as $categoryName) {
                // Check if category already exists
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) FROM Category 
                    WHERE CategoryName = ? AND UserID IS NULL
                ");
                $stmt->execute([$categoryName]);
                
                if ($stmt->fetchColumn() == 0) {
                    // Create the system category
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

    // Get all categories (system-wide and user-specific)
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

    // Create a new category
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

    // Check if category exists for user
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
    
    // Get category by ID
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
    
    // Delete a category (soft delete)
    // Note: This should only be used for user-specific categories
    // System categories should never be deleted
    public function deleteCategory($categoryId, $userId) {
        try {
            // Check if it's a system category
            $category = $this->getCategoryById($categoryId);
            if (!$category || $category['UserID'] === null) {
                return false; // Don't delete system categories
            }
            
            // Check if the category belongs to the user
            if ($category['UserID'] != $userId) {
                return false; // Don't delete another user's categories
            }
            
            // Check if the category is in use by any active goals
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM Goal 
                WHERE CategoryID = ? AND IsDeleted = FALSE
            ");
            $stmt->execute([$categoryId]);
            
            if ($stmt->fetchColumn() > 0) {
                // Category is in use, don't delete
                return false;
            }
            
            // Soft delete the category
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