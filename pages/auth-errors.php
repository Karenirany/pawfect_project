<?php
// pages/auth-errors.php
session_start();
$error = $_GET['error'] ?? 'Unknown authentication error';
$title = $_GET['title'] ?? 'Authentication Error';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($title); ?> - Pawfect Home</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .error-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p><?php echo htmlspecialchars($error); ?></p>
            <div style="margin-top: 2rem;">
                <a href="login.php" class="btn btn-primary">Back to Login</a>
                <a href="../index.php" class="btn btn-outline">Go Home</a>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>