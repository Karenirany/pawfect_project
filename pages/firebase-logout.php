<?php
// pages/firebase-logout.php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <?php include '../includes/firebase-init.php'; ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .logout-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="spinner"></div>
        <h2>Signing out from Google...</h2>
        <p>Please wait while we securely sign you out.</p>
    </div>

    <script>
        // Sign out from Firebase
        auth.signOut()
            .then(() => {
                console.log('Firebase sign-out successful');
                // Redirect to login page after successful sign-out
                setTimeout(() => {
                    window.location.href = 'login.php?logout=1&provider=google';
                }, 1000);
            })
            .catch((error) => {
                console.error('Firebase sign-out error:', error);
                // Still redirect to login page even if Firebase sign-out fails
                setTimeout(() => {
                    window.location.href = 'login.php?logout=1&provider=google';
                }, 1000);
            });
    </script>
</body>
</html>