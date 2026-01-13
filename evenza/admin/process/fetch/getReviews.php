<?php
/**
 * API endpoint to get reviews for an event
 */

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

require_once '../../../core/connect.php';

try {
    $eventId = isset($_GET['eventId']) ? intval($_GET['eventId']) : 0;

    if ($eventId <= 0) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Invalid event ID']);
        exit;
    }

    $query = "SELECT r.reviewId, r.rating, r.comment, r.createdAt,
                     u.fullName AS userName,
                     res.reservationCode
              FROM reviews r
              INNER JOIN users u ON r.userId = u.userId
              INNER JOIN reservations res ON r.reservationId = res.reservationId
              WHERE r.eventId = ?
              ORDER BY r.createdAt DESC
              LIMIT 50";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "i", $eventId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $reviews = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = [
            'reviewId' => intval($row['reviewId']),
            'rating' => intval($row['rating']),
            'comment' => $row['comment'],
            'userName' => $row['userName'],
            'reservationCode' => $row['reservationCode'],
            'createdAt' => $row['createdAt']
        ];
    }
    mysqli_stmt_close($stmt);

    // Calculate average rating
    $avgRating = 0;
    $totalReviews = count($reviews);
    if ($totalReviews > 0) {
        $sum = array_sum(array_column($reviews, 'rating'));
        $avgRating = round($sum / $totalReviews, 1);
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'averageRating' => $avgRating,
        'totalReviews' => $totalReviews
    ]);

} catch (Exception $e) {
    ob_end_clean();
    error_log('Get reviews error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching reviews']);
}

set_exception_handler(function($exception) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred']);
    exit;
});

