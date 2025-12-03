<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = is_logged_in();
$user_role = $is_logged_in ? ($_SESSION['role'] ?? null) : null;
$username = $is_logged_in ? ($_SESSION['username'] ?? null) : null;
$avatar = $is_logged_in ? ($_SESSION['avatar'] ?? null) : null;
$login_method = $is_logged_in ? ($_SESSION['login_method'] ?? 'regular') : null;

// Determine correct paths based on current file location
$current_file = $_SERVER['SCRIPT_FILENAME'];
$is_in_pages = (strpos($current_file, '/pages/') !== false);
$is_in_admin = (strpos($current_file, '/admin/') !== false);

if ($is_in_pages) {
    // We're in pages/ directory - go up one level to root
    $base_path = '../';
    $pages_path = ''; // We're already in pages
} elseif ($is_in_admin) {
    // We're in admin/ directory - go up one level to root
    $base_path = '../';
    $pages_path = '../pages/';
} else {
    // We're in root directory
    $base_path = '';
    $pages_path = 'pages/';
}
?>
<header>
    <nav>
        <a href="<?php echo $base_path; ?>index.php" class="logo">
            <i class="fas fa-paw"></i>
            <span>Pawfect Home</span>
        </a>
        
        <ul class="nav-links">
            <li><a href="<?php echo $base_path; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="<?php echo $pages_path; ?>dogs.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dogs.php' ? 'active' : ''; ?>">Browse Dogs</a></li>
           
            
            <?php if ($is_logged_in): ?>
                <?php if ($user_role === 'admin'): ?>
                    <li><a href="<?php echo $base_path; ?>admin/index.php">Admin Dashboard</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $pages_path; ?>my_requests.php">My Requests</a></li>
             <li><a href="<?php echo $pages_path; ?>volunteer.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'volunteer.php' ? 'active' : ''; ?>">Volunteer</a></li>
                <?php endif; ?>
            
                
                <div class="user-menu">
                    <div class="user-info">
                        <?php if ($avatar): ?>
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="User Avatar" class="user-avatar" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 8px;">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                        <span><?php echo $username ? htmlspecialchars($username) : 'User'; ?></span>
                        <?php if ($login_method === 'firebase'): ?>
                            <span class="login-badge" style="background: #4285f4; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.7rem; margin-left: 5px;">Google</span>
                        <?php endif; ?>
                        <span class="user-role <?php echo $user_role; ?>">
                            <?php echo $user_role ? ucfirst($user_role) : 'Member'; ?>
                        </span>
                    </div>
                    <a href="<?php echo $pages_path; ?>logout.php" class="btn btn-outline">Logout</a>
                </div>
            <?php else: ?>
                <li><a href="<?php echo $pages_path; ?>login.php">Login</a></li>
                <li><a href="<?php echo $pages_path; ?>register.php" class="btn btn-primary">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>