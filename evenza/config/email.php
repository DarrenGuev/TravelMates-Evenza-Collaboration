<?php
/**
 * Email Configuration for EVENZA
 * SMTP settings for PHPMailer
 */

return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls',
        'auth' => true,
        'username' => 'evenzacompany@gmail.com',
        'password' => 'dnddyxpiwrjekoip', // App password for Gmail (16 characters, no spaces)
    ],
    'from' => [
        'email' => 'evenzacompany@gmail.com',
        'name' => 'EVENZA'
    ],
    'reply_to' => [
        'email' => 'evenzacompany@gmail.com',
        'name' => 'EVENZA Support'
    ]
];

