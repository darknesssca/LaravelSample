<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Exceptions\ApiRequestsException;

class IngosstrahLoginService extends IngosstrahService implements IngosstrahLoginServiceContract
{

    public function run($company, $attributes, $token = false): array
    {
        $data = $this->prepareData();

        $requestLogData = [
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ];

        $this->writeRequestLog($requestLogData);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'Login', $data);

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
        if (!isset($response['response']->ResponseData->SessionToken)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'sessionToken' => $response['response']->ResponseData->SessionToken,
        ];
    }

    protected function prepareData()
    {
        return [
            'User' => $this->apiUser,
            'Password' => $this->apiPassword,
        ];
    }

}
