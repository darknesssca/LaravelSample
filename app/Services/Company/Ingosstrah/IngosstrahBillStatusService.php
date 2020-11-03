<?php

namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahBillStatusServiceContract;
use App\Exceptions\ApiRequestsException;

class IngosstrahBillStatusService extends IngosstrahService implements IngosstrahBillStatusServiceContract
{
    public function run($company, $processData, $token = false): array
    {
        $data = $this->prepareData($processData);

        $requestLogData = [
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ];

        $this->writeRequestLog($requestLogData);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'GetBill', $data);

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
                        'paid' => false,
                    ];
            }
        }
        if (!isset($response['response']->ResponseData->Bill->Paid)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'paid' => ($response['response']->ResponseData->Bill->Paid == 2) ||
                (mb_strtolower($response['response']->ResponseData->Bill->Status) == 'в банке'),
        ];
    }

    protected function prepareData($processData)
    {
        return [
            'SessionToken' => $processData['data']['SessionToken'],
            'BillISN' => $processData['data']['BillISN'],
        ];
    }

}
