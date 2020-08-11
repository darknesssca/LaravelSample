<?php


namespace App\Http\Requests;

use Benfin\Requests\AbstractRequest;

class CarModelsByMarkRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|required|int|exists:car_categories,id'
        ];
    }
}
