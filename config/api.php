<?php
return [
    'guidesCacheTtl' => env('GUIDES_CACHE_TTL'),
    'debugLog' => env('API_DEBUG_LOG'),
    'qiwi' => [
        'adminEmails' => env('QIWI_ADMIN_EMAILS'),
        'requestInterval' => env('QIWI_REQUEST_INTERVAL'),
        'balanceNotifyEmail' => env('QIWI_BALANCE_NOTIFY_EMAIL'),
    ],
    'notify_sender' => env('EMAIL_NOTIFY_SENDER'),
    'service_auth' => [
        'login' => env('AUTH_LOGIN'),
        'password' => env('AUTH_PASSWORD'),
        'token' => env('AUTH_TOKEN'),
    ],
];
