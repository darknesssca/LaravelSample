<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Exceptions\ApiRequestsException;

class IngosstrahCheckCreateService extends IngosstrahService implements IngosstrahCheckCreateServiceContract
{

    public function run($company, $processData, $token = false): array
    {
        $data = $this->prepareData($processData);

        $requestLogData = [
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ];

        $this->writeRequestLog($requestLogData);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'GetAgreement', $data);

        $this->writeResponseLog($response);

        if ($token !== false) {
            $this->writeDatabaseLog(
                $token,
                $requestLogData,
                $response,
                config('api_sk.logMicroserviceCode'),
                static::companyCode,
                $this->getName(__CLASS__)
            );
        }

        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : 'нет данных об ошибке'
            );
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
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        $response['parsedResponse'] = json_decode(
            json_encode(
                simplexml_load_string(
                    $response['response']->ResponseData->any,
                    "SimpleXMLElement",
                    LIBXML_NOCDATA)
            ),
            true);
        if (!isset($response['parsedResponse']['@attributes']['State'])) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'state' => mb_strtolower($response['parsedResponse']['@attributes']['State']),
            'isn' => isset($response['parsedResponse']['General']['ISN']) ? $response['parsedResponse']['General']['ISN'] : false,
            'policySerial' => isset($response['parsedResponse']['General']['Policy']['Serial']) ? $response['parsedResponse']['General']['Policy']['Serial'] : false,
            'policyNumber' => isset($response['parsedResponse']['General']['Policy']['No']) ? $response['parsedResponse']['General']['Policy']['No'] : false,
            'isEosago' => isset($response['parsedResponse']['General']['IsEOsago']) ? $response['parsedResponse']['General']['IsEOsago'] == 'Y' : false,
            'message' => isset($response['response']->ResponseStatus->ErrorMessage) ?
                $response['response']->ResponseStatus->ErrorMessage :
                'нет данных об ошибке',
        ];
    }

    protected function prepareData($data)
    {
        return [
            'SessionToken' => $data['data']['sessionToken'],
            'PolicyNumber' => $data['data']['policyId'],
        ];
    }

}
