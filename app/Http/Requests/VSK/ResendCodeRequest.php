<?php


namespace App\Http\Requests\VSK;


use Benfin\Requests\AbstractRequest;

class ResendCodeRequest extends AbstractRequest
{

    public function rules(): array
    {
        return [
            'token' => 'required',
        ];
    }
}
