<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahBillService extends IngosstrahService implements IngosstrahBillServiceContract
{

    public function run($company, $data, $additionalFields = []): array
    {
        return $this->sendCheckCreate($company, $data);
    }

    private function sendCheckCreate($company, $data): array
    {
        $data = $this->prepareData($data);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'CreateBill', $data);
        dump($response);
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
                    ];
            }
        }
        if (!isset($response['response']->ResponseData->BillISN)) {
            throw new \Exception('api not return status');
        }
        return[
            'billIsn' => $response['response']->ResponseData->BillISN,
        ];
    }

    public function prepareData($data)
    {
        $data = [
            'SessionToken' => $data['data']['sessionToken'],
            'PaymentType' => 114916,
            'Payer' => 'Customer',
            'AgreementList' => [
                'AgrID' => $data['data']['policyId'],
            ],
        ];
        return $data;
    }

}
