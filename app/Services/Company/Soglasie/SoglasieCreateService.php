<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Contracts\Repositories\Services\CarMarkServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Services\Repositories\AddressTypeService;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;

class SoglasieCreateService extends SoglasieService implements SoglasieCreateServiceContract
{
    use TransformBooleanTrait, DateFormatTrait;

    protected $usageTargetService;
    protected $carModelService;
    protected $docTypeService;
    protected $genderService;
    protected $countryService;
    protected $addressTypeService;
    protected $carMarkService;

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
        CarMarkServiceContract $carMarkService
    )
    {
        $this->usageTargetService = $usageTargetService;
        $this->carModelService = $carModelService;
        $this->docTypeService = $docTypeService;
        $this->genderService = $genderService;
        $this->countryService = $countryService;
        $this->addressTypeService = $addressTypeService;
        $this->carMarkService = $carMarkService;
        $this->apiRestUrl = config('api_sk.soglasie.createUrl');
        if (!$this->apiRestUrl) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($company, $attributes);
        $headers = $this->getHeaders();
        $url = $this->getUrl();
        $response = $this->postRequest($url, $data, $headers, false, false, true);
        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!isset($response['policyId']) || !$response['policyId']) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло номер созданного полиса',
                isset($response['error']) ? $response['error'] : 'нет данных об ошибке',
                isset($response['errorInfo']) ? $response['errorInfo'] : 'нет данных об ошибке'
            ]);
        }
        return [
            'policyId' => $response['policyId']
        ];
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiSubUser . ':' . $this->apiSubPassword),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function prepareData($company, $attributes)
    {

        $carModel = $this->carModelService->getCompanyModelByName(
            $attributes['car']['maker'],
            $attributes['car']['category'],
            $attributes['car']['model'],
            $company->id);
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $data = [
            'CodeInsurant' => '000',
            'BeginDate' => $this->dateTimeFromDate($attributes['policy']['beginDate']),
            'EndDate' => $this->dateTimeFromDate($attributes['policy']['endDate']),
            //'PrevPolicy' => '', //todo пролонгация
            'Period1Begin' => $attributes['policy']['beginDate'],
            'Period1End' => $attributes['policy']['endDate'],
            'IsTransCar' => false, // заглушка
            'IsInsureTrailer' => $this->transformAnyToBoolean($attributes['car']['isUsedWithTrailer']),
            'CarInfo' => [
                'VIN' => $attributes['car']['vin'],
                'MarkModelCarCode' => $carModel['model'] ? $carModel['model'] : $carModel['otherModel'],
                'MarkPTS' => $this->carMarkService->getCompanyMark($attributes['car']['maker'], $company->id),
                'ModelPTS' => $attributes['car']['model'],
                'YearIssue' => $attributes['car']['year'],
                'DocumentCar' => [],
                'TicketCar' => [
                    'TypeRSA' => $this->docTypeService->getCompanyInspectionDocType3(true, $company->id),
                    'Number' => $attributes['car']['inspection']['number'],
                    'Date' => $attributes['car']['inspection']['dateIssue'],
                ],
                'TicketCarYear' => $this->getYearFromDate($attributes['car']['inspection']['dateEnd']),
                'TicketCarMonth' => $this->getMonthFromDate($attributes['car']['inspection']['dateEnd']),
                'TicketDiagnosticDate' => $attributes['car']['inspection']['dateIssue'],
                'EngCap' => $attributes['car']['enginePower'],
                'GoalUse' => $this->usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id),
                'Rented' => $this->usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id) == 'Rent',
            ],
            'Insurer' => [
                'Phisical' => [
                    'Resident' => $this->countryService->getCountryById($insurer['citizenship'])['alpha2'] == 'RU',
                    'Surname' => $insurer['lastName'],
                    'Name' => $insurer['firstName'],
                    'BirthDate' => $insurer['birthdate'],
                    'Sex' => $this->genderService->getCompanyGender($insurer['gender'], $company->id),
                    'Email' => $insurer['email'],
                    'PhoneMobile' => $insurer['phone'],
                    'Documents' => [
                        'Document' => [],
                    ],
                    'Addresses' => [
                        'Address' => [],
                    ],
                ],
            ],
            'CarOwner' => [
                'Phisical' => [
                    'Resident' => $this->countryService->getCountryById($owner['citizenship'])['alpha2'] == 'RU',
                    'Surname' => $owner['lastName'],
                    'Name' => $owner['firstName'],
                    'BirthDate' => $owner['birthdate'],
                    'Sex' => $this->genderService->getCompanyGender($owner['gender'], $company->id),
                    'Email' => $owner['email'],
                    'PhoneMobile' => $owner['phone'],
                    'Documents' => [
                        'Document' => [],
                    ],
                    'Addresses' => [
                        'Address' => [],
                    ],
                ],
            ],
            'Drivers' => [
                'Driver' => [],
            ],
            'IKP1l' => ' ',
        ];
        $this->setValuesByArray($data['CarInfo'], [
            "MaxMass" => 'maxWeight',
            "PasQuant" => 'seats',
        ], $attributes['car']);
        $this->setValuesByArray($data['CarInfo']['TicketCar'], [
            "Serial" => 'documentSeries',
        ], $attributes['car']['inspection']);
        //car.documents
        $data['CarInfo']['DocumentCar'] = [
            'TypeRSA' => $this->docTypeService->getCompanyCarDocType3($attributes['car']['document']['documentType'], $company->id),
            'IsPrimary' => true,
        ];
        $this->setValuesByArray($data['CarInfo']['DocumentCar'], [
            "Serial" => 'series',
            "Number" => 'number',
            "Date" => 'dateIssue',
        ], $attributes['car']['document']);
        //owner
        $this->setValuesByArray($data['CarOwner']['Phisical'], [
            "Patronymic" => 'middleName',
        ], $owner);
        $data['CarOwner']['Phisical']['Documents']["Document"] = $this->prepareSubjectDocument($company,$owner);
        $data['CarOwner']['Phisical']['Addresses']["Address"] = $this->prepareSubjectAddress($company, $owner);
        //insurer
        $data['Insurer']['Phisical']['Documents']["Document"] = $this->prepareSubjectDocument($company,$insurer);
        $data['Insurer']['Phisical']['Addresses']["Address"] = $this->prepareSubjectAddress($company, $insurer);
        // drivers
        if (count($attributes['drivers'])) {
            $data['Drivers'] = [];
            foreach ($attributes['drivers'] as $driverRef) {
                $driver = $this->searchSubjectById($attributes, $driverRef['driver']['driverId']);
                $data['Drivers'][] = $this->prepareDriver($company, $driver, $driverRef);
            }
        } else {
            $data['Insurer']['Phisical']['Addresses'] = [];
            $driverRef = array_shift($attributes['drivers']);
            $driver = $this->searchSubjectById($attributes, $driverRef['driver']['driverId']);
            $data['Driver'] = $this->prepareDriver($company, $driver, $driverRef);
        }
        return $data;
    }

    protected function prepareSubjectDocument($company, $subject)
    {
        $documents = [];
        foreach ($subject['documents'] as $document) {
            $pDocument = [
                'TypeRSA' => $this->docTypeService->getCompanyDocTypeByRelation3($document['document']['documentType'], $document['document']['isRussian'], $company->id),
                'Number' => $document['document']['number'],
                'Date' => $document['document']['dateIssue'],
                'Exit' => $document['document']['issuedBy'],
                'IsPrimary' => $document['document']['documentType'] == 'passport' ? true : false,
            ];
            $this->setValuesByArray($pDocument, [
                "Serial" => 'series',
            ], $document['document']);
            $documents[] = $pDocument;
        }
        return $documents;
    }

    protected function prepareDriverDocument($company, $subject)
    {
        $documents = [];
        foreach ($subject['documents'] as $document) {
            $pDocument = [
                'TypeRSA' => $this->docTypeService->getCompanyDocTypeByRelation3($document['document']['documentType'], $document['document']['isRussian'], $company->id),
                'Number' => $document['document']['number'],
            ];
            $this->setValuesByArray($pDocument, [
                "Serial" => 'series',
            ], $document['document']);
            $documents[] = $pDocument;
        }
        return $documents;
    }

    protected function prepareSubjectAddress($company, $subject)
    {
        $addresses = [];
        foreach ($subject['addresses'] as $address) {
            $pAddress = [
                'Type' => $this->addressTypeService->getCompanyAddressType($address['address']['addressType'], $company->code),
                'Country' => $this->countryService->getCountryById($address['address']['country'])['code'],
            ];
            $this->setValuesByArray($pAddress, [
                'AddressCode' => 'streetKladr',
                'Street' => 'street',
                'Hous' => 'building',
                'Flat' => 'flat',
                'Index' => 'postCode',
            ], $address['address']);
            if (!$subject['isResident']) {
                $pAddress['AddressString'] = isset($address['address']['region']) ? $address['address']['region'] . ', ' : '' .
                isset($address['address']['district']) ? $address['address']['district'] . ', ' : '' .
                isset($address['address']['city']) ? $address['address']['city'] . ', ' : '' .
                isset($address['address']['populatedCenter']) ? $address['address']['populatedCenter'] . ', ' : '' .
                isset($address['address']['street']) ? $address['address']['street'] . ', ' : '' .
                isset($address['address']['building']) ? $address['address']['building'] . ', ' : '' .
                isset($address['address']['flat']) ? $address['address']['flat'] . ', ' : '';
            }
            $addresses[] = $pAddress;
        }
        return $addresses;
    }

    protected function prepareDriver($company, $driver, $driverRef)
    {
        $pDriver = [
            'Face' => [
                'Resident' => $this->countryService->getCountryById($driver['citizenship'])['alpha2'] == 'RU',
                'Surname' => $driver['lastName'],
                'Name' => $driver['firstName'],
                'BirthDate' => $driver['birthdate'],
                'Sex' => $this->genderService->getCompanyGender($driver['gender'], $company->id),
                'Documents' => [
                    'Document' => [],
                ],
                'Addresses' => [
                    'Address' => [],
                ],
            ],
            'DrivingExpDate' => $driverRef['driver']['drivingLicenseIssueDateOriginal'],
        ];
        $this->setValuesByArray($pDriver['Face'], [
            'Patronymic' => 'middleName',
        ], $driver);
        $pDriver['Face']['Documents']['Document'] = $this->prepareDriverDocument($company, $driver);
        $pDriver['Face']['Addresses']['Address'] = $this->prepareSubjectAddress($company, $driver);
        return $pDriver;
    }

}
