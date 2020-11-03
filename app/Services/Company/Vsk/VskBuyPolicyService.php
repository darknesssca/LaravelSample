<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskBuyPolicyServiceContract;
use App\Exceptions\PolicyNotFoundException;
use App\Exceptions\TokenException;
use App\Models\InsuranceCompany;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Benfin\Log\Facades\Log;
use Exception;
use Spatie\ArrayToXml\ArrayToXml;

class VskBuyPolicyService extends VskService implements VskBuyPolicyServiceContract
{

    /**
     * Метод подготавливает данные и отправляет их в СК
     * Каждый метод выполняет один конкретный запрос
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     */
    public function run(InsuranceCompany $company, $attributes): array
    {
        $data = [];
        $xml = $this->prepareXml($company, $attributes)->prettify()->toXml();

        $tag = sprintf('%sRequest | %s', $this->getName(__CLASS__), $attributes['token']);
        Log::daily(
            $xml,
            self::companyCode,
            $tag
        );

        $data = $this->sendRequest('/Policy/BuyPolicy', $xml, $attributes['token']);

        return $data;
    }

    private function prepareXml($company, $attributes)
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code, true);

        $fields = [
            'common:messageId' => 'BuyPolicy.' . $attributes['token'],
            'common:bpId' => $tokenData['bpId'],
            'common:sessionId' => $tokenData['sessionId'],
            'policy:policyNumber' => $tokenData['policyNumber'],
            'policy:returnUrl' => config('api_sk.billSuccessUrl'),
            'policy:failUrl' => config('api_sk.billFailUrl'),
            'policy:amount' => $this->RubToCop($tokenData['finalPremium']),
        ];

        $this->writeDatabaseLog(
            $attributes['token'],
            $fields,
            false,
            config('api_sk.logMicroserviceCode'),
            static::companyCode,
            $this->getName(__CLASS__)
        );

        return new ArrayToXml($fields, [
            'rootElementName' => 'policy:buyPolicyRequest',
            '_attributes' => [
                'xmlns:common' => 'http://www.vsk.ru/schema/partners/common',
                'xmlns:policy' => 'http://www.vsk.ru/schema/partners/policy',
            ]
        ], true, null, '1.0', [
            'encoding' => 'UTF-8',
            'standalone' => true
        ]);
    }

    /**
     * Метод обработки колбеков от ВСК
     * Для каждого сервиса свой метод обработки колбека
     *
     * @param InsuranceCompany $company - объект компании
     * @param array $token_data - информация о токене (метод и сам токен)
     * @param array $parsed_response - ответ в виде массива
     * @return array
     * @throws TokenException
     * @throws PolicyNotFoundException
     * @throws Exception
     */
    public function processCallback(InsuranceCompany $company, array $token_data, array $parsed_response): array
    {
        $tokenData = $this->getTokenData($token_data['token'], true);
        $callbackNumber = 1;

        foreach ($parsed_response as $tag) {
            if ($tag['tag'] == 'PAY:ORDERID') {
                $callbackNumber = 1;
                $tokenData[self::companyCode]['orderId'] = $tag['value'];
            }
            if ($tag['tag'] == 'PAY:FORMURL') {
                $callbackNumber = 1;
                $tokenData[self::companyCode]['formUrl'] = $tag['value'];
            }
            if ($tag['tag'] == 'POL:POLICYID') {
                $callbackNumber = 2;
                $tokenData[self::companyCode]['policyId'] = $tag['value'];
            }
            if ($tag['tag'] == 'POL:POLICYNUMBER') {
                $callbackNumber = 2;
            }
        }

        $tokenData[self::companyCode]['status'] = 'buying';
        $this->intermediateDataService->update($token_data['token'], [
            'data' => json_encode($tokenData),
        ]);

        if ($callbackNumber == 1) {
            GlobalStorage::setUser($tokenData[self::companyCode]['user']);
            $attributes = $token_data;
            $this->pushForm($attributes);
            $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
            $this->sendBillUrl($insurer['email'], $tokenData[self::companyCode]['formUrl']);
            $attributes['number'] = $tokenData[self::companyCode]['policyNumber'];
            $attributes['premium'] = $tokenData[self::companyCode]['finalPremium'];
            $this->createPolicy($company, $attributes);
            $logger = app(LogMicroserviceContract::class);
            $logger->sendLog(
                'пользователь отправил запрос на создание заявки в компанию ' . $company->name,
                config('api_sk.logMicroserviceCode'),
                GlobalStorage::getUserId()
            );
            return [
                'status' => 'done',
                'billUrl' => $tokenData[self::companyCode]['formUrl'],
            ];
        } elseif ($callbackNumber == 2) {
            $policy = $this->policyService->getNotPaidPolicyByPaymentNumber($tokenData[self::companyCode]['policyNumber']);
            if (!$policy) {
                throw new PolicyNotFoundException('Нет полиса с таким номером');
            }
            $this->policyService->update($policy->id, [
                'paid' => true,
            ]);
            $this->destroyToken($token_data['token']);
        }

        return [];
    }
}
