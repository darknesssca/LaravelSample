<?php
return [
    'cloud' => env('FILESYSTEM_CLOUD', 'minio'),

    'disks' => [
        'minio' => [
            'driver' => 'minio',
            'endpoint' => env('MINIO_ENDPOINT', 'http://172.27.1.121:9000/'),
            'use_path_style_endpoint' => true,
            'key' => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],
    ],
];
