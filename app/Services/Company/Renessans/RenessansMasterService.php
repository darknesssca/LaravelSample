<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCheckCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansMasterServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\MethodForbiddenException;
use App\Exceptions\TokenException;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class RenessansMasterService extends RenessansService implements RenessansMasterServiceContract
{
    public function calculate($company, $attributes):array
    {
        $this->pushForm($attributes);
        $serviceCalculate = app(RenessansCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes);
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
        $dataCreate = $createService->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code]['policyId'] = $dataCreate['policyId'];
        $tokenData[$company->code]['status'] = 'processing';
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        $this->requestProcessService->create([
            'token' => $attributes['token'],
            'state' => 50,
            'company' => $company->code,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'status' => 'processing',
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

    /**
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param InsuranceCompany $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function payment(InsuranceCompany $company, $attributes): void
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
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
                ];
            case 'error':
                throw new ApiRequestsException($tokenData['errorMessage']);
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
                return [
                    'status' => 'done',
                    'billUrl' => $tokenData['billUrl'],
                ];
            case 'hold':
                return [
                    'status' => 'hold',
                ];
            case 'error':
                throw new ApiRequestsException($tokenData['errorMessage']);
            default:
                throw new TokenException('Статус рассчета не валиден');
        }
    }

    public function preCalculating($company, $processData)
    {
        $serviceCalculate = app(RenessansCheckCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $processData);
        $processData['data']['premium'] = $dataCalculate['premium'];
        $attributes = [];
        $this->pushForm($attributes);
        $attributes['calcId'] = $processData['data']['calcId'];
        $attributes['CheckSegment'] = true;
        $serviceCreate = app(RenessansCreateServiceContract::class);
        $dataSegment = $serviceCreate->run($company, $attributes);
        $processData['data']['segmentPolicyId'] = $dataSegment['policyId'];
        $this->requestProcessService->update($processData['token'], [
            'state' => 5,
            'data' => json_encode($processData['data']),
            'checkCount' => 0,
        ]);
    }
}
