<?php


namespace App\Http\Requests\KaskoTariffs;


use Benfin\Requests\AbstractRequest;

class GetKaskoTariffListRequest extends AbstractRequest
{

    public function rules(): array
    {
        return [
            'active' => 'sometimes|boolean'
        ];
    }
}
