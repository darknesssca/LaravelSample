<?php


namespace App\Http\Requests\Policies;


use Benfin\Requests\AbstractRequest;

class PolicyStatisticRequest extends  AbstractRequest
{
    public function rules(): array
    {
       return [
           "from" => "required|date",
           "to" => "required|date",
           'agent_ids' => 'array',
           'agent_ids.*' => 'required|integer',
           'insurance_company_ids' => 'array',
           'insurance_company_ids.*' => 'required|integer',
           'grouped_by_user_order_by' => 'string|in:count,sum',
       ];
    }

    public function messages(): array
    {
        return [
            "from.required" => "Не заполнено обязательное поле 'from'!",
            "to.required" => "Не заполнено обязательное поле 'to'!",
            "from.date" => "Поле 'from' должно быть датой!",
            "to.date" => "Поле 'to' должно быть датой!",
        ];
    }
}
