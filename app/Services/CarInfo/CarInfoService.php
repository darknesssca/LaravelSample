<?php


namespace App\Services\CarInfo;


use App\Http\Controllers\RestController;
use GuzzleHttp\Client;

class CarInfoService
{
    protected function sendPost(string $url, array $data, array $headers = []): array
    {
        $client = new Client([
            'base_uri' => $url,
//            'timeout' => 1.0,
        ]);
        $response = $client->request("POST", $url, ["body" =>json_encode($data), "headers" => $headers]);
        $code = $response->getStatusCode();
        if ($code >=400) {
            return ["error" => "request exited with code $code"];
        }
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }

    public static function sendGet($url, $data = [], $headers = []): array
    {
           $client = new Client();
        $params = [];
        if ($headers and count($headers)) {
            $params['headers'] = $headers;
        }
        if ($data and count($data)) {
            $params['query'] = $data;
        }
        $response = $client->get($url, $params);
        $httpCode = $response->getStatusCode();
        if (!($httpCode == 200 || $httpCode == 201)) {
            throw new \Exception('request end with http code '.$httpCode);
        }
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }
}
