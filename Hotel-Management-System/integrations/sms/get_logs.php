<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . '/SmsService.php';

try {
    $smsService = new SmsService();

    // Get filter parameters
    $filters = [];
    
    if (!empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    
    if (!empty($_GET['direction'])) {
        $filters['direction'] = $_GET['direction'];
    }
    
    if (!empty($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    }
    
    if (!empty($_GET['phone_number'])) {
        $filters['phone_number'] = $_GET['phone_number'];
    }

    // Pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(10, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    // Get logs
    $logs = $smsService->getSmsLogs($filters, $limit, $offset);
    $totalCount = $smsService->getTotalCount($filters);
    $totalPages = ceil($totalCount / $limit);

    // Get statistics
    $stats = $smsService->getStatistics();

    echo json_encode([
        'success' => true,
        'data' => [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalCount,
                'per_page' => $limit
            ],
            'statistics' => $stats
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
