<?php
require_once __DIR__ . '/../Database.php';

class ProfileRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getProfile($userID) {
        try {
            $sql = "SELECT 
                        UserID,
                        FirstName,
                        LastName,
                        Email,
                        DateOfBirth,
                        Username,
                        ProfilePicture
                    FROM User 
                    WHERE UserID = ? AND IsDeleted = FALSE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userID]);
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'userID' => $result['UserID'],
                    'firstName' => $result['FirstName'],
                    'lastName' => $result['LastName'],
                    'email' => $result['Email'],
                    'dateOfBirth' => $result['DateOfBirth'],
                    'username' => $result['Username'],
                    'profilePicture' => $result['ProfilePicture']
                ];
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Error getting profile: " . $e->getMessage());
            return null;
        }
    }

    public function updateProfile($userID, $data, $profilePicturePath = null) {
        try {
            $sql = "UPDATE User SET 
                        FirstName = ?,
                        LastName = ?,
                        Email = ?";
            
            $params = [
                $data['firstName'],
                $data['lastName'],
                $data['email']
            ];

            if (!empty($data['password'])) {
                $sql .= ", PasswordHash = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if ($profilePicturePath) {
                $sql .= ", ProfilePicture = ?";
                $params[] = $profilePicturePath;
            }

            $sql .= " WHERE UserID = ? AND IsDeleted = FALSE";
            $params[] = $userID;

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfilePicture($userID, $profilePicturePath) {
        try {
            $sql = "UPDATE User SET ProfilePicture = ? WHERE UserID = ? AND IsDeleted = FALSE";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$profilePicturePath, $userID]);
        } catch (PDOException $e) {
            error_log("Error updating profile picture: " . $e->getMessage());
            return false;
        }
    }
}
?>