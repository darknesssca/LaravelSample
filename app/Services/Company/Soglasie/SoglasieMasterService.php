<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Contracts\Company\Soglasie\SoglasieMasterServiceContract;
use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Exceptions\MethodForbiddenException;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class SoglasieMasterService extends SoglasieService implements SoglasieMasterServiceContract
{
    public function calculate($company, $attributes):array
    {
        $this->pushForm($attributes);
        if (!$attributes['policy']['isMultidrive']) {
            $serviceKbm = app(SoglasieKbmServiceContract::class);
            $dataKbm = $serviceKbm->run($company, $attributes);
        } else {
            $dataKbm = [
                'kbmId' => 1,
            ];
        }
        $serviceScoring = app(SoglasieScoringServiceContract::class);
        $dataScoring = $serviceScoring->run($company, $attributes);
        $attributes['serviceData'] = [
            'kbmId' => $dataKbm['kbmId'],
            'scoringId' => $dataScoring['scoringId'],
        ];
        $serviceCalculate = app(SoglasieCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculated',
            'scoringId' => $dataScoring['scoringId'],
            'kbmId' => $dataKbm['kbmId'],
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        return [
            'status' => 'calculated',
            'premium' => $dataCalculate['premium'],
        ];
    }

    public function create($company, $attributes):array
    {
        $this->pushForm($attributes);
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        $attributes['serviceData'] = [
            'kbmId' => $tokenData['kbmId'],
            'scoringId' => $tokenData['scoringId'],
        ];
        $serviceCreate = app(SoglasieCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'policyId' => $dataCreate['policyId'],
            'packageId' => $dataCreate['packageId'],
            'status' => 'processing',
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        $this->requestProcessService->create([
            'token' => $attributes['token'],
            'state' => 50,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'packageId' => $dataCreate['packageId'],
                'status' => 'processing',
                'company' => $company->code,
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
     * @param $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function payment($company, $attributes): void
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }

    /**
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function preCalculating($company, $attributes):void
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }

    /**
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function segmenting($company, $attributes): void
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }

    /**
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function segmentCalculating($company, $attributes): void
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }

    /**
     * @inheritDoc
     */
    public function creating($company, $attributes): void
    {
        // TODO: Implement creating() method.
    }

    /**
     * @inheritDoc
     */
    public function holding($company, $attributes): void
    {
        // TODO: Implement holding() method.
    }

    /**
     * @inheritDoc
     */
    public function calculating($company, $attributes): array
    {
        // TODO: Implement calculating() method.
    }
}
