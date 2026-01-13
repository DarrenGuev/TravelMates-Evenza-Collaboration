<?php
/**
 * Email Configuration for EVENZA
 * SMTP settings for PHPMailer
 */

return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 465,
        'secure' => 'ssl',
        'auth' => true,
        'username' => 'evenzacompany@gmail.com',
        'password' => 'uzkb wcew mpwi wxfy', // App password for Gmail
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

