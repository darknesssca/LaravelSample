<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillStatusServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Models\IntermediateData;
use App\Models\PolicyStatus;
use App\Models\RequestProcess;
use App\Services\Company\CompanyService;

abstract class IngosstrahService extends CompanyService
{
    const companyCode = 'ingosstrah';

    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiWsdlUrl = config('api_sk.ingosstrah.wsdlUrl');
        $this->apiUser = config('api_sk.ingosstrah.user');
        $this->apiPassword = config('api_sk.ingosstrah.password');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    // FIXME рефакторинг

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

    public function checkPaid($company, $process)
    {
        $dataProcess = $process->toArray();
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run($company, []);
        $attributes = [
            'BillISN' => $dataProcess['bill']['bill_id'],
            'SessionToken' => $loginData['sessionToken'],
        ];
        $serviceStatus = app(IngosstrahBillStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes, $process);
        if (isset($dataStatus['paid']) && $dataStatus['paid']) {
            $process->update([
                'paid' => true,
                'status_id' => PolicyStatus::where('code', 'paid')->first()->id, // todo справочник
            ]);
            $process->bill()->delete();
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
            isset($checkData['policySerial']) && $checkData['policySerial'] &&
            isset($checkData['policyNumber']) && $checkData['policyNumber'] &&
            isset($checkData['isEosago']) && $checkData['isEosago']
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
        $tokenData[$company->code]['status'] = 'done';
        $tokenData[$company->code]['billIsn'] = $billData['billIsn'];
        $tokenData[$company->code]['billUrl'] = $billLinkData['PayUrl'];
        $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
        $this->sendBillUrl($insurer['email'], $billLinkData['PayUrl']);
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
}
