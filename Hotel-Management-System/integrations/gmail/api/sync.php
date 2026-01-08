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

try {
    $emailService = new EmailService();
    
    $folder = $input['folder'] ?? 'INBOX';
    $limit = (int)($input['limit'] ?? 50);
    
    // Fetch emails from IMAP - this also syncs to database
    $result = $emailService->fetchEmails($folder, $limit, 0);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Emails synced successfully',
            'synced_count' => count($result['emails']),
            'total_in_folder' => $result['total']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to sync emails'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
