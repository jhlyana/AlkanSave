<?php
// Enable error reporting for development
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
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->goalRepository = new GoalRepository();
        $this->categoryRepository = new CategoryRepository();
        $this->userService = new UserService();
    }

    public function handleRequest() {
        // Log all important information for debugging
        error_log("=== SavingsController::handleRequest() ===");
        error_log("Session data: " . print_r($_SESSION, true));
        error_log("GET data: " . print_r($_GET, true));
        error_log("POST data: " . print_r($_POST, true));
        
        // Try to read raw input
        $raw_input = file_get_contents('php://input');
        if (!empty($raw_input)) {
            error_log("Raw input: " . $raw_input);
        }
        
        // Check session and auth
        if (!isset($_SESSION['user_id'])) {
            error_log("No user_id in session. Session contents: " . print_r($_SESSION, true));
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized - No user ID in session']);
            exit();
        }

        $userId = $_SESSION['user_id'];
        $action = $_GET['action'] ?? '';

        error_log("Processing action: '$action' for user ID: $userId");

        try {
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
            error_log("Exception in SavingsController: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function getGoals($userId) {
        error_log("Getting goals for user ID: $userId");
        $goals = $this->goalRepository->getGoalsByUser($userId);
        $categories = $this->categoryRepository->getCategories($userId);
        
        error_log("Found " . count($goals) . " goals and " . count($categories) . " categories");
       
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'goals' => $goals,
            'categories' => $categories
        ]);
    }

    private function getGoal($userId, $goalId) {
        if (!$goalId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal ID is required']);
            return;
        }

        $goal = $this->goalRepository->getGoalById($goalId);
        
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

    private function addGoal($userId) {
        // Try to get data from POST or JSON
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
        
        // Use JSON data if valid, otherwise use POST
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
            error_log("Using JSON data: " . print_r($data, true));
        } else {
            $data = $_POST;
            error_log("Using POST data: " . print_r($data, true));
        }
        
        $categoryId = $data['categoryId'] ?? null;
        $goalName = $data['goalName'] ?? null;
        $targetAmount = $data['targetAmount'] ?? null;
        $startDate = $data['startDate'] ?? null;
        $targetDate = $data['targetDate'] ?? null;
        
        if (!$categoryId || !$goalName || !$targetAmount || !$startDate || !$targetDate) {
            error_log("Missing required fields: " . 
                "categoryId=" . ($categoryId ?? 'NULL') . ", " .
                "goalName=" . ($goalName ?? 'NULL') . ", " .
                "targetAmount=" . ($targetAmount ?? 'NULL') . ", " .
                "startDate=" . ($startDate ?? 'NULL') . ", " .
                "targetDate=" . ($targetDate ?? 'NULL'));
                
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

    private function addCategory($userId) {
        // Try to get data from POST or JSON
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
        
        // Use JSON data if valid, otherwise use POST
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
            error_log("Using JSON data for addCategory: " . print_r($data, true));
        } else {
            $data = $_POST;
            error_log("Using POST data for addCategory: " . print_r($data, true));
        }
        
        $categoryName = trim($data['categoryName'] ?? '');
       
        if (empty($categoryName)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Category name cannot be empty']);
            return;
        }
       
        if ($this->categoryRepository->categoryExists($userId, $categoryName)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Category already exists']);
            return;
        }
       
        $success = $this->categoryRepository->createCategory($userId, $categoryName);
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    private function addSavings($userId) {
        error_log("==== STARTING ADD SAVINGS ====");
        error_log("User ID: $userId");
        
        // Try to get data from POST or JSON
        $rawData = file_get_contents('php://input');
        error_log("Raw input for addSavings: " . $rawData);
        
        // Try to decode as JSON first
        $data = json_decode($rawData, true);
        
        // If JSON failed, try POST
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg() . ". Trying POST data.");
            $data = $_POST;
            error_log("POST data: " . print_r($_POST, true));
        }
        
        // Extract required fields with fallbacks and debug logging
        $goalId = isset($data['goalId']) ? $data['goalId'] : (isset($_POST['goalId']) ? $_POST['goalId'] : null);
        $amount = isset($data['amount']) ? $data['amount'] : (isset($_POST['amount']) ? $_POST['amount'] : null);
        $dateSaved = isset($data['dateSaved']) ? $data['dateSaved'] : (
                     isset($data['date']) ? $data['date'] : (
                     isset($_POST['dateSaved']) ? $_POST['dateSaved'] : (
                     isset($_POST['date']) ? $_POST['date'] : date('Y-m-d'))));
        
        error_log("Extracted data - goalId: " . var_export($goalId, true) . 
                  ", amount: " . var_export($amount, true) . 
                  ", date: " . var_export($dateSaved, true));
        
        // Validate goalId and amount
        if (empty($goalId)) {
            error_log("Missing goalId parameter");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing required field: goalId']);
            return;
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            error_log("Invalid amount: " . var_export($amount, true));
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Amount must be a positive number']);
            return;
        }
        
        // Verify goal ownership
        $goal = $this->goalRepository->getGoalById($goalId);
        
        if (!$goal) {
            error_log("Goal not found: $goalId");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal not found']);
            return;
        }
        
        if ($goal['UserID'] != $userId) {
            error_log("Goal doesn't belong to user. Goal UserID: {$goal['UserID']}, Session UserID: $userId");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You do not own this goal']);
            return;
        }
        
        // Now add the savings
        error_log("Calling goalRepository->addSavings(goalId=$goalId, amount=$amount, date=$dateSaved)");
        
        $success = $this->goalRepository->addSavings($goalId, $amount, $dateSaved);
        
        error_log("addSavings result: " . ($success ? 'true' : 'false'));
        
        if ($success) {
            // Get updated goal data
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

    private function editGoal($userId) {
        // Try to get data from POST or JSON
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
        
        // Use JSON data if valid, otherwise use POST
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
            error_log("Using JSON data for editGoal: " . print_r($data, true));
        } else {
            $data = $_POST;
            error_log("Using POST data for editGoal: " . print_r($data, true));
        }
        
        $goalId = $data['goalId'] ?? null;
        $categoryId = $data['categoryId'] ?? null;
        $goalName = $data['goalName'] ?? null;
        $targetAmount = $data['targetAmount'] ?? null;
        $targetDate = $data['targetDate'] ?? null;
        
        if (!$goalId || !$categoryId || !$goalName || !$targetAmount || !$targetDate) {
            error_log("Missing required fields for editGoal");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Verify goal ownership
        $goal = $this->goalRepository->getGoalById($goalId);
        if (!$goal || $goal['UserID'] != $userId) {
            error_log("Goal not found or unauthorized: goalId=$goalId, userId=$userId");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal not found or unauthorized']);
            return;
        }
        
        $success = $this->goalRepository->updateGoal($goalId, $categoryId, $goalName, $targetAmount, $targetDate);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    private function deleteGoal($userId) {
        // Try to get data from POST or JSON
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
        
        // Use JSON data if valid, otherwise use POST or GET
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
            error_log("Using JSON data for deleteGoal: " . print_r($data, true));
        } else {
            $data = $_POST ?: $_GET;
            error_log("Using POST/GET data for deleteGoal: " . print_r($data, true));
        }
        
        $goalId = $data['goalId'] ?? null;
        
        if (!$goalId) {
            error_log("Missing goalId for deleteGoal");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal ID is required']);
            return;
        }
        
        // Verify goal ownership
        $goal = $this->goalRepository->getGoalById($goalId);
        if (!$goal || $goal['UserID'] != $userId) {
            error_log("Goal not found or unauthorized: goalId=$goalId, userId=$userId");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Goal not found or unauthorized']);
            return;
        }
        
        $success = $this->goalRepository->deleteGoal($goalId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    private function searchGoals($userId) {
        $term = $_GET['term'] ?? '';
        error_log("Searching goals with term: '$term'");
        
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

// Initialize and handle the request
$controller = new SavingsController();
$controller->handleRequest();