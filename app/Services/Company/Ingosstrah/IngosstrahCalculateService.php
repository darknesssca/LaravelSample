<?php


namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Services\Company\Ingosstrah\IngosstrahService;

class IngosstrahCalculateService extends IngosstrahService implements IngosstrahCalculateServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendCalculate($company, $attributes);
    }

    private function sendCalculate($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'GetTariff', $data);
        dd($response);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if ($response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (isset($response->ErrorCode) && $response->ErrorCode) {
            throw new \Exception('api return validation error code: '.
                $response->ErrorCode.
                ' | message: '.
                (isset($response->ErrorMessage) ? $response->ErrorMessage : 'nocode')
            );
        }
        if (!isset($response->PremiumAmount)) {
            throw new \Exception('api not return premium');
        }
        $data = [
            'premium' => $response->PremiumAmount,
        ];
        return $data;
    }

    public function prepareData($attributes)
    {
        $data = [
            'SessionToken' => $attributes['sessionToken'],
            'TariffParameters' => [
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
                            "DocType" => $attributes['car']['docInspection']['documentType'],
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
            ],
        ];
        $this->setValue($data['TariffParameters']['Agreement']['Insurer'], 'MobilePhone', 'numberPhone', $attributes['subjects'][$attributes['policy']['insurantId']]['fields']['numberPhone']);
        $this->setValue($data['TariffParameters']['Agreement']['Insurer'], 'Email', 'email', $attributes['subjects'][$attributes['policy']['insurantId']]['fields']);
        $this->setValuesByArray($data['TariffParameters']['Agreement']['Vehicle'], [
            'NetWeight' => 'minWeight',
            'GrossWeigh' => 'maxWeight',
            'Seats' => 'seats',
        ], $attributes['car']);
        //SubjectList
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            $pSubject = [
                'SbjKey' => $subject['id'],
                '_' => [
                    "SbjType" => 0, // TODO: справочник
                    "SbjResident" => $this->transformBoolean($subject['fields']['isResident']),
                    'FullName' => $subject['fields']['lastName'] . ' ' . $subject['fields']['firstName'] .
                        (isset($subject['fields']['middleName']) ? ' ' . $subject['fields']['middleName'] : ''),
                    "Gender" => $subject['fields']['gender'], // TODO: справочник
                    "BirthDate" => $subject['fields']['birthdate'], // TODO: справочник
                    "CountryCode" => $subject['fields']['citizenship'], // TODO: справочник

                ],
            ];
            foreach ($attributes['fields']['addresses'] as $iAddress => $address) {
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
            foreach ($attributes['fields']['documents'] as $iDocument => $document) {
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
            $data['TariffParameters']['Agreement']['Insurer']['SubjectList']['Subject'][] = $pSubject;
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
            $data['TariffParameters']['Agreement']['Vehicle']['Document'][] = $pDocument;
        }
        $this->setValuesByArray($data['TariffParameters']['Agreement']['Vehicle']['DocInspection'], [
            "Serial" => 'documentSeries',
            "Number" => 'documentNumber',
            "DateEnd" => 'documentDateEmd',
        ], $attributes['car']['docInspection']);
        //DriverList
        if (!$attributes['policy']['isMultidrive']) {
            $data['TariffParameters']['Agreement']['DriverList'] = [];
            foreach ($attributes['drivers'] as $iDriver => $driver) {
                $pDriver = [
                    'SbjRef' => $driver['driver']['driverId'],
                    'DrvDateBeg' => $driver['driver']['drivingLicenseIssueDateOriginal'],
                ];
                $sDocument = $this->searchDocumentByType($attributes, $driver['driver']['driverId'], 'driverLicense'); // todo занчение из справочника
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
                $data['TariffParameters']['Agreement']['DriverList'][] = $pDriver;
            }
        }
        return $data;
    }

    protected function searchDocumentByType($attributes, $subjectId, $type)
    {
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            if ($subject['id'] != $subjectId) {
                continue;
            }
            foreach ($subject['fields']['documents'] as $iDocument => $document) {
                if ($document['document']['documentType'] == $type) { // TODO значение из справочника
                    return $document['document'];
                }
            }
        }
        return false;
    }

}
