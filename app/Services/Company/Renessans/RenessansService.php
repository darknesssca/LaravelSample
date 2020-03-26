<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Contracts\Company\Renessans\RenessansServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use App\Models\RequestProcess;
use App\Services\Company\CompanyService;

class RenessansService extends CompanyService implements RenessansServiceContract
{
    private $apiUrl;
    private $secretKey;

    public function __construct()
    {
        $this->companyCode = "renessans";
        $this->companyId = InsuranceCompany::where('code',$this->companyCode)->take(1)->get()[0]['id'];
        $this->apiUrl = config('api_sk.renessans.apiUrl');
        $this->secretKey = config('api_sk.renessans.apiKey');
        if (!($this->apiUrl && $this->secretKey)) {
            throw new \Exception('renessans api is not configured');
        }
    }

    public function calculate($company, $attributes, $additionalData)
    {
        $serviceCalculate = app(RenessansCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes, $additionalData);
        RequestProcess::create([
            'token' => $attributes['token'],
            'state' => 1,
            'company' => $company->code,
            'data' => json_encode($dataCalculate),
        ]);
        $tokenData = IntermediateData::getData($attributes['token']);
        $tokenData[$company->code] = [
            'status' => 'calculating',
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => json_encode($tokenData),
        ]);
    }

    public function calculating($company, $data, $additionalData)
    {
        if (!(isset($additionalData['tokenData']) && $additionalData['tokenData'])) {
            throw new \Exception('no token data');
        }
        if (!(isset($additionalData['tokenData']['status']) && $additionalData['tokenData']['status'])) {
            throw new \Exception('no status in token data');
        }
        switch ($additionalData['tokenData']['status']) {
            case 'calculating':
                return [
                    'status' => 'calculating',
                ];
            case 'calculated':
                return [
                    'status' => 'done',
                    'premium' => $additionalData['tokenData']['finalPremium'],
                ];
            case 'error':
                return [
                    'error' => true,
                    'errors' => [
                        [
                            'message' => $additionalData['tokenData']['errorMessage'],
                        ],
                    ],
                ];
            default:
                throw new \Exception('not valid status');
        }
    }

    public function create($company, $attributes, $additionalData)
    {
        if (!(isset($additionalData['tokenData']) && $additionalData['tokenData'])) {
            throw new \Exception('no token data');
        }
        $attributes['calcId'] = $additionalData['tokenData']['calcId'];
        $serviceCreate = app(RenessansCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes, $additionalData);
        $tokenData = IntermediateData::getData($attributes['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code]['policyId'] = $dataCreate['policyId'];
        $tokenData[$company->code]['status'] = 'processing';
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        RequestProcess::create([
            'token' => $attributes['token'],
            'state' => 50,
            'company' => $company->code,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'status' => 'processing',
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
            case 'done':
                return [
                    'status' => 'done',
                    'billUrl' => $additionalData['tokenData']['billUrl'],
                ];
            case 'hold':
                return [
                    'status' => 'hold',
                ];
            case 'error':
                return [
                    'error' => true,
                    'errors' => [
                        [
                            'message' => $additionalData['tokenData']['errorMessage'],
                        ],
                    ],
                ];
            default:
                throw new \Exception('not valid status');
        }
    }

    public function checkHold($company, $process)
    {
        $dataProcess = $process->toArray();
        $dataProcess['data'] = json_decode($dataProcess['data'], true);
        $attributes = [
            'policyId' => $dataProcess['data']['policyId']
        ];
        $serviceStatus = app(RenessansGetStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes, $process);
        if ($dataStatus['result']) {
            $tokenFullData = IntermediateData::where('token', $dataProcess['token'])->first()->toArray();
            $tokenData = json_decode($tokenFullData['data'], true);
            $form = $tokenData['form'];
            $serviceBill = app(RenessansBillLinkServiceContract::class);
            $dataBill = $serviceBill->run($company, $attributes, $process);
            $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
            RestController::sendBillUrl($insurer['email'], $dataBill['billUrl']);
            $tokenData[$company->code]['status'] = 'done';
            $tokenData[$company->code]['billUrl'] = $dataBill['billUrl'];
            IntermediateData::where('token', $dataProcess['token'])->update([
                'data' => json_encode($tokenData),
            ]);
            $process->delete();
        } else {
            $dataProcess['checkCount']++;
            if ($dataProcess['checkCount'] < config('api_sk.maxCheckCount')) {
                $process->update([
                    'checkCount' => $dataProcess['checkCount'],
                ]);
            } else {
                $tokenData = IntermediateData::getData($dataProcess['token']);
                $tokenData[$company->code]['status'] = 'error';
                $tokenData[$company->code]['errorMessage'] = 'Произошла ошибка, попробуйте позднее. Статус последней ошибки: '.$dataStatus['message'];
                IntermediateData::where('token', $dataProcess['token'])->update([
                    'data' => json_encode($tokenData),
                ]);
                $process->delete();
            }
        }
    }

    public function checkCreate($company, $process)
    {
        $dataProcess = $process->toArray();
        $dataProcess['data'] = json_decode($dataProcess['data'], true);
        $attributes = [
            'policyId' => $dataProcess['data']['policyId']
        ];
        $serviceCreate = app(RenessansCheckCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes, $process);
        if ($dataCreate['result']) {
            $serviceStatus = app(RenessansGetStatusServiceContract::class);
            $dataStatus = $serviceStatus->run($company, $attributes, $process);
            if ($dataStatus['result']) {
                $tokenFullData = IntermediateData::where('token', $dataProcess['token'])->first()->toArray();
                $tokenData = json_decode($tokenFullData['data'], true);
                $form = $tokenData['form'];
                $serviceBill = app(RenessansBillLinkServiceContract::class);
                $dataBill = $serviceBill->run($company, $attributes, $process);
                $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
                RestController::sendBillUrl($insurer['email'], $dataBill['billUrl']);
                $tokenData[$company->code]['status'] = 'done';
                $tokenData[$company->code]['billUrl'] = $dataBill['billUrl'];
                IntermediateData::where('token', $dataProcess['token'])->update([
                    'data' => json_encode($tokenData),
                ]);
                $process->delete();
            } else {
                $process->update([
                    'state' => 75,
                    'data' => json_encode($dataProcess['data']),
                    'checkCount' => 0,
                ]);
                $tokenData = IntermediateData::getData($dataProcess['token']);
                $tokenData[$company->code]['status'] = 'hold';
                IntermediateData::where('token', $dataProcess['token'])->update([
                    'data' => json_encode($tokenData),
                ]);
            }
        } elseif ($dataCreate['status'] == 'wait') {
            $dataProcess['checkCount']++;
            if ($dataProcess['checkCount'] < config('api_sk.maxCheckCount')) {
                $process->update([
                    'checkCount' => $dataProcess['checkCount'],
                ]);
            } else {
                $tokenData = IntermediateData::getData($dataProcess['token']);
                $tokenData[$company->code]['status'] = 'error';
                $tokenData[$company->code]['errorMessage'] = 'Произошла ошибка, попробуйте позднее. Статус последней ошибки: '.$dataCreate['message'];
                IntermediateData::where('token', $dataProcess['token'])->update([
                    'data' => json_encode($tokenData),
                ]);
                $process->delete();
            }
        } else {
            $tokenData = IntermediateData::getData($dataProcess['token']);
            $tokenData[$company->code]['status'] = 'error';
            $tokenData[$company->code]['errorMessage'] = 'Произошла ошибка, попробуйте позднее. Статус последней ошибки: '.$dataCreate['message'];
            IntermediateData::where('token', $dataProcess['token'])->update([
                'data' => json_encode($tokenData),
            ]);
            $process->delete();
        }


    }

    public function checkPreCalculate($company, $attributes, $process)
    {
        $dataProcess = $process->toArray();
        $dataProcess['data'] = json_decode($dataProcess['data'], true);
        $serviceCalculate = app(RenessansCheckCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $dataProcess['data'] , $process);
        if ($dataCalculate['result']) {
            $dataProcess['data']['premium'] = $dataCalculate['premium'];
            $attributes['calcId'] = $dataProcess['data']['calcId'];
            $attributes['CheckSegment'] = true;
            $serviceCreate = app(RenessansCreateServiceContract::class);
            $dataSegment = $serviceCreate->run($company, $attributes, $process);
            $dataProcess['data']['segmentPolicyId'] = $dataSegment['policyId'];
            $process->update([
                'state' => 5,
                'data' => json_encode($dataProcess['data']),
                'checkCount' => 0,
            ]);
        } else {
            $dataProcess['checkCount']++;
            if ($dataProcess['checkCount'] < config('api_sk.maxCheckCount')) {
                $process->update([
                    'checkCount' => $dataProcess['checkCount'],
                ]);
            } else {
                $process->delete();
                $tokenData = IntermediateData::getData($attributes['token']);
                $tokenData[$company->code]['status'] = 'error';
                $tokenData[$company->code]['errorMessage'] = 'Произошла ошибка, попробуйте позднее. Статус последней ошибки: '.$dataCalculate['message'];
                IntermediateData::where('token', $attributes['token'])->update([
                    'data' => json_encode($tokenData),
                ]);
            }
        }
    }

    public function checkSegment($company, $attributes, $process)
    {
        $dataProcess = $process->toArray();
        $dataProcess['data'] = json_decode($dataProcess['data'], true);
        $attributes['CheckSegment'] = true;
        $segmentAttributes = [
            'policyId' => $dataProcess['data']['segmentPolicyId']
        ];
        $serviceCreate = app(RenessansCheckCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $segmentAttributes, $process);
        if ($dataCreate['result']) {
            $dataProcess['data']['segment'] = true;
            $serviceCalculate = app(RenessansCalculateServiceContract::class);
            $dataCalculate = $serviceCalculate->run($company, $attributes, $process);
            $dataProcess['data']['finalCalcId'] = $dataCalculate['calcId'];
            $dataProcess['data']['finalPremium'] = $dataCalculate['premium'];
            $process->update([
                'state' => 10,
                'data' => json_encode($dataProcess['data']),
                'checkCount' => 0,
            ]);
        } elseif ($dataCreate['status'] == 'wait') {
            $dataProcess['checkCount']++;
            if ($dataProcess['checkCount'] < config('api_sk.maxCheckCount')) {
                $process->update([
                    'checkCount' => $dataProcess['checkCount'],
                ]);
            } else {
                $process->delete();
                $tokenData = IntermediateData::getData($attributes['token']);
                $tokenData[$company->code]['status'] = 'error';
                $tokenData[$company->code]['errorMessage'] = 'Произошла ошибка, попробуйте позднее. Статус последней ошибки: '.$dataCreate['message'];
                IntermediateData::where('token', $attributes['token'])->update([
                    'data' => json_encode($tokenData),
                ]);
            }
        } else {
            $process->delete();
            $tokenData = IntermediateData::getData($attributes['token']);
            $tokenData[$company->code]['status'] = 'error';
            $tokenData[$company->code]['errorMessage'] = 'Произошла ошибка, попробуйте позднее. Статус последней ошибки: '.$dataCreate['message'];
            IntermediateData::where('token', $attributes['token'])->update([
                'data' => json_encode($tokenData),
            ]);
        }
    }

    public function checkCalculate($company, $attributes, $process)
    {
        $dataProcess = $process->toArray();
        $dataProcess['data'] = json_decode($dataProcess['data'], true);
        $calculateAttributes = [
            'calcId' => $dataProcess['data']['finalCalcId'],
        ];
        $serviceCalculate = app(RenessansCheckCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $calculateAttributes, $process);
        if ($dataCalculate['result']) {
            $process->delete();
            $tokenData = IntermediateData::getData($attributes['token']);
            $tokenData[$company->code]['status'] = 'calculated';
            $tokenData[$company->code]['calcId'] = $dataProcess['data']['finalCalcId'];
            $tokenData[$company->code]['finalPremium'] = $dataCalculate['premium'];
            IntermediateData::where('token', $attributes['token'])->update([
                'data' => json_encode($tokenData),
            ]);
        } else {
            $dataProcess['checkCount']++;
            if ($dataProcess['checkCount'] < config('api_sk.maxCheckCount')) {
                $process->update([
                    'checkCount' => $dataProcess['checkCount'],
                ]);
            } else {
                $process->delete();
                $tokenData = IntermediateData::getData($attributes['token']);
                $tokenData[$company->code]['status'] = 'error';
                $tokenData[$company->code]['errorMessage'] = 'Произошла ошибка, попробуйте позднее. Статус последней ошибки: '.$dataCalculate['message'];
                IntermediateData::where('token', $attributes['token'])->update([
                    'data' => json_encode($tokenData),
                ]);
            }
        }
    }

    protected function setAuth(&$attributes)
    {
        $attributes['key'] = $this->secretKey;
    }

    protected function getUrl($data = [])
    {
        $url = (substr($this->apiUrl, -1) == '/' ? substr($this->apiUrl, 0, -1) : $this->apiUrl) .
            $this->apiPath;
        if ($data) {
            foreach ($data as $key => $value) {
                $url = str_replace('{{'.$key.'}}', $value, $url);
            }
        }
        return $url;
    }

    protected function transformBoolean($boolean)
    {
        return (bool)$boolean;
    }

    protected function transformBooleanToInteger($boolean)
    {
        return (int)$boolean;
    }
}
