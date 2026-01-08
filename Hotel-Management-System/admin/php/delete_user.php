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
    
    // Check if user exists
    $user = $userModel->find($userID);
    
    if (!$user) {
        header("Location: ../admin.php?error=User not found");
        exit();
    }
    
    // Delete user using model
    if ($userModel->delete($userID)) {
        header("Location: ../admin.php?success=User deleted successfully");
    } else {
        header("Location: ../admin.php?error=Failed to delete user");
    }
    exit();
} else {
    header("Location: ../admin.php");
    exit();
}
?>
