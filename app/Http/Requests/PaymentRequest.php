<?php


namespace App\Http\Requests;

use Benfin\Requests\AbstractRequest;

class PaymentRequest extends AbstractRequest
{
    public function rules():array
    {
        return [
            // в связи с тем, что на вход подается распарсенная произвольная xml, валидация в данном случае не нужна
        ];
    }
}