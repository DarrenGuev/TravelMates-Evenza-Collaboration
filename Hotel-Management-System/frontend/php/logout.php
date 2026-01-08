<?php
// Include class autoloader
require_once __DIR__ . '/../../classes/autoload.php';
require_once __DIR__ . '/../../config.php';

// Use Auth class to logout
Auth::logout();

header("Location: " . BASE_URL . "/index.php");
exit();
?>
