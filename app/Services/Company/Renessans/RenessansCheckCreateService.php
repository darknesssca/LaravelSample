<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;

class RenessansCheckCreateService extends RenessansService implements RenessansCheckCreateServiceContract
{
    protected $apiPath = '/policy/{{policyId}}/status/';

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
        $url = $this->getUrl($attributes);
        $response = $this->getRequest($url, $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!isset($response['data']['Status']) || ($response['data']['Status'] != 'ok')) {
            if (isset($response['data']['return']['Status']) && ($response['data']['return']['Status'] == 'wait')) {
                return [
                    'result' => false,
                    'status' => 'wait',
                    'message' => isset($response['message']) ? $response['message'] : '',
                ];
            } else {
                return [
                    'result' => false,
                    'status' => 'error',
                    'message' => isset($response['message']) ? $response['message'] : '',
                ];
            }
        }
        return [
            'result' => true,
            'status' => 'ok',
        ];
    }

}
