<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once CLASSES_PATH . '/autoload.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || empty(trim($input['username']))) {
    echo json_encode(['success' => false, 'message' => 'Username is required.']);
    exit;
}

if (!isset($input['phoneNumber']) || empty(trim($input['phoneNumber']))) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
    exit;
}

if (!isset($input['newPassword']) || empty(trim($input['newPassword']))) {
    echo json_encode(['success' => false, 'message' => 'New password is required.']);
    exit;
}

$username = trim($input['username']);
$phoneNumber = trim($input['phoneNumber']);
$newPassword = $input['newPassword'];

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

if (!isset($_SESSION['forgot_password_verified']) || $_SESSION['forgot_password_verified'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Please verify your phone number first.']);
    exit;
}

if (!isset($_SESSION['forgot_password_username']) || $_SESSION['forgot_password_username'] !== $username) {
    echo json_encode(['success' => false, 'message' => 'Username mismatch. Please start over.']);
    exit;
}

if (!isset($_SESSION['forgot_password_phone']) || $_SESSION['forgot_password_phone'] !== $phoneNumber) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
    exit;
}

if (!isset($_SESSION['forgot_password_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
    exit;
}

$userID = $_SESSION['forgot_password_user_id'];

try {
    $user = new User();
    $success = $user->updatePassword($userID, $newPassword);

    if ($success) {//clear all forgot password session data
        unset($_SESSION['forgot_password_otp']);
        unset($_SESSION['forgot_password_username']);
        unset($_SESSION['forgot_password_phone']);
        unset($_SESSION['forgot_password_user_id']);
        unset($_SESSION['forgot_password_otp_expiry']);
        unset($_SESSION['forgot_password_verified']);

        echo json_encode([
            'success' => true, 
            'message' => 'Password reset successful. Redirecting to login...'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password. Please try again.']);
    }
} catch (Exception $e) {
    error_log('Password Reset Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
