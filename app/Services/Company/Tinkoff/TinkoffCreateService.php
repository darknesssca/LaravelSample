<?php

namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Http\Controllers\SoapController;

class TinkoffCreateService extends TinkoffService implements TinkoffCreateServiceContract
{
    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendCreate($company, $attributes);
    }

    private function sendCreate($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'issueQuoteSetPartner', $data);
        dd($response);
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['response']->issueResult)) {
            throw new \Exception('api not return issueResult');
        }
        return [
            'status' => 'done',
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
