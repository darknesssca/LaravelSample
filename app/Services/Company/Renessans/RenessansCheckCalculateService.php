<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;

class RenessansCheckCalculateService extends RenessansService implements RenessansCheckCalculateServiceContract
{
    protected $apiPath = '/calculate/{{calcId}}/';

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl($attributes);
        $response = RestController::getRequest($url, $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!$response['result'] || !isset($response['data']['response']['Premium'])) {
            return [
                'result' => false,
                'message' => isset($response['message']) ? $response['message'] : '',
            ];
        }
        return [
            'result' => true,
            'premium' => $response['data']['response']['Premium'],
        ];
    }

}
