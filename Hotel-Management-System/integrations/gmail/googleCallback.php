<?php
session_start();
require __DIR__ . '/config.php';
require_once __DIR__ . '/../../config.php';
require __DIR__ . '/../../dbconnect/connect.php';

if (!google_is_configured()) {
    header('Location: ' . BASE_URL . '/frontend/login.php?error=google_not_configured');
    exit;
}

if (!isset($_GET['code'])) {
    header('Location: ' . BASE_URL . '/frontend/login.php?error=google_no_code');
    exit;
}

if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    header('Location: ' . BASE_URL . '/frontend/login.php?error=google_state_mismatch');
    exit;
}

$code = $_GET['code'];

$tokenUrl = 'https://oauth2.googleapis.com/token';
$postFields = http_build_query([
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$resp = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err || !$resp) {
    header('Location: ' . BASE_URL . '/frontend/login.php?error=google_token_error');
    exit;
}

$data = json_decode($resp, true);
if (!isset($data['access_token'])) {
    header('Location: ' . BASE_URL . '/frontend/login.php?error=google_no_access_token');
    exit;
}

// Save tokens for Admin Gmail Integration
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $accessToken = $data['access_token'];
    $refreshToken = $data['refresh_token'] ?? null; // Only returned on first consent or prompt=consent
    $expiresIn = $data['expires_in'];
    $created = time();

    // Save to file (simple storage for integration)
    $tokenFile = __DIR__ . '/tokens.json';
    
    $tokens = [];
    if (file_exists($tokenFile)) {
        $tokens = json_decode(file_get_contents($tokenFile), true);
    }
    
    $tokens['access_token'] = $accessToken;
    if ($refreshToken) {
        $tokens['refresh_token'] = $refreshToken;
    }
    $tokens['expires_in'] = $expiresIn;
    $tokens['created'] = $created;
    
    file_put_contents($tokenFile, json_encode($tokens));
    
    // Redirect to email dashboard
    header('Location: ' . BASE_URL . '/admin/emailDashboard.php?success=gmail_connected');
    exit;
}

$access_token = $data['access_token'];

$userinfoUrl = 'https://openidconnect.googleapis.com/v1/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userinfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
$profileResp = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err || !$profileResp) {
    header('Location: ' . BASE_URL . '/frontend/login.php?error=google_profile_error');
    exit;
}

$profile = json_decode($profileResp, true);
$email = $profile['email'] ?? null;
$firstName = $profile['given_name'] ?? '';
$lastName = $profile['family_name'] ?? '';

if (!$email) {
    header('Location: ' . BASE_URL . '/frontend/login.php?error=google_no_email');
    exit;
}

function generate_unique_username($conn, $email, $firstName) {
    $local = '';
    if ($email) {
        $parts = explode('@', $email);
        $local = $parts[0] ?? '';
    }
    $base = $local ?: preg_replace('/[^A-Za-z0-9]/', '', strtolower($firstName));
    $base = substr($base, 0, 12);
    if ($base === '') {
        $base = 'user';
    }

    $candidate = $base;
    $i = 0;
    while (true) {
        $check = $conn->prepare('SELECT userID FROM users WHERE username = ? LIMIT 1');
        $check->bind_param('s', $candidate);
        $check->execute();
        $r = $check->get_result();
        if ($r && $r->num_rows === 0) {
            return $candidate;
        }
        $i++;
        $candidate = $base . $i;
        if ($i > 1000) {
            return $base . bin2hex(random_bytes(4));
        }
    }
}

// find or create user
$conn = $GLOBALS['conn'];
$stmt = $conn->prepare('SELECT userID, role, username FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $userID = (int)$row['userID'];
    $role = $row['role'] ?? 'user';
    $username = $row['username'] ?? ($firstName ?: $email);
} else {
    // insert; use created_at column if present in schema
    $role = 'user';
    $username = generate_unique_username($conn, $email, $firstName);

    $insert = $conn->prepare('INSERT INTO users (firstName, lastName, email, username, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    if ($insert) {
        $insert->bind_param('sssss', $firstName, $lastName, $email, $username, $role);
        if ($insert->execute()) {
            $userID = $insert->insert_id;
        } else {
            if ($conn->errno === 1062) {
                $username = generate_unique_username($conn, $email, $firstName) . rand(100,9999);
                $insert->bind_param('sssss', $firstName, $lastName, $email, $username, $role);
                if ($insert->execute()) {
                    $userID = $insert->insert_id;
                } else {
                    $insert = null;
                }
            } else {
                $insert = null;
            }
        }
    }

    if (empty($userID)) {
        $insert2 = $conn->prepare('INSERT INTO users (firstName, lastName, email, username, role) VALUES (?, ?, ?, ?, ?)');
        if ($insert2) {
            $insert2->bind_param('sssss', $firstName, $lastName, $email, $username, $role);
            if ($insert2->execute()) {
                $userID = $insert2->insert_id;
            } else {
                if ($conn->errno === 1062) {
                    $username = generate_unique_username($conn, $email, $firstName) . rand(100,9999);
                    $insert2->bind_param('sssss', $firstName, $lastName, $email, $username, $role);
                    if ($insert2->execute()) {
                        $userID = $insert2->insert_id;
                    } else {
                        header('Location: ' . BASE_URL . '/frontend/login.php?error=google_db_error');
                        exit;
                    }
                } else {
                    header('Location: ' . BASE_URL . '/frontend/login.php?error=google_db_error');
                    exit;
                }
            }
        } else {
            header('Location: ' . BASE_URL . '/frontend/login.php?error=google_db_error');
            exit;
        }
    }
}

$_SESSION['userID'] = $userID;
$_SESSION['username'] = $username ?? ($firstName ?: $email);
$_SESSION['role'] = $role;
$_SESSION['email'] = $email;
$_SESSION['firstName'] = $firstName;
$_SESSION['givenName'] = $_SESSION['givenName'] ?? $firstName;
$_SESSION['name'] = trim(($firstName . ' ' . $lastName)) ?: ($email ?? $_SESSION['username']);

header('Location: ' . BASE_URL . '/index.php?google_success=1');
exit;

?>
