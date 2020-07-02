<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;

class RenessansBillLinkService extends RenessansService implements RenessansBillLinkServiceContract
{
    protected $apiPath = '/policy/{{policyId}}/acquiring/{{code}}/';

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
        $this->setUrlLinks($data);
        $this->setBillCode($attributes);
        $url = $this->getUrl($attributes);

        $this->writeRequestLog([
            'url' => $url,
            'payload' => $data
        ]);

        $response = $this->getRequest($url, $data, [], false);

        $this->writeResponseLog($response);

        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!$response['result'] || !(isset($response['data']['url']) && $response['data']['url'])) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло ответ',
                isset($response['message']) ? $response['message'] : 'нет данных об ошибке'
            ]);
        }
        return [
            'billUrl' => $response['data']['url'],
        ];
    }

    protected function setBillCode(&$data)
    {
        $data['code'] = 'renins'; // на данный момент задается статикой
    }

    protected function setUrlLinks(&$data)
    {
        $data['success_url'] = config('api_sk.billSuccessUrl');
        $data['fail_url'] = config('api_sk.billFailUrl');
    }

}
