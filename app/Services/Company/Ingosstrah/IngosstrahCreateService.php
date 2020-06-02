<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Traits\PrepareAddressesTrait;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;
use Spatie\ArrayToXml\ArrayToXml;

class IngosstrahCreateService extends IngosstrahService implements IngosstrahCreateServiceContract
{
    use TransformBooleanTrait, DateFormatTrait, PrepareAddressesTrait;

    protected $usageTargetService;
    protected $carModelService;
    protected $docTypeService;
    protected $genderService;
    protected $countryService;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService,
        UsageTargetServiceContract $usageTargetService,
        CarModelServiceContract $carModelService,
        DocTypeServiceContract $docTypeService,
        GenderServiceContract $genderService,
        CountryServiceContract $countryService
    )
    {
        $this->usageTargetService = $usageTargetService;
        $this->carModelService = $carModelService;
        $this->docTypeService = $docTypeService;
        $this->genderService = $genderService;
        $this->countryService = $countryService;
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($company, $attributes);

        $this->writeLog(
            $this->logPath,
            [
                'request' => [
                    'method' => 'Create',
                    'url' => $this->apiWsdlUrl,
                    'payload' => $data
                ]
            ]
        );

        $response = $this->requestBySoap($this->apiWsdlUrl, 'CreateAgreement', $data);

        $this->writeLog(
            $this->logPath,
            [
                'response' => [
                    'method' => 'Create',
                    'response' => $response
                ]
            ]
        );

        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
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
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->ResponseStatus->ErrorMessage) ?
                    $response['response']->ResponseStatus->ErrorMessage :
                    'нет данных об ошибке',
            ]);
        }
        $data = [
            'policyId' => $response['response']->ResponseData->AgrID,
        ];
        return $data;
    }

    protected function prepareData($company, $attributes)
    {

        $carModel = $this->carModelService->getCompanyModelByName(
            $attributes['car']['maker'],
            $attributes['car']['category'],
            $attributes['car']['model'],
            $company->id);
        $data = [
            'SessionToken' => $attributes['sessionToken'],
            'Agreement' => [
                "General" => [
                    "Product" => '753518300',
                    'DateBeg' => $this->dateTimeFromDate($attributes['policy']['beginDate']),
                    'DateEnd' => $attributes['policy']['endDate'],
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
                    'Model' => $carModel['model'] ? $carModel['model'] : $carModel['otherModel'],
                    'VIN' => $attributes['car']['vin'],
                    "Category" => $carModel['category'],
                    "Constructed" => $this->dateFromYear($attributes['car']['year']),
                    'EnginePowerHP' => $attributes['car']['enginePower'],
                    "Document" => [],
                    "DocInspection" => [
                        "DocType" => $this->docTypeService->getCompanyInspectionDocType(true, $company->id),
                    ],
                ],
                "Condition" => [
                    "Liability" => [
                        "RiskCtg" => $attributes['policy']['isMultidrive'] ? '28966316' : "28966116",
                        'UsageType' => '1381850903',
                        "UsageTarget" => [
                            $this->usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id) =>
                                $this->transformBooleanToChar(true),
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
        ];
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $this->setValuesByArray($data['Agreement']['Insurer'], [
            'MobilePhone' => 'phone',
            'Email' => 'email',
        ], $insurer);
        $this->setValuesByArray($data['Agreement']['Vehicle'], [
            'NetWeight' => 'minWeight',
            'GrossWeigh' => 'maxWeight',
            'Seats' => 'seats',
        ], $attributes['car']);
        //SubjectList
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            $pSubject = [
                '_attributes' => ['SbjKey' => $subject['id']],
                "SbjType" => "Ф",
                "SbjResident" => $this->transformBooleanToChar(
                    $this->countryService->getCountryById($subject['fields']['citizenship'])['alpha2'] == 'RU'
                ),
                'FullName' => $subject['fields']['lastName'] . ' ' . $subject['fields']['firstName'] .
                    (isset($subject['fields']['middleName']) ? ' ' . $subject['fields']['middleName'] : ''),
                "Gender" => $this->genderService->getCompanyGender($subject['fields']['gender'], $company->id),
                "BirthDate" => $subject['fields']['birthdate'],
                "CountryCode" => $this->countryService->getCountryById($subject['fields']['citizenship'])['code'],
            ];
            $regAddress = $this->searchAddressByType($subject['fields'], 'registration');
            if (isset($regAddress['streetKladr']) && $regAddress['streetKladr']) {
                $this->cutStreetKladr($regAddress['streetKladr']);
            }
            if (isset($regAddress['cityKladr']) && $regAddress['cityKladr']) {
                $this->cutCityKladr($regAddress['cityKladr']);
            }
            $pAddress = [
                "CountryCode" => $this->countryService->getCountryById($subject['fields']['citizenship'])['code']
            ];
            $this->setValuesByArray($pAddress, [
                'CityCode' => 'cityKladr',
                'StreetName' => 'street',
                'StreetCode' => 'streetKladr',
                'House' => 'building',
                "Flat" => 'flat',
            ], $regAddress);
            $pSubject['Address'] = $pAddress;
            $sDocument = $this->searchDocumentByType($subject['fields'], 'passport');
            if ($sDocument) {
                $pDocument = [
                    'DocType' => $this->docTypeService->getCompanyPassportDocType($sDocument['isRussian'], $company->id),
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
        $this->setValuesByArray($data['Agreement']['Vehicle'], [
            'RegNum' => 'regNumber',
        ], $attributes['car']);
        $data['Agreement']['Vehicle']['Document'] = [
            'DocType' => $this->docTypeService->getCompanyCarDocType($attributes['car']['document']['documentType'], $company->id),
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
                $sDocument = $this->searchDocumentByTypeAndId($attributes, $driver['driver']['driverId'], 'license');
                if ($sDocument) {
                    $pDriver['DriverLicense'] = [
                        'DocType' => $this->docTypeService->getCompanyLicenseDocType($sDocument['documentType'], $company->id),
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
