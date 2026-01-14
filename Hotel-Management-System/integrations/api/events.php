<?php
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

try {
    // Construct the Evenza API URL
    $evenzaApiUrl = 'http://localhost/TravelMates-Evenza-Collaboration/evenza/api/events.php';
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $evenzaApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    curl_close($ch);
    
    // Check for cURL errors
    if ($curlError) {
        throw new Exception('Failed to connect to Evenza API: ' . $curlError);
    }
    
    // Check HTTP status code
    if ($httpCode !== 200) {
        throw new Exception('Evenza API returned status code: ' . $httpCode);
    }
    
    // Decode the response
    $eventsData = json_decode($response, true);
    
    // Check if JSON decode was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Evenza API: ' . json_last_error_msg());
    }
    
    // Return the events data
    http_response_code(200);
    echo json_encode($eventsData);
    
} catch (Exception $e) {
    // Log error (optional - you can add file logging here if needed)
    error_log('Events API Proxy Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch events at this time',
        'message' => $e->getMessage()
    ]);
}
?>
