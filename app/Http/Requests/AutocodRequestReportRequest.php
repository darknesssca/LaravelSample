<?php


namespace App\Http\Requests;



use Benfin\Requests\AbstractRequest;

class AutocodRequestReportRequest extends AbstractRequest
{

    public  function rules():array
    {
        return  [
            'value' => [
                'required',
                'regex:/([A-HJ-NPR-Za-hj-npr-z0-9]+$)|([А-Я]{1}\d{3}[А-Я]{2}\d{2,3})/',
                'min:8',
                'max:17'
            ],
            'queryType' => [
                'required',
                'in:VIN,GRZ'
            ],
            'eosago' => 'boolean'
        ];
    }

}
