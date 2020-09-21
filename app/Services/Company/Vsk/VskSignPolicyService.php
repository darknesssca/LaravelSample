<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskSignPolicyServiceContract;
use App\Exceptions\TokenException;
use App\Models\InsuranceCompany;
use Exception;
use Spatie\ArrayToXml\ArrayToXml;

class VskSignPolicyService extends VskService implements VskSignPolicyServiceContract
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
        $xml = $this->prepareXml($company, $attributes)->toXml();

        $this->writeRequestLog(
            [
                'data' => $xml
            ]
        );

        $response = $this->client->post(
            '/cxf/rest/partners/api/v2/osago/Policy/SignPolicy',
            [
                'body' => $xml,
            ]);

        try {
            $data['uniqueId'] = $response->getHeader('X-VSK-CorrelationId')[0];
        } catch (Exception $exception) {
            //ignore
        }

        return $data;
    }

    private function prepareXml($company, $attributes)
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code, true);

        $fields = [
            'common:messageId' => 'SignPolicy.' . $attributes['token'],
            'common:bpId' => $tokenData['bpId'],
            'common:sessionId' => $tokenData['sessionId'],
            'policy:partnerClientId' => '',
            'policy:code' => $attributes['code']
        ];

        return new ArrayToXml($fields, [
            'rootElementName' => 'policy:signPolicyRequest',
            '_attributes' => [
                'xmlns:policy' => 'http://www.vsk.ru/schema/partners/policy',
                'xmlns:common' => 'http://www.vsk.ru/schema/partners/common'
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
     */
    public function processCallback(InsuranceCompany $company, array $token_data, array $parsed_response): array
    {
        $this->writeResponseLog([
            'data' => $parsed_response
        ]);

        $tokenData = $this->getTokenData($token_data['token'], true);

        foreach ($parsed_response as $tag) {
            if ($tag['tag'] == 'POL:POLICYNUMBER' && !empty($tag['value'])) {
                $tokenData[self::companyCode]['policyNumber'] = $tag['value'];
            }
        }

        $this->intermediateDataService->update($token_data['token'], [
            'data' => json_encode($tokenData),
        ]);

        return [
            'nextMethod' => 'calculating'
        ];
    }
}
