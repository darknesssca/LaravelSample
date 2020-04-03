<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\CarCategoryServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;

class SoglasieCalculateService extends SoglasieService implements SoglasieCalculateServiceContract
{
    use TransformBooleanTrait, DateFormatTrait;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.calculateWsdlUrl');
        if (!($this->apiWsdlUrl)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
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
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->response->ErrorList->ErrorInfo->Message) ?
                    $response['response']->response->ErrorList->ErrorInfo->Message :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'premium' => $response['response']->data->contract->result,
        ];
    }

    protected function prepareData($company, $attributes)
    {
        $usageTargetService = app(UsageTargetServiceContract::class);
        $carModelService = app(CarModelServiceContract::class);
        $countryService = app(CountryServiceContract::class);
        $categoryService = app(CarCategoryServiceContract::class);
        $genderService = app(GenderServiceContract::class);
        $carModel = $carModelService->getCompanyModelByName($attributes['car']['maker'],$attributes['car']['model'], $company->id);
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
//                    [
//                        'id' => 981,
//                        'val' => , // todo пролонгация
//                    ],
                    [
                        'id' => 1129,
                        'val' => 12,
                    ],
                    [
                        'id' => 1402,
                        'val' => $this->transformBooleanToInteger($attributes['car']['isUsedWithTrailer']),
                    ],
//                    [
//                        'id' => 722,
//                        'val' => , // todo пролонгация
//                    ],
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
                        'val' => $usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id),
                    ],
                    [
                        'id' => 961,
                        'val' => 1001,
                    ],
                    [
                        'id' => 642,
                        'val' => $categoryService->getCompanyCategory($attributes['car']['category'], $attributes['car']['isUsedWithTrailer'], $company->code),
                    ],
                    [
                        'id' => 463,
                        'val' => $this->transformBooleanToInteger(false), // заглушка
                    ],
                    [
                        'id' => 43,
                        'val' => $this->transformBooleanToInteger(
                            $countryService->getCountryById($attributes['car']['countryOfRegistration'])['alpha2'] == 'RU'
                        ),
                    ],
                ],
            ],
        ];
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
            'val' =>  $genderService->getCompanyGender($owner['gender'], $company->id),
        ];
        $data['contract']['param'][] = [
            'id' => 4024,
            'val' => $owner['phone'],
        ];
        $regAddress = $this->searchAddressByType($owner, 'registration');
        if ($regAddress) {
            $data['contract']['param'][] = [
                'id' => 1122,
                'val' => isset($address['address']['cityKladr']) ? $regAddress['cityKladr'] : $regAddress['populatedCenterKladr'],
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
            'val' =>  $genderService->getCompanyGender($owner['gender'], $company->id),
        ];
        $data = [
            'data' => $data,
        ];
        return $data;
    }

}
