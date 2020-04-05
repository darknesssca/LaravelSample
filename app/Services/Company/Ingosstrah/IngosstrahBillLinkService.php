<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Traits\TransformBooleanTrait;

class IngosstrahBillLinkService extends IngosstrahService implements IngosstrahBillLinkServiceContract
{

    use TransformBooleanTrait;

    public function run($company, $processData): array
    {
        $data = $this->prepareData($processData);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'CreateOnlineBill', $data);
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
        if (!isset($response['response']->ResponseData->PayURL)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'PayUrl' => $response['response']->ResponseData->PayURL,
        ];
    }

    public function prepareData($processData)
    {
        return [
            'SessionToken' => $processData['data']['sessionToken'],
            'Bill' => [
                'BillISN' => $processData['data']['billIsn'],
                'Client' => [
                    'Email' => $processData['data']['insurerEmail'],
                    'SendByEmail' => $this->transformBooleanToChar(true),
                    'DigitalPolicyEmail' => $processData['data']['insurerEmail'],
                ],
            ],
        ];
    }

}
