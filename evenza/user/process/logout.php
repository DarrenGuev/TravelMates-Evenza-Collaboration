<?php
session_start();

// Determine logout type from parameter or referrer
$logoutType = isset($_GET['type']) ? $_GET['type'] : '';

// If no type parameter, try to determine from referrer
if (empty($logoutType)) {
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if (strpos($referer, 'admin.php') !== false || 
        strpos($referer, 'adminLogin.php') !== false ||
        strpos($referer, 'eventManagement.php') !== false ||
        strpos($referer, 'reservationsManagement.php') !== false ||
        strpos($referer, 'userManagement.php') !== false ||
        strpos($referer, 'reviewsManagement.php') !== false ||
        strpos($referer, 'smsInbox.php') !== false) {
        $logoutType = 'admin';
    } else {
        $logoutType = 'user';
    }
}

$isAdmin = isset($_SESSION['admin_id']);
$isUser = isset($_SESSION['user_id']);

// Handle logout based on type
if ($logoutType === 'admin' && $isAdmin) {
    // Clear admin session variables only
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
    
    // If user is also logged in, keep user session intact
    if (!$isUser) {
        // Only destroy session if user is not logged in
        session_destroy();
    }
    
    // Redirect to admin login
    header('Location: ../../admin/adminPages/adminLogin.php');
    exit;
} elseif ($logoutType === 'user' && $isUser) {
    // Clear user session variables only
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_mobile']);
    unset($_SESSION['user_role']);
    unset($_SESSION['userId']);
    unset($_SESSION['firstName']);
    unset($_SESSION['role']);
    
    // If admin is also logged in, keep admin session intact
    if (!$isAdmin) {
        // Only destroy session if admin is not logged in
        session_destroy();
    }
    
    // Redirect to user login page
    header('Location: ../pages/login.php');
    exit;
} else {
    // Fallback: if type doesn't match active session, log out both and redirect appropriately
    if ($isAdmin) {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_email']);
    }
    if ($isUser) {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_mobile']);
        unset($_SESSION['user_role']);
        unset($_SESSION['userId']);
        unset($_SESSION['firstName']);
        unset($_SESSION['role']);
    }
    
    // If no active sessions, destroy session
    if (!$isAdmin && !$isUser) {
        session_destroy();
    }
    
    // Redirect based on logout type requested, default to user login
    if ($logoutType === 'admin') {
        header('Location: ../../admin/adminPages/adminLogin.php');
    } else {
        header('Location: ../pages/login.php');
    }
    exit;
}
