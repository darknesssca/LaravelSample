<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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

        $this->writeLog(
            $this->logPath,
            [
                'request' => [
                    'url' => $url,
                    'method' => 'CheckCalculate',
                    'payload' => $data
                ]
            ]
        );

        $response = $this->getRequest($url, $data, [], false);

        $this->writeLog(
            $this->logPath,
            [
                'response' => [
                    'method' => 'CheckCalculate',
                    'response' => $response
                ]
            ]
        );

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
