<?php
require_once __DIR__ . '/3_Data/Database.php';

try {
    $db = Database::getInstance();
    echo "Database connection successful!";
    
    // Test query
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetch();
    echo "<pre>Test query result: "; print_r($result); echo "</pre>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}