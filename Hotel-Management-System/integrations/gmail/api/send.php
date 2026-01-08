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
$requiredFields = ['to', 'subject', 'body'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit();
    }
}

// Validate email format
if (!filter_var($input['to'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit();
}

try {
    $emailService = new EmailService();
    
    $options = [];
    if (!empty($input['cc'])) $options['cc'] = $input['cc'];
    if (!empty($input['bcc'])) $options['bcc'] = $input['bcc'];
    if (!empty($input['replyTo'])) $options['replyTo'] = $input['replyTo'];
    
    $result = $emailService->sendEmail(
        $input['to'],
        $input['subject'],
        $input['body'],
        $options
    );
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully',
            'message_id' => $result['message_id'] ?? null
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to send email'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
