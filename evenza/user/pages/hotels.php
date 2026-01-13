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
$apiEndpoint = 'http://localhost/TravelMates-Evenza-Collaboration/Hotel-Management-System/integrations/api/rooms.php/info?id=1';

// For hotel data, we'll transform the rooms API response or use a dedicated hotels endpoint
// If you have a dedicated hotels API, replace the URL above
// Example: $apiEndpoint = 'https://your-collaborator-api.com/api/hotels';

$hotels = [];
$roomsGroupedByType = [];
$errorMessage = '';

// Base URL for the Hotel Management System (must match your folder structure)
// Local path is C:\xampp\htdocs\TravelMates-Evenza-Collaboration\Hotel-Management-System
$hotelSystemBaseUrl = 'http://localhost/TravelMates-Evenza-Collaboration/Hotel-Management-System';
// Fallback (in case API already prefixes /Hotel-Management-System)
$hotelSystemBaseUrlFallback = 'http://localhost/Hotel-Management-System';

/**
 * Normalize partner URLs (images/links) to the correct host/prefix
 */
function normalizePartnerUrl($url, $basePrimary, $baseFallback) {
    if (empty($url)) {
        return '';
    }
    // Already absolute with scheme
    if (preg_match('/^https?:\\/\\//i', $url)) {
        // Fix host if it points to /Hotel-Management-System without collaboration prefix
        if (stripos($url, 'http://localhost/Hotel-Management-System') === 0) {
            return str_replace('http://localhost/Hotel-Management-System', rtrim($basePrimary, '/'), $url);
        }
        // If already has the correct base, return as is
        if (stripos($url, rtrim($basePrimary, '/')) === 0) {
            return $url;
        }
        return $url;
    }
    // Relative paths - handle different formats
    $url = ltrim($url, '/');
    
    // If starts with Hotel-Management-System, ensure correct host
    if (stripos($url, 'hotel-management-system') === 0) {
        // Already has the full path, just add http://localhost
        return 'http://localhost/' . $url;
    }
    
    // If it's a path like frontend/roomPage.php or /frontend/roomPage.php
    if (stripos($url, 'frontend/') === 0 || stripos($url, 'user/') === 0) {
        // Prepend with the correct base URL
        return rtrim($basePrimary, '/') . '/' . $url;
    }
    
    // Default: prefix with primary base
    return rtrim($basePrimary, '/') . '/' . $url;
}

/**
 * Normalize image paths specifically - handles common image directory patterns
 */
function normalizeImagePath($imagePath, $basePrimary, $baseFallback) {
    if (empty($imagePath)) {
        return '';
    }
    
    // Already absolute URL
    if (preg_match('/^https?:\\/\\//i', $imagePath)) {
        // Fix host if it points to /Hotel-Management-System without collaboration prefix
        if (stripos($imagePath, 'http://localhost/Hotel-Management-System') === 0) {
            return str_replace('http://localhost/Hotel-Management-System', rtrim($basePrimary, '/'), $imagePath);
        }
        // If already has the correct base, return as is
        if (stripos($imagePath, rtrim($basePrimary, '/')) === 0) {
            return $imagePath;
        }
        return $imagePath;
    }
    
    // Remove leading slash
    $imagePath = ltrim($imagePath, '/');
    
    // Common image directory patterns that should be relative to Hotel-Management-System root
    $imageDirs = ['assets/', 'uploads/', 'images/', 'img/', 'media/', 'photos/', 'room-images/', 'room_images/'];
    $isImagePath = false;
    foreach ($imageDirs as $dir) {
        if (stripos($imagePath, $dir) === 0) {
            $isImagePath = true;
            break;
        }
    }
    
    // If path starts with Hotel-Management-System, extract the relative part
    if (stripos($imagePath, 'hotel-management-system/') === 0) {
        $imagePath = substr($imagePath, strlen('hotel-management-system/'));
        return rtrim($basePrimary, '/') . '/' . $imagePath;
    }
    
    // If it's a direct filename or starts with common image directories, prepend base
    if ($isImagePath || preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $imagePath)) {
        return rtrim($basePrimary, '/') . '/' . $imagePath;
    }
    
    // If path contains hotel-management-system anywhere, normalize it
    if (stripos($imagePath, 'hotel-management-system') !== false) {
        // Extract everything after hotel-management-system/
        $parts = explode('hotel-management-system/', $imagePath);
        if (count($parts) > 1) {
            return rtrim($basePrimary, '/') . '/' . $parts[1];
        }
    }
    
    // Default: try to construct full path
    return rtrim($basePrimary, '/') . '/' . $imagePath;
}

/**
 * Convert rooms.php?book=X URLs to roomPage.php?roomID=X format
 * This fixes incorrect API URLs that point to the listing page instead of the detail page
 */
function fixRoomBookingUrl($url, $roomID, $basePrimary) {
    if (empty($url)) {
        // If no URL provided, construct the correct one
        return rtrim($basePrimary, '/') . '/frontend/roomPage.php?roomID=' . urlencode($roomID);
    }
    
    // Check if URL points to rooms.php (listing page) instead of roomPage.php (detail page)
    if (preg_match('/rooms\.php\?book=(\d+)/i', $url, $matches)) {
        // Extract the room ID from the book parameter and convert to roomPage.php format
        $extractedRoomID = $matches[1];
        return rtrim($basePrimary, '/') . '/frontend/roomPage.php?roomID=' . urlencode($extractedRoomID);
    }
    
    // If URL already points to roomPage.php, ensure it has the correct base
    if (stripos($url, 'roomPage.php') !== false) {
        return normalizePartnerUrl($url, $basePrimary, 'http://localhost/Hotel-Management-System');
    }
    
    // For any other URL format, normalize it but prefer constructing our own if roomID is available
    if (!empty($roomID)) {
        return rtrim($basePrimary, '/') . '/frontend/roomPage.php?roomID=' . urlencode($roomID);
    }
    
    // Fallback: normalize the provided URL
    return normalizePartnerUrl($url, $basePrimary, 'http://localhost/Hotel-Management-System');
}

// Fetch hotel data from API
$apiResponse = fetchHotelsFromAPI($apiEndpoint);

if ($apiResponse !== false && is_array($apiResponse)) {
    // Handle different API response structures
    if (isset($apiResponse['data']) && is_array($apiResponse['data']) && isset($apiResponse['data']['rooms']) && is_array($apiResponse['data']['rooms'])) {
        // Get rooms data from the API
        $rooms = $apiResponse['data']['rooms'];
        
        // Group rooms by type for display
        foreach ($rooms as $room) {
            // Ensure room is an array before processing
            if (!is_array($room)) {
                continue;
            }
            
            // Get room type and ensure it's a string
            $roomType = 'Standard';
            if (isset($room['room_type']) && is_string($room['room_type'])) {
                $roomType = $room['room_type'];
            } elseif (isset($room['type']) && is_string($room['type'])) {
                $roomType = $room['type'];
            }
            
            if (!isset($roomsGroupedByType[$roomType])) {
                $roomsGroupedByType[$roomType] = [];
            }
            
            // Extract features from the room data
            $features = [];
            if (isset($room['features']) && is_array($room['features'])) {
                $features = $room['features'];
            } elseif (isset($room['amenities']) && is_array($room['amenities'])) {
                $features = $room['amenities'];
            }
            
            // Safely get links if they exist
            $viewUrl = '';
            $bookUrl = '';
            if (isset($room['links']) && is_array($room['links'])) {
                $viewUrl = $room['links']['view'] ?? '';
                $bookUrl = $room['links']['book'] ?? '';
            }
            if (empty($viewUrl) && isset($room['viewUrl'])) {
                $viewUrl = $room['viewUrl'];
            }
            if (empty($bookUrl) && isset($room['bookUrl'])) {
                $bookUrl = $room['bookUrl'];
            }
            
            // Extract price - try common keys and nested amount/value fields
            $basePrice = null;
            $priceKeys = [
                'price', 'base_price', 'basePrice', 'price_per_night', 'rate',
                'roomRate', 'room_rate', 'startingPrice', 'starting_price',
                'nightly_rate', 'nightlyRate', 'roomPrice', 'room_price',
                'ratePerNight', 'rate_per_night', 'tariff', 'cost', 'amount'
            ];
            foreach ($priceKeys as $pk) {
                if (!isset($room[$pk])) {
                    continue;
                }
                $p = $room[$pk];
                if (is_numeric($p)) {
                    $basePrice = (float) $p;
                    break;
                }
                if (is_string($p) && preg_match('/([0-9]+(?:\.[0-9]+)?)/', $p, $m)) {
                    $basePrice = (float) $m[1];
                    break;
                }
                if (is_array($p)) {
                    foreach (['amount', 'value', 'price', 'total', 'net', 'gross'] as $nested) {
                        if (isset($p[$nested]) && is_numeric($p[$nested])) {
                            $basePrice = (float) $p[$nested];
                            break 2;
                        }
                        if (isset($p[$nested]) && is_string($p[$nested]) && preg_match('/([0-9]+(?:\.[0-9]+)?)/', $p[$nested], $m)) {
                            $basePrice = (float) $m[1];
                            break 2;
                        }
                    }
                    // Nested arrays under pricing-like objects
                    if (is_array($p) && $basePrice === null) {
                        foreach ($p as $subVal) {
                            if (is_numeric($subVal)) {
                                $basePrice = (float) $subVal;
                                break;
                            }
                            if (is_string($subVal) && preg_match('/([0-9]+(?:\.[0-9]+)?)/', $subVal, $m)) {
                                $basePrice = (float) $m[1];
                                break;
                            }
                        }
                    }
                }
            }
            // Pricing array fallback
            if ($basePrice === null && isset($room['pricing']) && is_array($room['pricing'])) {
                foreach ($room['pricing'] as $pVal) {
                    if (is_numeric($pVal)) {
                        $basePrice = (float) $pVal;
                        break;
                    }
                    if (is_array($pVal)) {
                        foreach (['amount', 'value', 'price', 'total'] as $nested) {
                            if (isset($pVal[$nested]) && is_numeric($pVal[$nested])) {
                                $basePrice = (float) $pVal[$nested];
                                break 2;
                            }
                        }
                    }
                }
            }
            if ($basePrice === null) {
                // Heuristic: look for any numeric value in the room array with price/rate-like key
                foreach ($room as $rk => $rv) {
                    if (is_numeric($rv) && (stripos($rk, 'price') !== false || stripos($rk, 'rate') !== false || stripos($rk, 'amount') !== false)) {
                        $basePrice = (float) $rv;
                        break;
                    }
                    if (is_string($rv) && (stripos($rk, 'price') !== false || stripos($rk, 'rate') !== false || stripos($rk, 'amount') !== false)) {
                        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $rv, $m)) {
                            $basePrice = (float) $m[1];
                            break;
                        }
                    }
                    if (is_array($rv)) {
                        foreach ($rv as $rk2 => $rv2) {
                            if (is_numeric($rv2) && (stripos($rk2, 'price') !== false || stripos($rk2, 'rate') !== false || stripos($rk2, 'amount') !== false)) {
                                $basePrice = (float) $rv2;
                                break 2;
                            }
                            if (is_string($rv2) && (stripos($rk2, 'price') !== false || stripos($rk2, 'rate') !== false || stripos($rk2, 'amount') !== false)) {
                                if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $rv2, $m)) {
                                    $basePrice = (float) $m[1];
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
            if ($basePrice === null) {
                $basePrice = 0;
            }
            
            // Extract image path - try several possible fields
            $imagePath = '';
            $imageCandidates = [
                'image', 'imagePath', 'image_path', 'image_url', 'imageUrl', 'imageURL',
                'photo', 'photoUrl', 'photo_path', 'photo_url', 'photoURL',
                'cover_image', 'coverImage', 'featured_image', 'featuredImage',
                'thumbnail', 'thumb', 'picture', 'pic', 'img', 'roomImage', 'room_image'
            ];
            foreach ($imageCandidates as $ik) {
                if (!isset($room[$ik])) {
                    continue;
                }
                $img = $room[$ik];
                if (is_string($img) && $img !== '') {
                    $imagePath = $img;
                    break;
                }
                if (is_array($img)) {
                    // Try common nested keys
                    foreach (['url', 'path', 'src', 'file', 'filename', 'location', 'link'] as $nested) {
                        if (isset($img[$nested]) && is_string($img[$nested]) && $img[$nested] !== '') {
                            $imagePath = $img[$nested];
                            break 2;
                        }
                    }
                    // If array has numeric keys, try first element
                    if ($imagePath === '' && !empty($img) && is_array($img)) {
                        $firstVal = reset($img);
                        if (is_string($firstVal) && preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', $firstVal)) {
                            $imagePath = $firstVal;
                            break;
                        }
                    }
                }
            }
            // If still empty, check images array (take first)
            if ($imagePath === '' && isset($room['images']) && is_array($room['images']) && !empty($room['images'])) {
                $firstImg = $room['images'][0];
                if (is_string($firstImg)) {
                    $imagePath = $firstImg;
                } elseif (is_array($firstImg)) {
                    foreach (['url', 'path', 'src', 'file', 'filename'] as $nested) {
                        if (isset($firstImg[$nested]) && is_string($firstImg[$nested]) && $firstImg[$nested] !== '') {
                            $imagePath = $firstImg[$nested];
                            break;
                        }
                    }
                }
            }
            // Heuristic: search shallow arrays for any string that looks like an image path
            if ($imagePath === '') {
                foreach ($room as $rk => $rv) {
                    if (is_string($rv) && preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', $rv)) {
                        $imagePath = $rv;
                        break;
                    }
                    if (is_array($rv)) {
                        foreach ($rv as $rv2) {
                            if (is_string($rv2) && preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', $rv2)) {
                                $imagePath = $rv2;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            // Normalize image path with proper host/prefix using specialized image path handler
            $imagePath = normalizeImagePath($imagePath, $hotelSystemBaseUrl, $hotelSystemBaseUrlFallback);
            
            // Extract capacity - handle both scalar and array formats
            $capacity = 2;
            if (isset($room['capacity'])) {
                if (is_numeric($room['capacity'])) {
                    $capacity = (int) $room['capacity'];
                } elseif (is_array($room['capacity']) && isset($room['capacity']['max'])) {
                    $capacity = (int) $room['capacity']['max'];
                }
            } elseif (isset($room['max_guests']) && is_numeric($room['max_guests'])) {
                $capacity = (int) $room['max_guests'];
            }
            
            $viewUrl = normalizePartnerUrl($viewUrl, $hotelSystemBaseUrl, $hotelSystemBaseUrlFallback);
            $bookUrl = normalizePartnerUrl($bookUrl, $hotelSystemBaseUrl, $hotelSystemBaseUrlFallback);

            $roomsGroupedByType[$roomType][] = [
                'roomID' => $room['id'] ?? $room['roomID'] ?? '',
                'roomName' => $room['name'] ?? 'Room',
                'imagePath' => $imagePath,
                'base_price' => $basePrice,
                'capacity' => $capacity,
                'features' => $features,
                'viewUrl' => $viewUrl,
                'bookUrl' => $bookUrl
            ];
        }
        
        // Also create hotel entries for backward compatibility
        foreach ($rooms as $room) {
            if (!is_array($room)) {
                continue;
            }
            $website = '#';
            if (isset($room['links']) && is_array($room['links']) && isset($room['links']['view'])) {
                $website = $room['links']['view'];
            }
            
            // Extract image for hotel card
            $hotelImage = null;
            if (isset($room['image'])) {
                if (is_string($room['image'])) {
                    $hotelImage = $room['image'];
                } elseif (is_array($room['image']) && isset($room['image']['url'])) {
                    $hotelImage = $room['image']['url'];
                } elseif (is_array($room['image']) && isset($room['image']['path'])) {
                    $hotelImage = $room['image']['path'];
                }
            }
            
            // If image path is relative, prepend the hotel system base URL
            if (!empty($hotelImage) && !preg_match('/^https?:\/\//', $hotelImage)) {
                $hotelImage = ltrim($hotelImage, '/');
                $hotelImage = $hotelSystemBaseUrl . '/' . $hotelImage;
            }
            
            $hotels[] = [
                'name' => $room['name'] ?? 'Hotel Room',
                'image' => normalizeImagePath($hotelImage, $hotelSystemBaseUrl, $hotelSystemBaseUrlFallback),
                'location' => 'Available Location',
                'website' => normalizePartnerUrl($website, $hotelSystemBaseUrl, $hotelSystemBaseUrlFallback)
            ];
        }
    } elseif (isset($apiResponse['data']) && is_array($apiResponse['data']) && isset($apiResponse['data']['hotels']) && is_array($apiResponse['data']['hotels'])) {
        // Direct hotels API response
        $hotels = $apiResponse['data']['hotels'];
    } elseif (isset($apiResponse['hotels']) && is_array($apiResponse['hotels'])) {
        // Alternative API structure
        $hotels = $apiResponse['hotels'];
    } elseif (is_array($apiResponse) && !empty($apiResponse) && !isset($apiResponse['data'])) {
        // Assume the response is directly an array of hotels
        $hotels = $apiResponse;
    }
    
    // Validate hotel data structure
    if (!empty($hotels) && empty($roomsGroupedByType)) {
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
    
    if (empty($hotels) && empty($roomsGroupedByType)) {
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
    <title>Partner Hotel - EVENZA</title>
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
            transform: none;
            box-shadow: 0 8px 30px var(--shadow-soft);
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
        
        /* Room Cards Styles */
        .room-card .card {
            background-color: var(--card-cream, #FFFBF5);
            border: 1px solid rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .room-card .card:hover {
            transform: none;
            box-shadow: 0 8px 30px var(--shadow-soft);
        }
        
        .room-card .card-img-top {
            object-fit: cover;
            height: 100%;
            width: 100%;
        }
        
        .room-card .card-title {
            font-family: 'Playfair Display', serif;
            color: var(--text-charcoal, #2D2D2D);
        }
        
        .room-card .card-body {
            color: var(--text-dark-gray, #4A4A4A);
        }
        
        .room-card .btn-warning {
            background-color: var(--btn-olive, #6B7F5A);
            border-color: var(--btn-olive, #6B7F5A);
            color: white;
        }
        
        .room-card .btn-warning:hover {
            background-color: var(--btn-dark-beige, #8B9A7A);
            border-color: var(--btn-dark-beige, #8B9A7A);
        }
        
        .room-card .btn-outline-secondary {
            border-color: var(--accent-olive, #6B7F5A);
            color: var(--accent-olive, #6B7F5A);
        }
        
        .room-card .btn-outline-secondary:hover {
            background-color: var(--accent-olive, #6B7F5A);
            color: white;
        }
        
        .room-type-section {
            margin-bottom: 3rem;
        }
        
        .room-type-section h2 {
            font-family: 'Playfair Display', serif;
            color: var(--text-charcoal, #2D2D2D);
        }
        
        .section-divider {
            border-top: 2px solid var(--accent-olive, #6B7F5A);
            opacity: 0.3;
            margin: 3rem 0;
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

    <!-- Room Cards Section from Partner Hotel System -->
    <?php if (!empty($roomsGroupedByType)): ?>
    <div class="events-grid-section" style="padding-top: 0; padding-bottom: 3rem;">
        <hr class="section-divider container">
        
        <?php foreach ($roomsGroupedByType as $roomTypeName => $roomsData): ?>
            <?php if (count($roomsData) > 0): ?>
                <div class="container room-type-section">
                    <div class="row mt-4">
                        <div class="col">
                            <h2 class="fw-bold mb-3">
                                <?php echo htmlspecialchars($roomTypeName); ?> Room
                            </h2>
                        </div>
                    </div>
                    <div class="row" id="<?php echo strtolower(str_replace(' ', '', $roomTypeName)); ?>RoomCards">
                        <?php foreach ($roomsData as $row): 
                            $features = $row['features'];
                            // Convert features to string array for data attribute
                            $featureStrings = [];
                            foreach ($features as $f) {
                                if (is_string($f)) {
                                    $featureStrings[] = $f;
                                } elseif (is_array($f) && isset($f['name'])) {
                                    $featureStrings[] = $f['name'];
                                } elseif (is_array($f) && isset($f['featureName'])) {
                                    $featureStrings[] = $f['featureName'];
                                }
                            }
                        ?>
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3 pb-4 room-card"
                                data-room-type="<?php echo strtolower($roomTypeName); ?>"
                                data-price="<?php echo (float) $row['base_price']; ?>"
                                data-capacity="<?php echo (int) $row['capacity']; ?>"
                                data-features="<?php echo strtolower(implode(',', $featureStrings)); ?>">
                                <div class="card h-100 shadow rounded-3">
                                    <div class="ratio ratio-4x3 overflow-hidden rounded-top-3 position-relative gallery-item">
                                        <?php if (!empty($row['imagePath'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['imagePath']); ?>"
                                                class="card-img-top img-fluid"
                                                alt="<?php echo htmlspecialchars($row['roomName']); ?>"
                                                title="Image: <?php echo htmlspecialchars($row['imagePath']); ?>"
                                                onerror="console.error('Image failed to load:', '<?php echo htmlspecialchars($row['imagePath']); ?>'); this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23F5F1EB\' width=\'400\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' font-family=\'Arial\' font-size=\'18\' fill=\'%236B7F5A\'%3ERoom Image%3C/text%3E%3C/svg%3E'">
                                        <?php else: ?>
                                            <div style="display: flex; align-items: center; justify-content: center; background-color: var(--bg-warm-beige, #F5F1EB); height: 100%;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#6B7F5A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.3">
                                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-4">
                                        <h5 class="card-title fw-bold mb-1">
                                            <?php echo htmlspecialchars($row['roomName']); ?>
                                        </h5>
                                        <p class="text-secondary fst-italic small mb-2">
                                            <?php echo htmlspecialchars($roomTypeName); ?> Room • Max
                                            <?php echo (int) $row['capacity']; ?> Guests
                                        </p>
                                        <p class="fw-semibold mb-3">₱<?php echo number_format((float)$row['base_price'], 2); ?> /
                                            night</p>
                                        <div class="fst-italic">
                                            <?php if (!empty($features)): 
                                                $shown = 0;
                                                foreach ($features as $feature): 
                                                    if ($shown >= 3) break;
                                                    // Handle both string and array feature formats
                                                    $featureName = '';
                                                    if (is_string($feature)) {
                                                        $featureName = $feature;
                                                    } elseif (is_array($feature) && isset($feature['name'])) {
                                                        $featureName = $feature['name'];
                                                    } elseif (is_array($feature) && isset($feature['featureName'])) {
                                                        $featureName = $feature['featureName'];
                                                    }
                                                    if (!empty($featureName)):
                                            ?>
                                                <span class="text-muted small me-1 mb-1"><?php echo htmlspecialchars($featureName . " -"); ?></span>
                                            <?php 
                                                        $shown++;
                                                    endif;
                                                endforeach;
                                            else: ?>
                                                <span class="text-muted small">No features listed</span>
                                            <?php endif; ?>
                                            <?php if (!empty($features) && $shown > 0): ?>
                                                <span class="text-muted small">and More..</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0 p-4 pt-0">
                                        <div class="d-flex gap-2 flex-wrap">
                                            <?php 
                                            // Generate the booking URL - always use roomPage.php format, fix if API returns wrong format
                                            $bookingUrlRaw = '';
                                            if (!empty($row['bookUrl'])) {
                                                $bookingUrlRaw = $row['bookUrl'];
                                            } elseif (!empty($row['viewUrl'])) {
                                                $bookingUrlRaw = $row['viewUrl'];
                                            }
                                            // Use fixRoomBookingUrl to ensure correct format (roomPage.php?roomID=X)
                                            $bookingUrl = fixRoomBookingUrl($bookingUrlRaw, $row['roomID'], $hotelSystemBaseUrl);
                                            ?>
                                            <a href="<?php echo htmlspecialchars($bookingUrl); ?>"
                                                class="btn btn-warning w-100"
                                                target="_blank"
                                                rel="noopener noreferrer">Book Now</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

