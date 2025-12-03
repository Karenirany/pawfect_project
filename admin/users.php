<?php
// admin/users.php
session_start();
require_once '../includes/db.php';

// Simple sanitize function
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Handle form actions
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? 0;

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    try {
        // Check if user exists and get username for message
        $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Prevent admin from deleting their own account
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['error'] = 'You cannot delete your own account.';
            } else {
                // Delete user's adoption requests first (foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM adoption_requests WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $_SESSION['success'] = "User '{$user['username']}' deleted successfully.";
            }
        } else {
            $_SESSION['error'] = 'User not found.';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error deleting user. Please try again.';
        error_log("Delete user error: " . $e->getMessage());
    }
    header('Location: users.php');
    exit;
}

// Toggle user active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    try {
        // Prevent admin from deactivating their own account
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'You cannot deactivate your own account.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Get updated user info for message
            $stmt = $pdo->prepare("SELECT username, is_active FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $status = $user['is_active'] ? 'activated' : 'deactivated';
            $_SESSION['success'] = "User '{$user['username']}' {$status} successfully.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error updating user status. Please try again.';
        error_log("Toggle user status error: " . $e->getMessage());
    }
    header('Location: users.php');
    exit;
}

// Update user role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'] ?? '';
    
    // Validate role
    $allowed_roles = ['admin', 'user'];
    if (!in_array($new_role, $allowed_roles)) {
        $_SESSION['error'] = 'Invalid role specified.';
        header('Location: users.php');
        exit;
    }
    
    try {
        // Prevent admin from changing their own role
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'You cannot change your own role.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->execute([$new_role, $user_id]);
            
            // Get user info for message
            $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $_SESSION['success'] = "User '{$user['username']}' role updated to {$new_role}.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error updating user role. Please try again.';
        error_log("Update user role error: " . $e->getMessage());
    }
    header('Location: users.php');
    exit;
}

// Edit user (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = (int)$_POST['user_id'];
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone_number = sanitize_input($_POST['phone_number'] ?? '');
    
    // Basic validation
    if (!empty($username) && !empty($email)) {
        try {
            // Check if username or email already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
            $stmt->execute([$username, $email, $user_id]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                $_SESSION['error'] = 'Username or email already exists.';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, phone_number = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $email, $phone_number, $user_id]);
                
                $_SESSION['success'] = 'User updated successfully!';
                header('Location: users.php');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error updating user. Please try again.';
            error_log("Edit user error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields.';
    }
}

// Get all users with additional stats
try {
    $stmt = $pdo->query("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM adoption_requests ar WHERE ar.user_id = u.user_id) as request_count,
            (SELECT COUNT(*) FROM adoption_requests ar WHERE ar.user_id = u.user_id AND ar.status = 'approved') as approved_count
        FROM users u 
        ORDER BY u.registration_date DESC
    ");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
    error_log("Error fetching users: " . $e->getMessage());
}

// Get specific user for editing
$edit_user = null;
if ($action === 'edit' && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $edit_user = $stmt->fetch();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error fetching user details.';
        error_log("Error fetching user: " . $e->getMessage());
    }
}

// Get user statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch()['total_users'];
    
    // Active users
    $stmt = $pdo->query("SELECT COUNT(*) as active_users FROM users WHERE is_active = 1");
    $active_users = $stmt->fetch()['active_users'];
    
    // Admin users
    $stmt = $pdo->query("SELECT COUNT(*) as admin_users FROM users WHERE role = 'admin'");
    $admin_users = $stmt->fetch()['admin_users'];
    
    // New users this month
    $stmt = $pdo->query("SELECT COUNT(*) as new_users FROM users WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $new_users = $stmt->fetch()['new_users'];
} catch (Exception $e) {
    $total_users = $active_users = $admin_users = $new_users = 0;
    error_log("Error fetching user statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #bdc3c7;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-outline { background: transparent; border: 2px solid #3498db; color: #3498db; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .users-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        tr:hover {
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .role-admin { background: #ffeaa7; color: #856404; }
        .role-user { background: #d1ecf1; color: #0c5460; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-buttons .btn {
            flex: 1;
            text-align: center;
        }
        
        /* Form Styles */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-users"></i> Manage Users</h1>
            <div>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_users; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $admin_users; ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $new_users; ?></div>
                <div class="stat-label">New This Month</div>
            </div>
        </div>

        <!-- Edit User Form -->
        <?php if ($action === 'edit' && $edit_user): ?>
        <div class="form-container">
            <h2><i class="fas fa-edit"></i> Edit User: <?php echo htmlspecialchars($edit_user['username']); ?></h2>
            
            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_user['phone_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Current Role</label>
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <span class="role-badge role-<?php echo $edit_user['role']; ?>">
                                <?php echo ucfirst($edit_user['role']); ?>
                            </span>
                            <small style="display: block; margin-top: 5px; color: #7f8c8d;">
                                Use the role update feature below to change roles
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="edit_user" class="btn btn-success">
                        <i class="fas fa-save"></i> Update User
                    </button>
                    <a href="users.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="users-table">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Requests</th>
                            <th>Registration</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                                    <h3>No users found</h3>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if (!empty($user['avatar'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                                 alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                                 class="user-avatar"
                                                 onerror="this.style.display='none'">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                                <span style="color: #3498db; font-size: 0.8rem;">(You)</span>
                                            <?php endif; ?>
                                            <br>
                                            <small style="color: #7f8c8d;">
                                                <?php echo $user['login_method'] ?? 'email'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($user['email']); ?></div>
                                    <?php if (!empty($user['phone_number'])): ?>
                                        <small style="color: #7f8c8d;"><?php echo htmlspecialchars($user['phone_number']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="margin-top: 5px;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <select name="role" onchange="this.form.submit()" style="font-size: 0.8rem; padding: 2px 5px;">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <input type="hidden" name="update_role" value="1">
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="margin-top: 5px;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm btn-warning">
                                                <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>Total: <?php echo $user['request_count']; ?></div>
                                    <small style="color: #27ae60;">Approved: <?php echo $user['approved_count']; ?></small>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($user['registration_date'])); ?>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="users.php?action=edit&id=<?php echo $user['user_id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($user['username']); ?>? This will also delete all their adoption requests.')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>