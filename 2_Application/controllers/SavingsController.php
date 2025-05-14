<?php
// Show all PHP errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../../3_Data/repositories/GoalRepository.php';
require_once __DIR__ . '/../../3_Data/repositories/CategoryRepository.php';

class SavingsController {
    private $goalRepository;
    private $categoryRepository;
    private $userService;

    public function __construct() {
        // Start session if not already active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize database access objects
        $this->goalRepository = new GoalRepository();
        $this->categoryRepository = new CategoryRepository();
        $this->userService = new UserService();
    }

    public function handleRequest() {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("=== SavingsController::handleRequest() ===");
        error_log("Session data: " . print_r($_SESSION, true));
        error_log("GET data: " . print_r($_GET, true));
        error_log("POST data: " . print_r($_POST, true));
        
        $raw_input = file_get_contents('php://input');
        if (!empty($raw_input)) {
            error_log("Raw input: " . $raw_input);
        }
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("No user_id in session. Session contents: " . print_r($_SESSION, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized - No user ID in session']);
            exit();
        }

        $userId = $_SESSION['user_id'];
        $action = $_GET['action'] ?? '';

        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("Processing action: '$action' for user ID: $userId");
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE

        try {
            // Route to different functions based on action parameter
            switch ($action) {
                case 'getGoals':
                    $this->getGoals($userId);
                    break;
                case 'getGoal':
                    $this->getGoal($userId, $_GET['goalId'] ?? 0);
                    break;
                case 'addGoal':
                    $this->addGoal($userId);
                    break;
                case 'addCategory':
                    $this->addCategory($userId);
                    break;
                case 'addSavings':
                    $this->addSavings($userId);
                    break;
                case 'editGoal':
                    $this->editGoal($userId);
                    break;
                case 'deleteGoal':
                    $this->deleteGoal($userId);
                    break;
                case 'searchGoals':
                    $this->searchGoals($userId);
                    break;
                default:
                    $this->getGoals($userId);
            }
        } catch (Exception $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Exception in SavingsController: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    // Get all goals and categories for the user
    private function getGoals($userId) {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("Getting goals for user ID: $userId");
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        $goals = $this->goalRepository->getGoalsByUser($userId);
        $categories = $this->categoryRepository->getCategories($userId);
        
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("Found " . count($goals) . " goals and " . count($categories) . " categories");
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
       
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'goals' => $goals,
            'categories' => $categories
        ]);
    }

    // Get details of a specific goal
    private function getGoal($userId, $goalId) {
        if (!$goalId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal ID is required']);
            return;
        }

        $goal = $this->goalRepository->getGoalById($goalId);
        
        // Check if goal exists and belongs to user
        if (!$goal) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal not found']);
            return;
        }
        
        if ($goal['UserID'] != $userId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'goal' => $goal
        ]);
    }

    // Create a new savings goal
    private function addGoal($userId) {
        // Accept data from either JSON or form submission
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Using JSON data: " . print_r($data, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        } else {
            $data = $_POST;
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Using POST data: " . print_r($data, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        }
        
        // Validate required fields
        $categoryId = $data['categoryId'] ?? null;
        $goalName = $data['goalName'] ?? null;
        $targetAmount = $data['targetAmount'] ?? null;
        $startDate = $data['startDate'] ?? null;
        $targetDate = $data['targetDate'] ?? null;
        
        if (!$categoryId || !$goalName || !$targetAmount || !$startDate || !$targetDate) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Missing required fields: " . 
                "categoryId=" . ($categoryId ?? 'NULL') . ", " .
                "goalName=" . ($goalName ?? 'NULL') . ", " .
                "targetAmount=" . ($targetAmount ?? 'NULL') . ", " .
                "startDate=" . ($startDate ?? 'NULL') . ", " .
                "targetDate=" . ($targetDate ?? 'NULL'));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
                
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
       
        $success = $this->goalRepository->createGoal(
            $userId,
            $categoryId,
            $goalName,
            $targetAmount,
            $startDate,
            $targetDate
        );
       
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    // Add a new category for organizing goals
    private function addCategory($userId) {
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Using JSON data for addCategory: " . print_r($data, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        } else {
            $data = $_POST;
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Using POST data for addCategory: " . print_r($data, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        }
        
        $categoryName = trim($data['categoryName'] ?? '');
       
        if (empty($categoryName)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Category name cannot be empty']);
            return;
        }
       
        // Check for duplicate category names
        if ($this->categoryRepository->categoryExists($userId, $categoryName)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Category already exists']);
            return;
        }
       
        $success = $this->categoryRepository->createCategory($userId, $categoryName);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    // Add money to a savings goal
    private function addSavings($userId) {
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("==== STARTING ADD SAVINGS ====");
        error_log("User ID: $userId");
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        $rawData = file_get_contents('php://input');
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("Raw input for addSavings: " . $rawData);
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        $data = json_decode($rawData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("JSON decode error: " . json_last_error_msg() . ". Trying POST data.");
            error_log("POST data: " . print_r($_POST, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            $data = $_POST;
        }
        
        // Get required fields with fallback values
        $goalId = isset($data['goalId']) ? $data['goalId'] : (isset($_POST['goalId']) ? $_POST['goalId'] : null);
        $amount = isset($data['amount']) ? $data['amount'] : (isset($_POST['amount']) ? $_POST['amount'] : null);
        $dateSaved = isset($data['dateSaved']) ? $data['dateSaved'] : (
                     isset($data['date']) ? $data['date'] : (
                     isset($_POST['dateSaved']) ? $_POST['dateSaved'] : (
                     isset($_POST['date']) ? $_POST['date'] : date('Y-m-d'))));
        
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("Extracted data - goalId: " . var_export($goalId, true) . 
                  ", amount: " . var_export($amount, true) . 
                  ", date: " . var_export($dateSaved, true));
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        // Validate input
        if (empty($goalId)) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Missing goalId parameter");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing required field: goalId']);
            return;
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Invalid amount: " . var_export($amount, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Amount must be a positive number']);
            return;
        }
        
        // Verify the goal belongs to the user
        $goal = $this->goalRepository->getGoalById($goalId);
        
        if (!$goal) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Goal not found: $goalId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal not found']);
            return;
        }
        
        if ($goal['UserID'] != $userId) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Goal doesn't belong to user. Goal UserID: {$goal['UserID']}, Session UserID: $userId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You do not own this goal']);
            return;
        }
        
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("Calling goalRepository->addSavings(goalId=$goalId, amount=$amount, date=$dateSaved)");
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        $success = $this->goalRepository->addSavings($goalId, $amount, $dateSaved);
        
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("addSavings result: " . ($success ? 'true' : 'false'));
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        if ($success) {
            $updatedGoal = $this->goalRepository->getGoalById($goalId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Savings added successfully!',
                'goal' => $updatedGoal
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to add savings. Please check the error logs.'
            ]);
        }
    }

    // Update an existing goal
    private function editGoal($userId) {
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Using JSON data for editGoal: " . print_r($data, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        } else {
            $data = $_POST;
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Using POST data for editGoal: " . print_r($data, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        }
        
        $goalId = $data['goalId'] ?? null;
        $categoryId = $data['categoryId'] ?? null;
        $goalName = $data['goalName'] ?? null;
        $targetAmount = $data['targetAmount'] ?? null;
        $targetDate = $data['targetDate'] ?? null;
        
        if (!$goalId || !$categoryId || !$goalName || !$targetAmount || !$targetDate) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Missing required fields for editGoal");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Check goal ownership before editing
        $goal = $this->goalRepository->getGoalById($goalId);
        if (!$goal || $goal['UserID'] != $userId) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Goal not found or unauthorized: goalId=$goalId, userId=$userId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal not found or unauthorized']);
            return;
        }
        
        $success = $this->goalRepository->updateGoal($goalId, $categoryId, $goalName, $targetAmount, $targetDate);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    // Delete a savings goal
    private function deleteGoal($userId) {
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Using JSON data for deleteGoal: " . print_r($data, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        } else {
            $data = $_POST ?: $_GET;
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Using POST/GET data for deleteGoal: " . print_r($data, true));
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        }
        
        $goalId = $data['goalId'] ?? null;
        
        if (!$goalId) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Missing goalId for deleteGoal");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal ID is required']);
            return;
        }
        
        // Verify ownership before deletion
        $goal = $this->goalRepository->getGoalById($goalId);
        if (!$goal || $goal['UserID'] != $userId) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Goal not found or unauthorized: goalId=$goalId, userId=$userId");
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal not found or unauthorized']);
            return;
        }
        
        $success = $this->goalRepository->deleteGoal($goalId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    // Search goals by name
    private function searchGoals($userId) {
        $term = $_GET['term'] ?? '';
        // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
        error_log("Searching goals with term: '$term'");
        // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
        
        if (empty($term)) {
            $this->getGoals($userId);
            return;
        }
        
        $goals = $this->goalRepository->searchGoals($userId, $term);
        $categories = $this->categoryRepository->getCategories($userId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'goals' => $goals,
            'categories' => $categories
        ]);
    }
}

// Create controller and process the request
$controller = new SavingsController();
$controller->handleRequest();