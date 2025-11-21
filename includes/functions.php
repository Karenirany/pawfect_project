<?php
// includes/functions.php

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Log user actions to the activity_log table
 */
function log_action($user_id, $action) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
        $stmt->execute([$user_id, $action]);
        return true;
    } catch (PDOException $e) {
        error_log("Error logging action: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect to another page
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Sanitize output data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Display error messages
 */
function display_error($message) {
    return '<div class="alert alert-error">' . sanitize($message) . '</div>';
}

/**
 * Display success messages
 */
function display_success($message) {
    return '<div class="alert alert-success">' . sanitize($message) . '</div>';
}
?>