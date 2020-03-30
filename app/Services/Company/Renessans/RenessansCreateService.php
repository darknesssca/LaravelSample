<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Traits\TransformBooleanTrait;

class RenessansCreateService extends RenessansService implements RenessansCreateServiceContract
{
    use TransformBooleanTrait;

    protected $apiPath = '/create/';

    protected function setAdditionalFields(&$attributes) {
        $attributes['CheckSegment'] = intval(isset($attributes['CheckSegment']) && $attributes['CheckSegment']);
    }

    public function run($company, $attributes): array
    {
        $this->setAdditionalFields($attributes);
        $this->setAuth($attributes);
        $url = $this->getUrl();
        $data = $this->prepareData($attributes);
        $this->init();
        $response = $this->postRequest($url, $data, [], false);
        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!$response['result']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (!isset($response['data']['policyId']) || !$response['data']['policyId']) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло номер созданного полиса',
                isset($response['message']) ? $response['message'] : 'нет данных об ошибке'
            ]);
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
        $subjectData = [];
        $this->setValuesByArray($subjectData, [
            'email' => 'email',
            'phone' => 'phone',
            'name' => 'firstName',
            'lastname' => 'lastName',
            'middlename' => 'middleName',
            'birthday' => 'birthdate',
        ], $subject);
        $document = $this->searchDocumentByType($subject, 'RussianPassport'); //todo справочник
        if ($document) {
            $subjectData['document'] = [];
            $this->setValuesByArray($subjectData['document'], [
                'typeofdocument' => 'documentType',
                'series' => 'series',
                'number' => 'number',
                'dateIssue' => 'dateIssue',
                'issued' => 'issuedBy',
                'codeDivision' => 'subdivisionCode',
            ], $document);
        }
        $regAddress = $this->searchAddressByType($subject, 'registration'); //todo справочник
        if ($regAddress) {
            $subjectData['addressJuridical'] = $this->getAddressData($regAddress);
        }
        $factAddress = $this->searchAddressByType($subject, 'home'); //todo справочник
        if ($factAddress) {
            $subjectData['addressFact'] = $this->getAddressData($factAddress);
        }
        return $subjectData;
    }

    protected function getAddressData($address)
    {
        $addressData = [];
        $this->setValuesByArray($addressData, [
            'country' => 'country',
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
        return $addressData;
    }

}
