<?php


namespace App\Services\Company\Tinkoff;


use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Http\Controllers\SoapController;

class TinkoffCalculateService extends TinkoffService implements TinkoffCalculateServiceContract
{
    protected $apiMethods = [
        'sendCalculate' => 'calcPartnerFQuote',
    ];

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
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'calcPartnerFQuote', $data);
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['response']->OSAGOFQ->totalPremium)) {
            throw new \Exception('api not return premium');
        }
        $data = [
            'setNumber' => $response['response']->setNumber,
            'premium' => $response['response']->OSAGOFQ->totalPremium,
        ];
        return $data;
    }

    public function prepareData($attributes)
    {
        $data = [];
        $this->setHeader($data);
        //subjectInfo
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            $pSubject = [
                'subjectNumber' => $subject['id'],
                'subjectDetails' => [
                    "lastName" => $subject['fields']['lastName'],
                    "firstName" => $subject['fields']['firstName'],
                    "middleName" => $subject['fields']['middleName'],
                    "birthdate" => $subject['fields']['birthdate'],
                    "email" => $subject['fields']['email'],
                    "gender" => $subject['fields']['gender'], // TODO: справочник
                    "citizenship" => $subject['fields']['citizenship'], // TODO: справочник
                ],
            ];
            $this->setValue($pSubject['subjectDetails'], 'middleName', 'middleName', $subject['fields']['middleName']);
            foreach ($subject['fields']['addresses'] as $iAddress => $address) {
                $pAddress = [
                    'addressType' => $address['address']['addressType'],  // TODO: справочник
                    'country' => $address['address']['country'],  // TODO: справочник
                    'region' => $address['address']['region'],  // TODO: справочник
                ];
                $this->setValuesByArray($pAddress, [
                    "postCode" => 'postCode',
                    "KLADR1" => 'regionKladr',
                    "district" => 'district',
                    "KLADR2" => 'districtKladr',
                    "city" => 'city',
                    "KLADR3" => 'cityKladr',
                    "populatedCenter" => 'populatedCenter',
                    "KLADR4" => 'populatedCenterKladr',
                    "street" => 'street',
                    "KLADR5" => 'streetKladr',
                    "building" => 'building',
                    "KLADR6" => 'buildingKladr',
                    "flat" => 'flat',
                ], $address['address']);
                $pSubject['subjectDetails']['address'][] = $pAddress;
            }
            foreach ($subject['fields']['documents'] as $iDocument => $document) {
                $pDocument = [
                    'documentType' => $document['document']['documentType'],  // TODO: справочник
                ];
                $this->setValuesByArray($pDocument, [
                    "series" => 'series',
                    "number" => 'number',
                    "issuedBy" => 'issuedBy',
                    "dateIssue" => 'dateIssue',
                    "validTo" => 'validTo',
                ], $document['document']);
                $pSubject['subjectDetails']['document'][] = $pDocument;
            }
            $pSubject['subjectDetails']['phone'] = [
                "isPrimary" => true,
                "typePhone" => 'mobile',//$subject['fields']['phone']['typePhone'], // TODO: справочник
                "numberPhone" => $subject['fields']['phone']['numberPhone'],
            ];
            $data['subjectInfo'][] = $pSubject;
        }
        //vehicleInfo
        $data['vehicleInfo'] = [
            'vehicleDetails' => [
                'vehicleReferenceInfo' => [
                    'vehicleReferenceDetails' => [
                        'modelID' => $attributes['car']['model'], // TODO: справочник
                        'engPwrHP' => $attributes['car']['enginePower'],
                    ],
                ],
                'isChangeNumAgg' => false,
                'countryOfRegistration' => [
                    'isNoCountryOfRegistration' => false,
                    'countryOfRegistration' => $attributes['car']['countryOfRegistration']
                ],
                'chassis' => [
                    'isChassisMissing' => true,
                ],
                'isKeyless' => false, // TODO: понять будет ли поле или заглушка
                'isUsedWithTrailer' => $this->transformBoolean($attributes['car']['isUsedWithTrailer']),
                'kuzovNumber' => [
                    'isKuzovMissing' => true,
                ],
                'mileage' => $attributes['car']['mileage'],
                'numberOfOwners' => 1, // TODO: понять будет ли поле или заглушка
                'registrationNumber' => [
                    'isNoRegistrationNumber' => true,
                ],
                'sourceAcquisition' => $attributes['car']['sourceAcquisition'], // TODO: справочник
                'vehicleCost' => $attributes['car']['vehicleCost'],
                'vehicleUsage' => $attributes['car']['vehicleUsage'], // TODO: справочник
                'vehicleUseRegion' => $attributes['car']['vehicleUseRegion'], // TODO: справочник
                'VIN' => [
                    'isVINMissing' => false,
                    'isIrregularVIN' => $this->transformBoolean($attributes['car']['isIrregularVIN']),
                    'VIN' => $attributes['car']['vin'],
                ],
                'year' => $attributes['car']['year'],
                'isRightHandDrive' => false, // TODO: понять будет ли поле или заглушка
                'numberOfKeys' => [
                    'IsUnknownNumberOfKeys' => true,
                ],
            ],
        ];
        foreach ($attributes['car']['documents'] as $iDocument => $document) {
            $pDocument = [
                "documentType" => $document['document']['documentType'], // TODO: справочник
                "documentSeries" => $document['document']['documentSeries'],
                "documentNumber" => $document['document']['documentNumber'],
                "documentIssued" => $document['document']['documentIssued'],
            ];
            $data['vehicleInfo']['vehicleDetails']['vehicleDocument'][] = $pDocument;
        }
        //OSAGOFQ
        $data['OSAGOFQ'] = [
            'effectiveDate' => $this->formatDateTimeZone($attributes['policy']['beginDate']),
            'isEOSAGO' => true,
            'insurant' => [
                'subjectNumber' => $attributes['policy']['insurantId'],
            ],
            'carOwner' => [
                'subjectNumber' => $attributes['policy']['ownerId'],
            ],
            'driversList' => [
                'isMultidrive' => $this->transformBoolean($attributes['policy']['isMultidrive']),
            ],
        ];
        if (!$attributes['policy']['isMultidrive']) {
            $data['OSAGOFQ']['driversList']['namedList'] = [];
            foreach ($attributes['drivers'] as $iDriver => $driver) {
                $data['OSAGOFQ']['driversList']['namedList']['driver'][] = [
                    'subjectNumber' => $driver['driver']['driverId'],
                    'drivingLicenseIssueDateOriginal' => $driver['driver']['drivingLicenseIssueDateOriginal'],
                ];
            }
        } else {
            $data['OSAGOFQ']['driversList']['namedList'] = "";
        }
        return $data;
    }

}
