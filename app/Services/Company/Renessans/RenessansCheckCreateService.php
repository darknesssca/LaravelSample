<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;

class RenessansCheckCreateService extends RenessansService implements RenessansCheckCreateServiceContract
{
    protected $apiPath = '/policy/{{policyId}}/status/';

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
        $url = $this->getUrl($attributes);
        $this->companyName = $this->getName(__NAMESPACE__);
        $this->serviceName = $this->getName(__CLASS__);

        $this->writeRequestLog([
            'url' => $url,
            'payload' => $data
        ]);

        $response = $this->getRequest($url, $data, [], false);

        $this->writeResponseLog($response);

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

        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!isset($response['data']['Status']) || ($response['data']['Status'] != 'ok')) {
            if (isset($response['data']['return']['Status']) && ($response['data']['return']['Status'] == 'wait')) {
                throw new ApiRequestsException([
                    'API страховой компании не вернуло ответ',
                    isset($response['message']) ? $response['message'] : 'нет данных об ошибке'
                ]);
            } else {
                return [
                    'result' => true,
                    'status' => 'error',
                    'message' => isset($response['message']) ? $response['message'] : 'нет деталей ошибки',
                ];
            }
        } else if (
            isset($response['data']['RSA']) &&
            isset($response['data']['Upload']) &&
            (int) $response['data']['Upload'] > 0) {
                throw new ApiRequestsException(['Не пройдена проверка в РСА']);
        }
        return [
            'result' => true,
            'status' => 'ok',
            'message' => isset($response['message']) ? $response['message'] : 'ok',
        ];
    }

}
