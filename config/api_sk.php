<?php
return [
    'renessans' => [
        'apiUrl' => $_ENV['RENESSANS_API_URL'],
        'apiKey' => $_ENV['RENESSANS_API_KEY'],
    ],
    'tinkoff' => [
        'wsdlUrl' => $_ENV['TINKOFF_API_WSDL_URL'],
        'user' => $_ENV['TINKOFF_API_USER'],
        'password' => $_ENV['TINKOFF_API_PASSWORD'],
        'producerCode' => $_ENV['TINKOFF_API_PRODUCER_CODE'],
    ],
    'ingosstrah' => [
        'wsdlUrl' => $_ENV['INGOSSTRAH_API_WSDL_URL'],
        'user' => env('INGOSSTRAH_API_USER'),
        'password' => $_ENV['INGOSSTRAH_API_PASSWORD'],
    ],
];
