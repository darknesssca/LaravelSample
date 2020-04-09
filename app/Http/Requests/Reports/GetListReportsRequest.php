<?php


namespace App\Http\Requests\Reports;


use Benfin\Requests\AbstractRequest;

class GetListReportsRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'orderBy' => 'sometimes|required|string',
            'orderDirection' => 'sometimes|required|string|in:asc,desc',
            'search' => 'sometimes|required|string',
            'page' => 'sometimes|required|numeric|min:1',
            'count' => 'sometimes|required|numeric|min:1'
        ];
    }
}
