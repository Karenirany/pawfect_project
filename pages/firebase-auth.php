<?php
// pages/firebase-auth.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log the request for debugging
error_log("Firebase auth request received: " . date('Y-m-d H:i:s'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // If JSON parsing failed, try form data
    if (!$data) {
        $data = $_POST;
    }
    
    $uid = $data['uid'] ?? '';
    $email = $data['email'] ?? '';
    $name = $data['name'] ?? '';
    $photoURL = $data['photoURL'] ?? '';
    $provider = $data['provider'] ?? 'google';
    
    error_log("Processing user: $email, UID: $uid");

    if (empty($uid) || empty($email)) {
        error_log("Missing UID or email");
        echo json_encode(['success' => false, 'error' => 'Missing user data: UID or email']);
        exit;
    }
    
    try {
        // Check if user exists by email or Firebase UID
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR firebase_uid = ?");
        $stmt->execute([$email, $uid]);
        $user = $stmt->fetch();

        if ($user) {
            // Update existing user with Firebase data
            error_log("Updating existing user: " . $user['user_id']);
            
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET firebase_uid = ?, avatar = ?, provider = ?, login_method = 'firebase', last_login = NOW() 
                WHERE user_id = ?
            ");
            $update_stmt->execute([$uid, $photoURL, $provider, $user['user_id']]);
            $user_id = $user['user_id'];
            
        } else {
            // Create new user
            error_log("Creating new user for: $email");
            
            $insert_stmt = $pdo->prepare("
                INSERT INTO users 
                (username, email, firebase_uid, avatar, provider, login_method, role, is_active, registration_date, last_login) 
                VALUES (?, ?, ?, ?, ?, 'firebase', 'user', 1, NOW(), NOW())
            ");
            $insert_stmt->execute([$name, $email, $uid, $photoURL, $provider]);
            $user_id = $pdo->lastInsertId();
            error_log("New user created with ID: $user_id");
        }
        
        // Get updated user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (!$user_data) {
            throw new Exception("Failed to retrieve user data after creation");
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user_data['user_id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['role'] = $user_data['role'] ?? 'user';
        $_SESSION['avatar'] = $user_data['avatar'];
        $_SESSION['logged_in'] = true;
        $_SESSION['firebase_uid'] = $uid;
        $_SESSION['login_method'] = 'firebase';
        
        error_log("Session set for user_id: $user_id, username: " . $user_data['username']);
        
        echo json_encode([
            'success' => true, 
            'user_id' => $user_id,
            'message' => 'Login successful'
        ]);
        
    } catch (Exception $e) {
        error_log("Database error in firebase-auth.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid request method. Use POST.'
    ]);
}
?>