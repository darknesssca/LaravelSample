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
        $name = ucfirst(strtolower($company->code)) . ucfirst(strtolower($method));
        $this->soapWrapper->add($name, function ($service) use ($name) {
            $service
                ->wsdl($this->wsdlUrl)
                ->trace(true)
                ->classmap([
                    'App\\Soap\\Request\\'.$name.'Request',
                    'App\\Soap\\Request\\'.$name.'Response',
                ]);
        });
        return $this->soapWrapper->call($name . '.' . $name.'Request', $data);
    }
}
