<?php

namespace App\Http\Controllers;

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
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function success($httpCode = 200){
        $message = [
            'error' => false,
            'errors' => [],
        ];
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
        $this->sendPost("api/v1/log", $params, true);
    }

}
