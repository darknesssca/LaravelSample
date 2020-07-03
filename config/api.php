<?php
return [
    'guidesCacheTtl' => env('GUIDES_CACHE_TTL'),
    'debugLog' => env('API_DEBUG_LOG'),
    'qiwi' => [
        'adminEmails' => env('QIWI_ADMIN_EMAILS'),
        'requestInterval' => env('QIWI_REQUEST_INTERVAL'),
    ],
];
