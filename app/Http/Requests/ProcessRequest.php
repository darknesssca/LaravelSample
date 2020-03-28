<?php


namespace App\Http\Requests;

class ProcessRequest extends AbstractRequest
{
    public function rules():array
    {
        return [
            'token' => "required|string|min:32|max:32",
        ];
    }
}
