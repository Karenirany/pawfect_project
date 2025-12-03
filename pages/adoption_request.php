<?php
// pages/adoption_request.php
session_start();

// Include files with error handling
try {
    require_once '../includes/db.php';
    require_once '../includes/functions.php';
} catch (Exception $e) {
    error_log("Include error: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Check if user is logged in and not admin
if (!is_logged_in()) {
    $_SESSION['error'] = 'Please log in to submit an adoption request.';
    header('Location: login.php');
    exit;
}

if (is_admin()) {
    $_SESSION['error'] = 'Admins cannot submit adoption requests.';
    header('Location: dogs.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if dog ID is provided and valid
if (!isset($_GET['dog_id']) || !is_numeric($_GET['dog_id'])) {
    $_SESSION['error'] = 'Invalid dog selection.';
    header('Location: dogs.php');
    exit;
}

$dog_id = (int)$_GET['dog_id'];

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
    
    // Check if dog is available
    if ($dog['status'] !== 'available') {
        $_SESSION['error'] = 'This dog is not available for adoption.';
        header('Location: dog_details.php?id=' . $dog_id);
        exit;
    }
} catch (Exception $e) {
    error_log("Dog details error: " . $e->getMessage());
    $_SESSION['error'] = 'Error loading dog information.';
    header('Location: dogs.php');
    exit;
}

// Check if user already has a pending request for this dog
try {
    $stmt = $pdo->prepare("SELECT request_id FROM adoption_requests WHERE user_id = ? AND dog_id = ? AND status = 'pending'");
    $stmt->execute([$user_id, $dog_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'You already have a pending adoption request for this dog.';
        header('Location: dog_details.php?id=' . $dog_id);
        exit;
    }
} catch (Exception $e) {
    error_log("Existing request check error: " . $e->getMessage());
}

// Process adoption request form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $living_situation = isset($_POST['living_situation']) ? trim($_POST['living_situation']) : '';
    $previous_experience = isset($_POST['previous_experience']) ? trim($_POST['previous_experience']) : '';
    $family_members = isset($_POST['family_members']) ? trim($_POST['family_members']) : '';
    $other_pets = isset($_POST['other_pets']) ? trim($_POST['other_pets']) : '';
    $work_schedule = isset($_POST['work_schedule']) ? trim($_POST['work_schedule']) : '';
    $adoption_reason = isset($_POST['adoption_reason']) ? trim($_POST['adoption_reason']) : '';
    $additional_notes = isset($_POST['additional_notes']) ? trim($_POST['additional_notes']) : '';
    
    // Validate required fields
    $required_fields = [
        'living_situation' => 'Living Situation',
        'previous_experience' => 'Previous Pet Experience',
        'family_members' => 'Family Members',
        'work_schedule' => 'Work Schedule',
        'adoption_reason' => 'Adoption Reason'
    ];
    
    $missing_fields = [];
    foreach ($required_fields as $field => $label) {
        if (empty($$field)) {
            $missing_fields[] = $label;
        }
    }
    
    if (!empty($missing_fields)) {
        $error = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert adoption request
            $stmt = $pdo->prepare("
                INSERT INTO adoption_requests 
                (user_id, dog_id, living_situation, previous_experience, family_members, other_pets, work_schedule, adoption_reason, additional_notes, request_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user_id, 
                $dog_id, 
                $living_situation, 
                $previous_experience, 
                $family_members, 
                $other_pets, 
                $work_schedule, 
                $adoption_reason, 
                $additional_notes
            ]);
            
            // Update dog status to pending
            $stmt = $pdo->prepare("UPDATE dogs SET status = 'pending' WHERE dog_id = ?");
            $stmt->execute([$dog_id]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['success'] = 'Adoption request submitted successfully! We will review your application and contact you soon.';
            header('Location: my_requests.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Adoption request submission error: " . $e->getMessage());
            $error = 'Failed to submit adoption request. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adopt <?php echo htmlspecialchars($dog['name']); ?> - Pawfect Home</title>
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
            max-width: 800px;
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
        
        .adoption-header {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .dog-preview {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .header-content h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 2.2rem;
        }
        
        .dog-breed {
            color: #4e8df5;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }
        
        .dog-details {
            color: #6c757d;
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
        
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .alert-info {
            background: #e7f3ff;
            border-color: #4e8df5;
            color: #2c3e50;
        }
        
        .adoption-form {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }
        
        .form-section {
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .form-section h3 i {
            color: #4e8df5;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.7rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
        }
        
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4e8df5;
            box-shadow: 0 0 0 3px rgba(78, 141, 245, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }
        
        .form-group select {
            background: white;
            cursor: pointer;
        }
        
        .form-help {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
            font-style: italic;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e1e5e9;
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
        
        .educational-note {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #f39c12;
        }
        
        .educational-note h4 {
            color: #856404;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .educational-note p {
            color: #856404;
            margin: 0;
            font-size: 0.95rem;
        }
        
        @media (max-width: 768px) {
            .adoption-header {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }
            
            .dog-preview {
                width: 120px;
                height: 120px;
            }
            
            .header-content h1 {
                font-size: 1.8rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .adoption-form {
                padding: 1.5rem;
            }
            
            .adoption-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <a href="dog_details.php?id=<?php echo $dog_id; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dog Details
        </a>

        <!-- Adoption Header -->
        <div class="adoption-header">
            <img src="../<?php echo htmlspecialchars($dog['image_path'] ?? 'images/placeholder-dog.jpg'); ?>" 
                 alt="<?php echo htmlspecialchars($dog['name']); ?>" 
                 class="dog-preview"
                 onerror="this.src='../images/placeholder-dog.jpg'">
            <div class="header-content">
                <h1>Adopt <?php echo htmlspecialchars($dog['name']); ?></h1>
                <div class="dog-breed"><?php echo htmlspecialchars($dog['breed']); ?></div>
                <div class="dog-details">
                    <?php echo $dog['age']; ?> years • <?php echo ucfirst($dog['size']); ?> size • <?php echo ucfirst($dog['gender']); ?>
                </div>
                <p>Please complete the adoption application form below to apply for <?php echo htmlspecialchars($dog['name']); ?>.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Educational Note -->
        <div class="educational-note">
            <h4><i class="fas fa-graduation-cap"></i> Educational Note</h4>
            <p>This is a demonstration website for learning purposes. No real adoptions will take place through this platform. 
            The information you provide helps practice the adoption application process and form handling.</p>
        </div>

        <!-- Adoption Form -->
        <form method="POST" action="" class="adoption-form">
            <!-- Living Situation Section -->
            <div class="form-section">
                <h3><i class="fas fa-home"></i> Living Situation</h3>
                
                <div class="form-group">
                    <label for="living_situation" class="required">Type of Residence</label>
                    <select id="living_situation" name="living_situation" required>
                        <option value="">Select your living situation</option>
                        <option value="house_owned" <?php echo isset($_POST['living_situation']) && $_POST['living_situation'] === 'house_owned' ? 'selected' : ''; ?>>House (Owned)</option>
                        <option value="house_rented" <?php echo isset($_POST['living_situation']) && $_POST['living_situation'] === 'house_rented' ? 'selected' : ''; ?>>House (Rented)</option>
                        <option value="apartment" <?php echo isset($_POST['living_situation']) && $_POST['living_situation'] === 'apartment' ? 'selected' : ''; ?>>Apartment/Condo</option>
                        <option value="townhouse" <?php echo isset($_POST['living_situation']) && $_POST['living_situation'] === 'townhouse' ? 'selected' : ''; ?>>Townhouse</option>
                        <option value="other" <?php echo isset($_POST['living_situation']) && $_POST['living_situation'] === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <div class="form-help">Please select the type of residence where the dog will live</div>
                </div>

                <div class="form-group">
                    <label for="family_members" class="required">Family/Household Members</label>
                    <textarea id="family_members" name="family_members" 
                              placeholder="Who lives with you? Include ages of children if any, and how many adults..."
                              required><?php echo isset($_POST['family_members']) ? htmlspecialchars($_POST['family_members']) : ''; ?></textarea>
                    <div class="form-help">Please describe all people living in your household</div>
                </div>
            </div>

            <!-- Experience & Pets Section -->
            <div class="form-section">
                <h3><i class="fas fa-paw"></i> Experience & Current Pets</h3>
                
                <div class="form-group">
                    <label for="previous_experience" class="required">Previous Pet Experience</label>
                    <textarea id="previous_experience" name="previous_experience" 
                              placeholder="Tell us about your experience with pets. Have you owned dogs before? What breeds?..."
                              required><?php echo isset($_POST['previous_experience']) ? htmlspecialchars($_POST['previous_experience']) : ''; ?></textarea>
                    <div class="form-help">Share your experience with pets, especially dogs</div>
                </div>

                <div class="form-group">
                    <label for="other_pets">Current Pets</label>
                    <textarea id="other_pets" name="other_pets" 
                              placeholder="Do you currently have other pets? Please describe them (type, breed, age, temperament)..."><?php echo isset($_POST['other_pets']) ? htmlspecialchars($_POST['other_pets']) : ''; ?></textarea>
                    <div class="form-help">List any current pets in your home (leave blank if none)</div>
                </div>
            </div>

            <!-- Lifestyle & Schedule Section -->
            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> Lifestyle & Schedule</h3>
                
                <div class="form-group">
                    <label for="work_schedule" class="required">Work Schedule & Daily Routine</label>
                    <textarea id="work_schedule" name="work_schedule" 
                              placeholder="Describe your typical work schedule and daily routine. How many hours will the dog be alone?..."
                              required><?php echo isset($_POST['work_schedule']) ? htmlspecialchars($_POST['work_schedule']) : ''; ?></textarea>
                    <div class="form-help">Help us understand how the dog will fit into your daily life</div>
                </div>

                <div class="form-group">
                    <label for="adoption_reason" class="required">Why do you want to adopt <?php echo htmlspecialchars($dog['name']); ?>?</label>
                    <textarea id="adoption_reason" name="adoption_reason" 
                              placeholder="What makes <?php echo htmlspecialchars($dog['name']); ?> the right dog for you? What activities do you plan to do together?..."
                              required><?php echo isset($_POST['adoption_reason']) ? htmlspecialchars($_POST['adoption_reason']) : ''; ?></textarea>
                    <div class="form-help">Tell us why you're specifically interested in adopting this dog</div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
                
                <div class="form-group">
                    <label for="additional_notes">Additional Notes or Questions</label>
                    <textarea id="additional_notes" name="additional_notes" 
                              placeholder="Any other information you'd like to share or questions you have about the adoption process..."><?php echo isset($_POST['additional_notes']) ? htmlspecialchars($_POST['additional_notes']) : ''; ?></textarea>
                    <div class="form-help">Optional: Share any additional information or ask questions</div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="dog_details.php?id=<?php echo $dog_id; ?>" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Adoption Request
                </button>
            </div>
        </form>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const requiredFields = form.querySelectorAll('[required]');
            
            // Add real-time validation
            requiredFields.forEach(field => {
                field.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.style.borderColor = '#e74c3c';
                    } else {
                        this.style.borderColor = '#27ae60';
                    }
                });
                
                field.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.style.borderColor = '#4e8df5';
                    }
                });
            });
            
            // Form submission confirmation
            form.addEventListener('submit', function(e) {
                let missingFields = [];
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        missingFields.push(field.previousElementSibling?.textContent || 'Field');
                        field.style.borderColor = '#e74c3c';
                    }
                });
                
                if (missingFields.length > 0) {
                    e.preventDefault();
                    alert('Please fill in all required fields before submitting.');
                    return false;
                }
                
                return confirm('Are you ready to submit your adoption request? Please review your information before submitting.');
            });
            
            // Character counters for textareas
            const textareas = form.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                const counter = document.createElement('div');
                counter.className = 'form-help';
                counter.style.marginTop = '0.5rem';
                counter.style.fontSize = '0.8rem';
                counter.style.color = '#6c757d';
                textarea.parentNode.insertBefore(counter, textarea.nextSibling);
                
                function updateCounter() {
                    const length = textarea.value.length;
                    counter.textContent = `${length} characters`;
                    
                    if (length > 500) {
                        counter.style.color = '#27ae60';
                    } else if (length > 200) {
                        counter.style.color = '#f39c12';
                    } else {
                        counter.style.color = '#6c757d';
                    }
                }
                
                textarea.addEventListener('input', updateCounter);
                updateCounter();
            });
        });
    </script>
</body>
</html>