<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Services\Company\CompanyService;

abstract class SoglasieService extends CompanyService
{
    const companyCode = 'soglasie';

    protected $apiWsdlUrl; // wsdl url прописывается в дочерних классах
    protected $apiRestUrl;
    protected $apiUser;
    protected $apiPassword;
    protected $apiSubUser;
    protected $apiSubPassword;
    protected $apiIsTest;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiUser = config('api_sk.soglasie.user');
        $this->apiPassword = config('api_sk.soglasie.password');
        $this->apiSubUser = config('api_sk.soglasie.subUser');
        $this->apiSubPassword = config('api_sk.soglasie.subPassword');
        $this->apiIsTest = config('api_sk.soglasie.isTest');
        if (!($this->apiUser && $this->apiPassword && $this->apiSubUser && $this->apiSubPassword)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    protected function getHeaders()
    {
        return [];
    }

    protected function getAuth()
    {
        return [
            'login' => $this->apiUser,
            'password' => $this->apiPassword,
        ];
    }

    protected function getUrl($data = [])
    {
        $url = $this->apiRestUrl;
        if ($data) {
            foreach ($data as $key => $value) {
                $url = str_replace('{{'.$key.'}}', $value, $url);
            }
        }
        return $url;
    }
}
