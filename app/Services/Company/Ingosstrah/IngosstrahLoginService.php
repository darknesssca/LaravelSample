<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahLoginService extends IngosstrahService implements IngosstrahLoginServiceContract
{

    public function run($company, $attributes, $additionalFields = []): array
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
            throw new \Exception('страховая компания вернула некорректный результат' . (isset($response['response']->ResponseStatus->ErrorMessage) ? ' | ' . $response['response']->ResponseStatus->ErrorMessage : ''));
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
