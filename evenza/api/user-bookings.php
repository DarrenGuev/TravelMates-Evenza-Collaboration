<?php
/**
 * User Bookings API Endpoint
 * 
 * Returns user booking/reservation data in JSON format.
 * 
 * Usage:
 *   GET /api/user-bookings.php?userId=123  - Get all reservations for a specific user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use GET.']);
    exit();
}

// Check if userId parameter is provided
if (!isset($_GET['userId']) || !is_numeric($_GET['userId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'userId parameter is required and must be numeric']);
    exit();
}

$userId = (int)$_GET['userId'];

require_once __DIR__ . '/../core/connect.php';

try {
    $query = "
        SELECT r.*,
               e.title as eventName,
               e.venue,
               e.eventImage,
               p.packageName,
               r.reservationDate as date,
               r.paymentDeadline,
               r.userCancelled,
               r.status,
               r.totalAmount,
               r.createdAt,
               CONCAT(COALESCE(r.startTime, ''), ' - ', COALESCE(r.endTime, '')) as time,
               'evenza' as source
        FROM reservations r
        LEFT JOIN events e ON r.eventId = e.eventId
        LEFT JOIN packages p ON r.packageId = p.packageId
        WHERE r.userId = ?
        ORDER BY r.reservationDate DESC, r.createdAt DESC
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $reservations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Convert numeric fields to appropriate types
        $row['reservationId'] = (int)$row['reservationId'];
        $row['userId'] = (int)$row['userId'];
        $row['eventId'] = isset($row['eventId']) ? (int)$row['eventId'] : null;
        $row['packageId'] = isset($row['packageId']) ? (int)$row['packageId'] : null;
        $row['totalAmount'] = (float)$row['totalAmount'];
        
        // Normalize field names for cross-system compatibility
        $row['bookingId'] = $row['reservationId']; // Alias for compatibility
        $row['bookingType'] = 'event';
        $row['bookingStatus'] = $row['status'];
        $row['paymentStatus'] = 'paid'; // Evenza reservations are typically paid
        
        $reservations[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $reservations,
        'count' => count($reservations),
        'source' => 'evenza'
    ]);
    
} catch (Exception $e) {
    error_log('User Bookings API Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch bookings at this time',
        'message' => $e->getMessage()
    ]);
}
?>
