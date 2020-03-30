<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillStatusServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahBillStatusService extends IngosstrahService implements IngosstrahBillStatusServiceContract
{

    public function run($company, $attributes, $additionalFields = []): array
    {
        $data = $this->prepareData($attributes, $additionalFields);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'GetBill', $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
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
            throw new \Exception('страховая компания вернула некорректный результат' . (isset($response['response']->ResponseStatus->ErrorMessage) ? ' | ' . $response['response']->ResponseStatus->ErrorMessage : ''));
        }
        return [
            'paid' => $response['response']->ResponseData->Bill->Paid == 2,
        ];
    }

    public function prepareData($attributes, $form)
    {
        return [
            'SessionToken' => $attributes['SessionToken'],
            'BillISN' => $attributes['BillISN'],
        ];
    }

}
