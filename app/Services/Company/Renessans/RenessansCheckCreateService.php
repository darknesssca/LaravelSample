<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;

class RenessansCheckCreateService extends RenessansService implements RenessansCheckCreateServiceContract
{
    protected $apiPath = '/policy/{{policyId}}/status/';

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes);
        $response = RestController::getRequest($url, $data);
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
