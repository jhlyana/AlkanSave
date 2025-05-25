<?php

require_once __DIR__ . '/../../3_Data/repositories/DashboardRepository.php';

class DashboardService {
    private $dashboardRepository;

    public function __construct() {
        $this->dashboardRepository = new DashboardRepository();
    }

    public function getUserData($userId) {
        try {
            $userData = $this->dashboardRepository->getUserById($userId);
            
            if (empty($userData)) {
                throw new Exception("User not found with ID: $userId");
            }
            
            return $userData;
        } catch (Exception $e) {
            error_log("DashboardService::getUserData Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSavingsProgress($userId) {
        try {
            $progress = $this->dashboardRepository->calculateSavingsProgress($userId);
            return max(0, min(100, $progress)); // Ensure between 0-100
        } catch (Exception $e) {
            error_log("DashboardService::getSavingsProgress Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getGoalProgress($userId) {
        try {
            $progress = $this->dashboardRepository->calculateGoalProgress($userId);
            return max(0, min(100, $progress)); // Ensure between 0-100
        } catch (Exception $e) {
            error_log("DashboardService::getGoalProgress Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalSaved($userId) {
        try {
            $total = $this->dashboardRepository->getTotalSavedAmount($userId);
            return max(0, floatval($total)); // Ensure positive number
        } catch (Exception $e) {
            error_log("DashboardService::getTotalSaved Error: " . $e->getMessage());
            return 0;
        }
    }

    public function getUpcomingDeadlines($userId) {
        try {
            $deadlines = $this->dashboardRepository->getUpcomingDeadlines($userId);
            
            // Format the deadlines properly
            $formattedDeadlines = [];
            foreach ($deadlines as $deadline) {
                $formattedDeadlines[] = [
                    'GoalName' => $deadline['GoalName'],
                    'TargetDate' => $deadline['TargetDate'],
                    'TargetAmount' => $deadline['TargetAmount'],
                    'SavedAmount' => $deadline['SavedAmount'],
                    'DaysLeft' => $this->calculateDaysLeft($deadline['TargetDate'])
                ];
            }
            
            return $formattedDeadlines;
        } catch (Exception $e) {
            error_log("DashboardService::getUpcomingDeadlines Error: " . $e->getMessage());
            return [];
        }
    }
    
    private function calculateDaysLeft($targetDate) {
        $today = new DateTime();
        $target = new DateTime($targetDate);
        $interval = $today->diff($target);
        
        return $interval->days;
    }
}
?>