<?php
// pages/my_requests.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Check if user is regular user (not admin)
$user_data = get_current_user_data();
if ($user_data && $user_data['role'] === 'admin') {
    header('Location: ../admin/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get user's adoption requests
    $stmt = $pdo->prepare("
        SELECT ar.*, d.name as dog_name, d.breed, d.image_path, d.size, d.age, d.gender, d.status as dog_status
        FROM adoption_requests ar 
        JOIN dogs d ON ar.dog_id = d.dog_id 
        WHERE ar.user_id = ? 
        ORDER BY ar.request_date DESC
    ");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();
    
    // Get request statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
        FROM adoption_requests 
        WHERE user_id = ?
    ");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    error_log("Error fetching requests: " . $e->getMessage());
    $requests = [];
    $stats = ['total_requests' => 0, 'pending_requests' => 0, 'approved_requests' => 0, 'rejected_requests' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Adoption Requests - Pawfect Home</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .requests-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #4e8df5;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4e8df5;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .action-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 2rem;
            border-left: 4px solid #28a745;
        }
        
        .btn-primary {
            background: #4e8df5;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #357abd;
            color: white;
            text-decoration: none;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }
        
        .btn-success:hover {
            background: #218838;
            color: white;
            text-decoration: none;
        }
        
        .request-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #4e8df5;
            transition: transform 0.2s ease;
        }
        
        .request-card:hover {
            transform: translateY(-2px);
        }
        
        .request-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .dog-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .request-details {
            flex: 1;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending { 
            background: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeaa7;
        }
        .status-approved { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .status-rejected { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        .status-under_review { 
            background: #cce7ff; 
            color: #004085; 
            border: 1px solid #b3d7ff;
        }
        
        .no-requests {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .request-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .request-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .dog-status {
            font-size: 0.9rem;
            padding: 2px 8px;
            border-radius: 4px;
            background: #e9ecef;
            color: #495057;
        }
        
        .dog-available {
            background: #d4edda;
            color: #155724;
        }
        
        .dog-adopted {
            background: #f8d7da;
            color: #721c24;
        }
        
        .request-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #4e8df5;
            color: #4e8df5;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-outline:hover {
            background: #4e8df5;
            color: white;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .request-header {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="requests-container">
            <div class="page-header">
                <h1>My Adoption Requests</h1>
                <p>Manage and track your adoption applications</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending_requests']; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['approved_requests']; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['rejected_requests']; ?></div>
                    <div class="stat-label">Not Approved</div>
                </div>
            </div>
            
            <!-- Action Section -->
            <div class="action-section">
                <h3>Ready to Adopt Another Furry Friend?</h3>
                <p>Browse our available dogs and find your perfect companion</p>
                <a href="dogs.php" class="btn-success">
                    <i class="fas fa-search"></i> Browse Available Dogs
                </a>
                <a href="../index.php" class="btn-outline" style="margin-left: 10px;">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
            
            <?php if (empty($requests)): ?>
                <div class="no-requests">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h3>No Adoption Requests Yet</h3>
                    <p>You haven't made any adoption requests yet. Start your adoption journey today!</p>
                    <div style="margin-top: 1.5rem;">
                        <a href="dogs.php" class="btn-primary">
                            <i class="fas fa-paw"></i> Find Your Perfect Dog
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <h2>Your Adoption Applications (<?php echo count($requests); ?>)</h2>
                <div class="requests-list">
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <img src="../<?php echo htmlspecialchars($request['image_path'] ?? 'images/placeholder-dog.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($request['dog_name']); ?>" 
                                     class="dog-image"
                                     onerror="this.src='../images/placeholder-dog.jpg'">
                                <div class="request-details">
                                    <div style="display: flex; justify-content: between; align-items: start; gap: 1rem;">
                                        <div style="flex: 1;">
                                            <h3 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($request['dog_name']); ?></h3>
                                            <p style="margin: 0 0 0.5rem 0;">
                                                <i class="fas fa-dog"></i> <?php echo htmlspecialchars($request['breed']); ?> • 
                                                <i class="fas fa-birthday-cake"></i> <?php echo htmlspecialchars($request['age']); ?> years • 
                                                <i class="fas <?php echo $request['gender'] === 'male' ? 'fa-mars' : 'fa-venus'; ?>"></i> <?php echo ucfirst(htmlspecialchars($request['gender'])); ?> • 
                                                <i class="fas fa-weight"></i> <?php echo ucfirst(htmlspecialchars($request['size'])); ?>
                                            </p>
                                        </div>
                                        <div style="text-align: right;">
                                            <span class="status-badge status-<?php echo htmlspecialchars($request['status']); ?>">
                                                <?php 
                                                $status = $request['status'];
                                                if ($status === 'under_review') {
                                                    echo 'Under Review';
                                                } else {
                                                    echo ucfirst(htmlspecialchars($status));
                                                }
                                                ?>
                                            </span>
                                            <br>
                                            <span class="dog-status <?php echo $request['dog_status'] === 'available' ? 'dog-available' : 'dog-adopted'; ?>">
                                                Dog: <?php echo ucfirst($request['dog_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Application Details -->
                            <?php if ($request['living_situation'] || $request['previous_experience'] || $request['family_members']): ?>
                            <div class="request-info">
                                <h4 style="margin-top: 0;"><i class="fas fa-file-alt"></i> Application Details</h4>
                                <div class="info-grid">
                                    <?php if ($request['living_situation']): ?>
                                        <div>
                                            <strong><i class="fas fa-home"></i> Living Situation:</strong><br>
                                            <?php echo htmlspecialchars($request['living_situation']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['previous_experience']): ?>
                                        <div>
                                            <strong><i class="fas fa-history"></i> Previous Experience:</strong><br>
                                            <?php echo htmlspecialchars($request['previous_experience']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['family_members']): ?>
                                        <div>
                                            <strong><i class="fas fa-users"></i> Family Members:</strong><br>
                                            <?php echo htmlspecialchars($request['family_members']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['other_pets']): ?>
                                        <div>
                                            <strong><i class="fas fa-paw"></i> Other Pets:</strong><br>
                                            <?php echo htmlspecialchars($request['other_pets']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['work_schedule']): ?>
                                        <div>
                                            <strong><i class="fas fa-briefcase"></i> Work Schedule:</strong><br>
                                            <?php echo htmlspecialchars($request['work_schedule']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['adoption_reason']): ?>
                                        <div>
                                            <strong><i class="fas fa-heart"></i> Adoption Reason:</strong><br>
                                            <?php echo htmlspecialchars($request['adoption_reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($request['additional_notes']): ?>
                                    <div style="margin-top: 1rem;">
                                        <strong><i class="fas fa-sticky-note"></i> Additional Notes:</strong><br>
                                        <?php echo htmlspecialchars($request['additional_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="request-meta">
                                <div>
                                    <strong><i class="fas fa-calendar"></i> Request Date:</strong><br>
                                    <?php echo date('F j, Y g:i A', strtotime($request['request_date'])); ?>
                                </div>
                                
                                <?php if ($request['updated_at'] && $request['updated_at'] != $request['request_date']): ?>
                                    <div>
                                        <strong><i class="fas fa-sync"></i> Last Updated:</strong><br>
                                        <?php echo date('F j, Y g:i A', strtotime($request['updated_at'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($request['admin_comment']): ?>
                                    <div style="grid-column: 1 / -1; background: #fff3cd; padding: 1rem; border-radius: 5px; margin-top: 1rem;">
                                        <strong><i class="fas fa-comment"></i> Admin Comment:</strong><br>
                                        <?php echo htmlspecialchars($request['admin_comment']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="request-actions">
                                <a href="dogs.php" class="btn-outline">
                                    <i class="fas fa-search"></i> Browse More Dogs
                                </a>
                                <?php if ($request['status'] === 'rejected' || $request['dog_status'] === 'adopted'): ?>
                                    <a href="dogs.php" class="btn-primary">
                                        <i class="fas fa-redo"></i> Apply for Another Dog
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>