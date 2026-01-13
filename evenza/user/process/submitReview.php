<?php
/**
 * API endpoint to submit reviews and feedback
 * Only allows reviews for completed reservations with successful payments
 */

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

require_once '../../core/connect.php';

try {
    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['error' => 'You must be logged in to submit a review']);
        exit;
    }

    $userId = intval($_SESSION['user_id']);

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
        exit;
    }

    $reservationId = isset($input['reservationId']) ? intval($input['reservationId']) : 0;
    $rating = isset($input['rating']) ? intval($input['rating']) : 0;
    $comment = isset($input['comment']) ? trim($input['comment']) : '';

    if ($reservationId <= 0) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Invalid reservation ID']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Rating must be between 1 and 5 stars']);
        exit;
    }

    $verifyQuery = "SELECT r.reservationId, r.userId, r.eventId, r.status, 
                           p.paymentStatus 
                    FROM reservations r
                    LEFT JOIN payments p ON r.reservationId = p.reservationId
                    WHERE r.reservationId = ? AND r.userId = ?";
    
    $verifyStmt = mysqli_prepare($conn, $verifyQuery);
    if (!$verifyStmt) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }

    mysqli_stmt_bind_param($verifyStmt, "ii", $reservationId, $userId);
    mysqli_stmt_execute($verifyStmt);
    $verifyResult = mysqli_stmt_get_result($verifyStmt);
    $reservation = mysqli_fetch_assoc($verifyResult);
    mysqli_stmt_close($verifyStmt);

    if (!$reservation) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['error' => 'Reservation not found or does not belong to you']);
        exit;
    }

    if (empty($reservation['paymentStatus']) || strtolower($reservation['paymentStatus']) !== 'completed') {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['error' => 'You can only review reservations with completed payments']);
        exit;
    }

    $checkQuery = "SELECT reviewId FROM reviews WHERE reservationId = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $reservationId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existingReview = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($checkStmt);

    if ($existingReview) {
        ob_end_clean();
        http_response_code(409);
        echo json_encode(['error' => 'You have already submitted a review for this reservation']);
        exit;
    }

    $eventId = !empty($reservation['eventId']) ? intval($reservation['eventId']) : null;
    
    if ($eventId !== null) {
        $insertQuery = "INSERT INTO reviews (reservationId, userId, eventId, rating, comment) 
                        VALUES (?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        if (!$insertStmt) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save review']);
            exit;
        }
        mysqli_stmt_bind_param($insertStmt, "iiiis", $reservationId, $userId, $eventId, $rating, $comment);
    } else {
        $insertQuery = "INSERT INTO reviews (reservationId, userId, rating, comment) 
                        VALUES (?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        if (!$insertStmt) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save review']);
            exit;
        }
        mysqli_stmt_bind_param($insertStmt, "iiis", $reservationId, $userId, $rating, $comment);
    }
    
    if (mysqli_stmt_execute($insertStmt)) {
        $reviewId = mysqli_insert_id($conn);
        mysqli_stmt_close($insertStmt);
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your review!',
            'reviewId' => $reviewId
        ]);
    } else {
        $error = mysqli_stmt_error($insertStmt);
        error_log("Review Insert Error: " . $error);
        error_log("Review Data - ReservationId: $reservationId, UserId: $userId, EventId: " . ($eventId ?? 'NULL') . ", Rating: $rating, Comment: " . substr($comment, 0, 50));
        mysqli_stmt_close($insertStmt);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save review. Please try again.']);
    }

} catch (Exception $e) {
    ob_end_clean();
    error_log('Review submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while submitting your review']);
}

set_exception_handler(function($exception) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred']);
    exit;
});

