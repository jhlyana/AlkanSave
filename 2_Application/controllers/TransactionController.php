<?php
session_start();

require_once __DIR__ . '/../../3_Data/repositories/TransactionRepository.php';

class TransactionController {
    private $transactionRepo;
    
    public function __construct() {
        $this->transactionRepo = new TransactionRepository();
    }
    
    /**
     * Get transactions for the logged-in user
     */
    public function getTransactions() {
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(['error' => 'User not logged in'], 401);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
        
        try {
            $transactions = $this->transactionRepo->getUserTransactions($userId, $month, $year);
            
            $formattedTransactions = [];
            foreach ($transactions as $transaction) {
                $formattedTransactions[] = [
                    'id' => $transaction['TransactionID'],
                    'date' => $transaction['DateSaved'],
                    'formatted_date' => date('M d, Y', strtotime($transaction['DateSaved'])),
                    'category' => $transaction['CategoryName'],
                    'goal' => $transaction['GoalName'],
                    'amount' => number_format($transaction['Amount'], 2),
                    'formatted_amount' => '+ ₱' . number_format($transaction['Amount'], 2)
                ];
            }
            
            $this->sendJsonResponse([
                'success' => true,
                'transactions' => $formattedTransactions,
                'count' => count($formattedTransactions)
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getTransactions: " . $e->getMessage());
            $this->sendJsonResponse(['error' => 'Failed to fetch transactions'], 500);
        }
    }
    
    /**
     * Get monthly transaction counts for the logged-in user
     */
    public function getMonthlyStats() {
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(['error' => 'User not logged in'], 401);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
        
        try {
            $monthlyCounts = $this->transactionRepo->getMonthlyTransactionCounts($userId, $year);
            
            $this->sendJsonResponse([
                'success' => true,
                'monthly_stats' => $monthlyCounts
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getMonthlyStats: " . $e->getMessage());
            $this->sendJsonResponse(['error' => 'Failed to fetch monthly statistics'], 500);
        }
    }
    
    private function sendJsonResponse($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}

$action = $_GET['action'] ?? '';
$controller = new TransactionController();

switch ($action) {
    case 'getTransactions':
        $controller->getTransactions();
        break;
    case 'getMonthlyStats':
        $controller->getMonthlyStats();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>