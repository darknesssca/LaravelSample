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
use App\Exceptions\ApiRequestsException;
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
    )
    {
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
        $response = $this->requestBySoap($this->apiWsdlUrl, 'calcPartnerFQuote', $data);
        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (!isset($response['response']->OSAGOFQ->totalPremium)) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->Header->resultInfo->errorInfo->descr) ?
                    $response['response']->Header->resultInfo->errorInfo->descr :
                    'нет данных об ошибке',
            ]);
        }
        if (isset($response['response']->OSAGOFQ->isTerminalG) && $response['response']->OSAGOFQ->isTerminalG) {
            throw new ApiRequestsException('Выдача полиса запрещена страховой компанией');
        }
        $data = [
            'setNumber' => $response['response']->setNumber,
            'premium' => $response['response']->OSAGOFQ->totalPremium,
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
                    "gender" => $this->genderService->getCompanyGender($subject['fields']['gender'], $company->id),
                    "citizenship" => $this->countryService->getCountryById($subject['fields']['citizenship'])['alpha2'],
                    'document' => [],
                ],
            ];
            $this->setValuesByArray($pSubject['subjectDetails'], [
                'middleName' => 'middleName'
            ], $subject['fields']['middleName']);
            foreach ($subject['fields']['addresses'] as $iAddress => $address) {
                $pAddress = [
                    'addressType' => $this->addressTypeService->getCompanyAddressType($address['address']['addressType'], $company->code),
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
            foreach ($subject['fields']['documents'] as $document) {
                $pDocument = [
                    "documentType" => $this->docTypeService->getCompanyDocTypeByRelation(
                        $document['document']['documentType'],
                        $document['document']['isRussian'],
                        $company
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
            $pSubject['subjectDetails']['phone'] = [
                "isPrimary" => true,
                "typePhone" => 'mobile',
                "numberPhone" => $subject['fields']['phone'],
            ];
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
                    'isNoRegistrationNumber' => true,
                ],
                'sourceAcquisition' => $this->sourceAcquisitionService->getCompanySourceAcquisitions($attributes['car']['sourceAcquisition'], $company->id),
                'vehicleCost' => $attributes['car']['vehicleCost'],
                'vehicleUsage' => $this->usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id),
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
            $data['vehicleInfo']['vehicleDetails']['vehicleUseRegion'] = substr($insurerRegAddress['regionKladr'], 0, 2);
        }
        $data['vehicleInfo']['vehicleDetails']['vehicleDocument']['documentType'] = $this->docTypeService->getCompanyCarDocType($attributes['car']['document']['documentType'], $company->id);
        $this->setValuesByArray($data['vehicleInfo']['vehicleDetails']['vehicleDocument'], [
            'documentSeries' => 'series',
            'documentNumber' => 'number',
            'documentIssued' => 'dateIssue',
        ], $attributes['car']['document']);
        //OSAGOFQ
        $data['OSAGOFQ'] = [
            'effectiveDate' => $this->dateTimeZoneFromDate($attributes['policy']['beginDate']),
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
