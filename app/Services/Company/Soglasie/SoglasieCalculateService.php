<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use Illuminate\Support\Carbon;

class SoglasieCalculateService extends SoglasieService implements SoglasieCalculateServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.kbmWsdlUrl');
        parent::__construct();
    }

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendKbm($company, $attributes);
    }

    private function sendKbm($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $headers = $this->getHeaders();
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'Login', $data, $headers);
        dd($response);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
//        if (!isset($response->response->error) || ($response->response->ErrorList->ErrorInfo->Code != 3)) { // согласно приведенному примеру 3 является кодом успешного ответа
//            throw new \Exception('api not return error Code: '.
//                isset($response->response->ErrorList->ErrorInfo->Code) ? $response->response->ErrorList->ErrorInfo->Code : 'no code | message: '.
//                isset($response->response->ErrorList->ErrorInfo->Message) ? $response->response->ErrorList->ErrorInfo->Message : 'no message');
//        }
        if (!isset($response->response->scoringid) || !$response->response->scoringid) {
            throw new \Exception('api not return IdRequestCalc');
        }
        return [
            'scoringId' => $response->response->scoringid,
        ];
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => base64_encode($this->apiUser . "::" . $this->apiPassword),
            'Content-Type' => 'application/xml',
            'Accept' => 'application/xml',
        ];
    }

    public function prepareData($attributes)
    {
        $data = [
            'debug' => $this->apiIsTest,
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
                        'id' => 1329,
                        'val' => $attributes['policy']['isMultidrive'] ? 1 :  $attributes['serviceData']['kbmId'],
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
                        'val' => $attributes['car']['usageType'], // todo справочник
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
                foreach ($attributes['drivers'] as $driverInfo) {
                    if ($iDriver == $driverInfo['driver']['driverId']) {
                        $properties['experience'][] = $this->getYearsOld($driverInfo['driver']['drivingLicenseIssueDateOriginal']);
                        break;
                    }
                }
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
            'val' => $owner['sex'],
        ];
        $data['contract']['param'][] = [
            'id' => 4024,
            'val' => $owner['phone']['numberPhone'],
        ];
        foreach ($owner['addresses'] as $address) {
            if ($address['address']['addressType'] == 'registration_address') { // todo справочник
                $data['contract']['param'][] = [
                    'id' => 1122,
                    'val' => isset($address['address']['cityKladr']) ? $address['address']['cityKladr'] : $address['address']['populatedCenterKladr'],
                ];
                break;
            }
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
            'val' => $owner['sex'],
        ];
        return $data;
    }

    protected function getYearsOld($birthdate)
    {
        $date = Carbon::createFromFormat('Y-m-d', $birthdate);
        $now = date('Y');
        return (int)$now - (int)$date->format('Y');
    }

}
