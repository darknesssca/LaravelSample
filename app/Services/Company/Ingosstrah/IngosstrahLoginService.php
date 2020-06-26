<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Exceptions\ApiRequestsException;

class IngosstrahLoginService extends IngosstrahService implements IngosstrahLoginServiceContract
{

    public function run($company, $attributes): array
    {
        $data = $this->prepareData();

        $this->writeRequestLog([
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ]);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'Login', $data);

        $this->writeResponseLog($response);

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
