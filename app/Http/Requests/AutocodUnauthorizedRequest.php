<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class AutocodUnauthorizedRequest extends AbstractRequest
{
    public function messages():array
    {
        return [
            'value.required' => 'не заполнен государственный номер',
            'value.regex' => 'Неправильный формат государственного номера',
            'g-recaptcha-response.recaptcha' => 'Подтвердите что вы не робот',
            'g-recaptcha-response.required' => 'Подтвердите что вы не робот',
        ];
    }

    public function rules(): array
    {
        return [
            'value' => [
                'required',
                'regex:/[А-Я]{1}\d{3}[А-Я]{2}\d{2,3}/',
                'min:8',
                'max:9'
            ],
            'g-recaptcha-response' => 'required|recaptcha'
        ];
    }
}
