<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCancelCreateServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ConmfigurationException;

class SoglasieCancelCreateService extends SoglasieService implements SoglasieCancelCreateServiceContract
{

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService
    )
    {
        $this->apiRestUrl = config('api_sk.soglasie.cancelCreateUrl');
        if (!$this->apiRestUrl) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $processData): array
    {
        $url = $this->getUrl([
            'policyId' => $processData['data']['policyId'],
        ]);
        $headers = $this->getHeaders();
        $this->writeLog(
            $this->logPath,
            [
                'request' => [
                    'method' => 'CancelCreate',
                    'url' => $url,
                ]
            ]
        );
        return $this->putRequest($url, [], $headers, false); // нам без разницы что там произошло в результате, поэтому никаких эксепшенов отлавливать не будем
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
        ];
    }


}
