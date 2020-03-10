<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use Artisaninweb\SoapWrapper\SoapWrapper;

class SoapController
{
    /**
     * @var SoapWrapper
     */
    protected $soapWrapper;
    protected $wsdlUrl = '';

    /**
     * SoapController constructor.
     *
     * @param SoapWrapper $soapWrapper
     */
    public function __construct(SoapWrapper $soapWrapper)
    {
        $this->soapWrapper = $soapWrapper;
    }

    public function configure($wsdlUrl)
    {
        $this->wsdlUrl = $wsdlUrl;
    }

    public function requestBySoap($company, $method, $data = [])
    {
        $name = ucfirst(strtolower($company->code));
        $code = $company->code;
        $request = 'App\\Soap\\Request\\'.$code.'\\'.$method;
        $response = 'App\\Soap\\Request\\'.$code.'\\'.$method.'Response';
        $this->soapWrapper->add($name, function ($service) use ($request, $response) {
            $service
                ->wsdl($this->wsdlUrl)
                ->trace(true)
                ->classmap([
                    $request,
                    $response,
                ]);
        });
        return $this->soapWrapper->call($name . '.' . $method, $data);
    }
}
