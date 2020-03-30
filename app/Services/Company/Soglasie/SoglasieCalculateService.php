<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Traits\DateFormat;
use App\Traits\TransformBoolean;

class SoglasieCalculateService extends SoglasieService implements SoglasieCalculateServiceContract
{
    use TransformBoolean, DateFormat;

    public function __construct(
        IntermediateDataRepositoryContract $intermediateDataRepository,
        RequestProcessRepositoryContract $requestProcessRepository,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.calculateWsdlUrl');
        if (!($this->apiWsdlUrl)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataRepository, $requestProcessRepository, $policyRepository);
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
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

    protected function prepareData($attributes)
    {
        $data = [
            'subuser' => $this->apiSubUser,
            'product' => [
                'brief' => 'ОСАГО', // todo из справочника, возможно заменить на id
            ],
            'contract' => [
                'datebeg' => $attributes['policy']['beginDate'],
                'dateend' => $attributes['policy']['endDate'],
                'brief' => 'ОСАГО', // todo из справочника, возможно заменить на id
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
                        'val' => $attributes['car']['model'], // todo справочник
                    ],
                    [
                        'id' => 3,
                        'val' => $attributes['car']['enginePower'],
                    ],
                    [
                        'id' => 1130,
                        'val' => $this->transformBooleanToInteger(false), // todo заглушка
                    ],
                    [
                        'id' => 849,
                        'val' => $this->transformBooleanToInteger(false), // todo заглушка
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
                        'val' => 8, // todo справочник
                    ],
                    [
                        'id' => 964,
                        'val' => $this->transformBooleanToInteger($attributes['car']['isUsedWithTrailer']),
                    ],
                    [
                        'id' => 32,
                        'val' => 1001, // todo справочник
                    ],
                    [
                        'id' => 846,
                        'val' => $attributes['car']['vehicleUsage'], // todo справочник
                    ],
                    [
                        'id' => 961,
                        'val' => 1001, // todo справочник
                    ],
                    [
                        'id' => 642,
                        'val' => 2, // todo справочник
                    ],
                    [
                        'id' => 463,
                        'val' => $this->transformBooleanToInteger(false), // todo заглушка
                    ],
                    [
                        'id' => 43,
                        'val' => $this->transformBooleanToInteger($attributes['car']['countryOfRegistration'] == 'RU'), // todo справочник
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
                $driverLicense = $this->searchDocumentByType($driver, 'driverLicense'); // todo значение из справочника
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
            'val' => $owner['gender'],
        ];
        $data['contract']['param'][] = [
            'id' => 4024,
            'val' => $owner['phone']['numberPhone'],
        ];
        $regAddress = $this->searchAddressByType($owner, 'registration_address');
        if ($regAddress) {
            $data['contract']['param'][] = [
                'id' => 1122,
                'val' => isset($address['address']['cityKladr']) ? $regAddress['cityKladr'] : $regAddress['populatedCenterKladr'],
            ];
        }
        //insurer
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $insurerPassport = $this->searchDocumentByType($insurer, 'passport'); // todo справочник
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
            'val' => $owner['gender'],
        ];
        $data = [
            'data' => $data,
        ];
        return $data;
    }

}
