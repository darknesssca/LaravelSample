<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;

class RenessansCreateService extends RenessansService implements RenessansCreateServiceContract
{
    protected $apiPath = '/create/';

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = [31, 32, 30]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogStsDocType = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    private function setAdditionalFields(&$attributes) {
        $attributes['CheckSegment'] = intval(isset($attributes['CheckSegment']) && $attributes['CheckSegment']);
    }

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $this->setAdditionalFields($attributes);
        $this->setAuth($attributes);
        $url = $this->getUrl();
        $data = $this->prepareData($attributes);
        $response = RestController::postRequest($url, $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!$response['result']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['data']['policyId']) || !$response['data']['policyId']) {
            throw new \Exception('api not return policyId');
        }
        return [
            'policyId' => $response['data']['policyId'],
        ];
    }

    protected function prepareData($attributes): array
    {
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $data = [
            'key' => $attributes['key'],
            'CheckSegment' => $this->transformBooleanToInteger($attributes['CheckSegment']),
            'calculationId' => $attributes['calcId'],
            'purpose' => $attributes['car']['vehicleUsage'],
            'cabinet' => [
                'email' => $insurer['email'],
            ],
            'isInsurerJuridical' => $this->transformBooleanToInteger(false),
            'car' => [
                'year' => $attributes['car']['year'],
                //'isNew' => $attributes['car']['sourceAcquisition'] == 1 ? 1 : 0, //todo справочник
                'MarkAndModelString' => $attributes['car']['maker'] . ' ' . $attributes['car']['model'], //todo справочник
            ],
        ];
        if ($attributes['car']['document']['documentType'] == 'PTS') { //todo справочник
            $data['car']['pts'] = [];
            $this->setValuesByArray($data['car']['pts'], [
                'serie' => 'series',
                'number' => 'number',
                'dateIssue' => 'dateIssue',
            ], $attributes['car']['document']);
        } else {
            $data['car']['sts'] = [];
            $this->setValuesByArray($data['car']['sts'], [
                'serie' => 'series',
                'number' => 'number',
                'dateIssue' => 'dateIssue',
                'docType' => 'documentType',
            ], $attributes['car']['document']);
        }
        $data['car']['diagnostic'] = [];
        $this->setValuesByArray($data['car']['diagnostic'], [
            'number' => 'number',
            'validDate' => 'dateEnd',
        ], $attributes['car']['inspection']);
        $data['insurer'] = $this->getSubjectData($insurer);
        $data['owner'] = $this->getSubjectData($owner);
        return $data;
    }

    protected function getSubjectData($subject)
    {
        $subjectData = [
            'email' => $subject['email'],
            'phone' => $subject['phone'],
        ];
        $regAddress = $this->searchAddressByType($subject, 'registration'); //todo справочник
        if ($regAddress) {
            $subjectData['addressJuridical'] = $this->getAddressData($regAddress);
        }
        $factAddress = $this->searchAddressByType($subject, 'Home'); //todo справочник
        if ($factAddress) {
            $subjectData['addressFact'] = $this->getAddressData($factAddress);
        }
        $document = $this->searchDocumentByType($subject, 'passport');
        if ($document) {
            $subjectData['document'] = [];
            $this->setValuesByArray($subjectData, [
                'typeofdocument' => 'documentType',
                'series' => 'series',
                'number' => 'number',
                'dateIssue' => 'dateIssue',
                'issued' => 'issuedBy',
                'codeDivision' => 'subdivisionCode',
            ], $document);
        }
    }

    protected function getAddressData($address)
    {
        $addressData = [
            'country' => $address['country'],
        ];
        $this->setValuesByArray($addressData, [
            'zip' => 'postCode',
            'city' => 'city',
            'settlement' => 'populatedCenter',
            'street' => 'street',
            'home' => 'building',
            'flat' => 'flat',
            'area' => 'district',
            'region' => 'region',
            'kladr' => 'streetKladr',
        ], $address);
    }

}
