<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;

class RenessansGetStatusService extends RenessansService implements RenessansGetStatusServiceContract
{
    protected $apiPath = '/policy/{{policyId}}/info/';

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes);
        $response = RestController::getRequest($url, $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!$response['result'] || !isset($response['data']['return']['Status'])) {
            return [
                'result' => false,
                'message' => isset($response['message']) ? $response['message'] : '',
            ];
        }
        if ((mb_strtolower($response['data']['return']['Status']) == 'согласован') && isset($response['data']['return']['Number']) && $response['data']['return']['Number']) {
            return [
                'billId' => $response['data']['return']['Number'],
                'result' => true,
            ];
        }
        return [
            'result' => false,
            'message' => isset($response['message']) ? $response['message'] : '',
        ];
    }

}
