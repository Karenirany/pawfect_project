<?php
// includes/email_functions.php

// Load PHPMailer
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send volunteer application email using PHPMailer
 */
function send_volunteer_application_email($data) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pawfect.adoption.lb@gmail.com';
        $mail->Password   = 'xben fezu dlgr jbhx'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Set charset to UTF-8 for emoji support
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Recipients
        $mail->setFrom('pawfect.adoption.lb@gmail.com', 'Pawfect Adoption - Volunteer System');
        $mail->addAddress('pawfect.adoption.lb@gmail.com', 'Pawfect Adoption Team');
        $mail->addReplyTo($data['email'], $data['full_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Volunteer Application - ' . $data['full_name'];
        $mail->Body    = build_volunteer_email_html($data);
        $mail->AltBody = build_volunteer_email_text($data);
        
        $mail->send();
        error_log("✓ Volunteer email sent successfully to: pawfect.adoption.lb@gmail.com");
        return true;
        
    } catch (Exception $e) {
        error_log("✗ Volunteer email failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Build HTML email content
 */
function build_volunteer_email_html($data) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4a90e2; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
            .section { margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; }
            .section h3 { color: #4a90e2; margin-top: 0; border-bottom: 2px solid #4a90e2; padding-bottom: 5px; }
            .field { margin-bottom: 10px; }
            .field strong { color: #2c3e50; }
            .footer { background: #2c3e50; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>🐾 New Volunteer Application</h2>
                <p>Application ID: #' . htmlspecialchars($data['application_id']) . '</p>
            </div>
            
            <div class="content">
                <div class="section">
                    <h3>👤 Personal Information</h3>
                    <div class="field"><strong>Full Name:</strong> ' . htmlspecialchars($data['full_name']) . '</div>
                    <div class="field"><strong>Email:</strong> ' . htmlspecialchars($data['email']) . '</div>
                    <div class="field"><strong>Phone:</strong> ' . htmlspecialchars($data['phone']) . '</div>
                    <div class="field"><strong>Age:</strong> ' . htmlspecialchars($data['age']) . '</div>
                    <div class="field"><strong>Occupation:</strong> ' . htmlspecialchars($data['occupation'] ?: 'Not provided') . '</div>
                </div>
                
                <div class="section">
                    <h3>📅 Availability</h3>
                    <div class="field">' . nl2br(htmlspecialchars($data['availability'])) . '</div>
                </div>
                
                <div class="section">
                    <h3>🎯 Areas of Interest</h3>
                    <div class="field">' . nl2br(htmlspecialchars($data['volunteer_areas'])) . '</div>
                </div>
                
                <div class="section">
                    <h3>💭 Experience with Animals</h3>
                    <div class="field">' . nl2br(htmlspecialchars($data['experience'] ?: 'No previous experience mentioned')) . '</div>
                </div>
                
                <div class="section">
                    <h3>❤️ Why They Want to Volunteer</h3>
                    <div class="field">' . nl2br(htmlspecialchars($data['why_volunteer'])) . '</div>
                </div>
                
                <div class="section">
                    <h3>📞 Emergency Contact</h3>
                    <div class="field"><strong>Name:</strong> ' . htmlspecialchars($data['emergency_contact_name'] ?: 'Not provided') . '</div>
                    <div class="field"><strong>Phone:</strong> ' . htmlspecialchars($data['emergency_contact_phone'] ?: 'Not provided') . '</div>
                </div>';
                
    if (!empty($data['additional_info'])) {
        $html .= '
                <div class="section">
                    <h3>ℹ️ Additional Information</h3>
                    <div class="field">' . nl2br(htmlspecialchars($data['additional_info'])) . '</div>
                </div>';
    }
    
    $html .= '
            </div>
            
            <div class="footer">
                <p><strong>Pawfect Home - Dog Adoption Center</strong></p>
                <p>Please respond to the applicant at: ' . htmlspecialchars($data['email']) . '</p>
                <p style="font-size: 0.8em; margin-top: 10px;">Application received on ' . date('F j, Y \a\t g:i A') . '</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Build plain text email content
 */
function build_volunteer_email_text($data) {
    $text = "NEW VOLUNTEER APPLICATION\n";
    $text .= "========================\n\n";
    $text .= "Application ID: #" . $data['application_id'] . "\n\n";
    
    $text .= "PERSONAL INFORMATION\n";
    $text .= "-------------------\n";
    $text .= "Full Name: " . $data['full_name'] . "\n";
    $text .= "Email: " . $data['email'] . "\n";
    $text .= "Phone: " . $data['phone'] . "\n";
    $text .= "Age: " . $data['age'] . "\n";
    $text .= "Occupation: " . ($data['occupation'] ?: 'Not provided') . "\n\n";
    
    $text .= "AVAILABILITY\n";
    $text .= "------------\n";
    $text .= $data['availability'] . "\n\n";
    
    $text .= "AREAS OF INTEREST\n";
    $text .= "-----------------\n";
    $text .= $data['volunteer_areas'] . "\n\n";
    
    $text .= "EXPERIENCE WITH ANIMALS\n";
    $text .= "-----------------------\n";
    $text .= ($data['experience'] ?: 'No previous experience mentioned') . "\n\n";
    
    $text .= "WHY THEY WANT TO VOLUNTEER\n";
    $text .= "--------------------------\n";
    $text .= $data['why_volunteer'] . "\n\n";
    
    $text .= "EMERGENCY CONTACT\n";
    $text .= "-----------------\n";
    $text .= "Name: " . ($data['emergency_contact_name'] ?: 'Not provided') . "\n";
    $text .= "Phone: " . ($data['emergency_contact_phone'] ?: 'Not provided') . "\n\n";
    
    if (!empty($data['additional_info'])) {
        $text .= "ADDITIONAL INFORMATION\n";
        $text .= "----------------------\n";
        $text .= $data['additional_info'] . "\n\n";
    }
    
    $text .= "---\n";
    $text .= "Please respond to the applicant at: " . $data['email'] . "\n";
    $text .= "Application received on " . date('F j, Y \a\t g:i A') . "\n";
    
    return $text;
}
?>