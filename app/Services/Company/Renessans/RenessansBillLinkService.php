<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\ApiRequestsException;

class RenessansBillLinkService extends RenessansService implements RenessansBillLinkServiceContract
{
    protected $apiPath = '/policy/{{policyId}}/acquiring/{{code}}/';

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    public function run($company, $attributes): array
    {
        $data = [];
        $this->setAuth($data);
        $this->setUrlLinks($data);
        $this->setBillCode($attributes);
        $url = $this->getUrl($attributes);
        $response = $this->getRequest($url, $data);
        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!$response['result'] || !(isset($response['data']['url']) && $response['data']['url'])) {
            throw new ApiRequestsException(
                'API страховой компании не вернуло ответ',
                isset($response['message']) ? $response['message'] : 'нет данных об ошибке'
            );
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
