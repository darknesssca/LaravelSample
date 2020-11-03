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

    public function run($company, $attributes, $token = false): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes['data']);

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
            'kbm' => $response['data']['response']['KBM'][0]['KBM'] ?? '',
        ];
    }

}
