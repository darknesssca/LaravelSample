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

    public function run ($company, $attributes): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes);
        $this->companyName = $this->getName(__NAMESPACE__);
        $this->serviceName = $this->getName(__CLASS__);

        $this->writeRequestLog([
            'url' => $url,
            'payload' => $data
        ]);

        $response = $this->getRequest($url, $data, [], false);

        $this->writeDatabaseLog(
            $attributes['token'],
            [
                'url' => $url,
                'payload' => $data
            ],
            $response,
            config('api_sk.logMicroserviceCode'),
            $this->companyName,
            $this->serviceName,
        );

        $this->writeResponseLog($response);

        return [];
    }

}
