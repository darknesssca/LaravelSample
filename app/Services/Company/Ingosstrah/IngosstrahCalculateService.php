<?php


namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Traits\PrepareAddressesTrait;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;
use Spatie\ArrayToXml\ArrayToXml;

class IngosstrahCalculateService extends IngosstrahService implements IngosstrahCalculateServiceContract
{
    use TransformBooleanTrait, DateFormatTrait, PrepareAddressesTrait;

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $response = $this->requestBySoap($this->apiWsdlUrl, 'GetTariff', $data);
        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (!isset($response['response']->ResponseData->Tariff->PremiumAmount)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        $data = [
            'premium' => $response['response']->ResponseData->Tariff->PremiumAmount,
        ];
        return $data;
    }


    protected function prepareData($attributes)
    {
        $data = [
            'SessionToken' => $attributes['sessionToken'],
            'TariffParameters' => [
                'Agreement' => [
                    "General" => [
                        "Product" => '753518300', //todo из справочника, вероятно статика
                        'DateBeg' => $this->dateTimeFromDate($attributes['policy']['beginDate']),
                        'DateEnd' => $attributes['policy']['endDate'],
//                        "PrevAgrID" => "", //todo пролонгация
//                        "ParentISN" => "", //todo пролонгация
                        "Individual" => $this->transformBooleanToChar(false),
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
                        "Constructed" => $this->dateFromYear($attributes['car']['year']),
                        'EnginePowerHP' => $attributes['car']['enginePower'],
                        "Document" => [],
                        "DocInspection" => [
                            "DocType" => $attributes['car']['inspection']['documentType'],
                        ],
                    ],
                    "Condition" => [
                        "Liability" => [
                            "RiskCtg" => "28966116", // TODO из справочника
                            'UsageType' => '1381850903', // TODO из справочника
                            "UsageTarget" => [
                                $attributes['car']['vehicleUsage'] => $this->transformBooleanToChar(true), // TODO имя параметра из справочника
                            ],
                            "UseWithTrailer" => $this->transformBooleanToChar($attributes['car']['isUsedWithTrailer']),
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
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $this->setValuesByArray($data['TariffParameters']['Agreement']['Insurer'], [
            'MobilePhone' => 'phone',
            'Email' => 'email',
        ], $insurer);
        $this->setValuesByArray($data['TariffParameters']['Agreement']['Vehicle'], [
            'NetWeight' => 'minWeight',
            'GrossWeigh' => 'maxWeight',
            'Seats' => 'seats',
        ], $attributes['car']);
        //SubjectList
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            $pSubject = [
                '_attributes' => ['SbjKey' => $subject['id']],
                "SbjType" => 'Ф',
                "SbjResident" => $this->transformBooleanToChar($subject['fields']['isResident']),
                'FullName' => $subject['fields']['lastName'] . ' ' . $subject['fields']['firstName'] .
                    (isset($subject['fields']['middleName']) ? ' ' . $subject['fields']['middleName'] : ''),
                "Gender" => $subject['fields']['gender'], // TODO: справочник
                "BirthDate" => $subject['fields']['birthdate'],
                "CountryCode" => $subject['fields']['citizenship'], // TODO: справочник
            ];
            $regAddress = $this->searchAddressByType($subject['fields'], 'Registered'); // TODO: справочник
            if (isset($regAddress['StreetCode']) && $regAddress['StreetCode']) {
                $this->cutStreetKladr($regAddress['StreetCode']);
            }
            if (isset($regAddress['CityCode']) && $regAddress['CityCode']) {
                $this->cutCityKladr($regAddress['CityCode']);
            }
            $pAddress = [
                "CountryCode" => $subject['fields']['citizenship'], // TODO: справочник
            ];
            $this->setValuesByArray($pAddress, [
                'CityCode' => 'cityKladr',
                'StreetName' => 'street',
                'StreetCode' => 'streetKladr',
                'House' => 'building',
                "Flat" => 'flat',
            ], $regAddress);
            $pSubject['Address'] = $pAddress;
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
            $data['TariffParameters']['Agreement']['SubjectList']['Subject'][] = $pSubject;
        }
        //Vehicle
        $this->setValuesByArray($data['TariffParameters']['Agreement']['Vehicle'], [
            'RegNum' => 'regNumber',
        ], $attributes['car']);
        $data['TariffParameters']['Agreement']['Vehicle']['Document'] = [
            'DocType' => $attributes['car']['document']['documentType'],  // TODO: справочник
        ];
        $this->setValuesByArray($data['TariffParameters']['Agreement']['Vehicle']['Document'], [
            "Serial" => 'series',
            "Number" => 'number',
            "DocDate" => 'dateIssue',
        ], $attributes['car']['document']);
        $this->setValuesByArray($data['TariffParameters']['Agreement']['Vehicle']['DocInspection'], [
            "Serial" => 'series',
            "Number" => 'number',
            "DateEnd" => 'dateEnd',
        ], $attributes['car']['inspection']);
        //DriverList
        if (!$attributes['policy']['isMultidrive']) {
            $data['TariffParameters']['Agreement']['DriverList'] = [
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
                $data['TariffParameters']['Agreement']['DriverList']['Driver'][] = $pDriver;
            }
        }
        $xml = ArrayToXml::convert($data['TariffParameters'], 'TariffParameters');
        $xml = html_entity_decode($xml);
        $xml = str_replace('<?xml version="1.0"?>', '', $xml);

        $data['TariffParameters'] = new \SoapVar($xml, XSD_ANYXML);
        return $data;
    }

}
