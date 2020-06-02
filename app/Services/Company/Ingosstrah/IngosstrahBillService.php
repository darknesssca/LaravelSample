<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Exceptions\ApiRequestsException;
use Illuminate\Support\Facades\Storage;

class IngosstrahBillService extends IngosstrahService implements IngosstrahBillServiceContract
{

    public function run($company, $processData): array
    {
        $data = $this->prepareData($processData);

        $this->writeLog(
            $this->logPath,
            [
                'request' => [
                    'url' => $this->apiWsdlUrl,
                    'method' => 'Bill',
                    'payload' => $data
                ]
            ]
        );

        $response = $this->requestBySoap($this->apiWsdlUrl, 'CreateBill', $data);

        $this->writeLog(
            $this->logPath,
            [
                'response' => [
                    'method' => 'Bill',
                    'response' => $response
                ]
            ]
        );

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
        if (!isset($response['response']->ResponseData->BillISN)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        return[
            'billIsn' => $response['response']->ResponseData->BillISN,
        ];
    }

    protected function prepareData($processData)
    {
        return [
            'SessionToken' => $processData['data']['sessionToken'],
            'PaymentType' => 114916,
            'Payer' => 'Customer',
            'AgreementList' => [
                'AgrID' => $processData['data']['policyId'],
            ],
        ];
    }

}
