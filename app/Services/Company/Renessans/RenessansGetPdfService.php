<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansGetPdfServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;

class RenessansGetPdfService extends RenessansService implements RenessansGetPdfServiceContract
{
    protected $apiPath = '/policy/{{policyId}}/pdf/';

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService
    )
    {
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run ($company, $attributes, $token = false): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes);

        $requestLogData = [
            'url' => $url,
            'payload' => $data
        ];

        $this->writeRequestLog($requestLogData);

        $response = $this->getRequest($url, $data, [], false);

        if ($token !== false) {
            $this->writeDatabaseLog(
                $token,
                $requestLogData,
                $response,
                config('api_sk.logMicroserviceCode'),
                static::companyCode,
                $this->getName(__CLASS__)
            );
        }

        $this->writeResponseLog($response);

        return [];
    }

}
