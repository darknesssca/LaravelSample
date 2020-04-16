<?php

namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Exceptions\ApiRequestsException;

class TinkoffCreateService extends TinkoffService implements TinkoffCreateServiceContract
{
    public function run($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        dump('TinkoffCreateService', '$data', $data);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'issueQuoteSetPartner', $data);
        dump('TinkoffCreateService', '$response', $response);
        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (!isset($response['response']->Header->resultInfo->status)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->Header->resultInfo->errorInfo->descr) ?
                    $response['response']->Header->resultInfo->errorInfo->descr :
                    'нет данных об ошибке',
            ]);
        }
        if (!isset($response['response']->Header->resultInfo->status)) {
            throw new ApiRequestsException([
                'При попытке создать полис был не был возвращен статус',
                isset($response['response']->Header->resultInfo->errorInfo->descr) ?
                    $response['response']->Header->resultInfo->errorInfo->descr :
                    'нет данных об ошибке',
            ]);
        }
        if (strtolower($response['response']->Header->resultInfo->status) != 'ok') {
            throw new ApiRequestsException([
                'При попытке создать полис был возвращен некорректный статус: ' . $response['response']->Header->resultInfo->status,
                isset($response['response']->Header->resultInfo->errorInfo->descr) ?
                    $response['response']->Header->resultInfo->errorInfo->descr :
                    'нет данных об ошибке',
            ]);
        }
        if (!isset($response['response']->additionalPolicyInfo->quoteNumber)) {
            throw new ApiRequestsException([
                'При попытке создать полис был не был возвращен номер полиса',
                isset($response['response']->Header->resultInfo->errorInfo->descr) ?
                    $response['response']->Header->resultInfo->errorInfo->descr :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'status' => 'done',
            'number' => $response['response']->additionalPolicyInfo->quoteNumber,
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
