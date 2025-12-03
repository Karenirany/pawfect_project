<?php
// pages/register.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once "../functions/user_functions.php";

$avatar = generateAvatar($username);

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error = '';
$success = '';

// Helper functions for validation
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_strong_password($password) {
    return strlen($password) >= 6;
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is Firebase registration
    if (isset($_POST['uid']) && isset($_POST['provider']) && $_POST['provider'] === 'google') {
        // Firebase registration handled by firebase-auth.php
        $error = 'Please use the Google sign-up button for Google registration.';
    } else {
        // Regular email/password registration
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone_number = sanitize_input($_POST['phone_number'] ?? '');
        
        // Validate inputs
        $validation_errors = [];
        
        if (empty($username)) {
            $validation_errors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $validation_errors[] = 'Username must be at least 3 characters long.';
        }
        
        if (empty($email)) {
            $validation_errors[] = 'Email is required.';
        } elseif (!is_valid_email($email)) {
            $validation_errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($password)) {
            $validation_errors[] = 'Password is required.';
        } elseif (!is_strong_password($password)) {
            $validation_errors[] = 'Password must be at least 6 characters long.';
        }
        
        if (empty($confirm_password)) {
            $validation_errors[] = 'Please confirm your password.';
        } elseif ($password !== $confirm_password) {
            $validation_errors[] = 'Passwords do not match.';
        }
        
        if (!empty($phone_number) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $phone_number)) {
            $validation_errors[] = 'Please enter a valid phone number.';
        }
        
        if (empty($validation_errors)) {
            // Attempt registration
            $result = register_user($email, $password, $username, $phone_number);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
                header('Location: login.php');
                exit;
            } else {
                $error = $result['message'];
            }
        } else {
            $error = implode('<br>', $validation_errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Pawfect Home</title>
    <link rel="stylesheet" href="../css/styles.css">
    
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    
    <style>
        .auth-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            color: #4e8df5;
            margin-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4e8df5;
        }
        
        .form-group input.error {
            border-color: #dc3545;
        }
        
        .form-group input.success {
            border-color: #28a745;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .success-message {
            color: #28a745;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .btn-auth {
            width: 100%;
            padding: 0.75rem;
            background: #4e8df5;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-auth:hover {
            background: #357abd;
        }
        
        .btn-auth:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .auth-links a {
            color: #4e8df5;
            text-decoration: none;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Firebase Auth Styles */
        .firebase-auth-container {
            margin: 20px 0;
            text-align: center;
        }
        
        .firebase-divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .firebase-divider::before,
        .firebase-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .firebase-divider span {
            padding: 0 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .google-btn {
            width: 100%;
            padding: 12px;
            background: white;
            color: #333;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }
        
        .google-btn:hover {
            background: #f8f9fa;
            border-color: #ccc;
        }
        
        .google-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .firebase-error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        
        .firebase-loading {
            text-align: center;
            padding: 10px;
            color: #666;
            display: none;
        }
        
        .firebase-success {
            background: #efe;
            color: #363;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-fair { background-color: #fd7e14; width: 50%; }
        .strength-good { background-color: #ffc107; width: 75%; }
        .strength-strong { background-color: #28a745; width: 100%; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Join Pawfect Home</h1>
                <p>Create your account to start your adoption journey</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Firebase Google Registration -->
            <div class="firebase-auth-container">
                <button onclick="signUpWithGoogle()" class="google-btn" id="googleSignUpBtn">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" width="20" height="20">
                    Sign up with Google
                </button>
                
                <div class="firebase-divider">
                    <span>or sign up with email</span>
                </div>
                
                <div class="firebase-error" id="firebaseError"></div>
                <div class="firebase-success" id="firebaseSuccess"></div>
                <div class="firebase-loading" id="firebaseLoading">
                    Creating your account...
                </div>
            </div>

            <!-- Manual Registration Form -->
            <form method="POST" action="" id="registrationForm" novalidate>
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           required minlength="3" maxlength="50">
                    <div class="error-message" id="usernameError">Username must be at least 3 characters long</div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required>
                    <div class="error-message" id="emailError">Please enter a valid email address</div>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>"
                           pattern="[\+]?[0-9\s\-\(\)]{10,}" placeholder="Optional">
                    <div class="error-message" id="phoneError">Please enter a valid phone number</div>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <div class="password-requirements">Must be at least 6 characters long</div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="error-message" id="passwordError">Password must be at least 6 characters long</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div class="error-message" id="confirmPasswordError">Passwords do not match</div>
                </div>

                <button type="submit" class="btn-auth" id="submitBtn">Create Account</button>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyASCrVkQ_P7JLu79vXt9rTFYCpiu4cGRhU",
            authDomain: "pawfect-b4bc1.firebaseapp.com",
            projectId: "pawfect-b4bc1",
            storageBucket: "pawfect-b4bc1.firebasestorage.app",
            messagingSenderId: "383970177268",
            appId: "1:383970177268:web:a28df5b8f66d11d778eea1",
            measurementId: "G-6LMKZ94M0H"
        };

        // Initialize Firebase
        let auth;
        try {
            firebase.initializeApp(firebaseConfig);
            auth = firebase.auth();
            console.log('Firebase initialized successfully for registration');
        } catch (error) {
            console.error('Firebase initialization error:', error);
            document.getElementById('firebaseError').textContent = 'Firebase not configured properly';
            document.getElementById('firebaseError').style.display = 'block';
            document.getElementById('googleSignUpBtn').disabled = true;
        }

        // Enhanced Form Validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const inputs = {
                username: document.getElementById('username'),
                email: document.getElementById('email'),
                phone_number: document.getElementById('phone_number'),
                password: document.getElementById('password'),
                confirm_password: document.getElementById('confirm_password')
            };

            const errors = {
                username: document.getElementById('usernameError'),
                email: document.getElementById('emailError'),
                phone: document.getElementById('phoneError'),
                password: document.getElementById('passwordError'),
                confirmPassword: document.getElementById('confirmPasswordError')
            };

            // Real-time validation
            inputs.username.addEventListener('input', validateUsername);
            inputs.email.addEventListener('input', validateEmail);
            inputs.phone_number.addEventListener('input', validatePhone);
            inputs.password.addEventListener('input', validatePassword);
            inputs.confirm_password.addEventListener('input', validateConfirmPassword);

            // Password strength indicator
            inputs.password.addEventListener('input', updatePasswordStrength);

            // Form submission
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    // Show first error
                    const firstError = Object.values(errors).find(error => error.style.display === 'block');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            function validateUsername() {
                const value = inputs.username.value.trim();
                if (value.length < 3) {
                    showError(inputs.username, errors.username, 'Username must be at least 3 characters long');
                    return false;
                } else {
                    showSuccess(inputs.username, errors.username);
                    return true;
                }
            }

            function validateEmail() {
                const value = inputs.email.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    showError(inputs.email, errors.email, 'Please enter a valid email address');
                    return false;
                } else {
                    showSuccess(inputs.email, errors.email);
                    return true;
                }
            }

            function validatePhone() {
                const value = inputs.phone_number.value.trim();
                if (value === '') {
                    clearValidation(inputs.phone_number, errors.phone);
                    return true;
                }
                const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
                if (!phoneRegex.test(value)) {
                    showError(inputs.phone_number, errors.phone, 'Please enter a valid phone number');
                    return false;
                } else {
                    showSuccess(inputs.phone_number, errors.phone);
                    return true;
                }
            }

            function validatePassword() {
                const value = inputs.password.value;
                if (value.length < 6) {
                    showError(inputs.password, errors.password, 'Password must be at least 6 characters long');
                    return false;
                } else {
                    showSuccess(inputs.password, errors.password);
                    return true;
                }
            }

            function validateConfirmPassword() {
                const password = inputs.password.value;
                const confirm = inputs.confirm_password.value;
                if (confirm !== password) {
                    showError(inputs.confirm_password, errors.confirmPassword, 'Passwords do not match');
                    return false;
                } else {
                    showSuccess(inputs.confirm_password, errors.confirmPassword);
                    return true;
                }
            }

            function updatePasswordStrength() {
                const password = inputs.password.value;
                const strengthBar = document.getElementById('passwordStrengthBar');
                let strength = 0;
                
                if (password.length >= 6) strength += 25;
                if (password.length >= 8) strength += 25;
                if (/[A-Z]/.test(password)) strength += 25;
                if (/[0-9]/.test(password)) strength += 25;
                
                strengthBar.className = 'password-strength-bar';
                if (strength <= 25) {
                    strengthBar.classList.add('strength-weak');
                } else if (strength <= 50) {
                    strengthBar.classList.add('strength-fair');
                } else if (strength <= 75) {
                    strengthBar.classList.add('strength-good');
                } else {
                    strengthBar.classList.add('strength-strong');
                }
            }

            function validateForm() {
                return validateUsername() && 
                       validateEmail() && 
                       validatePhone() && 
                       validatePassword() && 
                       validateConfirmPassword();
            }

            function showError(input, errorElement, message) {
                input.classList.remove('success');
                input.classList.add('error');
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }

            function showSuccess(input, errorElement) {
                input.classList.remove('error');
                input.classList.add('success');
                errorElement.style.display = 'none';
            }

            function clearValidation(input, errorElement) {
                input.classList.remove('error', 'success');
                errorElement.style.display = 'none';
            }
        });

        // Google Sign Up
        function signUpWithGoogle() {
            if (!auth) {
                showFirebaseError('Firebase not initialized. Please try email registration.');
                return;
            }

            const provider = new firebase.auth.GoogleAuthProvider();
            const signUpBtn = document.getElementById('googleSignUpBtn');
            const errorDiv = document.getElementById('firebaseError');
            const loadingDiv = document.getElementById('firebaseLoading');
            const successDiv = document.getElementById('firebaseSuccess');
            
            // Show loading, hide errors/success
            loadingDiv.style.display = 'block';
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            signUpBtn.disabled = true;
            signUpBtn.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" width="20" height="20"> Creating account...';
            
            auth.signInWithPopup(provider)
                .then((result) => {
                    const user = result.user;
                    console.log('Google sign-up successful:', user);
                    
                    // Send user data to your server to create account
                    registerUserWithGoogle(user);
                })
                .catch((error) => {
                    console.error('Google sign-up error:', error);
                    showFirebaseError('Google sign-up failed: ' + error.message);
                    resetGoogleButton();
                });
        }

        // Register user with Google data
        function registerUserWithGoogle(user) {
            const formData = new FormData();
            formData.append('uid', user.uid);
            formData.append('email', user.email);
            formData.append('name', user.displayName || user.email.split('@')[0]);
            formData.append('photoURL', user.photoURL || '');
            formData.append('provider', 'google');

            fetch('firebase-auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                const loadingDiv = document.getElementById('firebaseLoading');
                loadingDiv.style.display = 'none';
                
                if (data.success) {
                    showFirebaseSuccess('Account created successfully! Redirecting...');
                    // Redirect to home page after short delay
                    setTimeout(() => {
                        window.location.href = '/index.php';
                    }, 1500);
                } else {
                    showFirebaseError('Registration failed: ' + data.error);
                    resetGoogleButton();
                }
            })
            .catch(error => {
                const loadingDiv = document.getElementById('firebaseLoading');
                loadingDiv.style.display = 'none';
                resetGoogleButton();
                
                console.error('Error:', error);
                showFirebaseError('Network error occurred. Please try again.');
            });
        }

        // Utility functions
        function showFirebaseError(message) {
            const errorDiv = document.getElementById('firebaseError');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function showFirebaseSuccess(message) {
            const successDiv = document.getElementById('firebaseSuccess');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
        }

        function resetGoogleButton() {
            const signUpBtn = document.getElementById('googleSignUpBtn');
            signUpBtn.disabled = false;
            signUpBtn.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" width="20" height="20"> Sign up with Google';
        }
    </script>
</body>
</html>