<?php
function generateAvatar($username) {
    // Clean the username for URL safety
    $safeName = urlencode($username);

    // DiceBear API (bottts – robot style)
    return "https://api.dicebear.com/9.x/bottts/svg?seed=" . $safeName;
}
?>
