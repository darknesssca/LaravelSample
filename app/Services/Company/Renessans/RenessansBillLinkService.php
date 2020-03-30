<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;

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
            throw new \Exception('api not return answer');
        }
        if (!$response['result']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['data']['url'])) {
            throw new \Exception('api no return url');
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
