<?php
session_start();
echo "<h1>Current Session Debug</h1>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "Email: " . ($_SESSION['email'] ?? 'Not set') . "<br>";
echo "<hr>";
echo "Session contents:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>