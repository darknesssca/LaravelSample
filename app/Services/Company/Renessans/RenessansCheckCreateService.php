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
        if (!$response['result'] && (!isset($response['data']['result']) || !$response['data']['result'])) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['data']['Status']) || ($response['data']['Status'] == 'ok')) {
            return [
                'status' => 'wait',
            ];
        }
        return [
            'result' => true,
        ];
    }

}
