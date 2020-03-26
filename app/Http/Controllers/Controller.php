<?php

namespace App\Http\Controllers;

use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Services\AuthMicroservice;
use Benfin\Api\Traits\HttpRequest;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use HttpRequest;
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
}
