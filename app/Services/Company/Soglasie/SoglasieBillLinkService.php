<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieBillLinkServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;

class SoglasieBillLinkService extends SoglasieService implements SoglasieBillLinkServiceContract
{

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiRestUrl = config('api_sk.soglasie.billLinkUrl');
        if (!$this->apiRestUrl) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    public function run($company, $data, $additionalFields = []): array
    {
        $url = $this->getUrl([
            'policyId' => $data->data['policyId'],
        ]);
        $headers = $this->getHeaders();
        $response = $this->getRequest($url, [], $headers, false);
        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (
            !isset($response['PayLink']) || !$response['PayLink']
        ) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло ссылку на оплату',
            ]);
        }
        return [
            'billUrl' => $response['PayLink'],
        ];
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }


}
