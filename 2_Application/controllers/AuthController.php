<?php

// Show all errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../3_Data/repositories/UserRepository.php';
require_once __DIR__ . '/../../3_Data/repositories/AdminRepository.php';

class AuthController {
    private $userRepo;
    private $adminRepo;
    
    public function __construct() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize database repositories
        $this->userRepo = new UserRepository();
        $this->adminRepo = new AdminRepository();
    }
    
    public function handleRequest() {
        // Route POST requests to login/signup methods
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['login'])) {
                $this->login();
            } elseif (isset($_POST['signup'])) {
                $this->signup();
            }
        } 
        // Handle session check for AJAX requests
        elseif (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'checkSession':
                    $this->checkSession();
                    break;
            }
        }
        
        // Default redirect if no valid action found
        header("Location: /AlkanSave/1_Presentation/login.html");
        exit();
    }
    
    private function checkSession() {
        // Start session if needed for this request
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Return JSON response about authentication status
        header('Content-Type: application/json');
        echo json_encode([
            'authenticated' => isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])
        ]);
        exit();
    }
    
    public function login() {
        // Clear previous session data on new login attempt
        $_SESSION = array();
        
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Special case: Handle admin login separately
        if ($email === 'admin@gmail.com') {
            $admin = $this->adminRepo->findByEmail($email);
            
            if ($admin && password_verify($password, $admin['PasswordHash'])) {
                // Set admin session variables
                $_SESSION['admin_id'] = $admin['AdminID'];
                $_SESSION['role'] = 'admin';
                $_SESSION['email'] = $email;
                $this->adminRepo->updateLastLogin($admin['AdminID']);
                
                // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
                error_log("Admin login success - session: " . print_r($_SESSION, true));
                
                $dashboardPath = $_SERVER['DOCUMENT_ROOT'] . '/AlkanSave/1_Presentation/admin_dashboard.html';
                if (!file_exists($dashboardPath)) {
                    error_log("Admin dashboard missing at: " . $dashboardPath);
                    die("Admin dashboard file not found");
                }
                // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
                
                header("Location: /AlkanSave/1_Presentation/admin_dashboard.html");
                exit();
            }
        }
        
        // Handle regular user login
        $user = $this->userRepo->findByEmail($email);
        
        if ($user && password_verify($password, $user['PasswordHash'])) {
            // Set user session variables
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['email'] = $user['Email'];
            
            // Redirect based on user role
            $redirect = ($user['Role'] === 'admin') 
                ? '/AlkanSave/1_Presentation/admin_dashboard.html' 
                : '/AlkanSave/1_Presentation/user_home.html';
            
            header("Location: $redirect");
            exit();
        }
        
        // Failed login attempt
        header("Location: /AlkanSave/1_Presentation/login.html?error=invalid_credentials");
        exit();
    }
    
    public function signup() {
        // Verify password confirmation matches
        if ($_POST['password'] !== $_POST['confirm_password']) {
            header("Location: /AlkanSave/1_Presentation/signup.html?error=password_mismatch");
            exit();
        }

        // Check if email already registered
        if ($this->userRepo->findByEmail($_POST['email'])) {
            header("Location: /AlkanSave/1_Presentation/signup.html?error=email_exists");
            exit();
        }

        // Prepare user data for registration
        $userData = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'dob' => $_POST['dob'],
            'password' => $_POST['password']
        ];

        // Attempt user creation and redirect accordingly
        if ($this->userRepo->createUser($userData)) {
            header("Location: /AlkanSave/1_Presentation/login.html?signup=success");
        } else {
            header("Location: /AlkanSave/1_Presentation/signup.html?error=create_failed");
        }
        exit();
    }
}

// Process the incoming request
$auth = new AuthController();
$auth->handleRequest();