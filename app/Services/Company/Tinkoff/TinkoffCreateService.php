<?php

namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Http\Controllers\SoapController;

class TinkoffCreateService extends TinkoffService implements TinkoffCreateServiceContract
{
    public function run($company, $attributes, $additionalFields = []): array
    {
        $data = $this->prepareData($attributes);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'issueQuoteSetPartner', $data);
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['response']->Header->resultInfo->status)) {
            throw new \Exception('При попытке создать полис был не был возвращен статус' . isset($response['response']->Header->resultInfo->errorInfo->descr) ? ' | ' . $response['response']->Header->resultInfo->errorInfo->descr : '');
        }
        if (strtolower($response['response']->Header->resultInfo->status) != 'ok') {
            throw new \Exception('При попытке создать полис был возвращен некорректный статус: ' . $response['response']->Header->resultInfo->status .
            ' | код ошибки: ' . (isset($response['response']->Header->resultInfo->errorInfo->code) ? $response['response']->Header->resultInfo->errorInfo->code : '') .
            ' | текст ошибки: ' . (isset($response['response']->Header->resultInfo->errorInfo->descr) ? $response['response']->Header->resultInfo->errorInfo->descr : ''));
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
