<?php
return [
    'renessans' => [
        'apiUrl' => env('RENESSANS_API_URL'),
        'apiKey' => env('RENESSANS_API_KEY'),
    ],
    'tinkoff' => [
        'wsdlUrl' => env('TINKOFF_API_WSDL_URL'),
        'user' => env('TINKOFF_API_USER'),
        'password' => env('TINKOFF_API_PASSWORD'),
        'producerCode' => env('TINKOFF_API_PRODUCER_CODE'),
        'modelsUrl' => env('TINKOFF_API_MODELS_URL')
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
    'vsk' => [
        'apiUrl' => env('VSK_API_URL'),
    ],
    'authMicroserviceUrl' => env('AUTH_MICROSERVICE_URL'),
    'logMicroserviceUrl' => env('LOG_MICROSERVICE_URL'),
    'logMicroserviceCode' => env('LOG_MICROSERVICE_CODE'),
    'notifyMicroserviceUrl' => env('NOTIFY_MICROSERVICE_URL'),
    'notifyMicroserviceCode' => env('NOTIFY_MICROSERVICE_CODE'),
    'billSuccessUrl' => env('API_BILL_SUCCESS_URL'),
    'billFailUrl' => env('API_BILL_FAIL_URL'),
    'maxCheckCount' => env('API_PROCESSING_MAX_CHECK_COUNT'),
    'maxRowsByCycle' => env('API_PROCESSING_MAX_ROWS_BY_CYCLE'),
    'maxPoliciesCountForPaymentCheck' => env('API_PROCESSING_MAX_POLICIES_COUNT_FOR_PAYMENT_CHECK'),
    'processingInterval' => env('API_PROCESSING_INTERVAL'),
    'processingGetPaymentDelay' => env('API_PROCESSING_GET_PAYMENT_DELAY'),
];
