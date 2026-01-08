<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../EmailService.php';
require_once __DIR__ . '/../../dbconnect/connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get JSON input or form data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Validate required fields
if (empty($input['bookingId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required field: bookingId']);
    exit();
}

try {
    // Get booking details
    $bookingId = (int)$input['bookingId'];
    
    $stmt = $conn->prepare("
        SELECT b.*, r.roomName, rt.roomType, u.firstName, u.lastName, u.email
        FROM bookings b
        INNER JOIN rooms r ON b.roomID = r.roomID
        INNER JOIN roomtypes rt ON r.roomTypeId = rt.roomTypeID
        INNER JOIN users u ON b.userID = u.userID
        WHERE b.bookingID = ?
    ");
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit();
    }
    
    $booking = $result->fetch_assoc();
    $booking['customerName'] = trim($booking['firstName'] . ' ' . $booking['lastName']);
    
    $emailService = new EmailService();
    $sendResult = $emailService->sendBookingReceipt($booking);
    
    if ($sendResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Receipt sent successfully to ' . $booking['email']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $sendResult['error'] ?? 'Failed to send receipt'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
