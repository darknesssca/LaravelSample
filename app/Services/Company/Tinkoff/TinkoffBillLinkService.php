<?php

namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Exceptions\ApiRequestsException;

class TinkoffBillLinkService extends TinkoffService implements TinkoffBillLinkServiceContract
{
    public function run($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'getPaymentReferencePartner', $data);
        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (!isset($response['response']->paymentURL)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->Header->resultInfo->errorInfo->descr) ?
                    $response['response']->Header->resultInfo->errorInfo->descr :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'billUrl' => $response['response']->paymentURL,
        ];
    }

    protected function prepareData($attributes)
    {
        $data = [];
        $this->setHeader($data);
        $data['setNumber'] = $attributes['setNumber'];
        return $data;
    }

}
