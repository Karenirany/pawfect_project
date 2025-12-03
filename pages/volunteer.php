<?php
// pages/volunteer.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Include files with error handling
try {
    require_once '../includes/functions.php';
    require_once '../includes/email_functions.php';
} catch (Exception $e) {
    error_log("Include error: " . $e->getMessage());
    die("System error. Please try again later.");
}

$error = '';
$success = '';

// Process volunteer form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $age = isset($_POST['age']) ? trim($_POST['age']) : '';
    $occupation = isset($_POST['occupation']) ? trim($_POST['occupation']) : '';
    $availability = isset($_POST['availability']) ? $_POST['availability'] : [];
    $volunteer_areas = isset($_POST['volunteer_areas']) ? $_POST['volunteer_areas'] : [];
    $experience = isset($_POST['experience']) ? trim($_POST['experience']) : '';
    $why_volunteer = isset($_POST['why_volunteer']) ? trim($_POST['why_volunteer']) : '';
    $emergency_contact_name = isset($_POST['emergency_contact_name']) ? trim($_POST['emergency_contact_name']) : '';
    $emergency_contact_phone = isset($_POST['emergency_contact_phone']) ? trim($_POST['emergency_contact_phone']) : '';
    $additional_info = isset($_POST['additional_info']) ? trim($_POST['additional_info']) : '';
    
    // Validate required fields
    $required_fields = [
        'full_name' => 'Full Name',
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'age' => 'Age',
        'why_volunteer' => 'Why You Want to Volunteer'
    ];
    
    $missing_fields = [];
    foreach ($required_fields as $field => $label) {
        if (empty($$field)) {
            $missing_fields[] = $label;
        }
    }
    
    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $missing_fields[] = 'Valid Email Address';
    }
    
    // Validate availability and volunteer areas
    if (empty($availability)) {
        $missing_fields[] = 'Availability (select at least one)';
    }
    
    if (empty($volunteer_areas)) {
        $missing_fields[] = 'Areas of Interest (select at least one)';
    }
    
    if (!empty($missing_fields)) {
        $error = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
    } else {
        // Prepare data for email
        $availability_str = implode(', ', $availability);
        $volunteer_areas_str = implode(', ', $volunteer_areas);
        
        $application_data = [
            'application_id' => date('YmdHis'), // Use timestamp as ID
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'age' => $age,
            'occupation' => $occupation,
            'availability' => $availability_str,
            'volunteer_areas' => $volunteer_areas_str,
            'experience' => $experience,
            'why_volunteer' => $why_volunteer,
            'emergency_contact_name' => $emergency_contact_name,
            'emergency_contact_phone' => $emergency_contact_phone,
            'additional_info' => $additional_info
        ];
        
        // Send email notification
        error_log("Attempting to send volunteer application email...");
        $email_sent = send_volunteer_application_email($application_data);
        error_log("Email send result: " . ($email_sent ? "SUCCESS" : "FAILED"));
        
        if ($email_sent) {
            $success = 'Thank you for your interest in volunteering! Your application has been submitted successfully. We will review your application and contact you soon at ' . htmlspecialchars($email) . '.';
            // Clear form data on success
            $_POST = [];
        } else {
            // Even if email fails, show success to user but log the error
            error_log("Email sending failed for volunteer application from: " . $email);
            
            // Show success anyway - in production, you might want to store failed emails
            $success = 'Thank you for your interest in volunteering! Your application has been received. We will contact you soon at ' . htmlspecialchars($email) . '.';
            $_POST = [];
            
            // Optionally, uncomment this to show the error during testing:
            // $error = 'There was an issue sending your application email. Please contact us directly at pawfect.adoption.lb@gmail.com with your details.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer With Us - Pawfect Home</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enhanced form styling */
        .form-input-wrapper {
            position: relative;
        }
        
        .form-input-wrapper input,
        .form-input-wrapper select,
        .form-input-wrapper textarea {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-input-wrapper input:focus,
        .form-input-wrapper select:focus,
        .form-input-wrapper textarea:focus {
            background: white;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }
        
        .form-input-wrapper textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .time-slot {
            display: grid;
            grid-template-columns: 1fr 100px 100px;
            gap: 0.5rem;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }
        
        .time-slot input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        
        .time-slot select {
            padding: 0.5rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            background: white;
        }
        
        .checkbox-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .checkbox-card:hover {
            background: white;
            border-color: #4a90e2;
            transform: translateY(-2px);
        }
        
        .checkbox-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-card input[type="checkbox"]:checked + .checkbox-content {
            color: #4a90e2;
            font-weight: 600;
        }
        
        .checkbox-card:has(input:checked) {
            background: #e7f3ff;
            border-color: #4a90e2;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container" style="padding: 3rem 20px;">
        <!-- Page Header -->
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="color: #2c3e50; margin-bottom: 1rem;">
                <i class="fas fa-hands-helping" style="color: #4a90e2;"></i> 
                Volunteer With Us
            </h1>
            <p class="section-subtitle">Make a difference in the lives of dogs waiting for their forever homes</p>
        </div>

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

        <!-- Volunteer Benefits Section -->
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <h2 style="color: #2c3e50; text-align: center; margin-bottom: 2rem;">Why Volunteer?</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div style="text-align: center; padding: 1rem;">
                    <div style="width: 70px; height: 70px; background: #4a90e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.8rem;">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 style="margin-bottom: 0.5rem;">Make a Difference</h3>
                    <p style="color: #7f8c8d;">Help dogs find loving homes and improve their quality of life</p>
                </div>
                <div style="text-align: center; padding: 1rem;">
                    <div style="width: 70px; height: 70px; background: #27ae60; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.8rem;">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 style="margin-bottom: 0.5rem;">Join Our Community</h3>
                    <p style="color: #7f8c8d;">Meet like-minded people who share your passion for animals</p>
                </div>
                <div style="text-align: center; padding: 1rem;">
                    <div style="width: 70px; height: 70px; background: #f39c12; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.8rem;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 style="margin-bottom: 0.5rem;">Learn & Grow</h3>
                    <p style="color: #7f8c8d;">Gain valuable experience working with animals and teamwork</p>
                </div>
            </div>
        </div>

        <!-- Educational Note -->
        <div style="background: linear-gradient(135deg, #fff3cd, #ffeaa7); border: 1px solid #ffeaa7; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; border-left: 4px solid #f39c12;">
            <h4 style="color: #856404; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-graduation-cap"></i> Educational Note
            </h4>
            <p style="color: #856404; margin: 0; font-size: 0.95rem;">
                This is a demonstration website for learning purposes. While the form sends real emails, 
                this is not an actual volunteer program. The purpose is to practice form handling and email integration.
            </p>
        </div>

        <!-- Volunteer Application Form -->
        <form method="POST" action="" style="background: white; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            
            <!-- Personal Information Section -->
            <div style="margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 2px solid #ecf0f1;">
                <h3 style="color: #2c3e50; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fas fa-user" style="color: #4a90e2;"></i> Personal Information
                </h3>
                
                <div style="display: grid; gap: 1.5rem;">
                    <div class="form-input-wrapper">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                            <i class="fas fa-user" style="color: #4a90e2; width: 20px;"></i> Full Name <span style="color: #e74c3c;">*</span>
                        </label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" placeholder="Enter your full name" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <div class="form-input-wrapper">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                                <i class="fas fa-envelope" style="color: #4a90e2; width: 20px;"></i> Email Address <span style="color: #e74c3c;">*</span>
                            </label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="your.email@example.com" required>
                        </div>
                        
                        <div class="form-input-wrapper">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                                <i class="fas fa-phone" style="color: #4a90e2; width: 20px;"></i> Phone Number <span style="color: #e74c3c;">*</span>
                            </label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+1234567890" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                        <div class="form-input-wrapper">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                                <i class="fas fa-birthday-cake" style="color: #4a90e2; width: 20px;"></i> Age <span style="color: #e74c3c;">*</span>
                            </label>
                            <input type="number" name="age" min="16" max="100" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" placeholder="Must be 16 or older" required>
                        </div>
                        
                        <div class="form-input-wrapper">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                                <i class="fas fa-briefcase" style="color: #4a90e2; width: 20px;"></i> Occupation (Optional)
                            </label>
                            <input type="text" name="occupation" value="<?php echo htmlspecialchars($_POST['occupation'] ?? ''); ?>" placeholder="e.g., Student, Teacher, Engineer">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Availability Section with Time Slots -->
            <div style="margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 2px solid #ecf0f1;">
                <h3 style="color: #2c3e50; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fas fa-calendar-alt" style="color: #4a90e2;"></i> Availability & Time Slots
                </h3>
                
                <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: #2c3e50;">
                    Select the days and times you're available <span style="color: #e74c3c;">*</span>
                </label>
                
                <div style="display: grid; gap: 0.5rem;">
                    <?php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $selected_availability = $_POST['availability'] ?? [];
                    
                    foreach ($days as $day):
                        $day_value = $day;
                        $from_value = isset($_POST[$day . '_from']) ? $_POST[$day . '_from'] : '09:00';
                        $to_value = isset($_POST[$day . '_to']) ? $_POST[$day . '_to'] : '17:00';
                        
                        // Check if this day is selected
                        $is_selected = false;
                        foreach ($selected_availability as $avail) {
                            if (strpos($avail, $day) !== false) {
                                $is_selected = true;
                                // Extract times if they exist
                                if (preg_match('/' . $day . ' \((\d{2}:\d{2}) - (\d{2}:\d{2})\)/', $avail, $matches)) {
                                    $from_value = $matches[1];
                                    $to_value = $matches[2];
                                }
                                break;
                            }
                        }
                    ?>
                        <div class="time-slot">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" class="day-checkbox" data-day="<?php echo $day; ?>" <?php echo $is_selected ? 'checked' : ''; ?>>
                                <span style="font-weight: 600;"><?php echo $day; ?></span>
                            </label>
                            <select name="<?php echo $day; ?>_from" class="time-from" data-day="<?php echo $day; ?>" <?php echo !$is_selected ? 'disabled' : ''; ?>>
                                <?php for($h = 6; $h <= 22; $h++): ?>
                                    <option value="<?php echo sprintf('%02d:00', $h); ?>" <?php echo $from_value == sprintf('%02d:00', $h) ? 'selected' : ''; ?>>
                                        <?php echo sprintf('%02d:00', $h); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="<?php echo $day; ?>_to" class="time-to" data-day="<?php echo $day; ?>" <?php echo !$is_selected ? 'disabled' : ''; ?>>
                                <?php for($h = 6; $h <= 22; $h++): ?>
                                    <option value="<?php echo sprintf('%02d:00', $h); ?>" <?php echo $to_value == sprintf('%02d:00', $h) ? 'selected' : ''; ?>>
                                        <?php echo sprintf('%02d:00', $h); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Hidden inputs to store formatted availability -->
                <div id="availability-hidden"></div>
            </div>

            <!-- Areas of Interest Section -->
            <div style="margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 2px solid #ecf0f1;">
                <h3 style="color: #2c3e50; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fas fa-tasks" style="color: #4a90e2;"></i> Areas of Interest
                </h3>
                
                <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: #2c3e50;">
                    What would you like to help with? <span style="color: #e74c3c;">*</span>
                </label>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                    <?php
                    $areas = [
                        'Dog Walking' => 'fas fa-walking',
                        'Dog Grooming' => 'fas fa-cut',
                        'Feeding & Care' => 'fas fa-utensils',
                        'Training & Socialization' => 'fas fa-graduation-cap',
                        'Adoption Events' => 'fas fa-calendar-check',
                        'Photography' => 'fas fa-camera',
                        'Social Media' => 'fas fa-share-alt',
                        'Fundraising' => 'fas fa-donate',
                        'Administrative Work' => 'fas fa-file-alt',
                        'Facility Maintenance' => 'fas fa-tools'
                    ];
                    $selected_areas = $_POST['volunteer_areas'] ?? [];
                    foreach ($areas as $area => $icon):
                    ?>
                        <label class="checkbox-card">
                            <input type="checkbox" name="volunteer_areas[]" value="<?php echo $area; ?>" 
                                   <?php echo in_array($area, $selected_areas) ? 'checked' : ''; ?>>
                            <div class="checkbox-content" style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="<?php echo $icon; ?>" style="color: #4a90e2; width: 20px;"></i>
                                <span><?php echo $area; ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Experience & Motivation Section -->
            <div style="margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 2px solid #ecf0f1;">
                <h3 style="color: #2c3e50; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fas fa-comment-dots" style="color: #4a90e2;"></i> Tell Us About Yourself
                </h3>
                
                <div style="margin-bottom: 1.5rem;" class="form-input-wrapper">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                        <i class="fas fa-paw" style="color: #4a90e2; width: 20px;"></i> Do you have any previous experience with dogs or animals?
                    </label>
                    <textarea name="experience" placeholder="Tell us about your experience with dogs, pets you've had, or any relevant background..."><?php echo htmlspecialchars($_POST['experience'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-input-wrapper">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                        <i class="fas fa-heart" style="color: #4a90e2; width: 20px;"></i> Why do you want to volunteer with us? <span style="color: #e74c3c;">*</span>
                    </label>
                    <textarea name="why_volunteer" required placeholder="Share your motivation for volunteering and what you hope to contribute..."><?php echo htmlspecialchars($_POST['why_volunteer'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Emergency Contact Section -->
            <div style="margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 2px solid #ecf0f1;">
                <h3 style="color: #2c3e50; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fas fa-phone-alt" style="color: #4a90e2;"></i> Emergency Contact
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <div class="form-input-wrapper">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                            <i class="fas fa-user" style="color: #4a90e2; width: 20px;"></i> Emergency Contact Name
                        </label>
                        <input type="text" name="emergency_contact_name" value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>" placeholder="Full name of emergency contact">
                    </div>
                    
                    <div class="form-input-wrapper">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                            <i class="fas fa-phone" style="color: #4a90e2; width: 20px;"></i> Emergency Contact Phone
                        </label>
                        <input type="tel" name="emergency_contact_phone" value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>" placeholder="Emergency contact phone number">
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #2c3e50; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fas fa-info-circle" style="color: #4a90e2;"></i> Additional Information
                </h3>
                
                <div class="form-input-wrapper">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2c3e50;">
                        <i class="fas fa-comment" style="color: #4a90e2; width: 20px;"></i> Is there anything else you'd like us to know?
                    </label>
                    <textarea name="additional_info" placeholder="Any special skills, certifications, or information that might be helpful..."><?php echo htmlspecialchars($_POST['additional_info'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 2rem; border-top: 2px solid #ecf0f1;">
                <a href="../index.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </div>
        </form>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Handle day checkbox changes
        document.querySelectorAll('.day-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.dataset.day;
                const fromSelect = document.querySelector(`select[name="${day}_from"]`);
                const toSelect = document.querySelector(`select[name="${day}_to"]`);
                
                if (this.checked) {
                    fromSelect.disabled = false;
                    toSelect.disabled = false;
                } else {
                    fromSelect.disabled = true;
                    toSelect.disabled = true;
                }
                
                updateAvailabilityHidden();
            });
        });
        
        // Handle time changes
        document.querySelectorAll('.time-from, .time-to').forEach(select => {
            select.addEventListener('change', updateAvailabilityHidden);
        });
        
        // Update hidden availability inputs
        function updateAvailabilityHidden() {
            const container = document.getElementById('availability-hidden');
            container.innerHTML = '';
            
            document.querySelectorAll('.day-checkbox:checked').forEach(checkbox => {
                const day = checkbox.dataset.day;
                const fromTime = document.querySelector(`select[name="${day}_from"]`).value;
                const toTime = document.querySelector(`select[name="${day}_to"]`).value;
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'availability[]';
                input.value = `${day} (${fromTime} - ${toTime})`;
                container.appendChild(input);
            });
        }
        
        // Initialize on page load
        updateAvailabilityHidden();
    </script>
</body>
</html>