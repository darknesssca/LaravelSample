<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class FilterDraftRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'query' => 'sometimes',
            'count' => 'sometimes|integer',
            'page' => 'sometimes|integer',
            'orderColumn' => 'sometimes|string',
            'orderDirection' => 'in:asc,desc|required_with:orderColumn',
        ];
    }
}
