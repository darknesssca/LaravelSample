<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskSavePolicyServiceContract;
use App\Exceptions\TokenException;
use App\Models\InsuranceCompany;
use Benfin\Log\Facades\Log;
use Exception;
use Spatie\ArrayToXml\ArrayToXml;

class VskSavePolicyService extends VskService implements VskSavePolicyServiceContract
{
    private $method = 'SavePolicy';

    /**
     * Метод подготавливает данные и отправляет их в СК
     * Каждый метод выполняет один конкретный запрос
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     * @throws TokenException
     */
    public function run(InsuranceCompany $company, $attributes): array
    {
        $data = [];
        $xml = $this->prepareXml($company, $attributes);

        $tag = sprintf('%sRequest | %s', $this->getName(__CLASS__), $attributes['token']);
        Log::daily(
            $xml,
            self::companyCode,
            $tag
        );

        $data = $this->sendRequest('/Policy/SavePolicy', $xml, $attributes['token']);
        $data['method'] = $this->method;

        return $data;
    }

    /**
     * @param $company
     * @param $attributes
     * @return mixed
     * @throws TokenException
     */
    private function prepareXml($company, $attributes)
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code, true);

        $structure = [
            'common:systemId' => '',
            'common:messageId' => $this->method . '.' . $attributes['token'],
            'common:bpId' => $tokenData['bpId'],
            'common:sessionId' => $tokenData['sessionId']
        ];
        $structure = array_merge($structure, $this->getPolicyArray($company, $attributes));

        $this->writeDatabaseLog(
            $attributes['token'],
            $structure,
            false,
            config('api_sk.logMicroserviceCode'),
            static::companyCode,
            $this->getName(__CLASS__)
        );

        return ArrayToXml::convert($structure,
            [
                'rootElementName' => 'policy:savePolicyRequest',
                '_attributes' => [
                    'xmlns:common' => 'http://www.vsk.ru/schema/partners/common',
                    'xmlns:model' => 'http://www.vsk.ru/schema/partners/model',
                    'xmlns:policy' => 'http://www.vsk.ru/schema/partners/policy',
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                ]
            ],
            true,
            null,
            '1.0',
            [
                'encoding' => 'UTF-8',
                'standalone' => true,
                'formatOutput' => true
            ]
        );
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
     */
    public function processCallback(InsuranceCompany $company, array $token_data, array $parsed_response): array
    {
        $tokenData = $this->getTokenData($token_data['token'], true);

        foreach ($parsed_response as $tag) {
            if ($tag['tag'] == 'POL:AMOUNT') {
                $tokenData[self::companyCode]['finalPremium'] = $this->CopToRub($tag['value']);
            }
        }

        if (!empty($tokenData[self::companyCode]['nextMethod'])) {
            $tokenData[self::companyCode]['status'] = 'resendSuccess';
        } else {
            $tokenData[self::companyCode]['status'] = 'signing';
        }

        $this->intermediateDataService->update($token_data['token'], [
            'data' => json_encode($tokenData),
        ]);

        return [];
    }
}
