<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class ProcessRequest extends AbstractRequest
{
    public function rules():array
    {
        return [
            'token' => "required|string|min:32|max:32",
        ];
    }
}
