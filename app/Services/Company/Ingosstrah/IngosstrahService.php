<?php


namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahServiceContract;
use App\Services\Company\CompanyService;

class IngosstrahService extends CompanyService implements IngosstrahServiceContract
{
    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;
    protected $apiProducerCode;

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.ingosstrah.wsdlUrl');
        $this->apiUser = config('api_sk.ingosstrah.user');
        $this->apiPassword = config('api_sk.ingosstrah.password');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword)) {
            throw new \Exception('ingosstrah api is not configured');
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
}
