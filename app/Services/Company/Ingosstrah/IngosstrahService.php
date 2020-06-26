<?php

namespace App\Services\Company\Ingosstrah;


use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Services\Company\CompanyService;

abstract class IngosstrahService extends CompanyService
{
    public  const companyCode = 'ingosstrah';

    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService
    )
    {
        $this->apiWsdlUrl = config('api_sk.ingosstrah.wsdlUrl');
        $this->apiUser = config('api_sk.ingosstrah.user');
        $this->apiPassword = config('api_sk.ingosstrah.password');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }
}
