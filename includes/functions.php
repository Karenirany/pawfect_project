<?php
// includes/functions.php

/**
 * Sanitize user input
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function get_current_user_data() {
    global $pdo;
    
    if (!is_logged_in()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT user_id, email, username, avatar, phone_number, role, is_active, registration_date, last_login, firebase_uid, provider, login_method FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user data: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user role - FIXED: Use consistent 'role' session variable
 */
function get_user_role() {
    // First try session, then database as fallback
    if (isset($_SESSION['role']) && !empty($_SESSION['role'])) {
        return $_SESSION['role'];
    }
    
    $user_data = get_current_user_data();
    if ($user_data && isset($user_data['role'])) {
        $_SESSION['role'] = $user_data['role']; // Update session with correct role
        return $user_data['role'];
    }
    
    return 'user'; // Default role
}

/**
 * Check if user is admin - FIXED: Use consistent role checking
 */
function is_admin() {
    if (!is_logged_in()) {
        return false;
    }
    return get_user_role() === 'admin';
}

/**
 * Check if user is regular user
 */
function is_regular_user() {
    if (!is_logged_in()) {
        return false;
    }
    return get_user_role() === 'user';
}

/**
 * Redirect user after login
 */
function redirect_after_login() {
    if (isset($_SESSION['redirect_url']) && !empty($_SESSION['redirect_url'])) {
        $url = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        header("Location: $url");
        exit;
    }
    
    // Redirect to index.php in root (htdocs)
    header('Location: /index.php');
    exit;
}

/**
 * Regular email/password login - FIXED: Use 'role' instead of 'user_role'
 */
function login_user($email, $password) {
    global $pdo;
    
    try {
        // Debug logging
        error_log("Login attempt for email: $email");
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        
        // Check if email exists with email provider
        $stmt = $pdo->prepare("SELECT user_id, email, password_hash, username, role, is_active, provider FROM users WHERE email = ? AND provider = 'email'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("Login failed: User not found - $email");
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            error_log("Login failed: Account inactive - $email");
            return ['success' => false, 'message' => 'Your account has been deactivated. Please contact support.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            error_log("Login failed: Invalid password for - $email");
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Set session variables - FIXED: Use 'role' to match database column name
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['username'] ?? $user['email'];
        $_SESSION['role'] = $user['role'] ?? 'user'; // FIX: Use 'role' not 'user_role'
        $_SESSION['login_time'] = time();
        $_SESSION['login_provider'] = $user['provider'];
        
        // Update last login timestamp
        $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $update_stmt->execute([$user['user_id']]);
        
        error_log("Login successful for user: " . $user['email'] . " with role: " . $_SESSION['role']);
        
        return [
            'success' => true, 
            'message' => 'Login successful!',
            'user_id' => $user['user_id']
        ];
        
    } catch (PDOException $e) {
        error_log("Database error during login: " . $e->getMessage());
        return ['success' => false, 'message' => 'A system error occurred. Please try again.'];
    }
}

/**
 * Firebase Google authentication handler
 */
function handle_firebase_login($uid, $email, $name, $photoURL = '') {
    global $pdo;
    
    try {
        error_log("Firebase login attempt - UID: $uid, Email: $email");
        
        // Validate required fields
        if (empty($uid) || empty($email)) {
            return ['success' => false, 'error' => 'Missing required user information'];
        }
        
        // Check if user already exists with this Firebase UID or email
        $stmt = $pdo->prepare("SELECT user_id, email, username, role, is_active, provider FROM users WHERE firebase_uid = ? OR email = ?");
        $stmt->execute([$uid, $email]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_user) {
            // User exists - update information and log them in
            return login_existing_firebase_user($existing_user, $uid, $email, $name, $photoURL);
        } else {
            // New user - create account
            return create_firebase_user($uid, $email, $name, $photoURL);
        }
        
    } catch (PDOException $e) {
        error_log("Database error during Firebase login: " . $e->getMessage());
        return ['success' => false, 'error' => 'A system error occurred. Please try again.'];
    }
}

/**
 * Login existing Firebase user - FIXED: Use 'role' instead of 'user_role'
 */
function login_existing_firebase_user($user, $uid, $email, $name, $photoURL) {
    global $pdo;
    
    try {
        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Your account has been deactivated. Please contact support.'];
        }
        
        // Update user information
        $update_stmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, avatar = ?, firebase_uid = ?, last_login = NOW(), provider = 'google', login_method = 'google'
            WHERE user_id = ?
        ");
        $update_stmt->execute([$name, $photoURL, $uid, $user['user_id']]);
        
        // Set session variables - FIXED: Use 'role' not 'user_role'
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['role'] = $user['role'] ?? 'user'; // FIX: Use 'role' not 'user_role'
        $_SESSION['login_time'] = time();
        $_SESSION['login_provider'] = 'google';
        
        error_log("Firebase login successful for existing user: " . $email . " with role: " . $_SESSION['role']);
        
        return ['success' => true];
        
    } catch (PDOException $e) {
        error_log("Error updating Firebase user: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update user information'];
    }
}

/**
 * Create new Firebase user - FIXED: Use 'role' instead of 'user_role'
 */
function create_firebase_user($uid, $email, $name, $photoURL) {
    global $pdo;
    
    try {
        // Generate a random password for Firebase users
        $random_password = bin2hex(random_bytes(16));
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
        
        // Use email as username if no name provided
        $username = !empty($name) ? $name : explode('@', $email)[0];
        
        // Insert new user - Role is explicitly set to 'user'
        $insert_stmt = $pdo->prepare("
            INSERT INTO users (
                firebase_uid, email, username, password_hash, avatar, 
                phone_number, role, is_active, registration_date, last_login, 
                provider, login_method
            ) VALUES (?, ?, ?, ?, ?, NULL, 'user', 1, NOW(), NOW(), 'google', 'google')
        ");
        
        $result = $insert_stmt->execute([$uid, $email, $username, $hashed_password, $photoURL]);
        
        if (!$result) {
            throw new Exception("Failed to insert new user");
        }
        
        $user_id = $pdo->lastInsertId();
        
        // Set session variables - FIXED: Use 'role' not 'user_role'
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $username;
        $_SESSION['role'] = 'user'; // FIX: Use 'role' not 'user_role'
        $_SESSION['login_time'] = time();
        $_SESSION['login_provider'] = 'google';
        
        error_log("New Firebase user created: " . $email . " with role: user");
        
        return ['success' => true];
        
    } catch (PDOException $e) {
        error_log("Error creating Firebase user: " . $e->getMessage());
        
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return ['success' => false, 'error' => 'User already exists with this email'];
        }
        
        return ['success' => false, 'error' => 'Failed to create user account'];
    }
}

/**
 * Register new user with email/password - FIXED: Use 'role' instead of 'user_role'
 */
function register_user($email, $password, $name, $phone_number = null) {
    global $pdo;
    
    try {
        error_log("Registration attempt for email: $email");
        
        // Validate inputs
        if (empty($email) || empty($password) || empty($name)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Use email as username if no name provided
        $username = !empty($name) ? $name : explode('@', $email)[0];
        
        // Insert new user - Role is explicitly set to 'user'
        $insert_stmt = $pdo->prepare("
            INSERT INTO users (
                email, username, password_hash, phone_number, 
                role, is_active, registration_date, last_login, 
                provider, login_method
            ) VALUES (?, ?, ?, ?, 'user', 1, NOW(), NOW(), 'email', 'email')
        ");
        
        $result = $insert_stmt->execute([$email, $username, $hashed_password, $phone_number]);
        
        if ($result) {
            $user_id = $pdo->lastInsertId();
            
            // Auto-login after registration
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $username;
            $_SESSION['role'] = 'user'; // FIX: Use 'role' not 'user_role'
            $_SESSION['login_time'] = time();
            $_SESSION['login_provider'] = 'email';
            
            error_log("Registration successful for user: " . $email . " with role: user");
            
            return [
                'success' => true, 
                'message' => 'Registration successful!',
                'user_id' => $user_id
            ];
        } else {
            throw new Exception("Failed to insert user");
        }
        
    } catch (PDOException $e) {
        error_log("Database error during registration: " . $e->getMessage());
        
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        return ['success' => false, 'message' => 'A system error occurred. Please try again.'];
    }
}

/**
 * Logout user
 */
function logout_user() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Verify user role in database (for debugging)
 */
function verify_user_role() {
    if (!is_logged_in()) {
        return "Not logged in";
    }
    
    $db_user = get_current_user_data();
    $session_role = $_SESSION['role'] ?? 'not set';
    $db_role = $db_user['role'] ?? 'not found';
    
    return "Session Role: $session_role, Database Role: $db_role";
}

/**
 * Update user role in session (if needed)
 */
function refresh_user_role() {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_data = get_current_user_data();
    if ($user_data && isset($user_data['role'])) {
        $_SESSION['role'] = $user_data['role'];
        return true;
    }
    
    return false;
}
?>