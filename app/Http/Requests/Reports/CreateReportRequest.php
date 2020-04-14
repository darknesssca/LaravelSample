<?php


namespace App\Http\Requests\Reports;

use Benfin\Requests\AbstractRequest;

class CreateReportRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required',
            'policies' => 'required|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле Название не заполнено',
            'policies.required' => 'Поле Полисы не заполнено',
        ];
    }
}
