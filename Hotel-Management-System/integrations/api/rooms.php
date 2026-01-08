<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

//handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use GET.']);
    exit();
}

require_once __DIR__ . '/../../classes/autoload.php';

try {
    //initialize models
    $roomModel = new Room();
    $roomTypeModel = new RoomType();
    
    //get query parameters for filtering
    $roomTypeId = isset($_GET['room_type']) ? (int)$_GET['room_type'] : null;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : null;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    
    //fetch all rooms with their types
    $rooms = $roomModel->getAllWithType();
    
    //filter by room type if specified
    if ($roomTypeId !== null) {
        $rooms = array_filter($rooms, function($room) use ($roomTypeId) {
            return (int)$room['roomTypeId'] === $roomTypeId;
        });
        $rooms = array_values($rooms); // Re-index array
    }
    
    //get base URL for redirects
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Hotel-Management-System';
    
    //transform rooms data for API response
    $roomsData = [];
    $totalCount = count($rooms);
    
    //apply pagination if specified
    if ($limit !== null) {
        $rooms = array_slice($rooms, $offset, $limit);
    }
    
    foreach ($rooms as $room) {
        //get room features
        $features = $roomModel->getFeatures((int)$room['roomID']);
        $featuresList = array_map(function($feature) {
            return [
                'id' => (int)($feature['featureID'] ?? $feature['id'] ?? 0),
                'name' => $feature['featureName'] ?? $feature['name'] ?? '',
                'category' => $feature['category'] ?? null
            ];
        }, $features);
        
        //build room data
        $roomsData[] = [
            'id' => (int)$room['roomID'],
            'name' => $room['roomName'] ?? '',
            'type' => [
                'id' => (int)($room['roomTypeId'] ?? $room['room_type_id'] ?? 0),
                'name' => $room['roomTypeName'] ?? $room['room_type_name'] ?? 'Unknown'
            ],
            'capacity' => (int)($room['capacity'] ?? 0),
            'quantity' => (int)($room['quantity'] ?? 0),
            'price' => [
                'base' => (float)($room['base_price'] ?? 0),
                'currency' => 'USD',
                'formatted' => '$' . number_format($room['base_price'] ?? 0, 2)
            ],
            'image' => !empty($room['roomImg']) ? $baseUrl . '/admin/assets/' . $room['roomImg'] : null,
            'features' => $featuresList,
            'description' => $room['description'] ?? null,
            'available' => (int)($room['quantity'] ?? 0) > 0,
            'links' => [
                'view' => $baseUrl . '/frontend/rooms.php#room-' . $room['roomID'],
                'book' => $baseUrl . '/frontend/rooms.php?book=' . $room['roomID']
            ]
        ];
    }
    
    //get all room types for reference
    $roomTypes = $roomTypeModel->getAll();
    $roomTypesData = array_map(function($type) {
        return [
            'id' => (int)($type['roomTypeId'] ?? $type['id'] ?? 0),
            'name' => $type['roomTypeName'] ?? $type['name'] ?? 'Unknown'
        ];
    }, $roomTypes);
    
    //build response
    $response = [
        'success' => true,
        'data' => [
            'rooms' => $roomsData,
            'pagination' => [
                'total' => $totalCount,
                'count' => count($roomsData),
                'offset' => $offset,
                'limit' => $limit
            ],
            'filters' => [
                'room_types' => $roomTypesData
            ]
        ],
        'meta' => [
            'timestamp' => date('c'),
            'version' => '1.0',
            'endpoint' => $_SERVER['REQUEST_URI']
        ]
    ];
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
