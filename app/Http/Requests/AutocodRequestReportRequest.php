<?php


namespace App\Http\Requests;


use Benfin\Requests\Interfaces\RequestValidationSource;

class AutocodRequestReportRequest implements RequestValidationSource
{

    public static function getRules()
    {
        return  ['vin' => 'required'];//todo добавить регулярку на вин
    }

    public static function getMessages()
    {
       return ['vin.required' => 'не задано поле vin'];//todo добавить регулярку на вин
    }
}
