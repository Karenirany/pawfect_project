<?php
// admin/requests.php
session_start();
require_once '../includes/db.php';

// Include PHPMailer and email config
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../includes/email_config.php';

// Simple sanitize function
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Enhanced Email function with PHPMailer
function sendAdoptionDecisionEmail($user_email, $user_name, $dog_name, $decision, $admin_comment = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings from config
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = 0; // Set to 2 for debugging
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($user_email, $user_name);
        $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
        
        // Set charset to UTF-8 for emoji support
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Adoption Request Update - $dog_name";
        
        if ($decision === 'approved') {
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #27ae60; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                    .approved { color: #27ae60; font-weight: bold; }
                    .comment { background: #fff; padding: 15px; border-left: 4px solid #3498db; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🎉 Adoption Request Approved!</h1>
                    </div>
                    <div class='content'>
                        <p>Dear $user_name,</p>
                        <p>We are thrilled to inform you that your adoption request for <strong>$dog_name</strong> has been <span class='approved'>APPROVED</span>!</p>
                        <p>Our team will contact you shortly to arrange the next steps for bringing your new furry friend home.</p>
                        " . (!empty($admin_comment) ? "
                        <div class='comment'>
                            <strong>Additional Notes from our team:</strong><br>
                            $admin_comment
                        </div>
                        " : "") . "
                        <p>Thank you for choosing to adopt and give a loving home to a dog in need.</p>
                        <p>Best regards,<br>The Adoption Team</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->AltBody = "Dear $user_name,\n\nWe are thrilled to inform you that your adoption request for $dog_name has been APPROVED!\n\nOur team will contact you shortly to arrange the next steps.\n\n" . 
                            (!empty($admin_comment) ? "Additional Notes: $admin_comment\n\n" : "") . 
                            "Thank you for choosing to adopt!\n\nBest regards,\nThe Adoption Team";
            
        } else {
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #e74c3c; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                    .rejected { color: #e74c3c; font-weight: bold; }
                    .comment { background: #fff; padding: 15px; border-left: 4px solid #e74c3c; margin: 15px 0; }
                    .encouragement { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Adoption Request Update</h1>
                    </div>
                    <div class='content'>
                        <p>Dear $user_name,</p>
                        <p>After careful consideration, we regret to inform you that your adoption request for <strong>$dog_name</strong> has been <span class='rejected'>REJECTED</span> at this time.</p>
                        " . (!empty($admin_comment) ? "
                        <div class='comment'>
                            <strong>Reason:</strong><br>
                            $admin_comment
                        </div>
                        " : "") . "
                        <div class='encouragement'>
                            <p>We encourage you to:</p>
                            <ul>
                                <li>Review other available dogs that might be a better fit</li>
                                <li>Consider different breeds or ages that match your lifestyle</li>
                                <li>Contact us if you have questions about this decision</li>
                            </ul>
                        </div>
                        <p>Thank you for your understanding and for considering adoption.</p>
                        <p>Best regards,<br>The Adoption Team</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->AltBody = "Dear $user_name,\n\nAfter careful consideration, your adoption request for $dog_name has been REJECTED at this time.\n\n" . 
                            (!empty($admin_comment) ? "Reason: $admin_comment\n\n" : "") . 
                            "We encourage you to review other available dogs on our website.\n\nThank you for your understanding.\n\nBest regards,\nThe Adoption Team";
        }
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Handle form actions
$action = $_GET['action'] ?? '';
$request_id = $_GET['id'] ?? 0;

// Update request status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['approve_request']) || isset($_POST['reject_request']))) {
    $request_id = (int)$_POST['request_id'];
    $admin_comment = sanitize_input($_POST['admin_comment'] ?? '');
    $new_status = isset($_POST['approve_request']) ? 'approved' : 'rejected';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get request details with user and dog information
        $stmt = $pdo->prepare("
            SELECT ar.*, u.username, u.email, d.name as dog_name, d.dog_id, d.status as dog_status
            FROM adoption_requests ar 
            JOIN users u ON ar.user_id = u.user_id 
            JOIN dogs d ON ar.dog_id = d.dog_id 
            WHERE ar.request_id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            throw new Exception('Adoption request not found.');
        }
        
        // Update request status
        $stmt = $pdo->prepare("
            UPDATE adoption_requests 
            SET status = ?, admin_comment = ? 
            WHERE request_id = ?
        ");
        $stmt->execute([$new_status, $admin_comment, $request_id]);
        
        // Handle dog status based on request decision
        if ($new_status === 'approved') {
            // Set dog status to adopted
            $stmt = $pdo->prepare("UPDATE dogs SET status = 'adopted' WHERE dog_id = ?");
            $stmt->execute([$request['dog_id']]);
            
            // Set all other pending requests for this dog to rejected
            $stmt = $pdo->prepare("
                UPDATE adoption_requests 
                SET status = 'rejected', 
                    admin_comment = CONCAT('This request was automatically rejected because ', ? , ' was approved for adoption.')
                WHERE dog_id = ? AND request_id != ? AND status = 'pending'
            ");
            $auto_comment = $request['username'] . "'s request";
            $stmt->execute([$auto_comment, $request['dog_id'], $request_id]);
            
        } else { // rejected
            // Check if there are any other pending requests for this dog
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as pending_count 
                FROM adoption_requests 
                WHERE dog_id = ? AND status = 'pending' AND request_id != ?
            ");
            $stmt->execute([$request['dog_id'], $request_id]);
            $pending_count = $stmt->fetch()['pending_count'];
            
            // If no other pending requests and dog wasn't already adopted, set to available
            if ($pending_count == 0 && $request['dog_status'] !== 'adopted') {
                $stmt = $pdo->prepare("UPDATE dogs SET status = 'available' WHERE dog_id = ?");
                $stmt->execute([$request['dog_id']]);
            }
        }
        
        // Send email notification
        $email_sent = sendAdoptionDecisionEmail(
            $request['email'],
            $request['username'],
            $request['dog_name'],
            $new_status,
            $admin_comment
        );
        
        // Commit transaction
        $pdo->commit();
        
        if ($email_sent) {
            $_SESSION['success'] = "Adoption request {$new_status} successfully. Email notification sent to {$request['email']}.";
        } else {
            $_SESSION['success'] = "Adoption request {$new_status} successfully. <strong>Note:</strong> Email notification failed to send.";
            $_SESSION['warning'] = "The request was processed but the email failed to send. You may want to contact {$request['username']} manually at {$request['email']}.";
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error updating adoption request. Please try again.';
        error_log("Update adoption request error: " . $e->getMessage());
    }
    
    header('Location: requests.php');
    exit;
}

// Delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    $request_id = (int)$_POST['request_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM adoption_requests WHERE request_id = ?");
        $stmt->execute([$request_id]);
        $_SESSION['success'] = 'Adoption request deleted successfully.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error deleting adoption request. Please try again.';
        error_log("Delete adoption request error: " . $e->getMessage());
    }
    header('Location: requests.php');
    exit;
}

// Get all adoption requests with user and dog information
try {
    $stmt = $pdo->query("
        SELECT 
            ar.*,
            u.username,
            u.email,
            u.phone_number,
            d.name as dog_name,
            d.breed,
            d.age,
            d.gender,
            d.image_path,
            d.status as dog_status
        FROM adoption_requests ar
        JOIN users u ON ar.user_id = u.user_id
        JOIN dogs d ON ar.dog_id = d.dog_id
        ORDER BY ar.request_date DESC
    ");
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    $requests = [];
    error_log("Error fetching adoption requests: " . $e->getMessage());
}

// Get request statistics
try {
    // Total requests
    $stmt = $pdo->query("SELECT COUNT(*) as total_requests FROM adoption_requests");
    $total_requests = $stmt->fetch()['total_requests'];
    
    // Pending requests
    $stmt = $pdo->query("SELECT COUNT(*) as pending_requests FROM adoption_requests WHERE status = 'pending'");
    $pending_requests = $stmt->fetch()['pending_requests'];
    
    // Approved requests
    $stmt = $pdo->query("SELECT COUNT(*) as approved_requests FROM adoption_requests WHERE status = 'approved'");
    $approved_requests = $stmt->fetch()['approved_requests'];
    
    // Rejected requests
    $stmt = $pdo->query("SELECT COUNT(*) as rejected_requests FROM adoption_requests WHERE status = 'rejected'");
    $rejected_requests = $stmt->fetch()['rejected_requests'];
} catch (Exception $e) {
    $total_requests = $pending_requests = $approved_requests = $rejected_requests = 0;
    error_log("Error fetching request statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Adoption Requests - Admin</title>
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
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
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
        
        .requests-container {
            display: grid;
            gap: 20px;
        }
        .request-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .request-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .request-body {
            padding: 20px;
        }
        .request-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 15px;
            align-items: start;
        }
        .dog-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
        }
        .request-info {
            display: grid;
            gap: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .info-item strong {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .close {
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
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
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        
        @media (max-width: 768px) {
            .request-details {
                grid-template-columns: 1fr;
            }
            .dog-image {
                width: 100%;
                height: 200px;
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
            <h1><i class="fas fa-clipboard-list"></i> Manage Adoption Requests</h1>
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

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['warning']; ?>
            </div>
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>

        <!-- Request Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_requests; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_requests; ?></div>
                <div class="stat-label">Pending Review</div>
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

        <!-- Adoption Requests -->
        <div class="requests-container">
            <?php if (empty($requests)): ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <i class="fas fa-clipboard-list" style="font-size: 4rem; margin-bottom: 15px; opacity: 0.5;"></i>
                    <h3>No adoption requests found</h3>
                    <p>When users submit adoption requests, they will appear here for review.</p>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                <div class="request-card">
                    <div class="request-header">
                        <div>
                            <h3 style="margin: 0; color: #2c3e50;">
                                <?php echo htmlspecialchars($request['username']); ?> → 
                                <?php echo htmlspecialchars($request['dog_name']); ?>
                            </h3>
                            <p style="margin: 5px 0 0 0; color: #7f8c8d;">
                                Submitted: <?php echo date('F j, Y g:i A', strtotime($request['request_date'])); ?>
                            </p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                            <span class="status-badge" style="background: #d6eaf8; color: #2c3e50;">
                                Dog: <?php echo ucfirst($request['dog_status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="request-body">
                        <div class="request-details">
                            <img src="../<?php echo $request['image_path']; ?>" 
                                 alt="<?php echo htmlspecialchars($request['dog_name']); ?>" 
                                 class="dog-image"
                                 onerror="this.src='../images/placeholder-dog.jpg'">
                            
                            <div class="request-info">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <strong>Applicant Contact</strong>
                                        <div><?php echo htmlspecialchars($request['email']); ?></div>
                                        <?php if (!empty($request['phone_number'])): ?>
                                            <div><?php echo htmlspecialchars($request['phone_number']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="info-item">
                                        <strong>Dog Information</strong>
                                        <div><?php echo htmlspecialchars($request['dog_name']); ?> - <?php echo htmlspecialchars($request['breed']); ?></div>
                                        <div><?php echo $request['age']; ?> years old, <?php echo ucfirst($request['gender']); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <strong>Living Situation</strong>
                                        <div><?php echo htmlspecialchars($request['living_situation'] ?? 'Not specified'); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <strong>Family Members</strong>
                                        <div><?php echo htmlspecialchars($request['family_members'] ?? 'Not specified'); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($request['work_schedule'])): ?>
                                <div class="info-item">
                                    <strong>Work Schedule</strong>
                                    <div><?php echo htmlspecialchars($request['work_schedule']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($request['previous_experience'])): ?>
                                <div class="info-item">
                                    <strong>Previous Pet Experience</strong>
                                    <div><?php echo htmlspecialchars($request['previous_experience']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($request['other_pets'])): ?>
                                <div class="info-item">
                                    <strong>Other Pets</strong>
                                    <div><?php echo htmlspecialchars($request['other_pets']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <strong>Adoption Reason</strong>
                                    <div><?php echo htmlspecialchars($request['adoption_reason']); ?></div>
                                </div>
                                
                                <?php if (!empty($request['additional_notes'])): ?>
                                <div class="info-item">
                                    <strong>Additional Notes</strong>
                                    <div><?php echo htmlspecialchars($request['additional_notes']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($request['admin_comment'])): ?>
                                <div class="info-item" style="background: #fff3cd;">
                                    <strong>Admin Comment</strong>
                                    <div><?php echo htmlspecialchars($request['admin_comment']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="action-buttons" style="margin-top: 20px;">
                            <?php if ($request['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-success" onclick="openDecisionModal(<?php echo $request['request_id']; ?>, 'approve')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger" onclick="openDecisionModal(<?php echo $request['request_id']; ?>, 'reject')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <button type="submit" name="delete_request" class="btn btn-outline" 
                                        onclick="return confirm('Are you sure you want to delete this adoption request? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Decision Modal -->
    <div id="decisionModal" class="modal">
        <div class="modal-content">
            <form method="POST" id="decisionForm">
                <input type="hidden" name="request_id" id="modalRequestId">
                <input type="hidden" name="approve_request" id="approveInput">
                <input type="hidden" name="reject_request" id="rejectInput">
                
                <div class="modal-header">
                    <h3 id="modalTitle">Make Decision</h3>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="admin_comment">Comment (Optional)</label>
                        <textarea id="admin_comment" name="admin_comment" class="form-control" 
                                  placeholder="Add a comment that will be included in the email notification to the user..."></textarea>
                        <small style="color: #7f8c8d;">This comment will be sent to the user via email.</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn" id="modalSubmitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDecisionModal(requestId, decision) {
            const modal = document.getElementById('decisionModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalSubmitBtn = document.getElementById('modalSubmitBtn');
            const approveInput = document.getElementById('approveInput');
            const rejectInput = document.getElementById('rejectInput');
            
            document.getElementById('modalRequestId').value = requestId;
            
            if (decision === 'approve') {
                modalTitle.textContent = 'Approve Adoption Request';
                modalSubmitBtn.textContent = 'Approve Request';
                modalSubmitBtn.className = 'btn btn-success';
                approveInput.name = 'approve_request';
                rejectInput.name = '';
            } else {
                modalTitle.textContent = 'Reject Adoption Request';
                modalSubmitBtn.textContent = 'Reject Request';
                modalSubmitBtn.className = 'btn btn-danger';
                rejectInput.name = 'reject_request';
                approveInput.name = '';
            }
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('decisionModal').style.display = 'none';
            document.getElementById('admin_comment').value = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('decisionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
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