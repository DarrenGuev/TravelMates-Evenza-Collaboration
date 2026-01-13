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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    ob_end_flush();
    exit;
}

$eventId = isset($_POST['eventId']) ? intval($_POST['eventId']) : 0;

if ($eventId <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    ob_end_flush();
    exit;
}

$query = "DELETE FROM events WHERE eventId = ?";
$stmt = mysqli_prepare($conn, $query);

ob_clean();

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $eventId);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
    } else {
        $errorMsg = mysqli_error($conn);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $errorMsg]);
    }
    mysqli_stmt_close($stmt);
} else {
    $errorMsg = mysqli_error($conn);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $errorMsg]);
}

ob_end_flush();
exit;
