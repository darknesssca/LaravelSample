<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Exceptions\ApiRequestsException;

class IngosstrahBillService extends IngosstrahService implements IngosstrahBillServiceContract
{

    public function run($company, $processData, $token = false): array
    {
        $data = $this->prepareData($processData);

        $requestLogData = [
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ];

        $this->writeRequestLog($requestLogData);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'CreateBill', $data);

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
