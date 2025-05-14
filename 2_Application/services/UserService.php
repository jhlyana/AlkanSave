<?php

// FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
// Show all errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);
// FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE

require_once __DIR__ . '/../../3_Data/repositories/UserRepository.php';

class UserService {
    private $userRepository;

    // Creates a new instance with UserRepository dependency
    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    // Checks if email and password match a user in database
    // Returns user data if valid, false if not
    public function authenticate($email, $password) {
        // Get user from database by email
        $user = $this->userRepository->findByEmail($email);
        
        // Verify password against stored hash
        if ($user && password_verify($password, $user['PasswordHash'])) {
            return $user;
        }
        
        return false;
    }
}
?>