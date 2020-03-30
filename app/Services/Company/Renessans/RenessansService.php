<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Models\IntermediateData;
use App\Models\PolicyStatus;
use App\Services\Company\CompanyService;

abstract class RenessansService extends CompanyService
{
    const companyCode = 'renessans';

    protected $apiUrl;
    protected $secretKey;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository)
    {
        $this->apiUrl = config('api_sk.renessans.apiUrl');
        $this->secretKey = config('api_sk.renessans.apiKey');
        if (!($this->apiUrl && $this->secretKey)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
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

    // FIXME требуется рефакторинг

    public function checkPaid($company, $process)
    {
        $dataProcess = $process->toArray();
        $attributes = [
            'policyId' => (int)$dataProcess['number']
        ];
        $serviceStatus = app(RenessansGetStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes, $process);
        if ($dataStatus['result'] && $dataStatus['payStatus']) {
            $process->update([
                'paid' => true,
                'status_id' => PolicyStatus::where('code', 'paid')->first()->id, // todo справочник
                'number' => $dataStatus['policyNumber'],
            ]);
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
        if ($dataStatus['result'] && $dataStatus['createStatus']) {
            $tokenFullData = IntermediateData::where('token', $dataProcess['token'])->first()->toArray();
            $tokenData = json_decode($tokenFullData['data'], true);
            $form = $tokenData['form'];
            $serviceBill = app(RenessansBillLinkServiceContract::class);
            $dataBill = $serviceBill->run($company, $attributes, $process);
            $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
            $this->sendBillUrl($insurer['email'], $dataBill['billUrl']);
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
            if ($dataStatus['result'] && $dataStatus['createStatus']) {
                $tokenFullData = IntermediateData::where('token', $dataProcess['token'])->first()->toArray();
                $tokenData = json_decode($tokenFullData['data'], true);
                $form = $tokenData['form'];
                $serviceBill = app(RenessansBillLinkServiceContract::class);
                $dataBill = $serviceBill->run($company, $attributes, $process);
                $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
                $this->sendBillUrl($insurer['email'], $dataBill['billUrl']);
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
}
