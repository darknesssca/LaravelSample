<?php

namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Http\Controllers\SoapController;

class TinkoffBillLinkService extends TinkoffService implements TinkoffBillLinkServiceContract
{
    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendBillLink($company, $attributes);
    }

    private function sendBillLink($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'issueQuoteSetPartner', $data);
        dd($response);
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['response']->paymentURL)) {
            throw new \Exception('api not return paymentURL');
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
