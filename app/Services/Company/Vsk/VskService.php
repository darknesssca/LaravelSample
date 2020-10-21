<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Repositories\Services\AddressTypeServiceContract;
use App\Contracts\Repositories\Services\CarCategoryServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Services\Company\CompanyService;
use Carbon\Carbon;
use GuzzleHttp\Client;

abstract class VskService extends CompanyService
{
    public const companyCode = 'vsk';

    /** @var Client $client */
    protected $client;

    protected $countryService;
    protected $docTypeService;
    protected $addressTypeService;
    protected $carCategoryService;
    protected $usageTargetService;
    protected $genderService;
    protected $insuranceCompanyService;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService,
        CountryServiceContract $countryService,
        DocTypeServiceContract $docTypeService,
        AddressTypeServiceContract $addressTypeService,
        CarCategoryServiceContract $carCategoryService,
        UsageTargetServiceContract $usageTargetService,
        GenderServiceContract $genderService,
        InsuranceCompanyServiceContract $insuranceCompanyService
    ) {

        $this->client = new Client(
            [
                'base_uri' => config('api_sk.vsk.apiUrl'),
                'cert' => dirname($_SERVER['DOCUMENT_ROOT']) . '/vsk_cert/pareks.pem',
                'ssl_key' => dirname($_SERVER['DOCUMENT_ROOT']) . '/vsk_cert/private.pem',
                'headers' => [
                    'Content-Type' => 'application/xml',
                    'ReplyTo ' => config('api_sk.vsk.replyTo')
                ]
            ]
        );

        $this->countryService = $countryService;
        $this->docTypeService = $docTypeService;
        $this->addressTypeService = $addressTypeService;
        $this->carCategoryService = $carCategoryService;
        $this->usageTargetService = $usageTargetService;
        $this->genderService = $genderService;
        $this->insuranceCompanyService = $insuranceCompanyService;

        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    protected function formatDateToIso(string $date, $start_day = true)
    {
        if ($date == '') {
            $time_object = Carbon::now();
        } else {
            $time_object = Carbon::createFromFormat('Y-m-d', $date);
        }

        $time_object->setTimezone('Europe/Moscow');

        if ($start_day) {
            $time_object->startOfDay();
        } else {
            $time_object->endOfDay();
        }

        return $time_object->toIso8601String();
    }

    protected function getOwnerArray($company, array $attributes): array
    {
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $full_name = sprintf('%s %s %s', $owner['lastName'], $owner['middleName'], $owner['lastName']);

        $data = [
            'model:objectType' => [
                'model:code' => 'CONTRACTOR'
            ],
            'model:name' => $full_name,
            'model:descr' => '',
            'model:docs' => $this->getDocs($company, $owner['documents']),
            'model:contractorType' => [
                'model:contractorTypeCode' => 'INDIVIDUAL'//Заглушка
            ],
            'model:isResident' => $this->countryService->getCountryById($owner['citizenship'])['alpha2'] == 'RU' ? 'true' : 'false',
            'model:contacts' => $this->getContacts($owner['phone'], $owner['email'], $full_name),
            'model:addresses' => $this->getAddresses($company, $owner['addresses']),
            'model:firstName' => $owner['firstName'],
            'model:surname' => $owner['lastName'],
            'model:secondName' => $owner['middleName'],
            'model:birthDate' => $owner['birthdate'],
            'model:gender' => $this->genderService->getCompanyGender($owner['gender'], $company->id),
            'model:fullName' => $full_name,
            'model:latName' => '',
        ];

        return $data;
    }

    protected function getVehicleArray($company, array $attributes): array
    {
        $category = $this->carCategoryService->getCategoryById($attributes['car']['category']);

        if (strtolower($category['code']) == 'b') {
            $computed_info['trailer'] = 'false';
            $computed_info['maxWeight'] = '0';
            $computed_info['unladenWeight'] = '0';
            $computed_info['passengers'] = '0';
        } elseif (strtolower($category['code']) == 'c') {
            $computed_info['trailer'] = $attributes['car']['isUsedWithTrailer'] == true ? 'true' : 'false';
            $computed_info['maxWeight'] = $attributes['car']['maxWeight'];
            $computed_info['unladenWeight'] = $attributes['car']['minWeight'];
        } elseif (strtolower($category['code']) == 'd') {
            $computed_info['trailer'] = $attributes['car']['isUsedWithTrailer'] == true ? 'true' : 'false';
            $computed_info['passengers'] = $attributes['car']['seats'];
        } else {
            $computed_info['trailer'] = $attributes['car']['isUsedWithTrailer'] == true ? 'true' : 'false';
            $computed_info['maxWeight'] = $attributes['car']['maxWeight'];
            $computed_info['unladenWeight'] = $attributes['car']['minWeight'];
            $computed_info['passengers'] = $attributes['car']['seats'];
        }

        $data = [
            'model:object' => [
                '_attributes' => [
                    'xsi:type' => 'model:VehicleXT'
                ],
                'model:objectType' => [
                    'model:code' => 'VEHICLE'
                ],
                'model:docs' => [
                    [
                        'model:objectDocType' => [
                            'model:objectDocTypeCode' => $this->docTypeService->getCompanyCarDocType(
                                $attributes['car']['document']['documentType'],
                                $company->id
                            ),
                        ],
                        'model:series' => $attributes['car']['document']['series'],
                        'model:number' => $attributes['car']['document']['number'],
                        'model:dateIssue' => $attributes['car']['document']['dateIssue'],
                    ],
                ],
                'model:model' => [
                    'model:mark' => [
                        'model:name' => $attributes['car']['maker_name']
                    ],
                    'model:type' => [
                        'model:vehicleTypeCode' => $category['name']
                    ],
                    'model:name' => $attributes['car']['model']
                ],
                'model:vin' => $attributes['car']['vin'],
                'model:bodyNumber' => '',
                'model:chassisNumber' => '',
                'model:licensePlate' => $attributes['car']['regNumber'],
                'model:purpose' => [
                    'model:vehiclePurposeCode' => $this->usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'],
                        $company->id)
                ],
                'model:country' => [
                    'model:countryCode' => $this->countryService->getCountryById($attributes['car']['countryOfRegistration'])['short_name']
                ],
                'model:owner' => $this->getOwnerArray($company, $attributes),
                'model:ownershipType' => [
                    'model:ownershipTypeCode' => 'INDIVIDUAL-OWNER'
                ],
                'model:trailer' => $computed_info['trailer'],
                'model:transit' => 'false',
                'model:lease' => 'false',
                'model:cost' => '0.00',
                'model:power' => $attributes['car']['enginePower'],
                'model:maxWeight' => $computed_info['maxWeight'],
                'model:unladenWeight' => $computed_info['unladenWeight'],
                'model:passengers' => $computed_info['passengers'],
                'model:usedSince' => $attributes['car']['year'] . '-01-01',
                'model:mileage' => '0',
                'model:engineNumber' => '',
                'model:keysCount' => '0',
                'model:yearIssue' => $attributes['car']['year'],
            ],
            'model:objectType' => [
                'model:code' => 'VEHICLE'
            ],
            'model:product' => [
                //todo Поменять осаго на динамику с каско
                'model:productCode' => 'OSAGO'
            ],
            'model:orderNo' => 1,
            'model:period1BeginDate' => $attributes['policy']['beginDate'],
            'model:period1EndDate' => $attributes['policy']['endDate'],
        ];


        if (!empty($attributes['car']['inspection']['number'])) {
            $data['model:object']['model:docs'][] = [
                'model:objectDocType' => [
                    'model:objectDocTypeCode' => 'DIAGNOSTIC_CARD'
                ],
                'model:series' => $attributes['car']['inspection']['series'],
                'model:number' => $attributes['car']['inspection']['number'],
                'model:dateIssue' => $attributes['car']['inspection']['dateIssue'],
            ];
        }

        return $data;
    }

    protected function getInsurerArray($company, array $attributes): array
    {
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $full_name = sprintf('%s %s %s', $insurer['lastName'], $insurer['middleName'], $insurer['lastName']);

        $data = [
            'model:contractor' => [
                '_attributes' => [
                    'xsi:type' => 'model:IndividualsXT'
                ],
                'model:objectType' => [
                    'model:code' => 'CONTRACTOR'
                ],
                'model:docs' => $this->getDocs($company, $insurer['documents']),
                'model:contractorType' => [
                    'model:contractorTypeCode' => 'INDIVIDUAL'//Заглушка
                ],
                'model:contacts' => $this->getContacts($insurer['phone'], $insurer['email'], $full_name),
                'model:addresses' => $this->getAddresses($company, $insurer['addresses']),
                'model:firstName' => $insurer['firstName'],
                'model:surname' => $insurer['lastName'],
                'model:secondName' => $insurer['middleName'],
                'model:birthDate' => $insurer['birthdate'],
                'model:gender' => $this->genderService->getCompanyGender($insurer['gender'], $company->id),
                'model:fullName' => $full_name,
                'model:snils' => '',
            ],
            'model:participantType' => [
                'model:participantTypeCode' => 'INSURANT'
            ]
        ];

        return $data;
    }

    protected function getDriversArray($company, array $attributes): array
    {
        $data = [];

        foreach ($attributes['drivers'] as $driver) {
            $driver_subject = $this->searchSubjectById($attributes, $driver['driver']['driverId']);
            $full_name = sprintf('%s %s %s', $driver_subject['lastName'], $driver_subject['middleName'], $driver_subject['lastName']);
            $data['model:object'][] = [
                '_attributes' => [
                    'xsi:type' => 'model:IndividualsXT'
                ],
                'model:objectType' => [
                    'model:code' => 'DRIVER'
                ],
                'model:docs' => $this->getDocs($company, $driver_subject['documents']),
                'model:contractorType' => [
                    'model:contractorTypeCode' => 'INDIVIDUAL'
                ],
                'model:firstName' => $driver_subject['firstName'],
                'model:surname' => $driver_subject['lastName'],
                'model:secondName' => $driver_subject['middleName'],
                'model:birthDate' => $driver_subject['birthdate'],
                'model:gender' => $this->genderService->getCompanyGender($driver_subject['gender'], $company->id),
                'model:fullName' => $full_name,
                'model:driveExperience' => $driver['driver']['drivingLicenseIssueDateOriginal']
            ];
        }

        return $data;
    }

    protected function getDocs($company, $docs)
    {
        $data = [];

        foreach ($docs as $doc) {
            $data[] = [
                'model:objectDocType' => [
                    'model:objectDocTypeCode' => $this->docTypeService->getCompanyDocTypeByRelation(
                        $doc['document']['documentType'],
                        $doc['document']['isRussian'],
                        $company->id
                    ),
                ],
                'model:series' => $doc['document']['series'],
                'model:number' => $doc['document']['number'],
                'model:dateIssue' => $doc['document']['dateIssue'],
            ];
        }

        return $data;
    }

    protected function getAddresses($company, $addresses)
    {
        $data = [];

        foreach ($addresses as $address) {
            $data[] = [
                'model:country' => [
                    'model:countryCode' => $this->countryService->getCountryById($address['address']['country'])['short_name']
                ],
                'model:addressType' => [
                    'model:addressTypeCode' => $this->addressTypeService->getCompanyAddressType($address['address']['addressType'],
                        $company->code),
                ],
                'model:region' => $address['address']['region'],
                'model:district' => $address['address']['district'],
                'model:city' => $address['address']['city'],
                'model:locality' => '',
                'model:street' => $address['address']['street'],
                'model:house' => $address['address']['building'],
                'model:building' => '',
                'model:flat' => $address['address']['flat'],
                'model:okato' => '',
                'model:addressStr' => '',
                'model:fias' => $address['address']['streetFias'],
            ];
        }

        return $data;
    }

    protected function getContacts($phone, $email, $full_name)
    {
        $data = [
            [
                'model:contactType' => [
                    'model:contactTypeCode' => 'EMAIL'
                ],
                'model:contact' => $email,
                'model:contactPerson' => $full_name
            ],

            [
                'model:contactType' => [
                    'model:contactTypeCode' => 'PHONE'
                ],
                'model:contact' => $phone,
                'model:contactPerson' => $full_name
            ]
        ];

        return $data;
    }

    protected function getPolicyArray($company, array $attributes): array
    {
        $computed_data['previousPolicyNumber'] = !empty($attributes['number']) ? str_replace(' ', '',
            $attributes['number']) : '';

        $data = [
            'policy:policy' => [
                'model:product' => [
                    'model:productCode' => 'OSAGO',
                ],
                'model:dateCreate' => $this->formatDateToIso(''),
                'model:dateStart' => $this->formatDateToIso($attributes['policy']['beginDate']),
                'model:dateEnd' => $this->formatDateToIso($attributes['policy']['endDate'], false),
                'model:dateIssue' => Carbon::now()->format('Y-m-d'),
                'model:dateCalc' => $this->formatDateToIso(''),
                'model:previousPolicyNumber' => $computed_data['previousPolicyNumber'],
                'model:policyObjects' => [
                    $this->getVehicleArray($company, $attributes),
                    $this->getDriversArray($company, $attributes),
                ],
                'model:participant' => $this->getInsurerArray($company, $attributes),
            ]
        ];

        return $data;
    }


    protected function sendRequest($url, $body) {
        $data = [];
        $url = '/cxf/rest/partners/api/v2/osago' . $url;

        $response = $this->client->post(
            $url,
            [
                'body' => $body,
            ]
        );

        try {
            $data['uniqueId'] = $response->getHeader('X-VSK-CorrelationId')[0];
        } catch (Exception $exception) {
            //ignore
        }

        return $data;
    }
}
