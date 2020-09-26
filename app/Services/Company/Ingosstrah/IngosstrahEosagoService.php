<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Exceptions\ApiRequestsException;

class IngosstrahEosagoService extends IngosstrahService implements IngosstrahEosagoServiceContract
{

    public function run($company, $processData, $token = false): array
    {
        $data = $this->prepareData($processData);

        $requestLogData = [
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ];

        $this->writeRequestLog($requestLogData);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'MakeEOsago', $data);

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
        if (
            isset($response['response']->ResponseStatus->ErrorMessage) &&
            ($response['response']->ResponseStatus->ErrorMessage == 'Превышен период ожидания обработки очереди.') // это тупо, но коллеги из ингосстраха не предоставили ErrorCode, который относится к этой проблеме
        ) {
            return [
                'hold' => true,
                'isEosago' => false,
                'message' => isset($response['response']->ResponseStatus->ErrorMessage) ? $response['response']->ResponseStatus->ErrorMessage : 'нет данных об ошибке',
            ];
        }
        if (!isset($response['response']->ResponseData->Bso->Serial) || !isset($response['response']->ResponseData->Bso->Number)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'hold' => false,
            'isEosago' => true,
            'message' => 'ok',
        ];
    }

    protected function prepareData($processData)
    {
        return [
            'SessionToken' => $processData['data']['sessionToken'],
            'AgrISN' => $processData['data']['policyIsn'],
        ];
    }

}
