<?php


namespace App\Services\Company\Tinkoff;


use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\SourceAcquisitionServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Services\Repositories\AddressTypeService;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;

class TinkoffCalculateService extends TinkoffService implements TinkoffCalculateServiceContract
{
    use TransformBooleanTrait, DateFormatTrait;

    protected $usageTargetService;
    protected $carModelService;
    protected $docTypeService;
    protected $genderService;
    protected $countryService;
    protected $addressTypeService;
    protected $sourceAcquisitionService;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService,
        UsageTargetServiceContract $usageTargetService,
        CarModelServiceContract $carModelService,
        DocTypeServiceContract $docTypeService,
        GenderServiceContract $genderService,
        CountryServiceContract $countryService,
        AddressTypeService $addressTypeService,
        SourceAcquisitionServiceContract $sourceAcquisitionService
    ) {
        $this->usageTargetService = $usageTargetService;
        $this->carModelService = $carModelService;
        $this->docTypeService = $docTypeService;
        $this->genderService = $genderService;
        $this->countryService = $countryService;
        $this->addressTypeService = $addressTypeService;
        $this->sourceAcquisitionService = $sourceAcquisitionService;
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($company, $attributes);

        $this->writeRequestLog([
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ]);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'calcPartnerFQuote', $data);

        $this->writeResponseLog($response);

        $data = [
            'setNumber' => $response['response']->setNumber ?? null,
            'quoteNumber' => $response['response']->OSAGOFQ->quoteNumber ?? null,
            'subjects' => $this->getSubjectIds($data, $response),
        ];

        if (isset($response['fault']) && $response['fault']) {
            $data['error'] = true;
            $data['errorMessage'] =
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : '';
            return $data;
        }
        if (!isset($response['response']->OSAGOFQ->totalPremium)) {
            $data['error'] = true;
            $data['errorMessage'] = [
                'API страховой компании не вернуло данных',
                isset($response['response']->Header->resultInfo->errorInfo->descr) ?
                    $response['response']->Header->resultInfo->errorInfo->descr :
                    'нет данных об ошибке'
            ];
            return $data;
        }
        if (isset($response['response']->OSAGOFQ->isTerminalG) && $response['response']->OSAGOFQ->isTerminalG) {
            $data['error'] = true;
            $data['errorMessage'] = 'Выдача полиса запрещена страховой компанией';
            return $data;
        }
        $data['error'] = false;
        $data['premium'] = $response['response']->OSAGOFQ->totalPremium;
        return $data;
    }

    protected function prepareData($company, $attributes)
    {
        $carModel = $this->carModelService->getCompanyModelByName(
            $attributes['car']['maker'],
            $attributes['car']['category'],
            $attributes['car']['model'],
            $company->id);
        $data = [];
        $this->setHeader($data);
        $this->setPrevSetNumber($attributes, $data);
        //subjectInfo
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            $pSubject = [
                'subjectNumber' => $subject['id'],
                'subjectDetails' => [
                    "lastName" => $subject['fields']['lastName'],
                    "firstName" => $subject['fields']['firstName'],
                    "middleName" => $subject['fields']['middleName'],
                    "birthdate" => $subject['fields']['birthdate'],
                    "gender" => $this->genderService->getCompanyGender($subject['fields']['gender'], $company->id),
                    "citizenship" => $this->countryService->getCountryById($subject['fields']['citizenship'])['alpha2'],
                    'document' => [],
                ],
            ];
            $this->setPrevSubjectId($attributes, $subject, $pSubject);
            $this->setValuesByArray($pSubject['subjectDetails'], [
                'email' => 'email'
            ], $subject['fields']);
            $this->setValuesByArray($pSubject['subjectDetails'], [
                'middleName' => 'middleName'
            ], $subject['fields']);
            $regAddress = $this->searchAddressByType($subject['fields'], 'registration');
            $homeAddress = $this->searchAddressByType($subject['fields'], 'home');
            if ($regAddress && !$homeAddress) {
                $homeAddress = [
                    'address' => $regAddress,
                ];
                $homeAddress['address']['addressType'] = 'home';
                $subject['fields']['addresses'][] = $homeAddress;
            }
            foreach ($subject['fields']['addresses'] as $iAddress => $address) {
                if (isset($address['address']['country']) && !empty($address['address']['country'])) {
                    $pAddress = [
                        'addressType' => $this->addressTypeService->getCompanyAddressType($address['address']['addressType'],
                            $company->code),
                        'country' => $this->countryService->getCountryById($address['address']['country'])['alpha2'],
                        //'region' => $address['address']['region'],
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
                    if (isset($address['address']['regionKladr'])) {
                        $pAddress['region'] = substr($address['address']['regionKladr'], 0, 2);
                    }
                    $pSubject['subjectDetails']['address'][] = $pAddress;
                }
            }
            foreach ($subject['fields']['documents'] as $document) {
                $pDocument = [
                    "documentType" => $this->docTypeService->getCompanyDocTypeByRelation(
                        $document['document']['documentType'],
                        $document['document']['isRussian'],
                        $company->id
                    ),
                ];
                $this->setValuesByArray($pDocument, [
                    "series" => 'series',
                    "number" => 'number',
                    "issuedBy" => 'issuedBy',
                    "dateIssue" => 'dateIssue',
                ], $document['document']);
                $pSubject['subjectDetails']['document'][] = $pDocument;
            }
            if (isset($subject['fields']['phone'])) {
                $pSubject['subjectDetails']['phone'] = [
                    "isPrimary" => true,
                    "typePhone" => 'mobile',
                    "numberPhone" => $subject['fields']['phone'],
                ];
            }

            $data['subjectInfo'][] = $pSubject;

        }
        //vehicleInfo
        $data['vehicleInfo'] = [
            'vehicleDetails' => [
                'vehicleReferenceInfo' => [
                    'vehicleReferenceDetails' => [
                        'modelID' => $carModel['model'] ? $carModel['model'] : $carModel['otherModel'],
                        'engPwrHP' => $attributes['car']['enginePower'],
                    ],
                ],
                'isChangeNumAgg' => false, // заглушка
                'countryOfRegistration' => [
                    'isNoCountryOfRegistration' => false,
                    'countryOfRegistration' => $this->countryService->getCountryById($attributes['car']['countryOfRegistration'])['alpha2'],
                ],
                'chassis' => [
                    'isChassisMissing' => true,
                ],
                'isKeyless' => false, // заглушка
                'isUsedWithTrailer' => $this->transformAnyToBoolean($attributes['car']['isUsedWithTrailer']),
                'kuzovNumber' => [
                    'isKuzovMissing' => true,
                ],
                'mileage' => $attributes['car']['mileage'],
                'numberOfOwners' => 1,
                'registrationNumber' => [
                    'isNoRegistrationNumber' => false,
                    'registrationNumber' => $attributes['car']['regNumber']
                ],
                'sourceAcquisition' => (int)$attributes['car']['sourceAcquisition'] > 0 ? $this->sourceAcquisitionService->getCompanySourceAcquisitions($attributes['car']['sourceAcquisition'],
                    $company->id) : 'PURCHASED_FROM_PERSON',
                'vehicleCost' => 0,
                'vehicleUsage' => $this->usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'],
                    $company->id),
                'VIN' => [
                    'isVINMissing' => false,
                    'isIrregularVIN' => $this->transformAnyToBoolean($attributes['car']['isIrregularVIN']),
                    'VIN' => $attributes['car']['vin'],
                ],
                'year' => $attributes['car']['year'],
                'isRightHandDrive' => false, // заглушка
                'numberOfKeys' => [
                    'IsUnknownNumberOfKeys' => true,
                ],
            ],
        ];
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $insurerRegAddress = $this->searchAddressByType($insurer, 'registration');
        if (isset($insurerRegAddress['regionKladr'])) {
            $data['vehicleInfo']['vehicleDetails']['vehicleUseRegion'] = substr($insurerRegAddress['regionKladr'], 0,
                2);
        }
        $data['vehicleInfo']['vehicleDetails']['vehicleDocument']['documentType'] = $this->docTypeService->getCompanyCarDocType($attributes['car']['document']['documentType'],
            $company->id);
        $this->setValuesByArray($data['vehicleInfo']['vehicleDetails']['vehicleDocument'], [
            'documentSeries' => 'series',
            'documentNumber' => 'number',
            'documentIssued' => 'dateIssue',
        ], $attributes['car']['document']);
        if (!empty($attributes['car']['inspection']['number']) && !empty($attributes['car']['inspection']['dateIssue']) && !empty($attributes['car']['inspection']['dateEnd'])) {
            $data['vehicleInfo']['vehicleDetails']['techInspectionInfo'] = [
                'sourceInfo' => 'CUSTOMER',
                'techDocumentType' => $this->docTypeService->getCompanyInspectionDocType(true, $company->id),
            ];
            $this->setValuesByArray($data['vehicleInfo']['vehicleDetails']['techInspectionInfo'], [
                'techInspSeries' => 'series',
                'techInspNumber' => 'number',
                'techInspIssuedDate' => 'dateIssue',
                'techInspExpirationDate' => 'dateEnd',
            ], $attributes['car']['inspection']);
        }

        //OSAGOFQ
        $data['OSAGOFQ'] = [
            'effectiveDate' => $this->dateTimeZoneFromDateStartOfDay($attributes['policy']['beginDate']),
            'expirationDate' => $this->dateTimeZoneFromDateEndOfDay($attributes['policy']['endDate']),
            'isEOSAGO' => true,
            'insurant' => [
                'subjectNumber' => $attributes['policy']['insurantId'],
            ],
            'carOwner' => [
                'subjectNumber' => $attributes['policy']['ownerId'],
            ],
            'driversList' => [
                'isMultidrive' => $this->transformAnyToBoolean($attributes['policy']['isMultidrive']),
            ],
        ];
        $this->setPrevQuoteNumber($attributes, $data['OSAGOFQ']);
        if (!$attributes['policy']['isMultidrive']) {
            $data['OSAGOFQ']['driversList']['namedList'] = [];
            foreach ($attributes['drivers'] as $iDriver => $driver) {
                $data['OSAGOFQ']['driversList']['namedList']['driver'][] = [
                    'subjectNumber' => $driver['driver']['driverId'],
                    'drivingLicenseIssueDateOriginal' => $driver['driver']['drivingLicenseIssueDateOriginal'],
                ];
            }
        }
        return $data;
    }

    private function getSubjectIds($data, $response)
    {
        if (!isset($response['response']->subjectInfo)) {
            return null;
        }
        $subjects = [];
        if (is_array($response['response']->subjectInfo)) {
            $subjects = $response['response']->subjectInfo;
        } else {
            $subjects[0] = $response['response']->subjectInfo; // 1 subject
        }
        return [
            'insurant' => $this->getResponseInsurant($data, $subjects),
            'owner' => $this->getResponseOwner($data, $subjects),
            'drivers' => $this->getResponseDrivers($data, $subjects),
        ];
    }

    private function getResponseInsurant($data, $subjects)
    {
        $sid = $data['OSAGOFQ']['insurant']['subjectNumber'];
        return $this->getResponseSubjectById($sid, $subjects);
    }

    private function getResponseOwner($data, $subjects)
    {
        $sid = $data['OSAGOFQ']['carOwner']['subjectNumber'];
        if ($sid == $data['OSAGOFQ']['insurant']['subjectNumber']) {
            return [
                'type' => 'linked',
                'link_key' => 'insurant',
            ];
        }
        return $this->getResponseSubjectById($sid, $subjects);
    }

    private function getResponseDrivers($data, $subjects)
    {
        if ($data['OSAGOFQ']['driversList']['isMultidrive']) {
            return [
                'type' => 'none',
            ];
        }
        $drivers = [];
        foreach ($data['OSAGOFQ']['driversList']['namedList']['driver'] as $driver) {
            if ($driver['subjectNumber'] == $data['OSAGOFQ']['insurant']['subjectNumber']) {
                $drivers[] = [
                    'type' => 'linked',
                    'link_key' => 'insurant',
                ];
                continue;
            } elseif ($driver['subjectNumber'] == $data['OSAGOFQ']['carOwner']['subjectNumber']) {
                $drivers[] = [
                    'type' => 'linked',
                    'link_key' => 'owner',
                ];
                continue;
            }
            $drivers[] = $this->getResponseSubjectById($driver['subjectNumber'], $subjects);
        }
        return $drivers;
    }

    private function getResponseSubjectById($sid, $subjects)
    {
        foreach ($subjects as $subject) {
            if (isset($subject->subjectNumber) && $subject->subjectNumber == $sid) {
                if (isset($subject->ID) && $subject->ID) {
                    return [
                        'type' => 'unique',
                        'subjectNumber' => $subject->subjectNumber,
                        'id' => $subject->ID,
                    ];
                }
            }
        }
        return null;
    }

    private function setPrevSetNumber($attributes, &$data)
    {
        if (
            $attributes['prevData'] &&
            isset($attributes['prevData']['setNumber']) && $attributes['prevData']['setNumber']
        ) {
            $data['setNumber'] = $attributes['prevData']['setNumber'];
        }
    }

    private function setPrevQuoteNumber($attributes, &$data)
    {
        if (
            $attributes['prevData'] &&
            isset($attributes['prevData']['quoteNumber']) && $attributes['prevData']['quoteNumber']
        ) {
            $data['quoteNumber'] = $attributes['prevData']['quoteNumber'];
        }
    }

    private function setPrevSubjectId(&$attributes, $subject, &$data)
    {
        if (!$attributes['prevData']) {
            return;
        }
        if ($subject['id'] == $attributes['policy']['insurantId']) {
            if (
                isset($attributes['prevData']['subjects']['insurant']['id']) && $attributes['prevData']['subjects']['insurant']['id']
            ) {
                $data['ID'] = $attributes['prevData']['subjects']['insurant']['id'];
            }
            return;
        } elseif ($subject['id'] == $attributes['policy']['ownerId']) {
            if (
                isset($attributes['prevData']['subjects']['owner']) && $attributes['prevData']['subjects']['owner']
            ) {
                if (
                    $attributes['prevData']['subjects']['owner']['type'] == 'unique' &&
                    isset($attributes['prevData']['subjects']['owner']['id']) && $attributes['prevData']['subjects']['owner']['id']
                ) {
                    $data['ID'] = $attributes['prevData']['subjects']['owner']['id'];
                }
            }
            return;
        } elseif (in_array($subject['id'], $this->getDriverSubjectNumbersList($attributes))) {
            if (
                isset($attributes['prevData']['subjects']['drivers']) && $attributes['prevData']['subjects']['drivers']
            ) {
                foreach ($attributes['prevData']['subjects']['drivers'] as $key => $driver) {
                    if (
                        $driver['type'] == 'unique' &&
                        isset($driver['id']) && $driver['id']
                    ) {
                        $data['ID'] = $driver['id'];
                        unset($attributes['prevData']['subjects']['drivers'][$key]);
                        return;
                    }
                }
                return;
            }
        }
    }

    private function getDriverSubjectNumbersList($attributes)
    {
        $list = [];
        if ($attributes['policy']['isMultidrive']) {
            return $list;
        }
        foreach ($attributes['drivers'] as $driver) {
            $list[] = $driver['driver']['driverId'];
        }
        return $list;
    }

}
