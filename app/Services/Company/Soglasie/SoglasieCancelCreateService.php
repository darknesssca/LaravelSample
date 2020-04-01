<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCancelCreateServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\ConmfigurationException;

class SoglasieCancelCreateService extends SoglasieService implements SoglasieCancelCreateServiceContract
{

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiRestUrl = config('api_sk.soglasie.cancelCreateUrl');
        if (!$this->apiRestUrl) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    public function run($company, $processData): array
    {
        $url = $this->getUrl([
            'policyId' => $processData['data']['policyId'],
        ]);
        $headers = $this->getHeaders();
        return $this->getRequest($url, [], $headers, false); // нам без разницы что там произошло в результате, поэтому никаких эксепшенов отлавливать не будем
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
        ];
    }


}
