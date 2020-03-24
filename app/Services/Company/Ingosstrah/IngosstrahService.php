<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahServiceContract;
use App\Http\Controllers\RestController;
use App\Models\IntermediateData;
use App\Models\RequestProcess;
use App\Services\Company\CompanyService;

class IngosstrahService extends CompanyService implements IngosstrahServiceContract
{
    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.ingosstrah.wsdlUrl');
        $this->apiUser = config('api_sk.ingosstrah.user');
        $this->apiPassword = config('api_sk.ingosstrah.password');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword)) {
            throw new \Exception('ingosstrah api is not configured');
        }
    }

    public function calculate($company, $attributes, $additionalData = [])
    {
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run($company, $attributes, $additionalData);
        //$loginData['sessionToken'] = 'LANAUg5a8KQVFA6LHpZAVACH9SSsHQOAXA2P';
        $attributes['sessionToken'] = $loginData['sessionToken'];
        $serviceCalculate = app(IngosstrahCalculateServiceContract::class);
        $data = $serviceCalculate->run($company, $attributes, $additionalData);
        $tokenData = IntermediateData::getData($attributes['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'sessionToken' => $loginData['sessionToken'],
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        return [
            'premium' => $data['premium'],
        ];
    }

    public function create($company, $attributes, $additionalData)
    {
        if (!(isset($additionalData['tokenData']) && $additionalData['tokenData'])) {
            throw new \Exception('no token data');
        }
        $attributes['sessionToken'] = $additionalData['tokenData']['sessionToken'];
        $sessionToken = $additionalData['tokenData']['sessionToken'];
        $serviceCreate = app(IngosstrahCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes, $additionalData);
        if (isset($dataCreate['tokenError'])) {
            $serviceLogin = app(IngosstrahLoginServiceContract::class);
            $loginData = $serviceLogin->run($company, $attributes, $additionalData);
            $sessionToken = $loginData['sessionToken'];
            $attributes['sessionToken'] = $additionalData['tokenData']['sessionToken'];
            $dataCreate = $serviceCreate->run($company, $attributes, $additionalData);
        }
        $tokenData = IntermediateData::getData($attributes['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'policyId' => $dataCreate['policyId'],
            'status' => 'processing',
            'sessionToken' => $sessionToken,
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        RequestProcess::create([
            'token' => $attributes['token'],
            'company' => $company->code,
            'state' => 50,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'status' => 'processing',
                'sessionToken' => $sessionToken,
            ]),
        ]);
        return [
            'status' => 'processing',
        ];
    }

    public function processing($company, $data, $additionalData)
    {
        if (!(isset($additionalData['tokenData']) && $additionalData['tokenData'])) {
            throw new \Exception('no token data');
        }
        if (!(isset($additionalData['tokenData']['status']) && $additionalData['tokenData']['status'])) {
            throw new \Exception('no status in token data');
        }
        switch ($additionalData['tokenData']['status']) {
            case 'processing':
                return [
                    'status' => 'processing',
                ];
            case 'hold':
                return [
                    'status' => 'hold',
                ];
            case 'done':
                return [
                    'status' => 'done',
                    'billUrl' => $additionalData['tokenData']['billUrl'],
                ];
            default:
                throw new \Exception('not valid status');
        }
    }

    public function checkHold($company, $data)
    {
        $data = $data->toArray();
        $data['data'] = json_decode($data['data'], true);
        $isNeedUpdateToken = false;
        $checkService = app(IngosstrahCheckCreateServiceContract::class);
        $checkData = $checkService->run($company, $data);
        if (isset($checkData['tokenError'])) {
            $serviceLogin = app(IngosstrahLoginServiceContract::class);
            $loginData = $serviceLogin->run($company, []);
            $sessionToken = $loginData['sessionToken'];
            $isNeedUpdateToken = true;
            $data['data']['sessionToken'] = $sessionToken;
            $checkData = $checkService->run($company, $data);
        }
        if (
            isset($checkData['response']->Agreement->IsOsago->IsEOsago) && ($checkData['response']->Agreement->IsOsago->IsEOsago == 'Y') &&
            isset($checkData['response']->Agreement->Policy->Serial) && $checkData['response']->Agreement->Policy->Serial &&
            isset($checkData['response']->Agreement->Policy->No) && $checkData['response']->Agreement->Policy->No
        ) {
            $this->createBill($company, $data);
        } else {
            $result = RequestProcess::updateCheckCount($data['token']);
            if ($result === false) {
                $this->dropCreate($company, $data['token'], 'no result by max check count');
            }
        }
        if ($isNeedUpdateToken) {
            $tokenData = IntermediateData::getData($data['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
            $tokenData[$company->code] = [
                'sessionToken' => $sessionToken,
            ];
            IntermediateData::where('token', $data['token'])->update([
                'data' => $tokenData,
            ]);
        }
    }

    protected function createBill($company, $data)
    {
        RequestProcess::where('token', $data['token'])->delete();
        $billService = app(IngosstrahBillServiceContract::class);
        $billData = $billService->run($company, $data);
        $data['data']['billIsn'] = $billData['billIsn'];
        $tokenFullData = IntermediateData::where('token', $data['token'])->first()->toArray();
        $tokenData = json_decode($tokenFullData['data'], true);
        $form = $tokenData['form'];
        $billLinkService = app(IngosstrahBillLinkServiceContract::class);
        $billLinkData = $billLinkService->run($company, $data, $form);
        $tokenData[$company->code] = [
            'status' => 'done',
            'billUrl' => $billLinkData['PayURL'],
        ];
        $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
        RestController::sendBillUrl($insurer['email'], $billLinkData['PayURL']);
        IntermediateData::where('token', $data['token'])->update([
            'data' => $tokenData,
        ]);
    }

    public function checkCreate($company, $data)
    {
        $data = $data->toArray();
        $data['data'] = json_decode($data['data'], true);
        $sessionToken = $data['data']['sessionToken'];
        $isNeedUpdateToken = false;
        $checkService = app(IngosstrahCheckCreateServiceContract::class);
        $checkData = $checkService->run($company, $data);
        if (isset($checkData['tokenError'])) {
            $serviceLogin = app(IngosstrahLoginServiceContract::class);
            $loginData = $serviceLogin->run($company, []);
            $sessionToken = $loginData['sessionToken'];
            $isNeedUpdateToken = true;
            $data['data']['sessionToken'] = $sessionToken;
            $checkData = $checkService->run($company, $data);
        }
        switch ($checkData['state']) {
            case 'аннулирован':
            case 'прекращен страхователем':
            case 'прекращен страховщиком':
            case 'выпущен':
                RequestProcess::where('token', $data['token'])->delete();
                $this->dropCreate($company, $data['token'], 'api return status Аннулирован');
                break;
            case 'заявление':
                $data['data']['policyIsn'] = $checkData['isn'];
                $eosagoService = app(IngosstrahEosagoServiceContract::class);
                $eosagoData = $eosagoService->run($company, $data);
                if (!$eosagoData['isEosago'] && $eosagoData['hold']) {
                    RequestProcess::where('token', $data['token'])->update([
                        'state' => 75,
                        'data' => json_encode([
                            'policyId' => $data['data']['policyId'],
                            'policyIsn' => $data['data']['policyIsn'],
                            'status' => 'hold',
                            'company' => $company->code,
                            'sessionToken' => $sessionToken,
                        ])
                    ]);
                    $tokenData = IntermediateData::getData($data['token']);
                    $tokenData[$company->code] = [
                        'sessionToken' => $sessionToken,
                        'policyIsn' => $data['data']['policyIsn'],
                        'status' => 'hold',
                    ];
                    IntermediateData::where('token', $data['token'])->update([
                        'data' => $tokenData,
                    ]);
                    return;
                }
                $this->createBill($company, $data);
                break;
            default: // все остальные статусы рассматриваем как WORKING
                $result = RequestProcess::updateCheckCount($data['token']);
                if ($result === false) {
                    $this->dropCreate($company, $data['token'], 'no result by max check count');
                } else {
                    if ($isNeedUpdateToken) {
                        $tokenData = IntermediateData::getData($data['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
                        $tokenData[$company->code] = [
                            'sessionToken' => $sessionToken,
                        ];
                        IntermediateData::where('token', $data['token'])->update([
                            'data' => $tokenData,
                        ]);
                    }
                }
                break;
        }

    }

    protected function dropCreate($company, $token, $error)
    {
        $tokenData = IntermediateData::getData($token); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'status' => 'error',
            'error' => true,
            'errors' => [
                [
                    'message' => $error
                ],
            ],
        ];
        IntermediateData::where('token', $token)->update([
            'data' => $tokenData,
        ]);
    }

    protected function transformBoolean($boolean)
    {
        return $boolean ? 'Y' : 'N';
    }

}
