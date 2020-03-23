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
        if (!$response['result']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['data']['response']['Premium'])) {
            return false;
        }
        return [
            'premium' => $response['data']['response']['Premium'],
        ];
    }

}
