<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahMasterServiceContract;
use App\Exceptions\MethodForbiddenException;
use App\Models\InsuranceCompany;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class IngosstrahMasterService extends IngosstrahService implements IngosstrahMasterServiceContract
{
    public function calculate($company, $attributes):array
    {
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run($company, $attributes);
        $this->pushForm($attributes);
        $attributes['sessionToken'] = $loginData['sessionToken'];
        $serviceCalculate = app(IngosstrahCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculated',
            'sessionToken' => $loginData['sessionToken'],
        ];
        $this->intermediateDataRepository->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        return [
            'premium' => $dataCalculate['premium'],
        ];
    }

    public function create($company, $attributes):array
    {
        $this->pushForm($attributes);
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        $newSessionToken = $tokenData['sessionToken'];
        $attributes['sessionToken'] = $newSessionToken;
        $serviceCreate = app(IngosstrahCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes);
        if (isset($dataCreate['tokenError'])) {
            $serviceLogin = app(IngosstrahLoginServiceContract::class);
            $loginData = $serviceLogin->run($company, $attributes);
            $newSessionToken = $loginData['sessionToken'];
            $attributes['sessionToken'] = $newSessionToken;
            $dataCreate = $serviceCreate->run($company, $attributes);
        }
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'policyId' => $dataCreate['policyId'],
            'status' => 'processing',
            'sessionToken' => $newSessionToken,
        ];
        $this->intermediateDataRepository->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        $this->requestProcessRepository->create([
            'token' => $attributes['token'],
            'company' => $company->code,
            'state' => 50,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'status' => 'processing',
                'sessionToken' => $newSessionToken,
            ]),
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
}
