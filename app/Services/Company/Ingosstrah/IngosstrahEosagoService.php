<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahEosagoService extends IngosstrahService implements IngosstrahEosagoServiceContract
{

    public function run($company, $data, $additionalFields = []): array
    {
        $data = $this->prepareData($data);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'MakeEOsago', $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (
            isset($response['response']->ResponseStatus->ErrorMessage) &&
            ($response['response']->ResponseStatus->ErrorMessage == 'Превышен период ожидания обработки очереди.') // это тупо, но коллеги из ингосстраха не предоставили ErrorCode, который относится к этой проблеме
        ) {
            return [
                'hold' => true,
                'isEosago' => false,
            ];
        }
        if (!isset($response['response']->ResponseData->Bso->Serial) || !isset($response['response']->ResponseData->Bso->Number)) {
            throw new \Exception('страховая компания вернула некорректный результат' . (isset($response['response']->ResponseStatus->ErrorMessage) ? ' | ' . $response['response']->ResponseStatus->ErrorMessage : ''));
        }
        return [
            'hold' => false,
            'isEosago' => true,
        ];
    }

    public function prepareData($data)
    {
        $data = [
            'SessionToken' => $data['data']['sessionToken'],
            'AgrISN' => $data['data']['policyIsn'],
        ];
        return $data;
    }

}
