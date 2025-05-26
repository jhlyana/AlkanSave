<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in using your existing session system
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

require_once __DIR__ . '/../services/ReportsService.php';

$reportsService = new ReportsService();
$userID = $_SESSION['user_id']; // This will use the actual logged-in user

// Get the action parameter
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'savings_growth':
            $data = $reportsService->getSavingsGrowthData($userID);
            echo json_encode($data);
            break;
            
        case 'goal_completion':
            $data = $reportsService->getGoalCompletionData($userID);
            echo json_encode($data);
            break;
            
        case 'completed_goals':
            $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $data = $reportsService->getCompletedGoalsByMonth($userID, $month, $year);
            echo json_encode($data);
            break;
            
        case 'months_with_goals':
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $data = $reportsService->getMonthsWithCompletedGoals($userID, $year);
            echo json_encode($data);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action parameter']);
            break;
    }
} catch (Exception $e) {
    error_log("Error in reports_data.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'debug' => $e->getMessage()]);
}
?>