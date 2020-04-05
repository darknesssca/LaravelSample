<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Repositories\Services\CarCategoryServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;

class SoglasieCalculateService extends SoglasieService implements SoglasieCalculateServiceContract
{
    use TransformBooleanTrait, DateFormatTrait;

    protected $usageTargetService;
    protected $carModelService;
    protected $genderService;
    protected $countryService;
    protected $carCategoryService;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService,
        UsageTargetServiceContract $usageTargetService,
        CarModelServiceContract $carModelService,
        GenderServiceContract $genderService,
        CountryServiceContract $countryService,
        CarCategoryServiceContract $carCategoryService
    )
    {
        $this->usageTargetService = $usageTargetService;
        $this->carModelService = $carModelService;
        $this->genderService = $genderService;
        $this->countryService = $countryService;
        $this->carCategoryService = $carCategoryService;
        $this->apiWsdlUrl = config('api_sk.soglasie.calculateWsdlUrl');
        if (!($this->apiWsdlUrl)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($company, $attributes);
        $headers = $this->getHeaders();
        $auth = $this->getAuth();
        $response = $this->requestBySoap($this->apiWsdlUrl, 'CalcProduct', $data, $auth, $headers);
        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (
            !isset($response['response']->data->contract->result) ||
            !$response['response']->data->contract->result
        ) {
            $messages = [
                'API страховой компании не вернуло данных',
            ];
            if (isset($response['response']->response->ErrorList->ErrorInfo->Message)) {
                $messages[] = $response['response']->response->ErrorList->ErrorInfo->Message;
            }
            if (isset($response['response']->data->contract->error) && (gettype($response['response']->data->contract->error) == 'array')) {
                foreach ($response['response']->data->contract->error as $error) {
                    $errorType = isset($error->level) ? strtolower($error->level) : 'default';
                    if (($errorType == 'error') || ($errorType == 'warning')) {
                        $messages[] = $error->_;
                    }
                }
            } else {
                $messages[] = 'нет данных об ошибке';
            }
            throw new ApiRequestsException($messages);
        }
        return [
            'premium' => $response['response']->data->contract->result,
        ];
    }

    protected function prepareData($company, $attributes)
    {
        $carModel = $this->carModelService->getCompanyModelByName(
            $attributes['car']['maker'],
            $attributes['car']['category'],
            $attributes['car']['model'],
            $company->id);
        $data = [
            'subuser' => $this->apiSubUser,
            'product' => [
                'brief' => 'ОСАГО',
            ],
            'contract' => [
                'datebeg' => $attributes['policy']['beginDate'],
                'dateend' => $attributes['policy']['endDate'],
                'brief' => 'ОСАГО',
                'param' => [
                    [
                        'id' => 1162,
                        'val' => $attributes['car']['year'],
                    ],
                    [
                        'id' => 1128,
                        'val' => $this->transformBooleanToInteger($attributes['policy']['isMultidrive']),
                    ],
                    [
                        'id' => 1222,
                        'val' => $attributes['serviceData']['scoringId'],
                    ],
                    [
                        'id' => 1421,
                        'val' => $attributes['car']['vin'],
                    ],
                    [
                        'id' => 22,
                        'val' => $carModel['model'] ? $carModel['model'] : $carModel['otherModel'],
                    ],
                    [
                        'id' => 3,
                        'val' => $attributes['car']['enginePower'],
                    ],
                    [
                        'id' => 1130,
                        'val' => $this->transformBooleanToInteger(false), // заглушка
                    ],
                    [
                        'id' => 849,
                        'val' => $this->transformBooleanToInteger(false), // заглушка
                    ],
                    [
                        'id' => 1129,
                        'val' => 12,
                    ],
                    [
                        'id' => 1402,
                        'val' => $this->transformBooleanToInteger($attributes['car']['isUsedWithTrailer']),
                    ],

                    [
                        'id' => 29,
                        'val' => 8,
                    ],
                    [
                        'id' => 964,
                        'val' => $this->transformBooleanToInteger($attributes['car']['isUsedWithTrailer']),
                    ],
                    [
                        'id' => 32,
                        'val' => 1001,
                    ],
                    [
                        'id' => 846,
                        'val' => $this->usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id),
                    ],
                    [
                        'id' => 961,
                        'val' => 1001,
                    ],
                    [
                        'id' => 642,
                        'val' => $this->carCategoryService->getCompanyCategory($attributes['car']['category'], $attributes['car']['isUsedWithTrailer'], $company->code),
                    ],
                    [
                        'id' => 463,
                        'val' => $this->transformBooleanToInteger(false), // заглушка
                    ],
                    [
                        'id' => 43,
                        'val' => $this->transformBooleanToInteger(
                            $this->countryService->getCountryById($attributes['car']['countryOfRegistration'])['alpha2'] == 'RU'
                        ),
                    ],
                ],
            ],
        ];
        // пролонгация
        $prolongationPolicyNumber = $this->policyService->searchOldPolicyByPolicyNumber($company->id, $attributes);
        if ($prolongationPolicyNumber) {
            $serialNumber = explode(' ', $prolongationPolicyNumber);
            $data['contract']['param'][] = [
                'id' => 981,
                'val' => $serialNumber[1],
            ];
            $data['contract']['param'][] = [
                'id' => 722,
                'val' => $this->transformBooleanToInteger(true),
            ];
        } else {
            $data['contract']['param'][] = [
                'id' => 722,
                'val' => $this->transformBooleanToInteger(false),
            ];
        }
        //kbm
        $data['contract']['param'][] = [
            'id' => 1329,
            'val' => $attributes['serviceData']['kbmId'],
        ];
        //drivers
        if (!$attributes['policy']['isMultidrive']) {
            $drivers = $this->searchDrivers($attributes);
            $properties = [
                'driverLicense' => [],
                'yearsOld' => [],
                'experience' => [],
                'fio' => [],
            ];
            foreach ($drivers as $iDriver => $driver) {
                $driverLicense = $this->searchDocumentByType($driver, 'license');
                if ($driverLicense) {
                    $properties['driverLicense'][] = (isset($driverLicense['series']) ? $driverLicense['series'] : '') . $driverLicense['number'];
                }
                $properties['yearsOld'][] = $this->getYearsOld($driver['birthdate']);
                $properties['experience'][] = $this->getYearsOld($driver['dateBeginDrive']);
                $properties['fio'][] = $driver['lastName'] . ' ' .
                    $driver['firstName'] .
                    isset($driver['middleName']) ? ' ' . $driver['middleName'] : '';
            }
            $data['contract']['param'][] = [
                'id' => 2128,
                'val' => implode(';', $properties['driverLicense']),
            ];
            $data['contract']['param'][] = [
                'id' => 11,
                'val' => $properties['yearsOld'],
            ];
            $data['contract']['param'][] = [
                'id' => 31,
                'val' => $properties['experience'],
            ];
            $data['contract']['param'][] = [
                'id' => 3206,
                'val' => implode(';', $properties['fio']),
            ];
        }
        //car
        if (isset($attributes['car']['seats'])) {
            $data['contract']['param'][] = [
                'id' => 1121,
                'val' => $attributes['car']['seats'],
            ];
        }
        if (isset($attributes['car']['maxWeight'])) {
            $data['contract']['param'][] = [
                'id' => 963,
                'val' => $attributes['car']['maxWeight'],
            ];
        }
        //owner
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $data['contract']['param'][] = [
            'id' => 4743,
            'val' => $owner['firstName'],
        ];
        if (isset($owner['middleName'])) {
            $data['contract']['param'][] = [
                'id' => 4745,
                'val' => $owner['middleName'],
            ];
        }
        $data['contract']['param'][] = [
            'id' => 4744,
            'val' => $owner['lastName'],
        ];
        $data['contract']['param'][] = [
            'id' => 4763,
            'val' =>  $this->genderService->getCompanyGender($owner['gender'], $company->id),
        ];
        $data['contract']['param'][] = [
            'id' => 4024,
            'val' => $owner['phone'],
        ];
        $regAddress = $this->searchAddressByType($owner, 'registration');
        if ($regAddress) {
            $data['contract']['param'][] = [
                'id' => 1122,
                'val' => isset($address['address']['cityKladr']) ? $regAddress['cityKladr'] :
                    isset($regAddress['populatedCenterKladr']) ? $regAddress['populatedCenterKladr'] :
                    '',
            ];
        }
        //insurer
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $insurerPassport = $this->searchDocumentByType($insurer, 'passport');
        $data['contract']['param'][] = [
            'id' => 3157,
            'val' => (isset($insurerPassport['series']) ? $insurerPassport['series'] : '') . $insurerPassport['number'],
        ];
        $data['contract']['param'][] = [
            'id' => 2363,
            'val' => $insurer['birthdate'],
        ];
        $data['contract']['param'][] = [
            'id' => 2625,
            'val' => $insurer['firstName'],
        ];
        if (isset($insurer['middleName'])) {
            $data['contract']['param'][] = [
                'id' => 2626,
                'val' => $insurer['middleName'],
            ];
        }
        $data['contract']['param'][] = [
            'id' => 2624,
            'val' => $insurer['lastName'],
        ];
        $data['contract']['param'][] = [
            'id' => 4764,
            'val' =>  $this->genderService->getCompanyGender($owner['gender'], $company->id),
        ];
        $data = [
            'data' => $data,
        ];
        return $data;
    }

}
