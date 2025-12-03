<?php
// pages/login.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a Firebase login
    if (isset($_POST['uid']) && isset($_POST['provider']) && $_POST['provider'] === 'google') {
        // Firebase Google login - handled by firebase-auth.php
        $error = 'Please use the Google sign-in button for Google authentication.';
    } else {
        // Regular email/password login
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $result = login_user($email, $password);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
                redirect_after_login();
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pawfect Home</title>
    <link rel="stylesheet" href="../css/styles.css">
    
    <style>
        .auth-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            margin: 10px 0;
            transition: background-color 0.3s;
        }
        
        .google-btn:hover {
            background: #f8f9fa;
        }
        
        .google-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #666;
            position: relative;
        }
        
        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
            z-index: 1;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            z-index: 2;
        }
        
        #firebase-error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="auth-container">
            <h2 style="text-align: center; margin-bottom: 1rem;">Welcome Back</h2>
            
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Firebase Google Sign-in -->
            <button class="google-btn" id="google-signin-btn" onclick="signInWithGoogle()">
                <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" width="20" height="20">
                <span id="google-btn-text">Continue with Google</span>
            </button>
            
            <div id="firebase-error"></div>
            
            <div class="divider"><span>or</span></div>

            <!-- Manual Login Form -->
            <form method="POST" action="">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           style="width: 100%; padding: 0.75rem; border: 2px solid #e1e5e9; border-radius: 5px;" 
                           required autocomplete="email">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Password</label>
                    <input type="password" name="password" 
                           style="width: 100%; padding: 0.75rem; border: 2px solid #e1e5e9; border-radius: 5px;" 
                           required autocomplete="current-password">
                </div>

                <button type="submit" style="width: 100%; padding: 0.75rem; background: #4e8df5; color: white; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;">
                    Sign In
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem;">
                <p>Don't have an account? <a href="register.php" style="color: #4e8df5;">Register here</a></p>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Firebase Script -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    
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
        let firebaseApp;
        let auth;
        
        try {
            firebaseApp = firebase.initializeApp(firebaseConfig);
            auth = firebase.auth();
            console.log('Firebase initialized successfully');
        } catch (error) {
            console.error('Firebase initialization error:', error);
            showError('Firebase initialization failed. Please refresh the page.');
            document.getElementById('google-signin-btn').disabled = true;
            document.getElementById('google-btn-text').textContent = 'Google Sign-in Unavailable';
        }

        function signInWithGoogle() {
            const provider = new firebase.auth.GoogleAuthProvider();
            const errorDiv = document.getElementById('firebase-error');
            const googleBtn = document.getElementById('google-signin-btn');
            
            // Show loading state
            errorDiv.style.display = 'none';
            googleBtn.classList.add('loading');
            googleBtn.disabled = true;
            document.getElementById('google-btn-text').textContent = 'Signing in...';
            
            auth.signInWithPopup(provider)
                .then((result) => {
                    const user = result.user;
                    console.log('Google sign-in successful:', user);
                    
                    // Send user data to server
                    sendUserToServer(user);
                })
                .catch((error) => {
                    console.error('Google sign-in error:', error);
                    showError('Google sign-in failed: ' + error.message);
                    
                    // Reset button state
                    googleBtn.classList.remove('loading');
                    googleBtn.disabled = false;
                    document.getElementById('google-btn-text').textContent = 'Continue with Google';
                });
        }

        function sendUserToServer(user) {
            const errorDiv = document.getElementById('firebase-error');
            
            // Create form data
            const formData = new FormData();
            formData.append('uid', user.uid);
            formData.append('email', user.email);
            formData.append('name', user.displayName || user.email.split('@')[0]);
            formData.append('photoURL', user.photoURL || '');
            formData.append('provider', 'google');

            // Send to firebase-auth.php in the same pages directory
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
                console.log('Server response:', data);
                
                if (data.success) {
                    // Redirect to home page on success
                    window.location.href = '/index.php';
                } else {
                    throw new Error(data.error || 'Unknown server error');
                }
            })
            .catch(error => {
                console.error('Error sending user data to server:', error);
                showError('Authentication error: ' + error.message);
                
                // Reset button state
                const googleBtn = document.getElementById('google-signin-btn');
                googleBtn.classList.remove('loading');
                googleBtn.disabled = false;
                document.getElementById('google-btn-text').textContent = 'Continue with Google';
                
                // Sign out from Firebase since server authentication failed
                auth.signOut().catch(err => console.error('Sign out error:', err));
            });
        }

        function showError(message) {
            const errorDiv = document.getElementById('firebase-error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    </script>
</body>
</html>