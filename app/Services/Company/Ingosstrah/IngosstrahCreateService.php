<?php


namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Services\Company\Ingosstrah\IngosstrahService;
use Spatie\ArrayToXml\ArrayToXml;

class IngosstrahCreateService extends IngosstrahService implements IngosstrahCreateServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendCreate($company, $attributes, $additionalFields);
    }

    private function sendCreate($company, $attributes, $additionalFields = []): array
    {
        $data = $this->prepareData($attributes);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'CreateAgreement', $data);
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
        if (!isset($response['response']->ResponseStatus->AgrID)) {
            throw new \Exception('api not return AgrID');
        }
        $data = [
            'policyId' => $response['response']->AgrID,
        ];
        return $data;
    }

    public function prepareData($attributes)
    {
        $data = [
            'SessionToken' => $attributes['sessionToken'],
            'Agreement' => [
                "General" => [
                    "Product" => '753518300', //todo из справочника, вероятно статика
                    'DateBeg' => $this->formatDateTime($attributes['policy']['beginDate']),
                    'DateEnd' => $attributes['policy']['endDate'],
//                        "PrevAgrID" => "", //todo пролонгация
//                        "ParentISN" => "", //todo пролонгация
                    "Individual" => $this->transformBoolean(false),
                    "IsEOsago" => $this->transformBoolean(true),
                ],
                "Insurer" => [
                    "SbjRef" => $attributes['policy']['insurantId'],
                ],
                "Owner" => [
                    'SbjRef' => $attributes['policy']['ownerId'],
                ],
                "SubjectList" => [
                    "Subject" => [],
                ],
                "Vehicle" => [
                    'Model' => $attributes['car']['model'], // TODO: справочник
                    'VIN' => $attributes['car']['vin'],
                    "Category" => "B", // TODO из справочника
                    "Constructed" => $attributes['car']['year'],
                    'EnginePowerHP' => $attributes['car']['enginePower'],
                    "Document" => [],
                    "DocInspection" => [
                        "DocType" => $attributes['car']['inspection']['documentType'],
                    ],
                ],
                "Condition" => [
                    "Liability" => [
                        "RiskCtg" => "28966116", // TODO из справочника
                        'UsageType' => $attributes['car']['usageType'],
                        "UsageTarget" => [
                            $attributes['car']['vehicleUsage'] => $this->transformBoolean(true), // TODO имя параметра из справочника
                        ],
                        "UseWithTrailer" => $this->transformBoolean($attributes['car']['isUsedWithTrailer']),
                        "PeriodList" => [
                            "Period" => [
                                "DateBeg" =>  $attributes['policy']['beginDate'],
                                "DateEnd" =>  $attributes['policy']['endDate'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $this->setValue($data['Agreement']['Insurer'], 'MobilePhone', 'numberPhone', $insurer['phone']);
        $this->setValue($data['Agreement']['Insurer'], 'Email', 'email', $insurer);
        $this->setValuesByArray($data['Agreement']['Vehicle'], [
            'NetWeight' => 'minWeight',
            'GrossWeigh' => 'maxWeight',
            'Seats' => 'seats',
        ], $attributes['car']);
        //SubjectList
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            $pSubject = [
                '_attributes' => ['SbjKey' => $subject['id']],
                "SbjType" => 0, // TODO: справочник
                "SbjResident" => $this->transformBoolean($subject['fields']['isResident']),
                'FullName' => $subject['fields']['lastName'] . ' ' . $subject['fields']['firstName'] .
                    (isset($subject['fields']['middleName']) ? ' ' . $subject['fields']['middleName'] : ''),
                "Gender" => $subject['fields']['gender'], // TODO: справочник
                "BirthDate" => $subject['fields']['birthdate'],
                "CountryCode" => $subject['fields']['citizenship'], // TODO: справочник
            ];
            foreach ($subject['fields']['addresses'] as $iAddress => $address) {
                $pAddress = [
                    "CountryCode" => $subject['fields']['citizenship'], // TODO: справочник
                    'CityCode' => $address['address']['cityKladr'],
                    'StreetCode' => $address['address']['streetKladr'],
                    'StreetName' => $address['address']['street'],
                    'House' => $address['address']['building'],
                ];
                $this->setValuesByArray($pAddress, [
                    "flat" => 'flat',
                ], $address['address']);
                $pSubject['address'] = $pAddress;
            }
            foreach ($subject['fields']['documents'] as $iDocument => $document) {
                $pDocument = [
                    'DocType' => $document['document']['documentType'],  // TODO: справочник
                ];
                $this->setValuesByArray($pDocument, [
                    "Serial" => 'series',
                    "Number" => 'number',
                    "DocIssuedBy" => 'issuedBy',
                    "DocDate" => 'dateIssue',
                ], $document['document']);
                $pSubject['IdentityDocument'][] = $pDocument;
            }
            $data['Agreement']['SubjectList']['Subject'][] = $pSubject;
        }
        //Vehicle
        foreach ($attributes['car']['documents'] as $iDocument => $document) {
            $pDocument = [
                'DocType' => $document['document']['documentType'],  // TODO: справочник
            ];
            $this->setValuesByArray($pDocument, [
                "Serial" => 'documentSeries',
                "Number" => 'documentNumber',
                "DocDate" => 'documentIssued',
            ], $document['document']);
            $data['Agreement']['Vehicle']['Document'][] = $pDocument;
        }
        $this->setValuesByArray($data['Agreement']['Vehicle']['DocInspection'], [
            "Serial" => 'documentSeries',
            "Number" => 'documentNumber',
            "DateEnd" => 'documentDateEmd',
        ], $attributes['car']['inspection']);
        //DriverList
        if (!$attributes['policy']['isMultidrive']) {
            $data['Agreement']['DriverList'] = [];
            foreach ($attributes['drivers'] as $iDriver => $driver) {
                $pDriver = [
                    'SbjRef' => $driver['driver']['driverId'],
                    'DrvDateBeg' => $driver['driver']['drivingLicenseIssueDateOriginal'],
                ];
                $sDocument = $this->searchDocumentByTypeAndId($attributes, $driver['driver']['driverId'], 'driverLicense'); // todo занчение из справочника
                if ($sDocument) {
                    $pDriver['DriverLicense'] = [
                        'DocType' => $sDocument['documentType'],  // TODO: справочник
                    ];
                    $this->setValuesByArray($pDriver['DriverLicense'], [
                        "Serial" => 'series',
                        "Number" => 'number',
                        "DocDate" => 'dateIssue',
                    ], $sDocument);
                }
                $data['Agreement']['DriverList'][] = $pDriver;
            }
        }
        $xml = ArrayToXml::convert($data['Agreement'], 'Agreement');
        $xml = str_replace('<?xml version="1.0"?>', '', $xml);

        $data = new \SoapVar($xml, XSD_ANYXML);
        return $data;
    }

}
