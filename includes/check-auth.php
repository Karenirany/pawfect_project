<?php
session_start();
require_once 'functions.php';

// Check if user needs to be authenticated
if (!is_logged_in() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: pages/login.php');
    exit();
}

// If user is logged in via Firebase, verify the token is still valid
if (is_logged_in() && isset($_SESSION['login_method']) && $_SESSION['login_method'] === 'firebase') {
    // You can add token verification here if needed
}
?>