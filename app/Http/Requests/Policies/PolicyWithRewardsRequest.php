<?php


namespace App\Http\Requests\Policies;


use Benfin\Requests\AbstractRequest;

class PolicyWithRewardsRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            "agent_id" => "required|integer",
            "reward_paid" => "required|bool",
            "police_paid" => "sometimes|bool",
            "from" => "sometimes|date",
            "to" => "sometimes|date",
            "page" => "sometimes|integer",
            "per_page" => "sometimes|integer",
            "sort" => "sometimes",
            "order" => "sometimes",
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
