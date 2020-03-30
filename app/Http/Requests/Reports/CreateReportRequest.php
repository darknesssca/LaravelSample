<?php


namespace App\Http\Requests\Reports;

use App\Contracts\ApiRequestContract;
use Illuminate\Http\Request;

class CreateReportRequest extends Request implements ApiRequestContract
{
    public function getRules(): array
    {
        return [
            'agent_id' => 'array',
            'client_name' => 'string',
        ];
    }

    public function getMessages(): array
    {
        return [];
    }

}
