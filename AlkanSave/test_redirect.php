<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';
header("Location: /AlkanSave/1_Presentation/user_home.html");
?>