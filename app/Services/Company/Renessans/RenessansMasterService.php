<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansBillLinkServiceContract;
use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansGetPdfServiceContract;
use App\Contracts\Company\Renessans\RenessansGetStatusServiceContract;
use App\Contracts\Company\Renessans\RenessansMasterServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\MethodForbiddenException;
use App\Exceptions\TokenException;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class RenessansMasterService extends RenessansService implements RenessansMasterServiceContract
{
    public function calculate($company, $attributes):array
    {
        $this->pushForm($attributes);

        $serviceCalculate = app(RenessansCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes, $attributes['token']);
        $dataCalculate['user'] = GlobalStorage::getUser();
        $this->requestProcessService->create([
            'token' => $attributes['token'],
            'state' => 1,
            'company' => $company->code,
            'data' => json_encode($dataCalculate),
        ]);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculating',
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        return [
            'status' => 'calculating',
        ];
    }

    public function create($company, $attributes):array
    {
        $this->pushForm($attributes);
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        $attributes['calcId'] = $tokenData['calcId'];
        $createService = app(RenessansCreateServiceContract::class);
        $dataCreate = $createService->run($company, $attributes, $attributes['token']);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code]['policyId'] = $dataCreate['policyId'];
        $tokenData[$company->code]['status'] = 'processing';
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        $user = GlobalStorage::getUser();
        $this->requestProcessService->create([
            'token' => $attributes['token'],
            'state' => 50,
            'company' => $company->code,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'status' => 'processing',
                'user' => $user,
            ])
        ]);
        $logger = app(LogMicroserviceContract::class);
        $logger->sendLog(
            'пользователь отправил запрос на создание заявки в компанию ' . $company->name,
            config('api_sk.logMicroserviceCode'),
            GlobalStorage::getUserId()
        );
        return [
            'status' => 'processing',
        ];
    }

    public function calculating($company, $attributes):array
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        if (!(isset($tokenData['status']) && $tokenData['status'])) {
            throw new TokenException('Нет данных о статусе рассчета в токене');
        }
        switch ($tokenData['status']) {
            case 'calculating':
                return [
                    'status' => 'calculating',
                ];
            case 'calculated':
                return [
                    'status' => 'done',
                    'premium' => $tokenData['finalPremium'],
                    'reward' => $tokenData['reward'],
                    'kbm' => $tokenData['kbm']
                ];
            case 'error':
                throw new ApiRequestsException($tokenData['errorMessages']);
            default:
                throw new TokenException('Статус рассчета не валиден');
        }
    }

    public function processing($company, $attributes):array
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        if (!(isset($tokenData['status']) && $tokenData['status'])) {
            throw new TokenException('Нет данных о статусе рассчета в токене');
        }
        switch ($tokenData['status']) {
            case 'processing':
                return [
                    'status' => 'processing',
                ];
            case 'done':
                $this->destroyToken($attributes['token']);
                return [
                    'status' => 'done',
                    'billUrl' => $tokenData['billUrl'],
                ];
            case 'hold':
                return [
                    'status' => 'hold',
                ];
            case 'error':
                throw new ApiRequestsException($tokenData['errorMessages']);
            default:
                throw new TokenException('Статус рассчета не валиден');
        }
    }

    /**
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function payment($company, $attributes): void
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }

    public function preCalculating($company, $processData):void
    {
        GlobalStorage::setUser($processData['data']['user']);
        $serviceCalculate = app(RenessansCheckCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $processData, $processData['token']);
        $processData['data']['premium'] = $dataCalculate['premium'];
        $attributes = [
            'token' => $processData['token'],
        ];
        $this->pushForm($attributes);
        $attributes['calcId'] = $processData['data']['calcId'];
        $attributes['CheckSegment'] = true;
        $serviceCreate = app(RenessansCreateServiceContract::class);
        $dataSegment = $serviceCreate->run($company, $attributes, $processData['token']);
        $processData['data']['segmentPolicyId'] = $dataSegment['policyId'];
        $this->requestProcessService->update($processData['token'], $company->code, [
            'state' => 5,
            'data' => json_encode($processData['data']),
            'checkCount' => 0,
        ]);
    }

    public function segmenting($company, $processData):void
    {
        $segmentAttributes = [
            'policyId' => $processData['data']['segmentPolicyId']
        ];
        GlobalStorage::setUser($processData['data']['user']);
        $serviceCreate = app(RenessansCheckCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $segmentAttributes, $processData['token']);
        if ($dataCreate['result'] && $dataCreate['status'] != 'ok') {
            $this->requestProcessService->delete($processData['token'], $company->code);
            $tokenData = $this->getTokenData($processData['token'], true);
            $tokenData[$company->code]['status'] = 'error';
            $tokenData[$company->code]['errorMessages'] = $dataCreate['message'];
            $this->intermediateDataService->update($processData['token'], [
                'data' => json_encode($tokenData),
            ]);
            return;
        }
        $processData['data']['segment'] = true;
        $attributes = [
            'token' => $processData['token'],
        ];
        $this->pushForm($attributes);
        $serviceCalculate = app(RenessansCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes, $processData['token']);
        $processData['data']['finalCalcId'] = $dataCalculate['calcId'];
        $processData['data']['finalPremium'] = $dataCalculate['premium'];
        $this->requestProcessService->update($processData['token'], $company->code, [
            'state' => 10,
            'data' => json_encode($processData['data']),
            'checkCount' => 0,
        ]);
    }

    public function segmentCalculating($company, $processData):void
    {
        $calculateAttributes = [
            'data' => [
                'calcId' => $processData['data']['finalCalcId'],
            ]
        ];
        GlobalStorage::setUser($processData['data']['user']);
        $serviceCalculate = app(RenessansCheckCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $calculateAttributes, $processData['token']);
        $this->requestProcessService->delete($processData['token'], $company->code);
        $tokenData = $this->getTokenData($processData['token'], true);
        $tokenData[$company->code]['status'] = 'calculated';
        $tokenData[$company->code]['calcId'] = $processData['data']['finalCalcId'];
        $tokenData[$company->code]['finalPremium'] = $dataCalculate['premium'];
        $tokenData[$company->code]['kbm'] = $dataCalculate['kbm'];
        $tokenData[$company->code]['reward'] = $this->getReward($company->id, $tokenData['form'], $dataCalculate['premium']);
        $this->intermediateDataService->update($processData['token'], [
            'data' => json_encode($tokenData),
        ]);
    }

    public function creating($company, $processData):void
    {
        $attributes = [
            'policyId' => $processData['data']['policyId']
        ];
        GlobalStorage::setUser($processData['data']['user']);
        $serviceCreate = app(RenessansCheckCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes, $processData['token']);
        if ($dataCreate['result'] && $dataCreate['status'] != 'ok') {
            $this->requestProcessService->delete($processData['token'], $company->code);
            $tokenData = $this->getTokenData($processData['token'], true);
            $tokenData[$company->code]['status'] = 'error';
            $tokenData[$company->code]['errorMessages'] = $dataCreate['message'];
            $this->intermediateDataService->update($processData['token'], [
                'data' => json_encode($tokenData),
            ]);
            return;
        }
        $serviceStatus = app(RenessansGetStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes, $processData['token']);
        if (!($dataStatus['result'] && $dataStatus['createStatus'])) {
            if ($dataStatus['status'] == 'error') {
                $this->requestProcessService->delete($processData['token'], $company->code);
                $tokenData = $this->getTokenData($processData['token'], true);
                $tokenData[$company->code]['status'] = 'error';
                $tokenData[$company->code]['errorMessages'] = $dataStatus['message'];
                $this->intermediateDataService->update($processData['token'], [
                    'data' => json_encode($tokenData),
                ]);
                return;
            }
            $attributes['token'] = $processData['token'];
            $this->pushForm($attributes);
            $attributes['number'] = $attributes['policyId'];
            $tokenData = $this->getTokenDataByCompany($processData['token'], $company->code);
            $attributes['premium'] = $tokenData['finalPremium'];
            GlobalStorage::setUser($processData['data']['user']);
            $this->createPolicy($company, $attributes); // if this move to hold we create policy for lk
            $this->requestProcessService->update($processData['token'], $company->code, [
                'state' => 75,
                'data' => json_encode($processData['data']),
                'checkCount' => 0,
            ]);
            $tokenData = $this->getTokenData($processData['token'], true);
            $tokenData[$company->code]['status'] = 'hold';
            $this->intermediateDataService->update($processData['token'], [
                'data' => json_encode($tokenData),
            ]);
            return;
        }
        $serviceBill = app(RenessansBillLinkServiceContract::class);
        $dataBill = $serviceBill->run($company, $attributes, $processData['token']);
        $attributes['token'] = $processData['token'];
        $this->pushForm($attributes);
        $attributes['number'] = $attributes['policyId'];
        $tokenData = $this->getTokenDataByCompany($processData['token'], $company->code);
        $attributes['premium'] = $tokenData['finalPremium'];
        GlobalStorage::setUser($processData['data']['user']);
        $this->createPolicy($company, $attributes);
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $this->sendBillUrl($insurer['email'], $dataBill['billUrl']);
        $this->requestProcessService->delete($processData['token'], $company->code);
        $tokenData = $this->getTokenData($processData['token'], true);
        $tokenData[$company->code]['status'] = 'done';
        $tokenData[$company->code]['billUrl'] = $dataBill['billUrl'];
        $this->intermediateDataService->update($processData['token'], [
            'data' => json_encode($tokenData),
        ]);
    }

    public function holding($company, $processData):void
    {
        $attributes = [
            'policyId' => $processData['data']['policyId']
        ];
        GlobalStorage::setUser($processData['data']['user']);
        $serviceStatus = app(RenessansGetStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes, $processData['token']);
        if (!($dataStatus['result'] && $dataStatus['createStatus'])) {
            if ($dataStatus['status'] == 'error') {
                $this->requestProcessService->delete($processData['token'], $company->code);
                $tokenData = $this->getTokenData($processData['token'], true);
                $tokenData[$company->code]['status'] = 'error';
                $tokenData[$company->code]['errorMessages'] = $dataStatus['message'];
                $this->intermediateDataService->update($processData['token'], [
                    'data' => json_encode($tokenData),
                ]);
                return;
            }
            throw new ApiRequestsException( // завершаем обработку эксепшеном, чтобы правильно отработать checkCount
                    'API страховой компании не вернуло ответ: ' .
                    isset($dataStatus['message']) ? $dataStatus['message'] : 'нет данных об ошибке'
            );
        }
        $serviceBill = app(RenessansBillLinkServiceContract::class);
        $dataBill = $serviceBill->run($company, $attributes);
        $attributes['token'] = $processData['token'];
        $this->pushForm($attributes);
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $this->sendBillUrl($insurer['email'], $dataBill['billUrl']);
        $this->requestProcessService->delete($processData['token'], $company->code);
        $this->destroyToken($attributes['token']);
    }

    public function getPayment($company, $processData): void
    {
        $attributes = [
            'policyId' => (int)$processData['number']
        ];
        $serviceStatus = app(RenessansGetStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes);
        if ($dataStatus['result'] && $dataStatus['payStatus'] && $dataStatus['policyNumber']) {
            $serviceGetPdf = app(RenessansGetPdfServiceContract::class);
            $serviceGetPdf->run($company, $attributes);
            $this->policyService->update($processData['id'], [
                'paid' => true,
                'number' => $dataStatus['policyNumber'],
            ]);
        }
    }
}
