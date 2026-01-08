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

try {
    $emailService = new EmailService();
    
    $source = $_GET['source'] ?? 'database';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    if ($source === 'imap') {
        // Fetch from Gmail IMAP
        $folder = $_GET['folder'] ?? 'INBOX';
        $result = $emailService->fetchEmails($folder, $limit, $offset);
    } else {
        // Fetch from database
        $filters = [];
        
        if (!empty($_GET['direction'])) {
            $filters['direction'] = $_GET['direction'];
        }
        
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['is_read'])) {
            $filters['is_read'] = (int)$_GET['is_read'];
        }
        
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        $result = $emailService->getEmailsFromDatabase($filters, $limit, $offset);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
