<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . '/SmsService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (empty($input['phone_number']) || empty($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Phone number and message are required']);
    exit();
}

$phoneNumber = trim($input['phone_number']);
$message = trim($input['message']);
$bookingId = isset($input['booking_id']) ? (int)$input['booking_id'] : null;

try {
    $smsService = new SmsService();
    $result = $smsService->sendCustomSms($phoneNumber, $message, $bookingId);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'SMS sent successfully',
            'data' => $result
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'data' => $result
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
