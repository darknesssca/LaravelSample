<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class GuidesInsuranceCompaniesRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
          'checkActive'=>'sometimes|boolean'
        ];
    }
}
