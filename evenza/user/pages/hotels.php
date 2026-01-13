<?php 
session_start();
require_once '../../core/connect.php';

/**
 * Fetch hotel data from collaborator's API endpoint
 * 
 * @param string $apiUrl The API endpoint URL
 * @return array|false Returns decoded JSON data or false on failure
 */
function fetchHotelsFromAPI($apiUrl) {
    // Initialize cURL if available, otherwise use file_get_contents
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error || $httpCode !== 200) {
            error_log("Hotel API Error: HTTP $httpCode - $error");
            return false;
        }
    } else {
        // Fallback to file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ],
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response === false) {
            error_log("Hotel API Error: Failed to fetch data from $apiUrl");
            return false;
        }
    }
    
    // Decode JSON response
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Hotel API Error: Invalid JSON response - " . json_last_error_msg());
        return false;
    }
    
    return $data;
}

// Configure API endpoint - Update this with your collaborator's actual API URL
$apiEndpoint = 'http://localhost/Hotel-Management-System/integrations/api/rooms.php';

// For hotel data, we'll transform the rooms API response or use a dedicated hotels endpoint
// If you have a dedicated hotels API, replace the URL above
// Example: $apiEndpoint = 'https://your-collaborator-api.com/api/hotels';

$hotels = [];
$errorMessage = '';

// Fetch hotel data from API
$apiResponse = fetchHotelsFromAPI($apiEndpoint);

if ($apiResponse !== false) {
    // Handle different API response structures
    if (isset($apiResponse['data']['rooms'])) {
        // Transform rooms data to hotels format (if using rooms API)
        // This is a temporary transformation - replace with actual hotels API when available
        $rooms = $apiResponse['data']['rooms'];
        foreach ($rooms as $room) {
            // Extract hotel information from room data
            // Note: This is a placeholder transformation. Update when actual hotels API is available
            $hotels[] = [
                'name' => $room['name'] ?? 'Hotel Room',
                'image' => $room['image'] ?? null,
                'location' => 'Available Location', // Update when location data is available
                'website' => $room['links']['view'] ?? '#'
            ];
        }
    } elseif (isset($apiResponse['data']['hotels'])) {
        // Direct hotels API response
        $hotels = $apiResponse['data']['hotels'];
    } elseif (isset($apiResponse['hotels'])) {
        // Alternative API structure
        $hotels = $apiResponse['hotels'];
    } elseif (is_array($apiResponse) && !empty($apiResponse)) {
        // Assume the response is directly an array of hotels
        $hotels = $apiResponse;
    }
    
    // Validate hotel data structure
    if (!empty($hotels)) {
        $validatedHotels = [];
        foreach ($hotels as $hotel) {
            if (isset($hotel['name']) && isset($hotel['website'])) {
                $validatedHotels[] = [
                    'name' => $hotel['name'],
                    'image' => $hotel['image'] ?? $hotel['imageUrl'] ?? null,
                    'location' => $hotel['location'] ?? $hotel['address'] ?? 'Location not specified',
                    'website' => $hotel['website'] ?? $hotel['websiteUrl'] ?? $hotel['url'] ?? '#'
                ];
            }
        }
        $hotels = $validatedHotels;
    }
    
    if (empty($hotels)) {
        $errorMessage = 'Our partner hotels are currently updating. Please check back soon.';
    }
} else {
    $errorMessage = 'Our partner hotels are currently updating. Please check back soon.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Hotels - EVENZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .hotel-card {
            background-color: var(--card-cream);
            border-radius: 20px;
            box-shadow: 0 8px 30px var(--shadow-soft);
            border: 1px solid #f0f0f0;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .hotel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px var(--shadow-medium);
        }
        
        .hotel-card-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background-color: var(--bg-warm-beige);
        }
        
        .hotel-card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .hotel-card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-charcoal);
            margin-bottom: 0.75rem;
        }
        
        .hotel-card-location {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: var(--text-dark-gray);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .hotel-card-location svg {
            width: 18px;
            height: 18px;
            color: var(--accent-olive);
            flex-shrink: 0;
        }
        
        .btn-hotel-visit {
            background-color: var(--btn-olive);
            border: none;
            color: white;
            font-weight: 500;
            padding: 0.7rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: auto;
            width: 100%;
        }
        
        .btn-hotel-visit:hover {
            background-color: var(--btn-dark-beige);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--shadow-medium);
        }
        
        .hotels-empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .hotels-empty-state-icon {
            font-size: 4rem;
            color: rgba(74, 93, 74, 0.3);
            margin-bottom: 1.5rem;
        }
        
        .hotels-empty-state-message {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--accent-olive);
            margin-bottom: 0.5rem;
        }
        
        .hotels-empty-state-subtitle {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark-gray);
            opacity: 0.7;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php $activePage = 'hotels'; include __DIR__ . '/includes/nav.php'; ?>

    <div class="page-header py-5 mt-5">
        <div class="container" style="padding-top: 40px;">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title mb-4">Partner Hotel</h1>
                    <p class="page-subtitle">Discover our curated selection of partner hotels. Click to visit their websites and explore their offerings.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="events-grid-section" style="padding-top: 5px; padding-bottom: 3rem;">
        <div class="container">
            <?php if (!empty($errorMessage)): ?>
                <div class="hotels-empty-state">
                    <div class="hotels-empty-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <h2 class="hotels-empty-state-message"><?php echo htmlspecialchars($errorMessage); ?></h2>
                    <p class="hotels-empty-state-subtitle">We're working to bring you the best hotel partners. Please check back later.</p>
                </div>
            <?php elseif (empty($hotels)): ?>
                <div class="hotels-empty-state">
                    <div class="hotels-empty-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <h2 class="hotels-empty-state-message">Our partner hotels are currently updating. Please check back soon.</h2>
                    <p class="hotels-empty-state-subtitle">We're working to bring you the best hotel partners. Please check back later.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($hotels as $hotel): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card hotel-card h-100">
                                <?php if (!empty($hotel['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($hotel['image']); ?>" 
                                         class="hotel-card-image" 
                                         alt="<?php echo htmlspecialchars($hotel['name']); ?>"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'250\'%3E%3Crect fill=\'%23F5F1EB\' width=\'400\' height=\'250\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' font-family=\'Arial\' font-size=\'18\' fill=\'%236B7F5A\'%3EHotel Image%3C/text%3E%3C/svg%3E'">
                                <?php else: ?>
                                    <div class="hotel-card-image" style="display: flex; align-items: center; justify-content: center; background-color: var(--bg-warm-beige);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#6B7F5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.3">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <div class="hotel-card-body">
                                    <h3 class="hotel-card-title"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                    <div class="hotel-card-location">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span><?php echo htmlspecialchars($hotel['location']); ?></span>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($hotel['website']); ?>" 
                                       class="btn btn-hotel-visit" 
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        Visit Hotel Website
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

