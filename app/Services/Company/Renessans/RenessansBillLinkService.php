<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;

class RenessansBillLinkService extends RenessansService implements RenessansBillLinkServiceContract
{
    protected $apiPath = '/policy/{{id}}/acquiring/{{code}}/';

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $data = [];
        $this->setAuth($data);
        $this->setBillCode($data);
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

    protected function setBillCode(&$data)
    {
        $data['code'] = 'renins'; // на данный момент задается статикой
    }

}
