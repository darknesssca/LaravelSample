<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskCalculatePolicyServiceContract;
use App\Exceptions\TokenException;
use App\Models\InsuranceCompany;
use Exception;
use Spatie\ArrayToXml\ArrayToXml;

class VskCalculatePolicyService extends VskService implements VskCalculatePolicyServiceContract
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
        $xml = $this->prepareXml($company, $attributes);

        $this->writeRequestLog(
            [
                'data' => $xml
            ]
        );

        $response = $this->client->post(
            '/cxf/rest/partners/api/v2/osago/Policy/CalculatePolicy',
            [
                'body' => $xml,
            ]
        );

        try {
            $data['uniqueId'] = $response->getHeader('X-VSK-CorrelationId')[0];
        } catch (Exception $exception) {
            //ignore
        }

        return $data;
    }

    private function prepareXml($company, $attributes)
    {

        $structure = [
            'common:systemId' => '',
            'common:messageId' => 'CalculatePolicy.' . $attributes['token'],
        ];
        $structure = array_merge($structure, $this->getPolicyArray($company, $attributes));

        return ArrayToXml::convert($structure,
            [
                'rootElementName' => 'policy:calculatePolicyRequest',
                '_attributes' => [
                    'xmlns:common' => 'http://www.vsk.ru/schema/partners/common',
                    'xmlns:model' => 'http://www.vsk.ru/schema/partners/model/calc',
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
        $this->writeResponseLog([
            'data' => $parsed_response
        ]);

        $tokenData = $this->getTokenData($token_data['token'], true);

        foreach ($parsed_response as $tag) {
            if ($tag['tag'] == 'POL:AMOUNT') {
                $tokenData[self::companyCode]['premium'] = $this->CopToRub($tag['value']);
            }
        }

        $tokenData[self::companyCode]['reward'] = $this->getReward($company->id, $tokenData['form'],
            $tokenData[self::companyCode]['premium']);
        $tokenData[self::companyCode]['status'] = 'calculated';

        $this->intermediateDataService->update($token_data['token'], [
            'data' => json_encode($tokenData),
        ]);

        return [];
    }
}
