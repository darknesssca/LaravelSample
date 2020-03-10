<?php


namespace App\Services\Company\Tinkoff;

use App\Services\Company\CompanyService;

abstract class TinkoffService extends CompanyService
{
    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;
    protected $apiProducerCode;

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.tinkoff.wsdlUrl');
        $this->apiUser = config('api_sk.tinkoff.user');
        $this->apiPassword = config('api_sk.tinkoff.password');
        $this->apiProducerCode = config('api_sk.tinkoff.producerCode');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword)) {
            throw new \Exception('tinkoff api is not configured');
        }
    }

    protected function setHeader(&$data)
    {
        $data['Header'] = [
            'user' => $this->apiUser,
            'password' => $this->apiPassword,
        ];
        $data['producerCode'] = $this->apiProducerCode;
    }

    public function setValue(&$target, $targetName, $sourceName, $source)
    {
        if (isset($source[$sourceName]) && $source[$sourceName]) {
            $target[$targetName] = $source[$sourceName];
        }
    }

    public function setValuesByArray(&$target, $dependencies, $source)
    {
        foreach ($dependencies as $targetName => $sourceName) {
            if (isset($source[$sourceName]) && $source[$sourceName]) {
                if (typeof($source[$sourceName]) == 'array') {
                    continue;
                }
                $target[$targetName] = $source[$sourceName];
            }
        }
    }
}
