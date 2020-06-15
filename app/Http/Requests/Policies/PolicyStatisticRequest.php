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
           'agent_ids.*' => 'integer',
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
