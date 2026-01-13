<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

session_start();
require_once '../../../core/connect.php';

if (!isset($_SESSION['admin_id']) && !(isset($_SESSION['user_id']) && isset($_SESSION['role']) && (strtolower($_SESSION['role']) === 'admin'))) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    ob_end_flush();
    exit;
}

$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;

if ($userId <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    ob_end_flush();
    exit;
}

$query = "SELECT userid, firstName, lastName, fullName, email, phone, role FROM users WHERE userid = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    ob_clean();
    
    if ($user) {
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

ob_end_flush();
exit;
