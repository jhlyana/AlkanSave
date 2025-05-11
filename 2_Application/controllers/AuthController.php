<?php
// Start session at the very top
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../3_Data/repositories/UserRepository.php';
require_once __DIR__ . '/../../3_Data/repositories/AdminRepository.php';

class AuthController {
    private $userRepo;
    private $adminRepo;
    
    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->adminRepo = new AdminRepository();
    }
    
    public function login() {
        session_start();
        
        // Clear any existing session data
        session_unset();
        
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // First check if it's the admin
        if ($email === 'admin@gmail.com') {
            $admin = $this->adminRepo->findByEmail($email);
            
            if ($admin && password_verify($password, $admin['PasswordHash'])) {
                // Set ONLY admin session variables
                $_SESSION['admin_id'] = $admin['AdminID'];
                $_SESSION['role'] = 'admin';
                $_SESSION['email'] = $email;
                $this->adminRepo->updateLastLogin($admin['AdminID']);
                
                // Debug output
                error_log("Admin login success - session: " . print_r($_SESSION, true));
                
                // Ensure the file exists
                $dashboardPath = $_SERVER['DOCUMENT_ROOT'] . '/AlkanSave/1_Presentation/admin_dashboard.html';
                if (!file_exists($dashboardPath)) {
                    error_log("Admin dashboard missing at: " . $dashboardPath);
                    die("Admin dashboard file not found");
                }
                
                header("Location: /AlkanSave/1_Presentation/admin_dashboard.html");
                exit();
            }
        }
        
        // If not admin, check regular users
        $user = $this->userRepo->findByEmail($email);
        
        if ($user && password_verify($password, $user['PasswordHash'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['email'] = $user['Email'];
            
            $redirect = ($user['Role'] === 'admin') 
                ? '/AlkanSave/1_Presentation/admin_dashboard.html' 
                : '/AlkanSave/1_Presentation/user_home.html';
            
            header("Location: $redirect");
            exit();
        }
        
        // If neither worked
        header("Location: /AlkanSave/1_Presentation/login.html?error=invalid_credentials");
        exit();
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