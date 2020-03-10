<?php


namespace App\Soap\Request\Tinkoff;


class calcPartnerFQuoteResponse
{
    protected $responseData;

    public function __construct($response)
    {
        $this->responseData = $response;
    }

    public function getResponseData()
    {
        return $this->responseData;
    }
}
