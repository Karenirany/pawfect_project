<?php
// pages/dogs.php
session_start();

// Include files with error handling
try {
    require_once '../includes/db.php';
    require_once '../includes/functions.php';
} catch (Exception $e) {
    error_log("Include error: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Initialize variables
$dogs = [];
$breeds = [];
$error = '';

// Get filter parameters safely
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$breed_filter = isset($_GET['breed']) ? trim($_GET['breed']) : '';
$size = isset($_GET['size']) ? trim($_GET['size']) : '';
$gender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : 'available';

// Build query safely
try {
    // Get available breeds first
    $breed_stmt = $pdo->query("SELECT DISTINCT breed FROM dogs WHERE breed IS NOT NULL AND breed != '' ORDER BY breed");
    $breeds = $breed_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Breeds query error: " . $e->getMessage());
    $breeds = [];
}

// Build main query
try {
    $sql = "SELECT * FROM dogs WHERE 1=1";
    $params = [];
    
    // Add search filter
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR breed LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Add breed filter
    if (!empty($breed_filter)) {
        $sql .= " AND breed = ?";
        $params[] = $breed_filter;
    }
    
    // Add size filter
    if (!empty($size)) {
        $sql .= " AND size = ?";
        $params[] = $size;
    }
    
    // Add gender filter
    if (!empty($gender)) {
        $sql .= " AND gender = ?";
        $params[] = $gender;
    }
    
    // Add status filter
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY dog_id DESC";
    
    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dogs = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dogs query error: " . $e->getMessage());
    $error = "Unable to load dogs. Please try again later.";
    $dogs = [];
}

// Check user requests if logged in
$user_requests = [];
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    try {
        $request_stmt = $pdo->prepare("SELECT dog_id FROM adoption_requests WHERE user_id = ? AND status = 'pending'");
        $request_stmt->execute([$_SESSION['user_id']]);
        $user_requests = $request_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("User requests error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Dogs - Pawfect Home</title>
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
        
        .page-header {
            text-align: center;
            padding: 2rem 0;
            color: white;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            font-size: 1.1rem;
            color: black;
            opacity: 0.9;
        }
        
        .filters-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #4e8df5;
            box-shadow: 0 0 0 3px rgba(78, 141, 245, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            padding-top: 1rem;
            border-top: 1px solid #e1e5e9;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
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
        
        .results-count {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 600;
            margin-left: auto;
        }
        
        .dogs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .dog-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .dog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .dog-image {
            position: relative;
            height: 250px;
            overflow: hidden;
        }
        
        .dog-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .dog-card:hover .dog-image img {
            transform: scale(1.05);
        }
        
        .dog-status {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-available {
            background: #27ae60;
            color: white;
        }
        
        .status-pending {
            background: #f39c12;
            color: white;
        }
        
        .status-adopted {
            background: #7f8c8d;
            color: white;
        }
        
        .dog-info {
            padding: 1.5rem;
        }
        
        .dog-info h3 {
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .dog-breed {
            color: #4e8df5;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .dog-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }
        
        .dog-detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .dog-detail-item i {
            color: #4e8df5;
            width: 16px;
        }
        
        .dog-description {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .dog-actions {
            display: flex;
            gap: 0.8rem;
        }
        
        .dog-actions .btn {
            flex: 1;
            justify-content: center;
            padding: 0.7rem;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: #4e8df5;
            color: white;
        }
        
        .btn-primary:hover {
            background: #357abd;
            transform: translateY(-2px);
        }
        
        .no-dogs {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .no-dogs i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }
        
        .no-dogs h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .no-dogs p {
            color: #6c757d;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid;
        }
        
        .alert-error {
            background: #fee;
            border-color: #e74c3c;
            color: #c33;
        }
        
        /* Loading indicator */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4e8df5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .dogs-grid {
                grid-template-columns: 1fr;
            }
            
            .dog-actions {
                flex-direction: column;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1>Find Your Perfect Companion</h1>
            <p>Browse our wonderful dogs looking for forever homes</p>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="" id="filterForm">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="search">
                            <i class="fas fa-search"></i> Search
                        </label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name or breed...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="breed">
                            <i class="fas fa-dog"></i> Breed
                        </label>
                        <select id="breed" name="breed">
                            <option value="">All Breeds</option>
                            <?php foreach ($breeds as $dog_breed): ?>
                                <option value="<?php echo htmlspecialchars($dog_breed); ?>" 
                                    <?php echo $breed_filter === $dog_breed ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dog_breed); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="size">
                            <i class="fas fa-weight"></i> Size
                        </label>
                        <select id="size" name="size">
                            <option value="">Any Size</option>
                            <option value="small" <?php echo $size === 'small' ? 'selected' : ''; ?>>Small</option>
                            <option value="medium" <?php echo $size === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="large" <?php echo $size === 'large' ? 'selected' : ''; ?>>Large</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="gender">
                            <i class="fas fa-venus-mars"></i> Gender
                        </label>
                        <select id="gender" name="gender">
                            <option value="">Any Gender</option>
                            <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">
                            <i class="fas fa-heart"></i> Status
                        </label>
                        <select id="status" name="status">
                            <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="adopted" <?php echo $status === 'adopted' ? 'selected' : ''; ?>>Adopted</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <a href="dogs.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear All
                    </a>
                    <div class="results-count">
                        <?php echo count($dogs); ?> dog<?php echo count($dogs) !== 1 ? 's' : ''; ?> found
                    </div>
                </div>
            </form>
        </div>

        <!-- Dogs Grid -->
        <div class="dogs-grid">
            <?php if (empty($dogs)): ?>
                <div class="no-dogs">
                    <i class="fas fa-search"></i>
                    <h3>No Dogs Found</h3>
                    <p>We couldn't find any dogs matching your criteria. Try adjusting your filters or browse all available dogs.</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="dogs.php" class="btn btn-primary">View All Dogs</a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="../admin/add_dog.php" class="btn btn-outline">Add New Dog</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($dogs as $dog): ?>
                    <?php
                    $has_pending_request = in_array($dog['dog_id'], $user_requests);
                    $can_adopt = isset($_SESSION['user_id']) && 
                                 isset($_SESSION['role']) && 
                                 $_SESSION['role'] !== 'admin' && 
                                 $dog['status'] === 'available' && 
                                 !$has_pending_request;
                    ?>
                    
                    <div class="dog-card">
                        <div class="dog-image">
                            <img src="../<?php echo htmlspecialchars($dog['image_path'] ?? 'images/placeholder-dog.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($dog['name']); ?>"
                                 onerror="this.src='../images/placeholder-dog.jpg'">
                            <span class="dog-status status-<?php echo $dog['status']; ?>">
                                <?php echo ucfirst($dog['status']); ?>
                            </span>
                        </div>
                        
                        <div class="dog-info">
                            <h3><?php echo htmlspecialchars($dog['name']); ?></h3>
                            <div class="dog-breed"><?php echo htmlspecialchars($dog['breed']); ?></div>
                            
                            <div class="dog-details">
                                <div class="dog-detail-item">
                                    <i class="fas fa-birthday-cake"></i>
                                    <span><?php echo htmlspecialchars($dog['age']); ?> years</span>
                                </div>
                                <div class="dog-detail-item">
                                    <i class="fas <?php echo $dog['gender'] === 'male' ? 'fa-mars' : 'fa-venus'; ?>"></i>
                                    <span><?php echo ucfirst($dog['gender']); ?></span>
                                </div>
                                <div class="dog-detail-item">
                                    <i class="fas fa-weight"></i>
                                    <span><?php echo ucfirst($dog['size']); ?> size</span>
                                </div>
                                <div class="dog-detail-item">
                                    <i class="fas fa-paw"></i>
                                    <span><?php echo htmlspecialchars($dog['breed']); ?></span>
                                </div>
                            </div>
                            
                            <p class="dog-description">
                                <?php
                                $description = $dog['description'] ?? 'A loving companion looking for a forever home.';
                                echo htmlspecialchars(substr($description, 0, 120));
                                if (strlen($description) > 120) echo '...';
                                ?>
                            </p>
                            
                            <div class="dog-actions">
                                <a href="dog_details.php?id=<?php echo $dog['dog_id']; ?>" class="btn btn-outline">
                                    <i class="fas fa-info-circle"></i> Details
                                </a>
                                
                                <?php if ($can_adopt): ?>
                                    <a href="adoption_request.php?dog_id=<?php echo $dog['dog_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-heart"></i> Adopt
                                    </a>
                                <?php elseif ($has_pending_request): ?>
                                    <button class="btn btn-outline" disabled style="background: #f8f9fa; color: #6c757d;">
                                        <i class="fas fa-clock"></i> Requested
                                    </button>
                                <?php elseif (!isset($_SESSION['user_id']) && $dog['status'] === 'available'): ?>
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Login to Adopt
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Auto-submit form when filters change
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filterForm');
            const searchInput = document.getElementById('search');
            const selectFilters = form.querySelectorAll('select');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            let searchTimeout;
            
            // Handle search input with debounce
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    submitForm();
                }, 500); // Wait 500ms after user stops typing
            });
            
            // Handle select changes immediately
            selectFilters.forEach(function(select) {
                select.addEventListener('change', function() {
                    submitForm();
                });
            });
            
            function submitForm() {
                // Show loading overlay
                loadingOverlay.classList.add('active');
                
                // Submit the form
                form.submit();
            }
        });
    </script>
</body>
</html>