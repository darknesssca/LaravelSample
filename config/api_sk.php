<?php
return [
    'renessans' => [
        'apiUrl' => env('RENESSANS_API_URL'),
        'apiKey' => env('RENESSANS_API_KEY'),
        'apiCheckCountByCommand' => env('RENESSANS_API_CHECK_COUNT_BY_COMMAND'),
    ],
    'tinkoff' => [
        'wsdlUrl' => env('TINKOFF_API_WSDL_URL'),
        'user' => env('TINKOFF_API_USER'),
        'password' => env('TINKOFF_API_PASSWORD'),
        'producerCode' => env('TINKOFF_API_PRODUCER_CODE'),
    ],
    'ingosstrah' => [
        'wsdlUrl' => env('INGOSSTRAH_API_WSDL_URL'),
        'user' => env('INGOSSTRAH_API_USER'),
        'password' => env('INGOSSTRAH_API_PASSWORD'),
    ],
    'soglasie' => [
        'kbmWsdlUrl' => env('SOGLASIE_API_KBM_WSDL_URL'),
        'scoringWsdlUrl' => env('SOGLASIE_API_SCORING_URL'),
        'calculateWsdlUrl' => env('SOGLASIE_API_CALCULATE_URL'),
        'createUrl' => env('SOGLASIE_API_CREATE_URL'),
        'checkCreateUrl' => env('SOGLASIE_API_CHECK_CREATE_URL'),
        'cancelCreateUrl' => env('SOGLASIE_API_CANCEL_CREATE_URL'),
        'billLinkUrl' => env('SOGLASIE_API_BILL_LINK_URL'),
        'user' => env('SOGLASIE_API_USER'),
        'password' => env('SOGLASIE_API_PASSWORD'),
        'subUser' => env('SOGLASIE_API_SUB_USER'),
        'subPassword' => env('SOGLASIE_API_SUB_PASSWORD'),
        'isTest' => env('SOGLASIE_API_TEST_MODE'),
    ],
    'authMicroserviceUrl' => env('AUTH_MICROSERVICE_URL'),
    'logMicroserviceUrl' => env('LOG_MICROSERVICE_URL'),
    'logMicroserviceCode' => env('LOG_MICROSERVICE_CODE'),
    'maxCheckCount' => env('API_PROCESSING_MAX_CHECK_COUNT'),
    'maxRowsByCycle' => env('API_PROCESSING_MAX_ROWS_BY_CYCLE'),
];
