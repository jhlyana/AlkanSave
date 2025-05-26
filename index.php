<?php
// AlkanSave/index.php
require_once __DIR__ . '/2_Application/bootstrap.php';

// Basic routing
$request = $_SERVER['REQUEST_URI'];
$basePath = '/AlkanSave';

switch ($request) {
    case $basePath.'/':
    case $basePath.'/dashboard':
        require __DIR__ . '/2_Application/views/dashboard.php';
        break;
    case $basePath.'/reports':
        require __DIR__ . '/2_Application/views/reports.php';
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/2_Application/views/404.php';
        break;
}