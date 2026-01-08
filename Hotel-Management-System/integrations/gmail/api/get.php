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

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Validate required parameters
if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameter: id']);
    exit();
}

try {
    $emailService = new EmailService();
    
    $source = $_GET['source'] ?? 'imap';
    $emailId = $_GET['id'];
    
    if ($source === 'imap') {
        $folder = $_GET['folder'] ?? 'INBOX';
        $result = $emailService->getEmail((int)$emailId, $folder);
    } else {
        // Get from database
        $filters = ['id' => (int)$emailId];
        $result = $emailService->getEmailsFromDatabase($filters, 1, 0);
        
        if ($result['success'] && !empty($result['emails'])) {
            $result = [
                'success' => true,
                'email' => $result['emails'][0]
            ];
            // Mark as read
            $emailService->markAsRead((int)$emailId);
        } else {
            $result = [
                'success' => false,
                'error' => 'Email not found'
            ];
        }
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
