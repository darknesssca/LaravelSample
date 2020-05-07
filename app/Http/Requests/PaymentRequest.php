<?php


namespace App\Http\Requests;

use Benfin\Requests\AbstractRequest;

class PaymentRequest extends AbstractRequest
{
    public function rules():array
    {
        return [
            'policyNumber' => 'required|string',
        ];
    }
}
