<?php


namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffMasterServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\MethodForbiddenException;
use App\Exceptions\PolicyNotFoundException;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class TinkoffMasterService extends TinkoffService implements TinkoffMasterServiceContract
{
    public function calculate($company, $attributes):array
    {
        $this->pushForm($attributes);
        $calculateService = app(TinkoffCalculateServiceContract::class);
        $dataCalculate = $calculateService->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculated',
            'setNumber' => $dataCalculate['setNumber'],
            'premium' => $dataCalculate['premium'],
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        return [
            'premium' => $dataCalculate['premium'],
        ];
    }

    public function create($company, $attributes):array
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        $attributes['setNumber'] = $tokenData['setNumber'];
        $createService = app(TinkoffCreateServiceContract::class);
        $createData = $createService->run($company, $attributes);
        $billLinkService = app(TinkoffBillLinkServiceContract::class);
        $billLinkData = $billLinkService->run($company, $attributes);
        $this->pushForm($attributes);
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $this->sendBillUrl($insurer['email'], $billLinkData['billUrl']);
        $attributes['number'] = $createData['number'];
        $attributes['premium'] = $tokenData['premium'];
        $this->createPolicy($company, $attributes);
        $this->destroyToken($attributes['token']);
        $logger = app(LogMicroserviceContract::class);
        $logger->sendLog(
            'пользователь отправил запрос на создание заявки в компанию ' . $company->name,
            config('api_sk.logMicroserviceCode'),
            GlobalStorage::getUserId()
        );
        return [
            'status' => 'done',
            'billUrl' => $billLinkData['billUrl'],
        ];
    }

    public function payment($company, $attributes): void
    {
        if (
            isset($attributes['Body']['sendPaymentNotificationPartnerRequest']['paymentStatus']) &&
            $attributes['Body']['sendPaymentNotificationPartnerRequest']['paymentStatus'] &&
            (strtolower($attributes['Body']['sendPaymentNotificationPartnerRequest']['paymentStatus']) == 'confirm') &&
            isset($attributes['Body']['sendPaymentNotificationPartnerRequest']['policyNumber']) &&
            $attributes['Body']['sendPaymentNotificationPartnerRequest']['policyNumber']
        ) {
            $policy = $this->policyService->getNotPaidPolicyByPaymentNumber($attributes['Body']['sendPaymentNotificationPartnerRequest']['policyNumber']);
            if (!$policy) {
                throw new PolicyNotFoundException('Нет полиса с таким номером');
            }
            $this->policyService->update($policy->id, [
                'paid' => true,
            ]);
        } else {
            throw new PolicyNotFoundException('Не указан номер полиса или полис уже был отмечен как оплаченный');
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
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function creating($company, $attributes): void
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
    public function holding($company, $attributes): void
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }

    /**
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param $company
     * @param $attributes
     * @return array
     * @throws MethodForbiddenException
     */
    public function calculating($company, $attributes): array
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }

    /**
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param $company
     * @param $attributes
     * @return array
     * @throws MethodForbiddenException
     */
    public function processing($company, $attributes): array
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }

    /**
     * Данный метод необходим только для совместимости обработчиков компании
     *
     * @param $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function getPayment($company, $attributes):void
    {
        throw new MethodForbiddenException('Вызов метода запрещен');
    }
}
