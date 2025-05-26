<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=alkansave_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h2>Updating User Table for Profile System</h2>";
    
    // Add new columns to User table
    $alterQueries = [
        "ALTER TABLE User ADD COLUMN IF NOT EXISTS DateOfBirth DATE",
        "ALTER TABLE User ADD COLUMN IF NOT EXISTS Username VARCHAR(50) UNIQUE",
        "ALTER TABLE User ADD COLUMN IF NOT EXISTS ProfilePicture VARCHAR(255)"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "✅ Column added successfully<br>";
        } catch (Exception $e) {
            echo "ℹ️ Column may already exist: " . $e->getMessage() . "<br>";
        }
    }
    
    // Update existing user with sample data
    $pdo->exec("UPDATE User SET 
                    DateOfBirth = '1998-04-15',
                    Username = 'john_doe_123'
                WHERE UserID = 6");
    
    echo "<div style='background: #28a745; color: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ Database Updated Successfully!</h3>";
    echo "<p>Profile system is ready to use</p>";
    echo "</div>";
    
    echo "<p><a href='1_Presentation/user_profile.html' style='background: #f07eae; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>🚀 VIEW PROFILE PAGE</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error: " . $e->getMessage() . "</h3>";
}
?>