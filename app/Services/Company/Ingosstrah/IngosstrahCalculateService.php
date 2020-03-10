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
        $data = $this->sendCalculate($company, $attributes);
        return $data;
    }

    private function sendCalculate($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $soapRequest = new SoapController();
        $soapRequest->configure($this->apiWsdlUrl);
        $response = $soapRequest->requestBySoap($company, 'calculate', $data);
        dd($response);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!$response['result']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        return $response['data'];
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
            foreach ($attributes['fields']['addresses'] as $iAddress => $address) {
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
                $pSubject['address:'.$iAddress] = $pAddress;
            }
            foreach ($attributes['fields']['documents'] as $iDocument => $document) {
                $pDocument = [
                    'documentType' => $document['address']['documentType'],  // TODO: справочник
                ];
                $this->setValuesByArray($pDocument, [
                    "series" => 'series',
                    "number" => 'number',
                    "issuedBy" => 'issuedBy',
                    "dateIssue" => 'dateIssue',
                    "validTo" => 'validTo',
                ], $document['document']);
                $pSubject['document:'.$iDocument] = $pDocument;
            }
            $pSubject['phone'] = [
                "isPrimary" => true,
                "typePhone" => $subject['fields']['phone']['typePhone'], // TODO: справочник
                "numberPhone" => $subject['fields']['phone']['typePhone'],
            ];
            $data['subjectInfo:'.$iSubject] = $pSubject;
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
                'isUsedWithTrailer' => $attributes['car']['isUsedWithTrailer'],
                'kuzovNumber' => [
                    'isKuzovMissing' => true,
                ],
                'mileage' => $attributes['car']['mileage'],
                'numberOfOwners' => 1, // TODO: понять будет ли поле или заглушка
                'sourceAcquisition' => $attributes['car']['sourceAcquisition'], // TODO: справочник
                'vehicleCost' => $attributes['car']['vehicleCost'],
                'vehicleUsage' => $attributes['car']['vehicleUsage'], // TODO: справочник
                'vehicleUseRegion' => $attributes['car']['vehicleUseRegion'], // TODO: справочник
                'VIN' => [
                    'isVINMissing' => false,
                    'isIrregularVIN' => $attributes['car']['isIrregularVIN'],
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
            $data['vehicleInfo']['vehicleDetails']['vehicleDocument:'.$iDocument] = $pDocument;
        }
        //OSAGOFQ
        $data['OSAGOFQ'] = [
            'effectiveDate' => $attributes['policy']['beginDate'],
            'isEOSAGO' => true,
            'insurant' => [
                'subjectNumber' => $attributes['policy']['insurantId'],
            ],
            'owner' => [
                'subjectNumber' => $attributes['policy']['ownerId'],
            ],
            'driversList' => [
                'isMultidrive' => $attributes['policy']['isMultidrive'],
            ],
        ];
        if (!$attributes['policy']['isMultidrive']) {
            $data['OSAGOFQ']['driversList']['namedList'] = [];
            foreach ($attributes['drivers'] as $iDriver => $driver) {
                $data['OSAGOFQ']['driversList']['namedList']['driver:'.$iDriver] = [
                    'subjectNumber' => $driver['driver']['driverId'],
                ];
            }
        } else {
            $data['OSAGOFQ']['driversList']['namedList'] = "";
        }
        return $data;
    }

}
