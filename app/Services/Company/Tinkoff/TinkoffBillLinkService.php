<?php

namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Http\Controllers\SoapController;

class TinkoffBillLinkService extends TinkoffService implements TinkoffBillLinkServiceContract
{
    public function run($company, $attributes, $additionalFields = []): array
    {
        $data = $this->prepareData($attributes);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'getPaymentReferencePartner', $data);
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['response']->paymentURL)) {
            throw new \Exception('api not return paymentURL' . isset($response['response']->Header->resultInfo->errorInfo->descr) ? ' | ' . $response['response']->Header->resultInfo->errorInfo->descr : '');
        }
        return [
            'billUrl' => $response['response']->paymentURL,
        ];
    }

    public function prepareData($attributes)
    {
        $data = [];
        $this->setHeader($data);
        $data['setNumber'] = $attributes['setNumber'];
        return $data;
    }

}
