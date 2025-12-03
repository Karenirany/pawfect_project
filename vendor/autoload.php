<?php
// vendor/autoload.php - Simplified version for InfinityFree
function firebase_autoload($class) {
    $prefix = 'Kreait\\';
    $base_dir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
}

spl_autoload_register('firebase_autoload');

// Include required dependencies manually
require_once __DIR__ . '/guzzlehttp/guzzle/src/functions_include.php';
require_once __DIR__ . '/guzzlehttp/psr7/src/functions_include.php';
require_once __DIR__ . '/guzzlehttp/promises/src/functions_include.php';
?>