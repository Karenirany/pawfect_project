<?php
// test_phpmailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

if ($_POST['email'] ?? false) {
    $to_email = $_POST['email'];
    $gmail_user = $_POST['gmail_user'];
    $gmail_pass = $_POST['gmail_pass'];
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmail_user;
        $mail->Password = $gmail_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom($gmail_user, 'Test From Website');
        $mail->addAddress($to_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'PHPMailer Test - Success!';
        $mail->Body = '
            <h1>PHPMailer Test Successful! 🎉</h1>
            <p>If you received this, PHPMailer with Gmail SMTP is working!</p>
            <p>Time: ' . date('Y-m-d H:i:s') . '</p>
        ';
        $mail->AltBody = 'PHPMailer Test Successful! Time: ' . date('Y-m-d H:i:s');
        
        $mail->send();
        $result = "✅ Email sent successfully using PHPMailer!";
        $color = "green";
    } catch (Exception $e) {
        $result = "❌ Error: " . $mail->ErrorInfo;
        $color = "red";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHPMailer Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        input, button { padding: 10px; margin: 5px; }
        input { width: 100%; padding: 8px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        .result { padding: 15px; margin: 15px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 PHPMailer + Gmail Test</h1>
        
        <?php if (isset($result)): ?>
            <div class="result" style="background: <?php echo $color === 'green' ? '#d4ffd4' : '#ffd4d4'; ?>">
                <?php echo $result; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Your Gmail Address:</label>
                <input type="email" name="gmail_user" placeholder="your-email@gmail.com" required>
            </div>
            
            <div class="form-group">
                <label>Gmail App Password:</label>
                <input type="password" name="gmail_pass" placeholder="16-digit app password" required>
                <small>Get this from Google Account → Security → App passwords</small>
            </div>
            
            <div class="form-group">
                <label>Send Test Email To:</label>
                <input type="email" name="email" placeholder="recipient@email.com" required>
            </div>
            
            <button type="submit" style="background: #4285f4; color: white; padding: 12px 20px; border: none; border-radius: 5px;">
                Send Test Email
            </button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #e8f4f8; border-radius: 5px;">
            <h3>🔐 How to Get Gmail App Password:</h3>
            <ol>
                <li>Go to <a href="https://myaccount.google.com/" target="_blank">Google Account</a></li>
                <li>Click "Security" on left sidebar</li>
                <li>Enable "2-Step Verification" if not already on</li>
                <li>Go to "App passwords"</li>
                <li>Select "Mail" and "Other" (name it "Website")</li>
                <li>Copy the 16-character password</li>
                <li>Use that password above (NOT your regular Gmail password)</li>
            </ol>
        </div>
    </div>
</body>
</html>