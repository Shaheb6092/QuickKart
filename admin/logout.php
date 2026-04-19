<?php
session_start();
// Unset only admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_user']);
header('Location: login.php');
exit;
?>