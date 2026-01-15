<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use GET.']);
    exit();
}

try {
    $evenzaApiUrl = 'http://172.20.10.10/TravelMates-Evenza-Collaboration/evenza/api/events.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $evenzaApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    if ($curlError) {
        throw new Exception('Failed to connect to Evenza API: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Evenza API returned status code: ' . $httpCode);
    }
    
    $eventsData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Evenza API: ' . json_last_error_msg());
    }
    
    http_response_code(200);
    echo json_encode($eventsData);
    
} catch (Exception $e) {
    error_log('Events API Proxy Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch events at this time',
        'message' => $e->getMessage()
    ]);
}
?>
