<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

session_start();
header('Content-Type: application/json');

try {
    require_once '../../../config/paypal.php';
    require_once '../../../core/connect.php';
    
    ob_clean();
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$eventId = isset($input['eventId']) ? intval($input['eventId']) : 0;
$packageId = isset($input['packageId']) ? intval($input['packageId']) : 0;
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$reservationId = isset($input['reservationId']) ? intval($input['reservationId']) : 0;

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

$accessToken = getPayPalAccessToken();
if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to authenticate with PayPal']);
    exit;
}

$packageName = 'Event Package';
if ($packageId > 0) {
    $packageQuery = "SELECT packageName FROM packages WHERE packageId = ?";
    $stmt = mysqli_prepare($conn, $packageQuery);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $packageId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $packageName = $row['packageName'];
        }
        mysqli_stmt_close($stmt);
    }
}

$eventName = 'Event Reservation';
if ($eventId > 0) {
    $eventQuery = "SELECT title FROM events WHERE eventId = ?";
    $stmt = mysqli_prepare($conn, $eventQuery);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $eventId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $eventName = $row['title'];
        }
        mysqli_stmt_close($stmt);
    }
}

$orderData = [
    'intent' => 'CAPTURE',
    'purchase_units' => [
        [
            'reference_id' => 'EVZ-' . time() . '-' . $eventId,
            'description' => $packageName . ' - ' . $eventName,
            'amount' => [
                'currency_code' => PAYPAL_CURRENCY,
                'value' => number_format($amount, 2, '.', '')
            ]
        ]
    ],
    'application_context' => [
        'brand_name' => 'EVENZA',
        'landing_page' => 'NO_PREFERENCE',
        'user_action' => 'PAY_NOW',
        'return_url' => getBaseUrl() . '/user/process/paypal/paypalCallback.php',
        'cancel_url' => getBaseUrl() . '/user/pages/payment.php?eventId=' . $eventId . '&packageId=' . $packageId . '&cancelled=1'
    ]
];

$ch = curl_init(getPayPalBaseUrl() . '/v2/checkout/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken,
    'PayPal-Request-Id: ' . uniqid('evenza-', true)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$orderResult = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && isset($orderResult['id'])) {
    $_SESSION['paypal_order_id'] = $orderResult['id'];
    $_SESSION['paypal_order_amount'] = $amount;
    $_SESSION['paypal_order_event_id'] = $eventId;
    $_SESSION['paypal_order_package_id'] = $packageId;
    $_SESSION['paypal_order_reservation_id'] = $reservationId;
    
    ob_end_clean();
    echo json_encode([
        'id' => $orderResult['id'],
        'status' => $orderResult['status']
    ]);
    exit;
} else {
    ob_end_clean();
    error_log('PayPal Create Order Error: ' . $response);
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to create PayPal order',
        'details' => $orderResult['details'] ?? null
    ]);
    exit;
}

function getPayPalAccessToken() {
    $clientId = getPayPalClientId();
    $secret = getPayPalSecret();
    
    $ch = curl_init(getPayPalBaseUrl() . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    error_log('PayPal Auth Error: ' . $response);
    return null;
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // Get the base path - remove /user/process/paypal from the script path
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']); // This gives /evenza/user/process/paypal
    $basePath = dirname(dirname(dirname($scriptPath))); // This gives /evenza
    return $protocol . '://' . $host . $basePath;
}

set_exception_handler(function($exception) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred']);
    exit;
});
