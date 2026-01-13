<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = isset($_SESSION['admin_id']) || (isset($_SESSION['user_id']) && isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin');
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../../core/connect.php';
require_once '../../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$reservationId = isset($_POST['reservationId']) ? intval($_POST['reservationId']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($reservationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
    exit;
}

if (!in_array(strtolower($status), ['pending', 'confirmed', 'cancelled', 'completed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$status = strtolower($status);

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if reservation was cancelled by user (admin cannot modify user-cancelled reservations)
// Also check paymentDeadline to detect non-payment cancellations
$checkQuery = "SELECT status, userCancelled, paymentDeadline FROM reservations WHERE reservationId = ?";
$checkStmt = mysqli_prepare($conn, $checkQuery);
$userCancelled = false;
$oldStatus = '';
$paymentDeadline = null;
if ($checkStmt) {
    mysqli_stmt_bind_param($checkStmt, "i", $reservationId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    if ($checkRow = mysqli_fetch_assoc($checkResult)) {
        $oldStatus = strtolower($checkRow['status']);
        $userCancelled = (bool)$checkRow['userCancelled'];
        $paymentDeadline = $checkRow['paymentDeadline'];
    }
    mysqli_stmt_close($checkStmt);
}

// Prevent admin from modifying user-cancelled reservations
if ($userCancelled) {
    echo json_encode(['success' => false, 'message' => 'Cannot modify reservation: User has cancelled this booking']);
    exit;
}

// Build update query with conditional fields
$updateFields = ["status = ?"];
$params = [$status];
$types = "s";

// If status is changing to 'confirmed', set confirmedAt and paymentDeadline (2 days from now)
if ($oldStatus !== 'confirmed' && $status === 'confirmed') {
    $updateFields[] = "confirmedAt = NOW()";
    $updateFields[] = "paymentDeadline = DATE_ADD(NOW(), INTERVAL 2 DAY)";
}

$query = "UPDATE reservations SET " . implode(", ", $updateFields) . " WHERE reservationId = ?";
$params[] = $reservationId;
$types .= "i";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($stmt)) {
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        if ($affectedRows > 0) {
            if ($oldStatus !== $status && ($status === 'confirmed' || $status === 'cancelled')) {
                // Determine cancellation type based on previous status
                $cancellationType = 'other'; // Default: other reasons
                
                if ($status === 'cancelled') {
                    if ($oldStatus === 'pending') {
                        // Pending → Cancelled: Date/time occupied
                        $cancellationType = 'date_occupied';
                    } elseif ($oldStatus === 'confirmed') {
                        // Confirmed → Cancelled: Always non-payment
                        // When admin cancels a confirmed reservation, it's because payment wasn't settled
                        // Check if payment deadline exists (should always exist for confirmed reservations)
                        if ($paymentDeadline && !empty($paymentDeadline) && $paymentDeadline !== '0000-00-00 00:00:00' && $paymentDeadline !== null) {
                            $cancellationType = 'non_payment';
                        } else {
                            // Even if no deadline set, confirmed → cancelled is still non-payment
                            $cancellationType = 'non_payment';
                        }
                    }
                }
                
                sendReservationStatusSMS($conn, $reservationId, $status, $cancellationType);
            }
            
            echo json_encode(['success' => true, 'message' => 'Reservation status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Reservation not found or status unchanged']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating status']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing query']);
}

mysqli_close($conn);
?>
