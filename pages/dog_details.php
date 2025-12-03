<?php
// pages/dog_details.php
session_start();

// Include files with error handling
try {
    require_once '../includes/db.php';
    require_once '../includes/functions.php';
} catch (Exception $e) {
    error_log("Include error: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Check if dog ID is provided and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid dog selection.';
    header('Location: dogs.php');
    exit;
}

$dog_id = (int)$_GET['id'];
$dog = null;
$error = '';
$success = '';

// Get dog details
try {
    $stmt = $pdo->prepare("SELECT * FROM dogs WHERE dog_id = ?");
    $stmt->execute([$dog_id]);
    $dog = $stmt->fetch();
    
    if (!$dog) {
        $_SESSION['error'] = 'Dog not found.';
        header('Location: dogs.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Dog details error: " . $e->getMessage());
    $error = 'Unable to load dog details. Please try again.';
}

// Get additional photos if available
$photos = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM dog_photos WHERE dog_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$dog_id]);
    $photos = $stmt->fetchAll();
} catch (Exception $e) {
    // It's okay if photos table doesn't exist
    error_log("Dog photos error: " . $e->getMessage());
}

// Check if user has already requested this dog
$has_requested = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    try {
        $stmt = $pdo->prepare("SELECT request_id FROM adoption_requests WHERE user_id = ? AND dog_id = ?");
        $stmt->execute([$_SESSION['user_id'], $dog_id]);
        $has_requested = $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Adoption request check error: " . $e->getMessage());
    }
}

// Get similar dogs (same breed)
$similar_dogs = [];
if ($dog) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM dogs 
            WHERE dog_id != ? 
            AND status = 'available' 
            AND breed = ?
            ORDER BY RAND() 
            LIMIT 3
        ");
        $stmt->execute([$dog_id, $dog['breed']]);
        $similar_dogs = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Similar dogs error: " . $e->getMessage());
    }
}

// Display success/error messages from session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dog ? htmlspecialchars($dog['name']) : 'Dog Details'; ?> - Pawfect Home</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #4e8df5;
            text-decoration: none;
            margin: 2rem 0;
            font-weight: 600;
            padding: 0.7rem 1.2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .back-link:hover {
            background: #4e8df5;
            color: white;
            transform: translateX(-5px);
        }
        
        .dog-detail-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }
        
        .dog-detail-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        
        .dog-images {
            position: relative;
            background: #f8f9fa;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            display: block;
        }
        
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            padding: 1rem;
            background: white;
            border-top: 1px solid #e1e5e9;
        }
        
        .gallery-thumb {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .gallery-thumb:hover,
        .gallery-thumb.active {
            border-color: #4e8df5;
            transform: scale(1.05);
        }
        
        .dog-info-detail {
            padding: 2.5rem;
            background: white;
        }
        
        .dog-name {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        
        .dog-breed {
            font-size: 1.5rem;
            color: #4e8df5;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            margin-bottom: 2rem;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
        
        .status-available {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        
        .status-adopted {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .detail-item i {
            color: #4e8df5;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            min-width: 80px;
        }
        
        .detail-value {
            color: #6c757d;
            font-weight: 500;
        }
        
        .dog-description-section {
            margin: 2.5rem 0;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #4e8df5;
        }
        
        .dog-description-section h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }
        
        .dog-description {
            line-height: 1.8;
            color: #495057;
            font-size: 1.05rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            justify-content: center;
        }
        
        .btn-primary {
            background: #4e8df5;
            color: white;
            flex: 1;
        }
        
        .btn-primary:hover {
            background: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 141, 245, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #4e8df5;
            color: #4e8df5;
        }
        
        .btn-outline:hover {
            background: #4e8df5;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .similar-dogs {
            margin: 4rem 0;
        }
        
        .similar-dogs h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 2rem;
        }
        
        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .similar-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .similar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .similar-image {
            height: 200px;
            overflow: hidden;
        }
        
        .similar-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .similar-info {
            padding: 1.5rem;
        }
        
        .similar-info h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .similar-breed {
            color: #4e8df5;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .alert {
            padding: 1.2rem 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .request-status {
            background: #e7f3ff;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            border-left: 4px solid #4e8df5;
        }
        
        .request-status h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 968px) {
            .dog-detail-header {
                grid-template-columns: 1fr;
            }
            
            .main-image {
                height: 400px;
            }
            
            .image-gallery {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .dog-name {
                font-size: 2rem;
            }
            
            .dog-breed {
                font-size: 1.3rem;
            }
            
            .image-gallery {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .similar-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <a href="dogs.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to All Dogs
        </a>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($dog): ?>
            <div class="dog-detail-container">
                <div class="dog-detail-header">
                    <!-- Dog Images -->
                    <div class="dog-images">
                        <img src="../<?php echo htmlspecialchars($dog['image_path'] ?? 'images/placeholder-dog.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($dog['name']); ?>" 
                             class="main-image"
                             id="mainImage"
                             onerror="this.src='../images/placeholder-dog.jpg'">
                        
                        <?php if (!empty($photos)): ?>
                            <div class="image-gallery">
                                <?php foreach ($photos as $index => $photo): ?>
                                    <img src="../<?php echo htmlspecialchars($photo['photo_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($dog['name']); ?>" 
                                         class="gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="changeImage(this, '../<?php echo htmlspecialchars($photo['photo_path']); ?>')">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Dog Information -->
                    <div class="dog-info-detail">
                        <h1 class="dog-name"><?php echo htmlspecialchars($dog['name']); ?></h1>
                        <h2 class="dog-breed"><?php echo htmlspecialchars($dog['breed']); ?></h2>
                        
                        <div class="status-badge status-<?php echo $dog['status']; ?>">
                            <i class="fas fa-<?php echo $dog['status'] === 'available' ? 'heart' : 
                                                   ($dog['status'] === 'pending' ? 'clock' : 'check'); ?>"></i>
                            <?php echo ucfirst($dog['status']); ?>
                        </div>

                        <div class="details-grid">
                            <div class="detail-item">
                                <i class="fas fa-birthday-cake"></i>
                                <span class="detail-label">Age:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($dog['age']); ?> years</span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas <?php echo $dog['gender'] === 'male' ? 'fa-mars' : 'fa-venus'; ?>"></i>
                                <span class="detail-label">Gender:</span>
                                <span class="detail-value"><?php echo ucfirst(htmlspecialchars($dog['gender'])); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-weight"></i>
                                <span class="detail-label">Size:</span>
                                <span class="detail-value"><?php echo ucfirst(htmlspecialchars($dog['size'])); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-paw"></i>
                                <span class="detail-label">Breed:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($dog['breed']); ?></span>
                            </div>
                        </div>

                        <div class="dog-description-section">
                            <h3>About <?php echo htmlspecialchars($dog['name']); ?></h3>
                            <div class="dog-description">
                                <?php 
                                $description = $dog['description'] ?? 'This wonderful dog is looking for a loving forever home. They have a great personality and would make a perfect companion for the right family.';
                                echo nl2br(htmlspecialchars($description));
                                ?>
                            </div>
                        </div>

                        <?php if ($has_requested): ?>
                            <div class="request-status">
                                <h4><i class="fas fa-clock"></i> Adoption Request Submitted</h4>
                                <p>You have already submitted an adoption request for <?php echo htmlspecialchars($dog['name']); ?>. 
                                We will review your application and contact you soon.</p>
                            </div>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin'): ?>
                                <?php if ($dog['status'] === 'available' && !$has_requested): ?>
                                    <a href="adoption_request.php?dog_id=<?php echo $dog_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-heart"></i> Adopt <?php echo htmlspecialchars($dog['name']); ?>
                                    </a>
                                <?php elseif ($has_requested): ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-check"></i> Request Submitted
                                    </button>
                                <?php elseif ($dog['status'] !== 'available'): ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-paw"></i> Not Available for Adoption
                                    </button>
                                <?php endif; ?>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login to Adopt
                                </a>
                            <?php endif; ?>
                            
                            <a href="dogs.php" class="btn btn-outline">
                                <i class="fas fa-search"></i> Browse More Dogs
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Similar Dogs Section -->
            <?php if (!empty($similar_dogs)): ?>
                <div class="similar-dogs">
                    <h2>Similar Dogs You Might Like</h2>
                    <div class="similar-grid">
                        <?php foreach ($similar_dogs as $similar_dog): ?>
                            <div class="similar-card">
                                <div class="similar-image">
                                    <img src="../<?php echo htmlspecialchars($similar_dog['image_path'] ?? 'images/placeholder-dog.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($similar_dog['name']); ?>"
                                         onerror="this.src='../images/placeholder-dog.jpg'">
                                </div>
                                <div class="similar-info">
                                    <h4><?php echo htmlspecialchars($similar_dog['name']); ?></h4>
                                    <div class="similar-breed"><?php echo htmlspecialchars($similar_dog['breed']); ?></div>
                                    <p><strong>Age:</strong> <?php echo htmlspecialchars($similar_dog['age']); ?> years</p>
                                    <p><strong>Size:</strong> <?php echo ucfirst($similar_dog['size']); ?></p>
                                    <div style="margin-top: 1rem;">
                                        <a href="dog_details.php?id=<?php echo $similar_dog['dog_id']; ?>" class="btn btn-outline" style="padding: 0.7rem 1rem; font-size: 0.9rem;">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                Dog not found. Please check the URL and try again.
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        function changeImage(thumb, newSrc) {
            // Update main image
            document.getElementById('mainImage').src = newSrc;
            
            // Update active state
            document.querySelectorAll('.gallery-thumb').forEach(t => {
                t.classList.remove('active');
            });
            thumb.classList.add('active');
        }

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const mainImage = document.getElementById('mainImage');
            if (mainImage) {
                mainImage.addEventListener('click', function() {
                    this.style.transform = this.style.transform === 'scale(1.5)' ? 'scale(1)' : 'scale(1.5)';
                    this.style.transition = 'transform 0.3s ease';
                });
            }
        });
    </script>
</body>
</html>