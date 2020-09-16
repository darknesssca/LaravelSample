<?php


namespace App\Http\Requests\VSK;


use Benfin\Requests\AbstractRequest;

class SignRequest extends AbstractRequest
{

    public function rules(): array
    {
        return [
            'code' => 'required',
            'token' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Укажите код из СМС'
        ];
    }
}
