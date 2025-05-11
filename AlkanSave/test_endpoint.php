<?php
header('Content-Type: text/plain');
echo "TEST ENDPOINT WORKING!\n";
echo "POST data:\n";
print_r($_POST);
echo "Server info:\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
?>