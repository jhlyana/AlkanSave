<?php
session_start();
header('Content-Type: application/json');

// Debug logging
error_log("Reports endpoint hit. Session: " . print_r($_SESSION, true));
error_log("GET parameters: " . print_r($_GET, true));

// Proper session validation
if (!isset($_SESSION['user_id'])) {
    error_log("Unauthorized access attempt");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please login']);
    exit();
}

require_once __DIR__ . '/../../3_Data/Database.php';
require_once __DIR__ . '/../../2_Application/services/ReportsService.php';

$userID = $_SESSION['user_id'];
error_log("Processing request for user ID: $userID");

try {
    $reportsService = new ReportsService();
    $action = $_GET['action'] ?? '';
    
    error_log("Action requested: $action");
    
    switch ($action) {
        case 'savings_growth':
            $data = $reportsService->getSavingsGrowthData($userID);
            break;
            
        case 'goal_completion':
            $data = $reportsService->getGoalCompletionData($userID);
            break;
            
        case 'completed_goals':
            $month = $_GET['month'] ?? null;
            $year = $_GET['year'] ?? date('Y');
            $data = $reportsService->getCompletedGoalsByMonth($userID, $month, $year);
            break;
            
        case 'months_with_goals':
            $year = $_GET['year'] ?? date('Y');
            $data = $reportsService->getMonthsWithCompletedGoals($userID, $year);
            break;
            
        default:
            http_response_code(400);
            $data = ['error' => 'Invalid action parameter'];
            break;
    }
    
    error_log("Returning data: " . print_r($data, true));
    echo json_encode($data);
    
} catch (Exception $e) {
    error_log("Error in reports_data.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'details' => $e->getMessage()]);
}
?>