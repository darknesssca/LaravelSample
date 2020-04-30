<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class OptionsUpdateInsuranceCompaniesRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'companies' => 'sometimes|array'
        ];
    }
}
