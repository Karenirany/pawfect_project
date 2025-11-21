<?php
// index.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get featured dogs (available dogs)
$featured_dogs = [];
try {
    // Check if $pdo is set and connected
    if (isset($pdo)) {
        $stmt = $pdo->prepare("
            SELECT * FROM dogs 
            WHERE status = 'available' 
            ORDER BY dog_id DESC 
            LIMIT 6
        ");
        $stmt->execute();
        $featured_dogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new Exception("Database connection not available");
    }
} catch (Exception $e) {
    error_log("Error fetching featured dogs: " . $e->getMessage());
    $featured_dogs = []; // Ensure it's an empty array on error
}

// Check if user is logged in
$is_logged_in = is_logged_in();
$user_role = $is_logged_in ? $_SESSION['role'] : null;
$username = $is_logged_in ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawsome Home - Dog Adoption Center</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Furry Companion</h1>
            <p>Give a loving home to dogs in need. Browse our available dogs and start your adoption journey today.</p>
            <div class="hero-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="pages/dogs.php" class="btn btn-primary">Browse All Dogs</a>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="admin/index.php" class="btn btn-secondary">Admin Dashboard</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="pages/register.php" class="btn btn-primary">Get Started</a>
                    <a href="pages/login.php" class="btn btn-secondary">Login</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-image">
            <img src="images/hero-dog.jpg" alt="Happy Dog" onerror="this.src='images/placeholder-dog.jpg'">
        </div>
    </section>

    <!-- Featured Dogs Section -->
    <section class="featured-dogs">
        <div class="container">
            <h2>Featured Dogs</h2>
            <p class="section-subtitle">Meet some of our wonderful dogs looking for forever homes</p>
            
            <div class="dogs-grid" id="featuredDogs">
                <?php if (empty($featured_dogs)): ?>
                    <div class="no-dogs">
                        <i class="fas fa-paw"></i>
                        <p>No dogs available at the moment. Please check back later.</p>
                        <?php if (is_admin()): ?>
                            <a href="admin/add_dog.php" class="btn btn-primary">Add First Dog</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_dogs as $dog): ?>
                        <div class="dog-card">
                            <div class="dog-image">
                                <img src="<?php echo htmlspecialchars($dog['image_path'] ?? 'images/placeholder-dog.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($dog['name']); ?>"
                                     onerror="this.src='images/placeholder-dog.jpg'">
                                <span class="dog-status <?php echo htmlspecialchars($dog['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($dog['status'])); ?>
                                </span>
                            </div>
                            <div class="dog-info">
                                <h3><?php echo htmlspecialchars($dog['name']); ?></h3>
                                <div class="dog-details">
                                    <span class="breed"><i class="fas fa-dog"></i> <?php echo htmlspecialchars($dog['breed']); ?></span>
                                    <span class="age"><i class="fas fa-birthday-cake"></i> <?php echo htmlspecialchars($dog['age']); ?> years</span>
                                    <span class="gender"><i class="fas <?php echo $dog['gender'] === 'male' ? 'fa-mars' : 'fa-venus'; ?>"></i> <?php echo ucfirst(htmlspecialchars($dog['gender'])); ?></span>
                                    <span class="size"><i class="fas fa-weight"></i> <?php echo ucfirst(htmlspecialchars($dog['size'])); ?></span>
                                </div>
                                <p class="dog-description"><?php echo htmlspecialchars(substr($dog['description'], 0, 100)) . '...'; ?></p>
                                <div class="dog-actions">
                                    <a href="pages/dog_details.php?id=<?php echo $dog['dog_id']; ?>" class="btn btn-outline">View Details</a>
                                    <?php if ($is_logged_in && $user_role === 'user'): ?>
                                        <a href="pages/adoption_request.php?dog_id=<?php echo $dog['dog_id']; ?>" class="btn btn-primary">Adopt Me</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($featured_dogs)): ?>
                <div class="view-all">
                    <a href="pages/dogs.php" class="btn btn-large">View All Available Dogs</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <h2>How Adoption Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>1. Browse Dogs</h3>
                    <p>Look through our available dogs and find your perfect match</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>2. Submit Request</h3>
                    <p>Fill out the adoption application for your chosen dog</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3>3. Welcome Home</h3>
                    <p>Bring your new furry friend home after approval</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
    
</body>
</html>