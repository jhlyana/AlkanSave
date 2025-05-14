<?php

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../3_Data/repositories/UserRepository.php';

class UserService {
    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function authenticate($email, $password) {
        $user = $this->userRepository->findByEmail($email);
        
        if ($user && password_verify($password, $user['PasswordHash'])) {
            return $user;
        }
        
        return false;
    }
}
?>