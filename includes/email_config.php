<?php
// includes/email_config.php

// For GMAIL (Recommended):
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'pawfect.adoption.lb@gmail.com');
define('SMTP_PASSWORD', 'xben fezu dlgr jbhx'); // Gmail App Password
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');



// For OTHER EMAIL PROVIDERS:
/*
// Outlook/Office365:
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);

// Yahoo:
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
*/

define('FROM_EMAIL', 'pawfect.adoption.lb@gmail.com');
define('FROM_NAME', 'Pawfect Adoption Team');
?>