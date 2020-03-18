<?php


namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Services\Company\Ingosstrah\IngosstrahService;
use Spatie\ArrayToXml\ArrayToXml;

class IngosstrahCheckCreateService extends IngosstrahService implements IngosstrahCheckCreateServiceContract
{

    public function run($company, $data, $additionalFields = []): array
    {
        return $this->sendCheckCreate($company, $data);
    }

    private function sendCheckCreate($company, $data): array
    {
        $data = $this->prepareData($data);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'GetAgreement', $data);
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
        if (!isset($response['response']->Agreement->State)) {
            throw new \Exception('api not return status');
        }
        $data = [
            'response' => $response['response'],
        ];
        return $data;
    }

    public function prepareData($data)
    {
        $data = [
            'SessionToken' => $data->data['sessionToken'],
            'PolicyNumber' => $data->data['policyId'],
        ];
        return $data;
    }

}
