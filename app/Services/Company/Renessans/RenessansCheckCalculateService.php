<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;

class RenessansCheckCalculateService extends RenessansService implements RenessansCheckCalculateServiceContract
{
    protected $apiPath = '/calculate/{{calcId}}/';

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService
    )
    {
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $attributes): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes['data']);

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
            static::companyCode,
            $this->getName(__CLASS__)
        );

        $this->writeResponseLog($response);

        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!$response['result'] || !isset($response['data']['response']['Premium'])) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло ответ',
                isset($response['message']) ? $response['message'] : 'нет данных об ошибке'
            ]);
        }
        return [
            'premium' => $response['data']['response']['Premium'],
        ];
    }

}
