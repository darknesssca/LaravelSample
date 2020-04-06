<?php


namespace App\Http\Requests\Policies;


use Benfin\Requests\AbstractRequest;

class PolicyStatisticRequest extends  AbstractRequest
{
    public function rules(): array
    {
       return [
           'from' => 'string',
           'to' => 'string',
           'agent_ids' => 'required|array'
       ];
    }
}
