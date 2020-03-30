<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;

class RenessansGetStatusService extends RenessansService implements RenessansGetStatusServiceContract
{
    protected $apiPath = '/policy/{{policyId}}/info/';

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
        if (!$response['result'] || !isset($response['data']['return']['Status'])) {
            return [
                'result' => false,
                'message' => isset($response['message']) ? $response['message'] : '',
                'createStatus' => false,
                'payStatus' => false,
                'status' => 'error',
                'policyNumber' => false,
                'billId' => false,
            ];
        }
        return [
            'result' => true,
            'status' => $response['data']['return']['Status'],
            'createStatus' => (mb_strtolower($response['data']['return']['Status']) == 'согласован') && isset($response['data']['return']['Number']) && $response['data']['return']['Number'],
            'billId' => isset($response['data']['return']['Number']) ? $response['data']['return']['Number'] : false,
            'payStatus' => (mb_strtolower($response['data']['return']['Status']) == 'оформлен') && (isset($response['data']['return']['StatusPay']) && mb_strtolower($response['data']['return']['StatusPay']) == 'оплачен'),
            'message' => isset($response['message']) ? $response['message'] : '',
            'policyNumber' => isset($response['data']['return']['DocNumber']) ? $response['data']['return']['DocNumber'] : false,
        ];
    }

}
