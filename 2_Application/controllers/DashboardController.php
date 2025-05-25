<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../services/DashboardService.php';

class DashboardController {
    private $dashboardService;

    public function __construct() {
        $this->dashboardService = new DashboardService();
    }

    public function getDashboardData() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set content type to JSON
        header('Content-Type: application/json');
        
        // Enable CORS for local development
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not logged in', 
                    'session' => $_SESSION,
                    'debug' => 'No user_id in session'
                ]);
                return;
            }

            $userId = $_SESSION['user_id'];

            // Get all dashboard data
            $userData = $this->dashboardService->getUserData($userId);
            $savingsProgress = $this->dashboardService->getSavingsProgress($userId);
            $goalProgress = $this->dashboardService->getGoalProgress($userId);
            $totalSaved = $this->dashboardService->getTotalSaved($userId);
            $upcomingDeadlines = $this->dashboardService->getUpcomingDeadlines($userId);

            $response = [
                'success' => true,
                'userData' => $userData,
                'savingsProgress' => round($savingsProgress, 1),
                'goalProgress' => round($goalProgress, 1),
                'totalSaved' => number_format($totalSaved, 2, '.', ''),
                'upcomingDeadlines' => $upcomingDeadlines,
                'debug' => [
                    'userId' => $userId,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'sessionData' => $_SESSION
                ]
            ];

            echo json_encode($response);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load dashboard data', 
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'debug' => [
                    'userId' => $_SESSION['user_id'] ?? 'not set',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }
}

// Handle the request
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getDashboardData') {
        $controller = new DashboardController();
        $controller->getDashboardData();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request', 
            'method' => $_SERVER['REQUEST_METHOD'], 
            'get' => $_GET,
            'debug' => 'Controller endpoint reached but invalid request'
        ]);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>