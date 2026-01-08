<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once __DIR__ . '/../../classes/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstname'] ?? '');
    $lastName = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {
        header("Location: ../register.php?error=Passwords do not match");
        exit();
    }

    // Use the User model for registration
    $userModel = new User();

    // Check if email exists
    if ($userModel->emailExists($email)) {
        header("Location: ../register.php?error=Email already registered");
        exit();
    }

    // Check if username exists
    if ($userModel->usernameExists($username)) {
        header("Location: ../register.php?error=Username already taken");
        exit();
    }

    // Register the user
    $userData = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'username' => $username,
        'phoneNumber' => $phone,
        'password' => $password,
        'role' => 'user'
    ];
    
    $result = $userModel->register($userData);
    
    if ($result['success']) {
        header("Location: ../login.php?success=Registration successful! Please login.");
        exit();
    } else {
        $errorMsg = urlencode($result['message']);
        header("Location: ../register.php?error=" . $errorMsg);
        exit();
    }
} else {
    header("Location: ../register.php");
    exit();
}
?>