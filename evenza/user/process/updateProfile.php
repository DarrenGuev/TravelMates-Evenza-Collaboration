<?php
header('Content-Type: application/json');
session_start();
require_once '../../core/connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = intval($_SESSION['user_id']);
$fullName = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['mobile'] ?? '');

if ($fullName === '' || $email === '' || $phone === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

$checkEmailQuery = "SELECT userId FROM users WHERE email = ? AND userId != ?";
$checkStmt = mysqli_prepare($conn, $checkEmailQuery);
if ($checkStmt) {
    mysqli_stmt_bind_param($checkStmt, "si", $email, $userId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    if (mysqli_num_rows($checkResult) > 0) {
        mysqli_stmt_close($checkStmt);
        echo json_encode(['success' => false, 'message' => 'This email is already registered to another account.']);
        exit;
    }
    mysqli_stmt_close($checkStmt);
}

$query = "UPDATE users SET fullName = ?, email = ?, phone = ? WHERE userId = ?";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    $error = mysqli_error($conn);
    error_log("Profile update error: " . $error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $error]);
    exit;
}

mysqli_stmt_bind_param($stmt, "sssi", $fullName, $email, $phone, $userId);
$ok = mysqli_stmt_execute($stmt);

if (!$ok) {
    $error = mysqli_error($conn);
    error_log("Profile update execution error: " . $error);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'message' => 'Failed to update profile. Please try again.']);
    exit;
}

mysqli_stmt_close($stmt);

$_SESSION['user_name'] = $fullName;
$_SESSION['user_email'] = $email;
$_SESSION['user_mobile'] = $phone;

echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
exit;

