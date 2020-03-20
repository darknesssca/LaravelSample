<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Models\InsuranceCompany;

class RenessansCreateService extends RenessansService implements RenessansCreateServiceContract
{
    protected $apiPath = [
        'sendCreate' => '/create/',
    ];

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = [31, 32, 30]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogStsDocType = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    private function setAdditionalFields(&$attributes, $additionalFields) {
        $attributes['CheckSegment'] = intval(isset($additionalFields['isCheckSegment']) && $additionalFields['isCheckSegment']);
    }

    private function setCalculationId(&$attributes, $calculationId) {
        $attributes['calculationId'] = $calculationId;
    }

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $this->setAdditionalFields($attributes, $additionalFields);
        $result = [];
        foreach ($additionalFields['calculationId'] as $calculationId) {
            $result[$calculationId] = $this->sendCreate($attributes, $calculationId);
        }
        return $result;
    }

    private function sendCreate($attributes, $calculationId)
    {
        $this->setCalculationId($attributes, $calculationId);
        $this->setAuth($attributes);
        $url = $this->getUrl(__FUNCTION__);
        $this->prepareData($attributes);
        $response = $this->postRequest($url, $attributes);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!$response['result']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        return $response['data'];
    }

    protected function prepareData($attributes): array
    {
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $data = [
            'calculationId' => $attributes['calculationId'],
            'purpose' => $attributes['car']['vehicleUsage'],
            'cabinet' => [
                'email' => $insurer['email'],
            ],
            'isInsurerJuridical' => $this->transformBooleanToInteger(false),
        ];
        $data['insurer'] = $this->getSubjectData($insurer);
        return $data;
    }

    protected function getSubjectData($subject)
    {
        $subjectData = [
            'email' => $subject['email'],
            'phone' => $subject['phone'],
        ];
        $regAddress = $this->searchAddressByType($subject, 'registration'); //todo справочник
    }

    protected function getAddressData($address)
    {
        $addressData = [

        ];
    }

}
