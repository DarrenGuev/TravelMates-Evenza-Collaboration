<?php
/**
 * User Bookings API Endpoint (Hotel Management System)
 * 
 * Returns user booking data from both the Hotel Management System and Evenza.
 * 
 * Usage:
 *   GET /integrations/api/user-bookings.php?userId=123  - Get all bookings for a specific user
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

require_once __DIR__ . '/../../config.php';
require_once DBCONNECT_PATH . '/connect.php';

try {
    // Fetch bookings from Hotel Management System database
    $query = "
        SELECT b.*,
               r.roomName,
               r.imagePath,
               rt.typeName as roomType,
               b.checkInDate,
               b.checkOutDate,
               b.totalPrice,
               b.bookingStatus,
               b.paymentStatus,
               b.paymentMethod,
               b.createdAt,
               'hotel' as source
        FROM bookings b
        LEFT JOIN rooms r ON b.roomID = r.roomID
        LEFT JOIN room_types rt ON r.typeID = rt.typeID
        WHERE b.userID = ?
        ORDER BY b.checkInDate DESC, b.createdAt DESC
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        // Convert numeric fields to appropriate types
        $row['bookingID'] = (int)$row['bookingID'];
        $row['userID'] = (int)$row['userID'];
        $row['roomID'] = isset($row['roomID']) ? (int)$row['roomID'] : null;
        $row['totalPrice'] = (float)$row['totalPrice'];
        
        // Normalize field names for cross-system compatibility
        $row['bookingId'] = $row['bookingID']; // Alias for consistency
        $row['bookingType'] = 'hotel';
        
        $bookings[] = $row;
    }
    
    $stmt->close();
    
    // Fetch bookings from Evenza API
    $evenzaBookings = [];
    try {
        $evenzaApiUrl = 'http://10.77.123.198/TravelMates-Evenza-Collaboration/evenza/api/user-bookings.php?userId=' . $userId;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $evenzaApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if (!$curlError && $httpCode === 200) {
            $evenzaData = json_decode($response, true);
            if (isset($evenzaData['success']) && $evenzaData['success'] && isset($evenzaData['data'])) {
                $evenzaBookings = $evenzaData['data'];
            }
        }
    } catch (Exception $e) {
        // Log the error but don't fail the entire request
        error_log('Failed to fetch Evenza bookings: ' . $e->getMessage());
    }
    
    // Combine bookings from both systems
    $allBookings = array_merge($bookings, $evenzaBookings);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $allBookings,
        'count' => count($allBookings),
        'hotelCount' => count($bookings),
        'evenzaCount' => count($evenzaBookings)
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
