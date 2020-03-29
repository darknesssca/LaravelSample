<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Company\Renessans\RenessansMasterServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\TokenException;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class RenessansMasterService extends RenessansService implements RenessansMasterServiceContract
{
    public function calculate($company, $attributes)
    {
        $this->pushForm($attributes);
        $serviceCalculate = app(RenessansCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes);
        $this->requestProcessRepository->create([
            'token' => $attributes['token'],
            'state' => 1,
            'company' => $company->code,
            'data' => json_encode($dataCalculate),
        ]);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculating',
        ];
        $this->intermediateDataRepository->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
    }

    public function create($company, $attributes)
    {
        $this->pushForm($attributes);
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        $attributes['calcId'] = $tokenData['calcId'];
        $createService = app(RenessansCreateServiceContract::class);
        $dataCreate = $createService->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code]['policyId'] = $dataCreate['policyId'];
        $tokenData[$company->code]['status'] = 'processing';
        $this->intermediateDataRepository->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        $this->requestProcessRepository->create([
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

    public function calculating($company, $attributes)
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

    public function processing($company, $attributes)
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
}
