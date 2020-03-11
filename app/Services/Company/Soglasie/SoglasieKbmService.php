<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;

class SoglasieKbmService extends SoglasieService implements SoglasieKbmServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendKbm($company, $attributes);
    }

    private function sendKbm($company, $attributes): array
    {
        $data = $this->prepareData();
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'Login', $data);
        dd($response);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if ($response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response->SessionToken)) {
            throw new \Exception('api not return SessionToken');
        }
        return [
            'sessionToken' => $response->SessionToken,
        ];
    }

    public function prepareData()
    {
        $data = [
            'User' => $this->apiUser,
            'Password' => $this->apiPassword,
        ];
    }

}
