<?php
/**
 * Mark SMS as read
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = isset($_SESSION['admin_id']) || (isset($_SESSION['user_id']) && isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin');
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

require_once '../../../core/connect.php';

$smsId = isset($_POST['smsId']) ? intval($_POST['smsId']) : 0;

if ($smsId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid SMS ID']);
    exit;
}

$query = "UPDATE sms_messages SET is_read = 1 WHERE sms_id = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $smsId);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'SMS marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating SMS']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing query']);
}

mysqli_close($conn);
?>

