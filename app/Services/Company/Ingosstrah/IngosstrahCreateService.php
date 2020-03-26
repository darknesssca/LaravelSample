<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\Ingosstrah\IngosstrahService;
use Carbon\Carbon;
use Spatie\ArrayToXml\ArrayToXml;

class IngosstrahCreateService extends IngosstrahService implements IngosstrahCreateServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function run($company, $attributes, $additionalFields = []): array
    {
        $data = $this->prepareData($attributes);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'CreateAgreement', $data);
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
        if (!isset($response['response']->ResponseData->AgrID)) {
            throw new \Exception('страховая компания вернула некорректный результат' . (isset($response['response']->ResponseStatus->ErrorMessage) ? ' | ' . $response['response']->ResponseStatus->ErrorMessage : ''));
        }
        $data = [
            'policyId' => $response['response']->ResponseData->AgrID,
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
//                    "IsEOsago" => $this->transformBoolean(true),
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
                    "Constructed" => Carbon::createFromFormat('Y', $attributes['car']['year'])->format('Y-m-d'),
                    'EnginePowerHP' => $attributes['car']['enginePower'],
                    "Document" => [],
                    "DocInspection" => [
                        "DocType" => 34, // TODO из справочника
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
                "SbjType" => "Ф", // TODO: справочник
                "SbjResident" => $this->transformBoolean($subject['fields']['isResident']),
                'FullName' => $subject['fields']['lastName'] . ' ' . $subject['fields']['firstName'] .
                    (isset($subject['fields']['middleName']) ? ' ' . $subject['fields']['middleName'] : ''),
                "Gender" => $subject['fields']['gender'], // TODO: справочник
                "BirthDate" => $subject['fields']['birthdate'],
                "CountryCode" => $subject['fields']['citizenship'], // TODO: справочник
            ];
            foreach ($subject['fields']['addresses'] as $iAddress => $address) {
                if ($address['address']['addressType'] != 'Registered') {// TODO: справочник
                    continue;
                }
                $pAddress = [
                    "CountryCode" => $subject['fields']['citizenship'], // TODO: справочник
                    'StreetName' => $address['address']['street'],
                    'House' => $address['address']['building'],
                ];
                if (isset($address['address']['StreetCode']) && $address['address']['StreetCode']) {
                    if (strlen($address['address']['StreetCode']) > 15) {
                        $address['address']['StreetCode'] = substr($address['address']['StreetCode'], 0, -2);
                    }
                }
                if (isset($address['address']['CityCode']) && $address['address']['CityCode']) {
                    if (strlen($address['address']['CityCode']) > 11) {
                        $address['address']['CityCode'] = substr($address['address']['CityCode'], 0, -2);
                    }
                }
                $this->setValuesByArray($pAddress, [
                    'StreetCode' => 'streetKladr',
                    'CityCode' => 'cityKladr',
                    "Flat" => 'flat',
                ], $address['address']);
                $pSubject['Address'] = $pAddress;
            }
            $sDocument = $this->searchDocumentByType($subject['fields'], '30363316'); // todo занчение из справочника
            if ($sDocument) {
                $pDocument = [
                    'DocType' => $sDocument['documentType'],  // TODO: справочник
                ];
                $this->setValuesByArray($pDocument, [
                    "Serial" => 'series',
                    "Number" => 'number',
                    "DocIssuedBy" => 'issuedBy',
                    "DocDate" => 'dateIssue',
                ], $sDocument);
                if (isset($pDocument['Serial'])) {
                    $pDocument['Serial'] = substr($pDocument['Serial'], 0, 2) . ' ' . substr($pDocument['Serial'], 2);
                }
                $pSubject['IdentityDocument'] = $pDocument;
            }
            $data['Agreement']['SubjectList']['Subject'][] = $pSubject;
        }
        //Vehicle
        $this->setValue($data['Agreement']['Vehicle'], 'RegNum', 'regNumber', $attributes['car']);
        $data['Agreement']['Vehicle']['Document'] = [
            'DocType' => $attributes['car']['document']['documentType'],  // TODO: справочник
        ];
        $this->setValuesByArray($data['Agreement']['Vehicle']['Document'], [
            "Serial" => 'series',
            "Number" => 'number',
            "DocDate" => 'dateIssue',
        ], $attributes['car']['document']);
        $this->setValuesByArray($data['Agreement']['Vehicle']['DocInspection'], [
            "Serial" => 'series',
            "Number" => 'number',
            "DateEnd" => 'dateEnd',
        ], $attributes['car']['inspection']);
        //DriverList
        if (!$attributes['policy']['isMultidrive']) {
            $data['Agreement']['DriverList'] = [
                'Driver' => [],
            ];
            foreach ($attributes['drivers'] as $iDriver => $driver) {
                $pDriver = [
                    'SbjRef' => $driver['driver']['driverId'],
                    'DrvDateBeg' => $driver['driver']['drivingLicenseIssueDateOriginal'],
                ];
                $sDocument = $this->searchDocumentByTypeAndId($attributes, $driver['driver']['driverId'], '765912000'); // todo занчение из справочника
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
                $data['Agreement']['DriverList']['Driver'][] = $pDriver;
            }
        }
        $xml = ArrayToXml::convert($data['Agreement'], 'Agreement');
        $xml = html_entity_decode($xml);
        $xml = str_replace('<?xml version="1.0"?>', '', $xml);

        $data['Agreement'] = new \SoapVar($xml, XSD_ANYXML);
        return $data;
    }

}
