<?php
require_once __DIR__ . '/../../dbconnect/load_env.php';
require_once __DIR__ . '/../../config.php';

$google_client_id = getenv('GOOGLE_CLIENT_ID');
$google_client_secret = getenv('GOOGLE_CLIENT_SECRET');
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

$google_redirect_uri = $scheme . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/integrations/gmail/googleCallback.php';

if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', $google_client_id);
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', $google_client_secret);
if (!defined('GOOGLE_REDIRECT_URI')) define('GOOGLE_REDIRECT_URI', $google_redirect_uri);


$smtp_host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtp_port = getenv('SMTP_PORT') ?: 587;
$smtp_user = getenv('SMTP_USER') ?: getenv('GMAIL_ADDRESS');
$smtp_pass = getenv('SMTP_PASS') ?: getenv('GMAIL_APP_PASSWORD');

if (!defined('SMTP_HOST')) define('SMTP_HOST', $smtp_host);
if (!defined('SMTP_PORT')) define('SMTP_PORT', (int)$smtp_port);
if (!defined('SMTP_USER')) define('SMTP_USER', $smtp_user);
if (!defined('SMTP_PASS')) define('SMTP_PASS', $smtp_pass);


$imap_host = getenv('IMAP_HOST') ?: 'imap.gmail.com';
$imap_port = getenv('IMAP_PORT') ?: 993;

if (!defined('IMAP_HOST')) define('IMAP_HOST', $imap_host);
if (!defined('IMAP_PORT')) define('IMAP_PORT', (int)$imap_port);


$email_from = getenv('EMAIL_FROM') ?: $smtp_user;
$email_from_name = getenv('EMAIL_FROM_NAME') ?: 'TravelMates Hotel';

if (!defined('EMAIL_FROM')) define('EMAIL_FROM', $email_from);
if (!defined('EMAIL_FROM_NAME')) define('EMAIL_FROM_NAME', $email_from_name);

$email_db_host = getenv('EMAIL_DB_HOST') ?: 'localhost';
$email_db_user = getenv('EMAIL_DB_USER') ?: 'root';
$email_db_pass = getenv('EMAIL_DB_PASS') ?: '';
$email_db_name = getenv('EMAIL_DB_NAME') ?: 'travelMates';

if (!defined('EMAIL_DB_HOST')) define('EMAIL_DB_HOST', $email_db_host);
if (!defined('EMAIL_DB_USER')) define('EMAIL_DB_USER', $email_db_user);
if (!defined('EMAIL_DB_PASS')) define('EMAIL_DB_PASS', $email_db_pass);
if (!defined('EMAIL_DB_NAME')) define('EMAIL_DB_NAME', $email_db_name);

function google_is_configured()
{
    return !empty(GOOGLE_CLIENT_ID) && !empty(GOOGLE_CLIENT_SECRET);
}
function gmail_is_configured()
{
    return !empty(GOOGLE_CLIENT_ID) && !empty(GOOGLE_CLIENT_SECRET);
}
function gmail_is_authenticated()
{
    return file_exists(__DIR__ . '/tokens.json') && filesize(__DIR__ . '/tokens.json') > 0;
}
function get_gmail_config_status()
{
    return [
        'oauth_configured' => google_is_configured(),
        'is_authenticated' => gmail_is_authenticated(),
        'smtp_configured' => gmail_is_configured(), // Backward compatibility
        'imap_configured' => gmail_is_configured(), // Backward compatibility
        'smtp_host' => SMTP_HOST,
        'smtp_port' => SMTP_PORT,
        'imap_host' => IMAP_HOST,
        'imap_port' => IMAP_PORT,
        'from_email' => EMAIL_FROM,
        'from_name' => EMAIL_FROM_NAME
    ];
}

?>
