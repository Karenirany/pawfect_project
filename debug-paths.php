<?php
// debug-paths.php - Place this in your root directory
echo "<h2>Path Debug Information</h2>";
echo "<pre>";

echo "Current file: " . __FILE__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Base URL: http://" . $_SERVER['HTTP_HOST'] . "\n\n";

// Test if files exist
$files_to_test = [
    'pages/my_requests.php',
    'pages/logout.php',
    'includes/header.php',
    'index.php'
];

foreach ($files_to_test as $file) {
    $exists = file_exists($file) ? '✅ EXISTS' : '❌ MISSING';
    echo "$exists: $file\n";
}

echo "</pre>";
?>