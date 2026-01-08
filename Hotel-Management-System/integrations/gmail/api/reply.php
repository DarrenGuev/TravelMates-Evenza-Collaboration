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
if (empty($input['emailId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required field: emailId']);
    exit();
}

if (empty($input['body'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required field: body']);
    exit();
}

try {
    $emailService = new EmailService();
    
    $folder = $input['folder'] ?? 'INBOX';
    
    $result = $emailService->replyToEmail(
        (int)$input['emailId'],
        $input['body'],
        $folder
    );
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Reply sent successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to send reply'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
