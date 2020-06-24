<?php


namespace App\Http\Requests;



use Benfin\Requests\AbstractRequest;

class AutocodRequestReportRequest extends AbstractRequest
{

    public  function rules():array
    {
        return  [
            'vin' => 'required|regex:/^[A-HJ-NPR-Za-hj-npr-z0-9]+$/|min:12|max:17',
            'needSave' => 'boolean'
        ];
    }

}
