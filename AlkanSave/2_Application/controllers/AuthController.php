<?php
// Start session at the very top
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../3_Data/repositories/UserRepository.php';

class AuthController {
    private $userRepo;
    
    public function __construct() {
        $this->userRepo = new UserRepository();
    }
    
    public function login() {
        // Get user from database
        $user = $this->userRepo->findByEmail($_POST['email']);
        
        // Verify credentials
        if ($user && password_verify($_POST['password'], $user['PasswordHash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['email'] = $user['Email'];
            
            // Determine redirect path
            $redirect = ($user['Role'] === 'admin') 
                ? '/AlkanSave/1_Presentation/admin_dashboard.html' 
                : '/AlkanSave/1_Presentation/user_home.html';
            
            // Redirect
            header("Location: $redirect");
            exit();
        } else {
            // Invalid credentials
            header("Location: /AlkanSave/1_Presentation/login.html?error=invalid_credentials");
            exit();
        }
    }
    
    public function signup() {
        // Validate password match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            header("Location: /AlkanSave/1_Presentation/signup.html?error=password_mismatch");
            exit();
        }

        // Check if email exists
        if ($this->userRepo->findByEmail($_POST['email'])) {
            header("Location: /AlkanSave/1_Presentation/signup.html?error=email_exists");
            exit();
        }

        // Create user data array
        $userData = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'dob' => $_POST['dob'],
            'password' => $_POST['password']
        ];

        // Attempt to create user
        if ($this->userRepo->createUser($userData)) {
            header("Location: /AlkanSave/1_Presentation/login.html?signup=success");
        } else {
            header("Location: /AlkanSave/1_Presentation/signup.html?error=create_failed");
        }
        exit();
    }
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    
    if (isset($_POST['login'])) {
        $auth->login();
    } elseif (isset($_POST['signup'])) {
        $auth->signup();
    }
    
    // Fallback for invalid requests
    header("Location: /AlkanSave/1_Presentation/login.html");
    exit();
}
?>