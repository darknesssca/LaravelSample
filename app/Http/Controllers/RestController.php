<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Nowakowskir\JWT\TokenEncoded;

class RestController
{
    protected static $client;

    public static function sendLog($data)
    {
        $tokenEncoded = new TokenEncoded($data['auth_token']);
        $payload = $tokenEncoded->decode()->getPayload();
        $userId = $payload['user_id'];
        $body = [
            'auth_token' => $data['auth_token'],
            'user_id' => $userId,
            'message' => 'пользователь отправил форму со следующими полями: '.\GuzzleHttp\json_encode($data),
            'code' => config('api_sk.logMicroserviceCode'),
        ];
        self::postRequest(config('api_sk.logMicroserviceUrl'), $body);
    }

    public static function sendBillUrl($email, $billUrl)
    {
        $body = [
            'message' => $billUrl,
            'receiver' => $email,
            'code' => config('api_sk.notifyMicroserviceCode'),
        ];
        self::postRequest(config('api_sk.notifyMicroserviceUrl'), $body);
    }

    public static function checkToken($data)
    {
        return true; // fixme only for test
        $body = [
            'auth_token' => $data['auth_token'],
        ];
        $response = self::getRequest(config('api_sk.authMicroserviceUrl'), $body);
        if ($response['error']) {
            $message = '';
            if (isset($response['errors'])) {
                foreach ($response['errors'] as $error) {
                    $message .= $error['message'].' | ';
                }
            }
            throw new \Exception('auth service return error: '.$message);
        }
        return true;
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
        $httpCode = $response->getStatusCode();
        if (!($httpCode == 200 || $httpCode == 201)) {
            throw new \Exception('request end with http code '.$httpCode);
        }
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
        $httpCode = $response->getStatusCode();
        if (!($httpCode == 200 || $httpCode == 201)) {
            throw new \Exception('request end with http code '.$httpCode);
        }
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }
}

