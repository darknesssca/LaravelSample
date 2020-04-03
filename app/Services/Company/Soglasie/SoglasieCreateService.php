<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\CarMarkServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Services\Repositories\AddressTypeService;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;

class SoglasieCreateService extends SoglasieService implements SoglasieCreateServiceContract
{
    use TransformBooleanTrait, DateFormatTrait;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiRestUrl = config('api_sk.soglasie.createUrl');
        if (!$this->apiRestUrl) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($company, $attributes);
        $headers = $this->getHeaders();
        $url = $this->getUrl();
        $response = $this->postRequest($url, $data, $headers, false);
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
        $usageTargetService = app(UsageTargetServiceContract::class);
        $carMarkService = app(CarMarkServiceContract::class);
        $docTypeService = app(DocTypeServiceContract::class);
        $countryService = app(CountryServiceContract::class);
        $genderService = app(GenderServiceContract::class);
        $carModelService = app(CarModelServiceContract::class);
        $carModel = $carModelService->getCompanyModelByName($attributes['car']['maker'],$attributes['car']['model'], $company->id);
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
                'MarkPTS' => $carMarkService->getCompanyMark($attributes['car']['maker'], $company->id),
                'ModelPTS' => $attributes['car']['model'],
                'YearIssue' => $attributes['car']['year'],
                'DocumentCar' => [],
                'TicketCar' => [
                    'TypeRSA' => $docTypeService->getCompanyInspectionDocType3(true, $company->id),
                    'Number' => $attributes['car']['inspection']['number'],
                    'Date' => $attributes['car']['inspection']['dateIssue'],
                ],
                'TicketCarYear' => $this->getYearFromDate($attributes['car']['inspection']['dateEnd']),
                'TicketCarMonth' => $this->getMonthFromDate($attributes['car']['inspection']['dateEnd']),
                'TicketDiagnosticDate' => $attributes['car']['inspection']['dateIssue'],
                'EngCap' => $attributes['car']['enginePower'],
                'GoalUse' => $usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id),
                'Rented' => $usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id) == 'Rent',
            ],
            'Insurer' => [
                'Phisical' => [
                    'Resident' => $countryService->getCountryById($insurer['citizenship'])['alpha2'] == 'RU',
                    'Surname' => $insurer['lastName'],
                    'Name' => $insurer['firstName'],
                    'BirthDate' => $insurer['birthdate'],
                    'Sex' => $genderService->getCompanyGender($insurer['gender'], $company->id),
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
                    'Resident' => $countryService->getCountryById($owner['citizenship'])['alpha2'] == 'RU',
                    'Surname' => $owner['lastName'],
                    'Name' => $owner['firstName'],
                    'BirthDate' => $owner['birthdate'],
                    'Sex' => $genderService->getCompanyGender($owner['gender'], $company->id),
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
            'TypeRSA' => $docTypeService->getCompanyCarDocType3($attributes['car']['document']['documentType'], $company->id),
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
        $docTypeService = app(DocTypeServiceContract::class);
        $documents = [];
        foreach ($subject['documents'] as $document) {
            $pDocument = [
                'TypeRSA' => $docTypeService->getCompanyDocTypeByRelation3($document['document']['documentType'], $document['document']['isRussian'], $company->id),
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
        $docTypeService = app(DocTypeServiceContract::class);
        $documents = [];
        foreach ($subject['documents'] as $document) {
            $pDocument = [
                'TypeRSA' => $docTypeService->getCompanyDocTypeByRelation3($document['document']['documentType'], $document['document']['isRussian'], $company->id),
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
        $countryService = app(CountryServiceContract::class);
        $addressTypeService = app(AddressTypeService::class);
        $addresses = [];
        foreach ($subject['addresses'] as $address) {
            $pAddress = [
                'Type' => $addressTypeService->getCompanyAddressType($address['address']['addressType'], $company->code),
                'Country' => $countryService->getCountryById($address['address']['country'])['code'],
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
        $countryService = app(CountryServiceContract::class);
        $genderService = app(GenderServiceContract::class);
        $pDriver = [
            'Face' => [
                'Resident' => $countryService->getCountryById($driver['citizenship'])['alpha2'] == 'RU',
                'Surname' => $driver['lastName'],
                'Name' => $driver['firstName'],
                'BirthDate' => $driver['birthdate'],
                'Sex' => $genderService->getCompanyGender($driver['gender'], $company->id),
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
