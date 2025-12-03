<?php
// admin/index.php
// Start session and include files
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Get basic statistics (with error handling)
$total_dogs = $available_dogs = $total_users = $total_requests = $pending_requests = $approved_requests = $rejected_requests = 0;
$recent_requests = [];

try {
    // Total dogs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM dogs");
    $result = $stmt->fetch();
    $total_dogs = $result['count'];
    
    // Available dogs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM dogs WHERE status = 'available'");
    $result = $stmt->fetch();
    $available_dogs = $result['count'];
    
    // Total users (non-admin)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $result = $stmt->fetch();
    $total_users = $result['count'];
    
    // Total adoption requests
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM adoption_requests");
    $result = $stmt->fetch();
    $total_requests = $result['count'];
    
    // Pending requests
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM adoption_requests WHERE status = 'pending'");
    $result = $stmt->fetch();
    $pending_requests = $result['count'];
    
    // Approved requests
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM adoption_requests WHERE status = 'approved'");
    $result = $stmt->fetch();
    $approved_requests = $result['count'];
    
    // Rejected requests
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM adoption_requests WHERE status = 'rejected'");
    $result = $stmt->fetch();
    $rejected_requests = $result['count'];
    
    // Recent requests (last 5) - ALL STATUSES
    $stmt = $pdo->query("
        SELECT ar.*, d.name as dog_name, u.username as user_name 
        FROM adoption_requests ar 
        JOIN dogs d ON ar.dog_id = d.dog_id 
        JOIN users u ON ar.user_id = u.user_id 
        ORDER BY ar.request_date DESC 
        LIMIT 5
    ");
    $recent_requests = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Log error but don't break the page
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pawfect Home</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        
        .sidebar-header h1 {
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-info {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        
        .admin-name {
            font-weight: bold;
            color: #ecf0f1;
        }
        
        .nav-links {
            padding: 0 10px;
        }
        
        .nav-item {
            display: block;
            padding: 12px 15px;
            color: #bdc3c7;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-item:hover, .nav-item.active {
            background: #3498db;
            color: white;
        }
        
        .nav-item i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            flex: 1;
            background: #ecf0f1;
        }
        
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #bdc3c7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .dashboard-content {
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .action-icon {
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .btn-action {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 10px;
        }
        
        .recent-activity {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; } /* FIXED: Using 'rejected' */
    </style>
</head>
<body>
   

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>Dashboard Overview</h1>
                <a href="../pages/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
             
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_dogs; ?></div>
                        <div class="stat-label">Total Dogs</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $available_dogs; ?></div>
                        <div class="stat-label">Available Dogs</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_users; ?></div>
                        <div class="stat-label">Registered Users</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_requests; ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $pending_requests; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $approved_requests; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $rejected_requests; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-dog"></i>
                        </div>
                        <h3>Manage Dogs</h3>
                        <p>Add, edit, or remove dogs from the adoption listings</p>
                        <a href="dogs.php" class="btn-action">Manage Dogs</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Manage Users</h3>
                        <p>View and manage user accounts and permissions</p>
                        <a href="users.php" class="btn-action">Manage Users</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Adoption Requests</h3>
                        <p>Review and process adoption applications</p>
                        <a href="requests.php" class="btn-action">View Requests</a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Recent Adoption Requests</h2>
                        <a href="requests.php" class="btn-action" style="margin: 0;">
                            <i class="fas fa-list"></i> View All
                        </a>
                    </div>
                    
                    <?php if (empty($recent_requests)): ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 40px 0;">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                            No recent adoption requests.
                        </p>
                    <?php else: ?>
                        <?php foreach ($recent_requests as $request): ?>
                            <div class="activity-item">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($request['user_name']); ?></strong> 
                                        applied for <strong><?php echo htmlspecialchars($request['dog_name']); ?></strong>
                                        <br>
                                        <small style="color: #7f8c8d;">
                                            <?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?>
                                        </small>
                                    </div>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- View All button at the bottom as well -->
                        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                            <a href="requests.php" class="btn-action">
                                <i class="fas fa-list"></i> View All Requests
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>