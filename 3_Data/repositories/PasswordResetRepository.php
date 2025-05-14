<?php
require_once __DIR__ . '/../Database.php';

class PasswordResetRepository {
    private $db;
    
    public function __construct() {
        // Get database connection
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function createResetRequest($email) {
        // First check if email exists in users table
        $stmt = $this->db->prepare("SELECT UserID FROM User WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        // Generate 6-digit code and set 1-hour expiration
        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save reset request to database
        $stmt = $this->db->prepare(
            "INSERT INTO PasswordReset 
            (UserID, Email, VerificationCode, Expiration) 
            VALUES (?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $user['UserID'],
            $email,
            $code,
            $expiration
        ]) ? $code : false;
    }
    
    public function validateResetCode($email, $code) {
        // Check if code is valid and not expired
        $stmt = $this->db->prepare(
            "SELECT * FROM PasswordReset 
            WHERE Email = ? AND VerificationCode = ? 
            AND Used = FALSE AND Expiration > NOW()"
        );
        $stmt->execute([$email, $code]);
        return $stmt->fetch();
    }
    
    public function markCodeAsUsed($resetId) {
        // Mark code as used to prevent reuse
        $stmt = $this->db->prepare(
            "UPDATE PasswordReset SET Used = TRUE 
            WHERE ResetID = ?"
        );
        return $stmt->execute([$resetId]);
    }
    
    public function updatePassword($email, $newPassword) {
        // Update password with secure hash
        $stmt = $this->db->prepare(
            "UPDATE User SET PasswordHash = ? 
            WHERE Email = ?"
        );
        return $stmt->execute([
            password_hash($newPassword, PASSWORD_BCRYPT),
            $email
        ]);
    }
    
    public function deleteResetRequest($email) {
        try {
            // Remove any existing reset requests for this email
            $stmt = $this->db->prepare(
                "DELETE FROM PasswordReset WHERE Email = ?"
            );
            return $stmt->execute([$email]);
        } catch (PDOException $e) {
            // FOR DEBUGGING, DELETE THIS LATER..... START DELETING HERE
            error_log("Failed to delete reset request: " . $e->getMessage());
            // FOR DEBUGGING, DELETE THIS LATER..... END DELETING HERE
            return false;
        }
    }
}