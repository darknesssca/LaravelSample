<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahBillLinkService extends IngosstrahService implements IngosstrahBillLinkServiceContract
{

    public function run($company, $data, $additionalFields = []): array
    {
        $data = $this->prepareData($data, $additionalFields);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'CreateOnlineBill', $data);
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
        if (!isset($response['response']->ResponseData->PayURL)) {
            throw new \Exception('страховая компания вернула некорректный результат' . (isset($response['response']->ResponseStatus->ErrorMessage) ? ' | ' . $response['response']->ResponseStatus->ErrorMessage : ''));
        }
        return [
            'PayUrl' => $response['response']->ResponseData->PayURL,
        ];
    }

    public function prepareData($data, $form)
    {
        $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
        return [
            'SessionToken' => $data['data']['sessionToken'],
            'Bill' => [
                'BillISN' => $data['data']['billIsn'],
                'Client' => [
                    'Email' => $insurer['email'],
                    'SendByEmail' => $this->transformBoolean(true),
                    'DigitalPolicyEmail' => $insurer['email'],
                ],
            ],
        ];
    }

}
