<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

require_once __DIR__ . '/../../classes/autoload.php';

Auth::requireAdmin('../admin.php');

$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = (int)$_POST['userID'];
    $newRole = trim($_POST['role'] ?? '');
    
    // Validate role
    if ($newRole !== 'user' && $newRole !== 'admin') {
        header("Location: ../admin.php?error=Invalid role selected");
        exit();
    }
    
    // Update user role using model
    if ($userModel->update($userID, ['role' => $newRole])) {
        header("Location: ../admin.php?success=User role updated successfully");
    } else {
        header("Location: ../admin.php?error=Failed to update user role");
    }
    exit();
} else {
    header("Location: ../admin.php");
    exit();
}
?>
