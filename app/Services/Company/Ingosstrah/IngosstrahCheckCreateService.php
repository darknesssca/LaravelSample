<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahCheckCreateService extends IngosstrahService implements IngosstrahCheckCreateServiceContract
{

    public function run($company, $data, $additionalFields = []): array
    {
        return $this->sendCheckCreate($company, $data);
    }

    private function sendCheckCreate($company, $processData): array
    {
        $data = $this->prepareData($processData);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'GetAgreement', $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (isset($response['response']->ResponseStatus->ErrorCode)) {
            switch ($response['response']->ResponseStatus->ErrorCode) {
                case -20852:
                case -20841:
                case -20812:
                case -20808:
                case -20807:
                    return [
                        'tokenError' => true,
                    ];
            }
        }
        if (!isset($response['response']->ResponseData->any)) {
            throw new \Exception('api not return xml');
        }
        $response['parsedResponse'] = json_decode(json_encode(simplexml_load_string($response['response']->ResponseData->any, "SimpleXMLElement", LIBXML_NOCDATA)), true);
        if (!isset($response['parsedResponse']['@attributes']['State'])) {
            throw new \Exception('api not return status');
        }
        return [
            'state' => mb_strtolower($response['parsedResponse']['@attributes']['State']),
            'isn' => isset($response['parsedResponse']['General']['ISN']) ? $response['parsedResponse']['General']['ISN'] : false,
        ];
    }

    public function prepareData($data)
    {
        return [
            'SessionToken' => $data['data']['sessionToken'],
            'PolicyNumber' => $data['data']['policyId'],
        ];
    }

}
