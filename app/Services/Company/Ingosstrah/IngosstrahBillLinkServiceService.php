<?php


namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Services\Company\Ingosstrah\IngosstrahService;
use Spatie\ArrayToXml\ArrayToXml;

class IngosstrahBillLinkServiceService extends IngosstrahService implements IngosstrahBillLinkServiceContract
{

    public function run($company, $data, $additionalFields = []): array
    {
        return $this->sendBillLink($company, $data, $additionalFields);
    }

    private function sendCheckCreate($company, $data, $additionalFields): array
    {
        $form = json_decode($additionalFields['form']);
        $data = $this->prepareData($data, $form);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'CreateOnlineBill', $data);
        dd($response);
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
        if (!isset($response['response']->PayURL)) {
            throw new \Exception('api not return status');
        }
        $data = [
            'response' => $response['response'],
        ];
        return $data;
    }

    public function prepareData($data, $form)
    {
        $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
        $data = [
            'SessionToken' => $data->data['sessionToken'],
            'Bill' => [
                'BillISN' => $data->data['BillISN'],
            ],
            'Client' => [
                'Email' => $insurer['email'],
                'SendByEmail' => $this->transformBoolean(true),
            ],
            'PaymentType' => 114916,
            'Payer' => 'Customer',
            'AgreementList' => [
                'AgrID' => $data->data['policyId'],
            ],
        ];
        return $data;
    }

}
