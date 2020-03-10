<?php


namespace App\Soap\Request;


class TinkoffCalculateResponse
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
