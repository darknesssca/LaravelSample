<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
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
            'data' => json_encode($dataCalculate),
        ]);
        $tokenData = IntermediateData::getData($attributes['token']);
        $tokenData[$company->code] = [
            'calcIds' => $dataCalculate,
            'status' => 'calculating',
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
    }

    public function checkPreCalculate($company, $attributes, $process)
    {
        $dataProcess = $process->toArray();
        $attributes['CheckSegment'] = true;
        $calculatedCount = 0;
        $isNeedUpdate = false;
        foreach ($dataProcess['data']['calculateValues'] as $key => $calcItem) {
            if ($calcItem['premium'] !== false) {
                $calculatedCount++;
                continue;
            }
            $serviceCalculate = app(RenessansCheckCalculateServiceContract::class);
            $dataCalculate = $serviceCalculate->run($company, $calcItem, $process);
            if ($dataCalculate['result']) {
                $isNeedUpdate = true;
                $dataProcess['data']['calculateValues'][$key]['premium'] = $dataCalculate['premium'];
                $attributes['calcId'] = $calcItem['calcId'];
                $serviceCreate = app(RenessansCreateServiceContract::class);
                $dataSegment = $serviceCreate->run($company, $attributes, $process);
                $dataProcess['data']['calculateValues']['segmentPolicyId'] = $dataSegment['policyId'];
                $calculatedCount++;
            }
        }
        $isNeedChangeState = false;
        if ($calculatedCount == count($dataProcess['data']['calculateValues'])) {
            $isNeedChangeState = true;
            $isNeedUpdate = true;
        }
        $dataProcess['checkCount']++;
        if ($dataProcess['checkCount'] < config('api_sk.maxCheckCount')) {
            if ($isNeedUpdate) {
                $process->update([
                    'state' => $isNeedChangeState ? 5 : 1,
                    'data' => json_encode($dataProcess['data']),
                    'checkCount' => $isNeedChangeState ? 0 : $dataProcess['checkCount'],
                ]);
            } else {
                $process->update([
                    'checkCount' => $dataProcess['checkCount'],
                ]);
            }
        } else {
            $calculatedCount = 0;
            foreach ($dataProcess['data']['calculateValues'] as $key => $value) {
                if ($value['segmentPolicyId'] && $value['premium']) {
                    $calculatedCount++;
                } else {
                    unset($dataProcess['data']['calculateValues'][$key]);
                }
            }
            if (!$calculatedCount) {
                $process->delete();
                $tokenData = IntermediateData::getData($attributes['token']);
                $tokenData[$company->code]['status'] = 'error';
                IntermediateData::where('token', $attributes['token'])->update([
                    'data' => $tokenData,
                ]);
            } else {
                $process->update([
                    'state' => 5,
                    'data' => json_encode($dataProcess['data']),
                    'checkCount' => 0,
                ]);
            }
        }
    }

    public function checkSegment($company, $attributes, $process)
    {
        $dataProcess = $process->toArray();
        $attributes['CheckSegment'] = true;
        $calculatedCount = 0;
        $isNeedUpdate = false;
        foreach ($dataProcess['data']['calculateValues'] as $key => $calcItem) {
            if ($calcItem['premium'] !== false) {
                $calculatedCount++;
                continue;
            }
            $serviceCalculate = app(RenessansCheckCalculateServiceContract::class);
            $dataCalculate = $serviceCalculate->run($company, $calcItem, $process);
            if ($dataCalculate['result']) {
                $isNeedUpdate = true;
                $dataProcess['data']['calculateValues'][$key]['premium'] = $dataCalculate['premium'];
                $attributes['calcId'] = $calcItem['calcId'];
                $serviceCreate = app(RenessansCreateServiceContract::class);
                $dataSegment = $serviceCreate->run($company, $attributes, $process);
                $dataProcess['data']['calculateValues']['segmentPolicyId'] = $dataSegment['policyId'];
                $calculatedCount++;
            }
        }
        $isNeedChangeState = false;
        if ($calculatedCount == count($dataProcess['data']['calculateValues'])) {
            $isNeedChangeState = true;
            $isNeedUpdate = true;
        }
        $dataProcess['checkCount']++;
        if ($dataProcess['checkCount'] < config('api_sk.maxCheckCount')) {
            if ($isNeedUpdate) {
                $process->update([
                    'state' => $isNeedChangeState ? 5 : 1,
                    'data' => json_encode($dataProcess['data']),
                    'checkCount' => $isNeedChangeState ? 0 : $dataProcess['checkCount'],
                ]);
            } else {
                $process->update([
                    'checkCount' => $dataProcess['checkCount'],
                ]);
            }
        } else {
            $calculatedCount = 0;
            foreach ($dataProcess['data']['calculateValues'] as $key => $value) {
                if ($value['segmentPolicyId'] && $value['premium']) {
                    $calculatedCount++;
                } else {
                    unset($dataProcess['data']['calculateValues'][$key]);
                }
            }
            if (!$calculatedCount) {
                $process->delete();
                $tokenData = IntermediateData::getData($attributes['token']);
                $tokenData[$company->code]['status'] = 'error';
                IntermediateData::where('token', $attributes['token'])->update([
                    'data' => $tokenData,
                ]);
            } else {
                $process->update([
                    'state' => 5,
                    'data' => json_encode($dataProcess['data']),
                    'checkCount' => 0,
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
