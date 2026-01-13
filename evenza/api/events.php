<?php
/**
 * Events API Endpoint
 * 
 * Returns event data in JSON format.
 * 
 * Usage:
 *   GET /api/events.php              - Get all events
 *   GET /api/events.php?id=1         - Get a single event by ID
 *   GET /api/events.php?category=business - Filter by category
 *   GET /api/events.php?limit=10&offset=0 - Pagination
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

require_once __DIR__ . '/../core/connect.php';

/**
 * Get the full URL for an event image
 */
function getEventImageUrl($imagePath) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/evenza';
    
    if (empty($imagePath)) {
        return $baseUrl . '/assets/images/event_images/placeholder.jpg';
    }
    
    // Clean up the image path
    $imagePath = ltrim($imagePath, '/\\');
    
    // Remove any existing path prefixes
    $prefixes = [
        '../../assets/images/event_images/',
        '../assets/images/event_images/',
        'assets/images/event_images/'
    ];
    
    foreach ($prefixes as $prefix) {
        if (strpos($imagePath, $prefix) === 0) {
            $imagePath = substr($imagePath, strlen($prefix));
            break;
        }
    }
    
    $filename = basename($imagePath);
    return $baseUrl . '/assets/images/event_images/' . $filename;
}

/**
 * Get package inclusions for an event
 */
function getPackageInclusions($conn, $eventId) {
    $inclusions = [
        'bronze' => [],
        'silver' => [],
        'gold' => []
    ];
    
    $query = "SELECT packageTier, inclusionText FROM package_inclusions WHERE eventId = ? ORDER BY displayOrder ASC, inclusionId ASC";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $eventId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $tierKey = strtolower($row['packageTier']);
            if (isset($inclusions[$tierKey])) {
                $inclusions[$tierKey][] = $row['inclusionText'];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    return $inclusions;
}

/**
 * Get all packages with their prices
 */
function getPackages($conn) {
    $packages = [];
    $packageMap = [];
    
    $query = "SELECT packageId, packageName, price FROM packages ORDER BY packageId DESC";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tier = strtolower(str_replace(' Package', '', $row['packageName']));
            if (!isset($packageMap[$tier])) {
                $packageMap[$tier] = [
                    'id' => (int)$row['packageId'],
                    'name' => $row['packageName'],
                    'tier' => $tier,
                    'price' => (float)$row['price']
                ];
            }
        }
        mysqli_free_result($result);
    }
    
    // Order: bronze, silver, gold
    $orderedTiers = ['bronze', 'silver', 'gold'];
    foreach ($orderedTiers as $tier) {
        if (isset($packageMap[$tier])) {
            $packages[] = $packageMap[$tier];
        }
    }
    
    return $packages;
}

/**
 * Normalize category for filtering
 */
function normalizeCategory($category) {
    $category = strtolower(trim($category ?? ''));
    
    $categoryMap = [
        'premium' => 'premium',
        'conference' => 'business',
        'business' => 'business',
        'wedding' => 'weddings',
        'weddings' => 'weddings',
        'seminar' => 'workshops',
        'workshop' => 'workshops',
        'workshops' => 'workshops',
        'social' => 'socials',
        'socials' => 'socials',
        'hotel-hosted events' => 'socials',
        'hotel-hosted' => 'socials'
    ];
    
    if (isset($categoryMap[$category])) {
        return $categoryMap[$category];
    }
    
    // Check for partial matches
    if (stripos($category, 'wedding') !== false) return 'weddings';
    if (stripos($category, 'workshop') !== false || stripos($category, 'seminar') !== false || 
        stripos($category, 'training') !== false || stripos($category, 'masterclass') !== false) return 'workshops';
    if (stripos($category, 'social') !== false || stripos($category, 'gala') !== false) return 'socials';
    if (stripos($category, 'premium') !== false || stripos($category, 'exhibition') !== false || 
        stripos($category, 'tasting') !== false) return 'premium';
    if (stripos($category, 'business') !== false || stripos($category, 'conference') !== false) return 'business';
    
    return $category;
}

try {
    // Get query parameters
    $eventId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $category = isset($_GET['category']) ? trim($_GET['category']) : null;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : null;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    
    // Get base URL for links
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/evenza';
    
    // Fetch single event by ID
    if ($eventId !== null) {
        $query = "SELECT eventId, title, venue, category, imagePath, description FROM events WHERE eventId = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception('Database error: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $eventId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$event) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Event not found'
            ], JSON_PRETTY_PRINT);
            exit();
        }
        
        // Get package inclusions for this event
        $packageInclusions = getPackageInclusions($conn, $eventId);
        $packages = getPackages($conn);
        
        // Build packages with inclusions
        $packagesData = [];
        foreach ($packages as $package) {
            $tierKey = strtolower($package['tier']);
            $packagesData[] = [
                'id' => $package['id'],
                'name' => $package['name'],
                'tier' => $package['tier'],
                'price' => [
                    'amount' => $package['price'],
                    'currency' => 'PHP',
                    'formatted' => '₱ ' . number_format($package['price'], 2)
                ],
                'inclusions' => $packageInclusions[$tierKey] ?? []
            ];
        }
        
        // Build response
        $response = [
            'success' => true,
            'data' => [
                'id' => (int)$event['eventId'],
                'title' => $event['title'] ?? '',
                'description' => $event['description'] ?? '',
                'category' => $event['category'] ?? '',
                'normalizedCategory' => normalizeCategory($event['category'] ?? ''),
                'venue' => $event['venue'] ?? '',
                'image' => getEventImageUrl($event['imagePath'] ?? ''),
                'packages' => $packagesData,
                'links' => [
                    'view' => $baseUrl . '/user/pages/eventDetails.php?id=' . $event['eventId'],
                    'reserve' => $baseUrl . '/user/pages/reservation.php?eventId=' . $event['eventId']
                ]
            ],
            'meta' => [
                'timestamp' => date('c'),
                'version' => '1.0',
                'endpoint' => $_SERVER['REQUEST_URI']
            ]
        ];
        
        http_response_code(200);
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Fetch all events (only columns that exist in the database)
    $query = "SELECT eventId, title, venue, category, imagePath, description FROM events ORDER BY eventId DESC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }
    
    $events = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    mysqli_free_result($result);
    
    // Filter by category if specified
    if ($category !== null && strtolower($category) !== 'all') {
        $normalizedFilter = normalizeCategory($category);
        $events = array_filter($events, function($event) use ($normalizedFilter) {
            return normalizeCategory($event['category'] ?? '') === $normalizedFilter;
        });
        $events = array_values($events); // Re-index array
    }
    
    $totalCount = count($events);
    
    // Apply pagination
    if ($limit !== null) {
        $events = array_slice($events, $offset, $limit);
    }
    
    // Transform events data
    $eventsData = [];
    foreach ($events as $event) {
        $eventsData[] = [
            'id' => (int)$event['eventId'],
            'title' => $event['title'] ?? '',
            'description' => $event['description'] ?? '',
            'category' => $event['category'] ?? '',
            'normalizedCategory' => normalizeCategory($event['category'] ?? ''),
            'venue' => $event['venue'] ?? '',
            'image' => getEventImageUrl($event['imagePath'] ?? ''),
            'links' => [
                'view' => $baseUrl . '/user/pages/eventDetails.php?id=' . $event['eventId'],
                'reserve' => $baseUrl . '/user/pages/reservation.php?eventId=' . $event['eventId']
            ]
        ];
    }
    
    // Get available categories for filtering
    $categories = [
        ['value' => 'all', 'label' => 'All Categories'],
        ['value' => 'business', 'label' => 'Business'],
        ['value' => 'weddings', 'label' => 'Weddings'],
        ['value' => 'socials', 'label' => 'Socials'],
        ['value' => 'workshops', 'label' => 'Workshops'],
        ['value' => 'premium', 'label' => 'Premium']
    ];
    
    // Build response
    $response = [
        'success' => true,
        'data' => [
            'events' => $eventsData,
            'pagination' => [
                'total' => $totalCount,
                'count' => count($eventsData),
                'offset' => $offset,
                'limit' => $limit
            ],
            'filters' => [
                'categories' => $categories,
                'applied' => [
                    'category' => $category
                ]
            ]
        ],
        'meta' => [
            'timestamp' => date('c'),
            'version' => '1.0',
            'endpoint' => $_SERVER['REQUEST_URI']
        ]
    ];
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
