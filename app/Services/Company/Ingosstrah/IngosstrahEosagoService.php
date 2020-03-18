<?php


namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Services\Company\Ingosstrah\IngosstrahService;
use Spatie\ArrayToXml\ArrayToXml;

class IngosstrahEosagoService extends IngosstrahService implements IngosstrahEosagoServiceContract
{

    public function run($company, $data, $additionalFields = []): array
    {
        return $this->sendEosago($company, $data);
    }

    private function sendEosago($company, $data): array
    {
        $data = $this->prepareData($data);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'MakeEOsago', $data);
        dd($response);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
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
            'AgrISN' => $data->data['AgrISN'],
        ];
        return $data;
    }

}
