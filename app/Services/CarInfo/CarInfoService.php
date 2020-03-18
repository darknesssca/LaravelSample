<?php


namespace App\Services\CarInfo;


use App\Http\Controllers\RestController;
use GuzzleHttp\Client;

class CarInfoService
{
    /**отправка лога
     * @param $data
     * данные для отправки
     * @param string $code
     * код события
     * @param int $user_id
     * id пользователя
     * @param int $log_type_id
     * id типа лога
     */
    protected function sendLog($data, string $code, int $user_id = -1, int $log_type_id = -1)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $params = [
            "message" => $data,
            "code" => $code
        ];
        if ($user_id != -1) {
            $params["user_id"] = $user_id;
        }
        if ($log_type_id != -1) {
            $params["log_type_id"] = $log_type_id;
        }
        $this->sendPostAsync(env("LOG_MICROSERVICE_URL"), $params, true);
    }

    /**отправка асинхронного POST запроса
     * @param string $url
     * адрес
     * @param array $data
     * данные
     * @param bool $async
     * если истина, то запрос выполняется асинхронно и без результата
     * @return bool
     * если запрос прошел успешно, то true
     */
    protected function sendPostAsync(string $url, array $data, bool $async = false)
    {
        $client = new Client([
            'base_uri' => $url,
            'timeout' => 1.0,
        ]);
        $client->requestAsync("POST", $url, ["form_params" => $data, "headers" => ["Content-Type" => "application/json"]]);
        return true;
    }


    protected function sendPost(string $url, array $data, array $headers = []): array
    {
        $client = new Client([
            'base_uri' => $url,
//            'timeout' => 1.0,
        ]);
        $response = $client->request("POST", $url, ["body" => json_encode($data), "headers" => $headers]);
        $code = $response->getStatusCode();
        if ($code >= 400) {
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
            throw new \Exception('request end with http code ' . $httpCode);
        }
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }
}
