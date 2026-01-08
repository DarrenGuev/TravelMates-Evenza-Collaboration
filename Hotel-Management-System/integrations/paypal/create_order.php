<?php
require __DIR__ . '/config.php';
require_once __DIR__ . '/../../config.php';

$amount = $_REQUEST['amount'] ?? null;
$roomID = $_REQUEST['roomID'] ?? null;

if (!$amount || !is_numeric($amount)) {
    http_response_code(400);
    echo 'Invalid amount';
    exit;
}

$access = paypal_get_access_token();
if ($access['error']) {
    http_response_code(500);
    echo 'Token error: ' . ($access['message'] ?? 'unknown');
    exit;
}
$token = $access['access_token'];
$bookingID = $_REQUEST['bookingID'] ?? null;
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$returnUrl = $base . BASE_URL . '/integrations/paypal/capture.php?roomID=' . urlencode($roomID) . ($bookingID ? '&bookingID=' . urlencode($bookingID) : '');
$cancelUrl = $base . BASE_URL . '/frontend/rooms.php?paypal_cancel=1' . ($bookingID ? '&bookingID=' . urlencode($bookingID) : '');

$body = [
    'intent' => 'CAPTURE',
    'purchase_units' => [
        [
            'amount' => [
                'currency_code' => 'USD',
                'value' => number_format((float)$amount, 2, '.', '')
            ],
            'description' => 'Hotel booking payment' . ($roomID ? ' (room ' . $roomID . ')' : '')
        ]
    ],
    'application_context' => [
        'return_url' => $returnUrl,
        'cancel_url' => $cancelUrl
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYPAL_API_BASE . '/v2/checkout/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

// DNS resolution settings
curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
$response = curl_exec($ch);
$err = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    http_response_code(500);
    echo 'Curl error: ' . $err;
    exit;
}

$data = json_decode($response, true);
if (!$data || !isset($data['links'])) {
    http_response_code($status ?: 500);
    echo 'PayPal error: ' . ($response ?: 'no response');
    exit;
}

$approveUrl = null;
foreach ($data['links'] as $link) {
    if (isset($link['rel']) && $link['rel'] === 'approve') {
        $approveUrl = $link['href'];
        break;
    }
}

if ($approveUrl) {
    header('Location: ' . $approveUrl);
    exit;
}

http_response_code(500);
echo 'Approval link not found';
