<?php
session_start();
require_once __DIR__ . '/../../3_Data/repositories/ProfileRepository.php';

class ProfileController {
    private $profileRepository;

    public function __construct() {
        $this->profileRepository = new ProfileRepository();
    }

    public function handleRequest() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            $userID = $_SESSION['user_id'] ?? 6;
            $action = $_GET['action'] ?? '';

            switch ($action) {
                case 'getProfile':
                    $this->getProfile($userID);
                    break;
                    
                case 'updateProfile':
                    $this->updateProfile($userID);
                    break;
                    
                case 'sendVerificationCode':
                    $this->sendVerificationCode();
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    private function getProfile($userID) {
        $profile = $this->profileRepository->getProfile($userID);
        
        if ($profile) {
            echo json_encode([
                'success' => true,
                'data' => $profile
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Profile not found']);
        }
    }

    private function updateProfile($userID) {
        $data = [
            'firstName' => $_POST['firstName'] ?? '',
            'lastName' => $_POST['lastName'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirmPassword' => $_POST['confirmPassword'] ?? '',
            'emailVerified' => $_POST['emailVerified'] ?? false
        ];

        // Validate password confirmation
        if (!empty($data['password']) && $data['password'] !== $data['confirmPassword']) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            return;
        }

        // Handle profile picture upload
        $profilePicturePath = null;
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $profilePicturePath = $this->handleProfilePictureUpload($_FILES['profilePicture']);
        }

        $result = $this->profileRepository->updateProfile($userID, $data, $profilePicturePath);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
    }

    private function sendVerificationCode() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            return;
        }

        // Generate verification code
        $code = sprintf('%06d', mt_rand(1, 999999));
        
        // Store in session for verification
        $_SESSION['verification_code'] = $code;
        $_SESSION['verification_email'] = $email;
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent',
            'code' => $code // In production, this should be sent via email
        ]);
    }

    private function handleProfilePictureUpload($file) {
        $uploadDir = __DIR__ . '/../../1_Presentation/images/profiles/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . ($_SESSION['user_id'] ?? 6) . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return 'images/profiles/' . $fileName;
        }

        return null;
    }
}

$controller = new ProfileController();
$controller->handleRequest();
?>