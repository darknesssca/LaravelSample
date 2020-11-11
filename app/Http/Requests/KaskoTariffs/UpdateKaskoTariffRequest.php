<?php


namespace App\Http\Requests\KaskoTariffs;


use Benfin\Requests\AbstractRequest;

class UpdateKaskoTariffRequest extends AbstractRequest
{

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',
            'active' => 'required|boolean',
        ];
    }
}
