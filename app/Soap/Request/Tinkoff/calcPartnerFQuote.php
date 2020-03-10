<?php


namespace App\Soap\Request\Tinkoff;


class calcPartnerFQuote
{
    protected $requestData;

    public function __construct($request)
    {
        $this->requestData = $request;
    }

    public function getRequestData()
    {
        return $this->requestData;
    }
}
