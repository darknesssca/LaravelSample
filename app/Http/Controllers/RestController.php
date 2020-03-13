<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class RestController
{
    protected static $client;

    public static function sendLog($data)
    {
        $body = [
            'auth_token' => $data['auth_token'],
            'message' => 'пользователь отправил форму со следующими полями: '.\GuzzleHttp\json_encode($data),
            'code' => config('api_sk.logMicroserviceCode'),
        ];
        self::postRequest(config('api_sk.logMicroserviceUrl'), $body);
    }

    public static function postRequest($url, $data = [], $headers = []): array
    {
        if (!self::$client) {
            self::$client = new Client();
        }
        $params = [];
        if ($headers and count($headers)) {
            $params['headers'] = $headers;
        }
        if ($data and count($data)) {
            $params['form_params'] = $data;
        }
        $response = self::$client->post($url, $params);
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }

    public static function getRequest($url, $data = [], $headers = []): array
    {
        if (!self::$client) {
            self::$client = new Client();
        }
        $params = [];
        if ($headers and count($headers)) {
            $params['headers'] = $headers;
        }
        if ($data and count($data)) {
            $params['query'] = $data;
        }
        $response = self::$client->get($url, $params);
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }
}

