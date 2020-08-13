<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class DeferredResultRequest extends AbstractRequest
{
    public function rules():array
    {
        return [
            'id' => "required|string|size:32",
        ];
    }
}
