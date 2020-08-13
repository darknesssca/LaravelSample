<?php


namespace App\Http\Requests\Policies;


use Benfin\Requests\AbstractRequest;

class PolicyUsersRequest extends AbstractRequest
{
    public function rules(): array
    {
       return [
           "from" => "date",
           "to" => "date",
       ];
    }

    public function messages(): array
    {
        return [
            "from.date" => "Поле 'from' должно быть датой!",
            "to.date" => "Поле 'to' должно быть датой!",
        ];
    }
}
