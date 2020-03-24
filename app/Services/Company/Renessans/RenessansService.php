<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansServiceContract;
use App\Models\IntermediateData;
use App\Models\RequestProcess;
use App\Services\Company\CompanyService;

class RenessansService extends CompanyService implements RenessansServiceContract
{
    private $apiUrl;
    private $secretKey;

    public function __construct()
    {
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
                $tokenData[$company->code]['errorMessage'] = 'Произошла ошибка, попробуйте позднее. Статус последней ошибки: '.$dataCreate['message'];
                IntermediateData::where('token', $attributes['token'])->update([
                    'data' => json_encode($tokenData),
                ]);
            }
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
