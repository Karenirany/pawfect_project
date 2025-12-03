<?php
// pages/logout.php
session_start();
require_once '../includes/functions.php';

// Store login method before logout
$login_method = $_SESSION['login_method'] ?? 'regular';

// Logout the user
logout_user();

// Redirect based on login method
if ($login_method === 'firebase') {
    // Redirect to Firebase logout handler
    header('Location: firebase-logout.php');
    exit;
} else {
    // Regular logout - redirect to login page
    header('Location: login.php?logout=1');
    exit;
}
?>