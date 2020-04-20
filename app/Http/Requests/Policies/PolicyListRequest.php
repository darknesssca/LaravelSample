<?php


namespace App\Http\Requests\Policies;


use Benfin\Requests\AbstractRequest;

class PolicyListRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            "from" => "date",
            "to" => "date",
            "page" => "integer",
            "per_page" => "integer",
            "order" => "in:asc,desc",
            "commission_paid" => "boolean",
            "referer" => "boolean",
        ];
    }

    public function messages(): array
    {
        return [
            "from.date" => "Поле 'from' должно быть датой!",
            "to.date" => "Поле 'to' должно быть датой!",
            "page.integer" => "Поле 'page' должно быть целым числом!",
            "per_page.integer" => "Поле 'per_page' должно быть датой!",
            "order.in" => "Поле 'order' должно быть asc либо desc!",
        ];
    }
}
