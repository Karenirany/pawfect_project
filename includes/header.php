<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : null;
$username = $is_logged_in ? $_SESSION['username'] : null;
?>
<header>
    <nav>
        <a href="/" class="logo">
            <i class="fas fa-paw"></i>
            <span>Pawfect Home</span>
        </a>
        
        <ul class="nav-links">
            <li><a href="/" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="pages/dogs.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dogs.php' ? 'active' : ''; ?>">Browse Dogs</a></li>
            
            <?php if ($is_logged_in): ?>
                <?php if ($user_role === 'admin'): ?>
                    <li><a href="admin/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : ''; ?>">Admin Dashboard</a></li>
                <?php else: ?>
                    <li><a href="pages/adoption_requests.php">My Requests</a></li>
                <?php endif; ?>
                
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($username); ?></span>
                        <span class="user-role <?php echo $user_role; ?>">
                            <?php echo ucfirst($user_role); ?>
                        </span>
                    </div>
                    <a href="pages/logout.php" class="btn btn-outline">Logout</a>
                </div>
            <?php else: ?>
                <li><a href="pages/login.php">Login</a></li>
                <li><a href="pages/register.php" class="btn btn-primary">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>