<?php
// includes/db.php
session_start();

$host = 'sql305.infinityfree.com';
$dbname = 'if0_40544813_dog_adoption';
$username = 'if0_40544813'; 
$password = 'LwCxBLUuGX8xa'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set timezone to Lebanon (Beirut) - UTC+2 (or UTC+3 during daylight saving)
    date_default_timezone_set('Asia/Beirut');
    
    // Set MySQL timezone to match PHP timezone
    $pdo->exec("SET time_zone = '+02:00'");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>