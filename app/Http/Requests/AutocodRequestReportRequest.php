<?php


namespace App\Http\Requests;



use Benfin\Requests\AbstractRequest;

class AutocodRequestReportRequest extends AbstractRequest
{

    public  function rules():array
    {
        return  ['vin' => 'required'];//todo добавить регулярку на вин
    }

    public  function messages():array
    {
       return ['vin.required' => 'не задано поле vin'];//todo добавить регулярку на вин
    }
}
