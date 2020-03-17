<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
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

    protected function success($httpCode = 200){
        $message = [
            'error' => false,
            'errors' => [],
        ];
        return response()->json($message, $httpCode);
    }
}
