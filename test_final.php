<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'includes/email_config.php';

if ($_POST['test_email'] ?? false) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($_POST['test_email']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Test - Email System Working!';
        $mail->Body = '<h1>✅ Email System Test Successful!</h1><p>Your PHPMailer + Gmail setup is working!</p>';
        
        $mail->send();
        $result = "✅ Test email sent successfully!";
    } catch (Exception $e) {
        $result = "❌ Error: " . $mail->ErrorInfo;
    }
}
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Final Email Test</h1>
    <?php if (isset($result)) echo "<p>$result</p>"; ?>
    <form method="POST">
        <input type="email" name="test_email" placeholder="Enter your email" required>
        <button type="submit">Test Email</button>
    </form>
</body>
</html>