<?php
require __DIR__ . '/config.php';
session_start();

if (!google_is_configured()) {
    http_response_code(500);
    echo 'Google OAuth is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.';
    exit;
}

$state = bin2hex(random_bytes(8));
$_SESSION['google_oauth_state'] = $state;

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/gmail.modify https://www.googleapis.com/auth/gmail.send openid email profile',
    'access_type' => 'offline',
    'prompt' => 'consent select_account',
    'state' => $state
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;

?>
