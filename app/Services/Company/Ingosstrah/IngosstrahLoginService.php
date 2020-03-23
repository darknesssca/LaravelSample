<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahLoginService extends IngosstrahService implements IngosstrahLoginServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendLogin($company, $attributes);
    }

    private function sendLogin($company, $attributes): array
    {
        $data = $this->prepareData();
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'Login', $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['response']->ResponseData->SessionToken)) {
            throw new \Exception('api not return SessionToken');
        }
        return [
            'sessionToken' => $response['response']->ResponseData->SessionToken,
        ];
    }

    public function prepareData()
    {
        $data = [
            'User' => $this->apiUser,
            'Password' => $this->apiPassword,
        ];
        return $data;
    }

}
