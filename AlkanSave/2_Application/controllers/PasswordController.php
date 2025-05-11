<?php
require_once __DIR__ . '/../../3_Data/repositories/PasswordResetRepository.php';

class PasswordController {
    private $resetRepo;
    
    public function __construct() {
        $this->resetRepo = new PasswordResetRepository();
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['email']) && !isset($_POST['code'])) {
                $this->handleResetRequest();
            } elseif (isset($_POST['code'])) {
                $this->handlePasswordUpdate();
            }
        }
    }
    
    private function handleResetRequest() {
        $email = $_POST['email'];
        $code = $this->resetRepo->createResetRequest($email);
        
        if ($code) {
            // In a real app, you would send an email here
            // For testing, we'll just display the code
            error_log("Password reset code for $email: $code");
            header("Location: /AlkanSave/1_Presentation/reset-password.html?email=" . urlencode($email));
        } else {
            header("Location: /AlkanSave/1_Presentation/forgotpass.html?error=invalid_email");
        }
        exit();
    }
    
    private function handlePasswordUpdate() {
        $email = $_POST['email'];
        $code = $_POST['code'];
        $newPassword = $_POST['password'];
        
        $resetRecord = $this->resetRepo->validateResetCode($email, $code);
        
        if ($resetRecord) {
            if ($this->resetRepo->updatePassword($email, $newPassword)) {
                $this->resetRepo->markCodeAsUsed($resetRecord['ResetID']);
                header("Location: /AlkanSave/1_Presentation/login.html?password_reset=success");
            } else {
                header("Location: /AlkanSave/1_Presentation/reset-password.html?email=" . urlencode($email) . "&error=update_failed");
            }
        } else {
            header("Location: /AlkanSave/1_Presentation/reset-password.html?email=" . urlencode($email) . "&error=invalid_code");
        }
        exit();
    }
}

(new PasswordController())->handleRequest();
?>