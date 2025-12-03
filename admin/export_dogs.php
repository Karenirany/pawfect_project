<?php
// admin/export_dogs.php
session_start();
require_once '../includes/db.php';

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Get all dogs
$stmt = $pdo->query("SELECT * FROM dogs ORDER BY dog_id");
$dogs = $stmt->fetchAll();

// Set headers for download
header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="dogs_export_' . date('Y-m-d_H-i-s') . '.xml"');

// Generate XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<dogs_export>
    <export_info>
        <export_date><?php echo date('Y-m-d H:i:s'); ?></export_date>
        <total_dogs><?php echo count($dogs); ?></total_dogs>
        <exported_by><?php echo htmlspecialchars($_SESSION['username']); ?></exported_by>
    </export_info>
    
    <dogs>
        <?php foreach ($dogs as $dog): ?>
        <dog>
            <dog_id><?php echo $dog['dog_id']; ?></dog_id>
            <name><![CDATA[<?php echo $dog['name']; ?>]]></name>
            <breed><![CDATA[<?php echo $dog['breed']; ?>]]></breed>
            <age><?php echo $dog['age']; ?></age>
            <gender><?php echo htmlspecialchars($dog['gender']); ?></gender>
            <size><?php echo htmlspecialchars($dog['size']); ?></size>
            <description><![CDATA[<?php echo $dog['description']; ?>]]></description>
            <image_path><?php echo htmlspecialchars($dog['image_path']); ?></image_path>
            <status><?php echo htmlspecialchars($dog['status']); ?></status>
            <created_at><?php echo $dog['created_at']; ?></created_at>
            <updated_at><?php echo $dog['updated_at']; ?></updated_at>
        </dog>
        <?php endforeach; ?>
    </dogs>
</dogs_export>