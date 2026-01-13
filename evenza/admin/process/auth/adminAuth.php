<?php
session_start();

if (!isset($_SESSION['admin_id']) && !(isset($_SESSION['user_id']) && isset($_SESSION['role']) && (strtolower($_SESSION['role']) === 'admin'))) {
    // Redirect back to the admin login page (absolute path to avoid relative path issues)
    header('Location: /evenza/admin/adminPages/adminLogin.php');
    exit;
}
