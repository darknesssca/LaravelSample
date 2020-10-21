<?php


namespace App\Http\Requests\VSK;


use Benfin\Requests\AbstractRequest;

class CallbackRequest extends AbstractRequest
{

    public function rules(): array
    {
        return [
            'content' => 'required|string',
            'callback_id' => 'required|string'
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Контент ответа отсуствует',
            'content.string' => 'Неверный формат ответа',
            'callback_id.required' => 'Отсутствует id ответа',
            'callback_id.string' => 'Неверный формат id ответа',
        ];
    }
}
