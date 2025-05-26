<?php
// Simple test to check if everything is working
echo "<h1>AlkanSave Reports Test</h1>";

// Test 1: Database connection
echo "<h2>1. Database Test</h2>";
try {
    require_once '3_Data/Database.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected!<br>";
    
    // Count records in each table
    $tables = ['User', 'Category', 'Goal'];
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "Table $table: $count records<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: Direct API call
echo "<h2>2. API Test</h2>";
session_start();
$_SESSION['user_id'] = 1; // Set test user

try {
    require_once '2_Application/services/ReportsService.php';
    $service = new ReportsService();
    
    $savingsData = $service->getSavingsGrowthData(1);
    echo "<h3>Savings Data:</h3>";
    echo "<pre>" . json_encode($savingsData, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ Service error: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Next Steps:</h2>";
echo "1. If database is empty, add sample data<br>";
echo "2. Make sure you access via localhost/AlkanSave/1_Presentation/user_reports.html<br>";
echo "3. Check browser console for JavaScript errors<br>";
?>

<h2>4. Sample Data (Copy to phpMyAdmin SQL tab):</h2>
<textarea rows="10" cols="80">
INSERT IGNORE INTO User (UserID, FirstName, LastName, Email, PasswordHash) VALUES 
(1, 'Test', 'User', 'test@example.com', 'hash123');

INSERT IGNORE INTO Category (CategoryID, CategoryName) VALUES 
(1, 'Emergency Funds'), (2, 'Travel'), (3, 'Education');

INSERT IGNORE INTO Goal (UserID, CategoryID, GoalName, TargetAmount, SavedAmount, StartDate, TargetDate, Status, CompletionDate, IsDeleted) VALUES 
(1, 1, 'Emergency Fund', 50000, 50000, '2025-01-01', '2025-12-31', 'Completed', '2025-04-15', FALSE),
(1, 2, 'Travel Fund', 30000, 15000, '2025-01-01', '2025-12-31', 'Active', NULL, FALSE),
(1, 3, 'Education Fund', 25000, 25000, '2025-01-01', '2025-12-31', 'Completed', '2025-03-20', FALSE);
</textarea>