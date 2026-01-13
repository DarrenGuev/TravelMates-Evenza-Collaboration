<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logFile = __DIR__ . '/../../../php_errors.log';
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    $message = sprintf('[%s] PHP %s: %s in %s on line %d', date('c'), $errno, $errstr, $errfile, $errline);
    error_log($message . PHP_EOL, 3, $logFile);
    return false; // Let normal error handling continue for fatal errors
});

set_exception_handler(function($ex) use ($logFile) {
    $message = sprintf('[%s] Uncaught Exception: %s in %s on line %d%s%s', date('c'), $ex->getMessage(), $ex->getFile(), $ex->getLine(), PHP_EOL, $ex->getTraceAsString());
    error_log($message . PHP_EOL, 3, $logFile);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
    exit;
});

function respondJson($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        respondJson(false, 'Please login to cancel a reservation');
    }

    require_once __DIR__ . '/../../../core/connect.php';
    require_once __DIR__ . '/../../../includes/helpers.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondJson(false, 'Invalid request method');
    }

    $reservationId = isset($_POST['reservationId']) ? intval($_POST['reservationId']) : 0;
    $userId = $_SESSION['user_id'];

    if ($reservationId <= 0) {
        respondJson(false, 'Invalid reservation ID');
    }

    if (!$conn) {
        respondJson(false, 'Database connection failed');
    }

    // Verify that the reservation belongs to the logged-in user
    $verifyQuery = "SELECT reservationId, status, userCancelled FROM reservations WHERE reservationId = ? AND userId = ?";
    $verifyStmt = mysqli_prepare($conn, $verifyQuery);

    if (!$verifyStmt) {
        respondJson(false, 'Error verifying reservation');
    }

    mysqli_stmt_bind_param($verifyStmt, "ii", $reservationId, $userId);
    mysqli_stmt_execute($verifyStmt);
    $verifyResult = mysqli_stmt_get_result($verifyStmt);
    $reservation = mysqli_fetch_assoc($verifyResult);
    mysqli_stmt_close($verifyStmt);

    if (!$reservation) {
        respondJson(false, 'Reservation not found or you do not have permission to cancel it');
    }

    // Check if already cancelled by user
    if (!empty($reservation['userCancelled'])) {
        respondJson(false, 'This reservation has already been cancelled');
    }

    // Update reservation: set status to cancelled and mark as user-cancelled
    $updateQuery = "UPDATE reservations SET status = 'cancelled', userCancelled = 1 WHERE reservationId = ? AND userId = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);

    if (!$updateStmt) {
        respondJson(false, 'Error preparing update query');
    }

    mysqli_stmt_bind_param($updateStmt, "ii", $reservationId, $userId);

    if (mysqli_stmt_execute($updateStmt)) {
        $affectedRows = mysqli_stmt_affected_rows($updateStmt);
        if ($affectedRows > 0) {
            // Attempt SMS notification; log failure but do not block cancellation
            $smsResult = sendReservationStatusSMS($conn, $reservationId, 'cancelled', 'user');
            if (!$smsResult) {
                error_log('SMS notification failed for reservation ' . $reservationId . PHP_EOL, 3, $logFile);
            }
            respondJson(true, 'Reservation cancelled successfully');
        } else {
            respondJson(false, 'Failed to cancel reservation');
        }
    } else {
        respondJson(false, 'Error cancelling reservation');
    }
} catch (Throwable $t) {
    $message = sprintf('[%s] Throwable: %s in %s on line %d%s%s', date('c'), $t->getMessage(), $t->getFile(), $t->getLine(), PHP_EOL, $t->getTraceAsString());
    error_log($message . PHP_EOL, 3, $logFile);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
    exit;
} finally {
    if (isset($updateStmt) && $updateStmt instanceof mysqli_stmt) {
        mysqli_stmt_close($updateStmt);
    }
    if (isset($conn) && $conn instanceof mysqli) {
        mysqli_close($conn);
    }
}
?>
