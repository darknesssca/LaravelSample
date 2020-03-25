<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahCheckCreateService extends IngosstrahService implements IngosstrahCheckCreateServiceContract
{

    public function run($company, $processData, $additionalFields = []): array
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
            throw new \Exception('страховая компания вернула некорректный результат' . (isset($response['response']->ResponseStatus->ErrorMessage) ? ' | ' . $response['response']->ResponseStatus->ErrorMessage : ''));
        }
        return [
            'state' => mb_strtolower($response['parsedResponse']['@attributes']['State']),
            'isn' => isset($response['parsedResponse']['General']['ISN']) ? $response['parsedResponse']['General']['ISN'] : false,
            'policySerial' => isset($response['parsedResponse']['General']['Policy']['Serial']) ? $response['parsedResponse']['General']['Policy']['Serial'] : false,
            'policyNumber' => isset($response['parsedResponse']['General']['Policy']['No']) ? $response['parsedResponse']['General']['Policy']['No'] : false,
            'isEosago' => isset($response['parsedResponse']['General']['IsEOsago']) ? $response['parsedResponse']['General']['IsEOsago'] == 'Y' : false,
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
