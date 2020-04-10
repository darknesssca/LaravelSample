<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class FilterDraftRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'query' => 'sometimes',
            'count'=>'required',
            'page'=>'required',
            'orderColumn'=>'sometimes',
            'orderDirrection'=>'sometimes',
        ];
    }
}
