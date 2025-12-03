<?php
// admin/dogs.php
session_start();
require_once '../includes/db.php';

// Simple sanitize function if not in functions.php
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
$dog_id = $_GET['id'] ?? 0;

// Delete dog
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dog'])) {
    $dog_id = (int)$_POST['dog_id'];
    try {
        // Get dog info to delete image file
        $stmt = $pdo->prepare("SELECT image_path FROM dogs WHERE dog_id = ?");
        $stmt->execute([$dog_id]);
        $dog = $stmt->fetch();
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM dogs WHERE dog_id = ?");
        $stmt->execute([$dog_id]);
        
        // Delete image file if it's not the placeholder
        if ($dog && $dog['image_path'] !== 'images/placeholder-dog.jpg') {
            $image_path = '../' . $dog['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $_SESSION['success'] = 'Dog deleted successfully.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error deleting dog. Please try again.';
        error_log("Delete dog error: " . $e->getMessage());
    }
    header('Location: dogs.php');
    exit;
}

// Add/Edit dog
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_dog']) || isset($_POST['edit_dog']))) {
    $name = sanitize_input($_POST['name'] ?? '');
    $breed = sanitize_input($_POST['breed'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $size = $_POST['size'] ?? '';
    $description = sanitize_input($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'available';
    
    // Default image path
    $image_path = 'images/placeholder-dog.jpg';
    
    // Handle image upload
    if (isset($_FILES['dog_image']) && $_FILES['dog_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/dogs/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['dog_image']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Validate file type
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            // Generate unique filename
            $filename = uniqid('dog_') . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['dog_image']['tmp_name'], $target_path)) {
                $image_path = 'images/dogs/' . $filename;
            } else {
                $_SESSION['error'] = 'Error uploading image. Please try again.';
            }
        } else {
            $_SESSION['error'] = 'Invalid file type. Please upload JPG, PNG, or GIF images only.';
        }
    } elseif (isset($_POST['existing_image']) && !empty($_POST['existing_image'])) {
        // Use existing image for edits
        $image_path = $_POST['existing_image'];
    }
    
    // Basic validation
    if (empty($_SESSION['error']) && !empty($name) && !empty($breed) && $age > 0) {
        try {
            if (isset($_POST['add_dog'])) {
                // Add new dog
                $stmt = $pdo->prepare("
                    INSERT INTO dogs (name, breed, age, gender, size, description, image_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $breed, $age, $gender, $size, $description, $image_path, $status]);
                $_SESSION['success'] = 'Dog added successfully!';
            } else {
                // Edit existing dog
                $dog_id = (int)$_POST['dog_id'];
                
                // If new image was uploaded, delete old image (if it's not placeholder)
                if (isset($_FILES['dog_image']) && $_FILES['dog_image']['error'] === UPLOAD_ERR_OK) {
                    // Get old image path
                    $stmt = $pdo->prepare("SELECT image_path FROM dogs WHERE dog_id = ?");
                    $stmt->execute([$dog_id]);
                    $old_dog = $stmt->fetch();
                    
                    // Delete old image file if it exists and is not placeholder
                    if ($old_dog && $old_dog['image_path'] !== 'images/placeholder-dog.jpg') {
                        $old_image_path = '../' . $old_dog['image_path'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                }
                
                $stmt = $pdo->prepare("
                    UPDATE dogs 
                    SET name = ?, breed = ?, age = ?, gender = ?, size = ?, description = ?, image_path = ?, status = ? 
                    WHERE dog_id = ?
                ");
                $stmt->execute([$name, $breed, $age, $gender, $size, $description, $image_path, $status, $dog_id]);
                $_SESSION['success'] = 'Dog updated successfully!';
            }
            header('Location: dogs.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error saving dog. Please try again.';
            error_log("Save dog error: " . $e->getMessage());
        }
    } else {
        if (empty($_SESSION['error'])) {
            $_SESSION['error'] = 'Please fill in all required fields.';
        }
    }
}

// Get all dogs
try {
    $stmt = $pdo->query("
        SELECT d.*, 
               (SELECT COUNT(*) FROM adoption_requests ar WHERE ar.dog_id = d.dog_id) as request_count
        FROM dogs d 
        ORDER BY d.dog_id DESC
    ");
    $dogs = $stmt->fetchAll();
} catch (Exception $e) {
    $dogs = [];
    error_log("Error fetching dogs: " . $e->getMessage());
}

// Get specific dog for editing
$edit_dog = null;
if ($action === 'edit' && $dog_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM dogs WHERE dog_id = ?");
        $stmt->execute([$dog_id]);
        $edit_dog = $stmt->fetch();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error fetching dog details.';
        error_log("Error fetching dog: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Dogs - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <style>
        .container {
            max-width: 1200px;
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
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-outline { background: transparent; border: 2px solid #3498db; color: #3498db; }
        
        .dogs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .dog-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .dog-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .dog-info {
            padding: 15px;
        }
        .dog-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .dog-breed {
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        .dog-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .dog-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .status-available { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-adopted { background: #f8d7da; color: #721c24; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }
        .action-buttons .btn {
            flex: 1;
            padding: 6px 12px;
            font-size: 0.8rem;
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
        .image-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            border: 2px solid #bdc3c7;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
        }
        .file-input-label {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .file-input-label:hover {
            background: #2980b9;
        }
        .file-name {
            margin-left: 10px;
            font-size: 14px;
            color: #7f8c8d;
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
            .dogs-grid {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
     <!-- Header -->
<div class="header">
    <h1><i class="fas fa-dog"></i> Manage Dogs</h1>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        
    
       
        <!-- Add XML Export Button -->
        <a href="export_dogs.php" class="btn btn-warning">
            <i class="fas fa-file-export"></i> Export XML
        </a>
        
        <button type="button" class="btn btn-primary" onclick="toggleForm()">
            <i class="fas fa-plus"></i> Add New Dog
        </button>
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

        <!-- Add/Edit Dog Form -->
        <div id="dogForm" class="form-container" style="<?php echo ($action === 'edit' || isset($_POST['add_dog'])) ? 'display: block;' : 'display: none;'; ?>">
            <h2><?php echo $edit_dog ? 'Edit Dog' : 'Add New Dog'; ?></h2>
            
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_dog): ?>
                    <input type="hidden" name="dog_id" value="<?php echo $edit_dog['dog_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Dog Name *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_dog['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="breed">Breed *</label>
                        <input type="text" id="breed" name="breed" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_dog['breed'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="age">Age (years) *</label>
                        <input type="number" id="age" name="age" class="form-control" min="0" max="30"
                               value="<?php echo $edit_dog['age'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($edit_dog['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($edit_dog['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="size">Size *</label>
                        <select id="size" name="size" class="form-control" required>
                            <option value="">Select Size</option>
                            <option value="small" <?php echo ($edit_dog['size'] ?? '') === 'small' ? 'selected' : ''; ?>>Small</option>
                            <option value="medium" <?php echo ($edit_dog['size'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="large" <?php echo ($edit_dog['size'] ?? '') === 'large' ? 'selected' : ''; ?>>Large</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="available" <?php echo ($edit_dog['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="pending" <?php echo ($edit_dog['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="adopted" <?php echo ($edit_dog['status'] ?? '') === 'adopted' ? 'selected' : ''; ?>>Adopted</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($edit_dog['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="dog_image">Dog Image</label>
                    <div>
                        <div class="file-input-wrapper">
                            <input type="file" id="dog_image" name="dog_image" class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(this)">
                            <label for="dog_image" class="file-input-label">
                                <i class="fas fa-upload"></i> Choose Image
                            </label>
                        </div>
                        <span id="file-name" class="file-name">No file chosen</span>
                    </div>
                    <small>Accepted formats: JPG, PNG, GIF, WebP. Max size: 5MB</small>
                    
                    <div id="image-preview-container">
                        <?php if ($edit_dog && $edit_dog['image_path']): ?>
                            <input type="hidden" name="existing_image" value="<?php echo $edit_dog['image_path']; ?>">
                            <div>
                                <img src="../<?php echo $edit_dog['image_path']; ?>" class="image-preview" id="current-image-preview"
                                     onerror="this.src='../images/placeholder-dog.jpg'">
                                <p><small>Current image</small></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <?php if ($edit_dog): ?>
                        <button type="submit" name="edit_dog" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Dog
                        </button>
                    <?php else: ?>
                        <button type="submit" name="add_dog" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Dog
                        </button>
                    <?php endif; ?>
                    <a href="dogs.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Dogs List -->
        <h2>All Dogs (<?php echo count($dogs); ?>)</h2>
        
        <?php if (empty($dogs)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <i class="fas fa-dog" style="font-size: 4rem; margin-bottom: 15px; opacity: 0.5;"></i>
                <h3>No dogs found</h3>
                <p>Get started by adding your first dog to the adoption platform.</p>
                <button type="button" class="btn btn-primary" onclick="toggleForm()">
                    <i class="fas fa-plus"></i> Add First Dog
                </button>
            </div>
        <?php else: ?>
            <div class="dogs-grid">
                <?php foreach ($dogs as $dog): ?>
                    <div class="dog-card">
                        <img src="../<?php echo $dog['image_path']; ?>" 
                             alt="<?php echo htmlspecialchars($dog['name']); ?>" 
                             class="dog-image"
                             onerror="this.src='../images/placeholder-dog.jpg'">
                        
                        <div class="dog-info">
                            <div class="dog-name"><?php echo htmlspecialchars($dog['name']); ?></div>
                            <div class="dog-breed"><?php echo htmlspecialchars($dog['breed']); ?></div>
                            
                            <div class="dog-details">
                                <span><i class="fas fa-birthday-cake"></i> <?php echo $dog['age']; ?> years</span>
                                <span><i class="fas <?php echo $dog['gender'] === 'male' ? 'fa-mars' : 'fa-venus'; ?>"></i> <?php echo ucfirst($dog['gender']); ?></span>
                                <span><i class="fas fa-weight"></i> <?php echo ucfirst($dog['size']); ?></span>
                                <span><i class="fas fa-heart"></i> <?php echo $dog['request_count']; ?> requests</span>
                            </div>
                            
                            <span class="dog-status status-<?php echo $dog['status']; ?>">
                                <?php echo ucfirst($dog['status']); ?>
                            </span>
                            
                            <div class="action-buttons">
                                <a href="dogs.php?action=edit&id=<?php echo $dog['dog_id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="dog_id" value="<?php echo $dog['dog_id']; ?>">
                                    <button type="submit" name="delete_dog" class="btn btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($dog['name']); ?>? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleForm() {
            const form = document.getElementById('dogForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            
            // Scroll to form
            if (form.style.display === 'block') {
                form.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function previewImage(input) {
            const fileName = document.getElementById('file-name');
            const previewContainer = document.getElementById('image-preview-container');
            
            if (input.files && input.files[0]) {
                fileName.textContent = input.files[0].name;
                
                // Remove current preview if exists
                const currentPreview = document.getElementById('current-image-preview');
                if (currentPreview) {
                    currentPreview.style.display = 'none';
                }
                
                // Create new preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    let img = document.getElementById('new-image-preview');
                    if (!img) {
                        img = document.createElement('img');
                        img.id = 'new-image-preview';
                        img.className = 'image-preview';
                        previewContainer.appendChild(img);
                    }
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                fileName.textContent = 'No file chosen';
                const newPreview = document.getElementById('new-image-preview');
                if (newPreview) {
                    newPreview.style.display = 'none';
                }
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