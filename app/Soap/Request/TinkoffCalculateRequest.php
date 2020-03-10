<?php


namespace App\Soap\Request;


class TinkoffCalculateRequest
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
