<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * @param $messages
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function error($messages, $httpCode = 500)
    {
        $errors = [];
        if (gettype($messages) == 'array') {
            foreach ($messages as $message) {
                $errors[] = [
                    'message' => $message,
                ];
            }
        } else {
            $errors[] = [
                'message' => (string)$messages,
            ];
        }
        $message = [
            'error' => true,
            'errors' => $errors,
        ];
        return response()->json($message, $httpCode);
    }

    /**
     * @param array $data
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function success($data = [], $httpCode = 200){
        $message = [
            'error' => false,
            'data' => $data,
        ];
        if ($data) {
            $message['data'] = $data;
        }
        return response()->json($message, $httpCode);
    }

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
        $this->sendRequest("POST", "api/v1/log", $params, true);
    }

    /**отправка запроса
     * @param string $method
     * метод запроса
     * @param string $url
     * адрес
     * @param array $data
     * данные
     * @param bool $async
     * если истина, то запрос выполняется асинхронно и без результата
     * @return array|bool
     * если запрос прошел успешно, то true
     */
    protected function sendRequest(string $method, string $url, array $data=[], bool $async = false)
    {
        $method = strtoupper($method);

        $client = new Client([
            'base_uri' => $url,
            'timeout' => 1.0,
        ]);
        if (!$async) {
            $response = $client->request($method, $url, ["form_params" => $data, "headers" => ["Content-Type" => "application/json"]]);
            $code = $response->getStatusCode();
            $content = $response->getBody();
            return [
                'success' => $code == 200,
                'content' => $content
            ];
        } else {
            $client->requestAsync($method, $url, ["form_params" => $data, "headers" => ["Content-Type" => "application/json"]]);
            return true;
        }
    }
}
