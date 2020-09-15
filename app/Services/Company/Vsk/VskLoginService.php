<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskLoginServiceContract;
use App\Exceptions\TokenException;
use App\Models\InsuranceCompany;
use Exception;
use Spatie\ArrayToXml\ArrayToXml;

class VskLoginService extends VskService implements VskLoginServiceContract
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

        $response = $this->client->post(
            '/cxf/rest/partners/api/v2/osago/Auth/Login',
            [
                'body' => $this->prepareXml($attributes)->toXml(),
            ]);

        try {
            $data['uniqueId'] = $response->getHeader('X-VSK-CorrelationId')[0];
        } catch (Exception $exception) {
            //ignore
        }

        return $data;
    }

    private function prepareXml($attributes)
    {
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);

        $fields = [
            'common:messageId' => 'Login.' . $attributes['token'],
            'auth:partnerClientId' => $insurer['phone']
        ];

        return new ArrayToXml($fields, [
            'rootElementName' => 'auth:loginRequest',
            '_attributes' => [
                'xmlns:auth' => 'http://www.vsk.ru/schema/partners/auth',
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
        $tokenData = $this->getTokenData($token_data['token'], true);

        foreach ($parsed_response as $tag) {
            if ($tag['tag'] == 'SCH1:SESSIONID') {
                $tokenData[self::companyCode]['sessionId'] = $tag['value'];
            }

            if ($tag['tag'] == 'SCH1:BPID') {
                $tokenData[self::companyCode]['bpId'] = $tag['value'];
            }
        }

        $this->intermediateDataService->update($token_data['token'], [
            'data' => json_encode($tokenData),
        ]);

        return [
            'nextMethod' => 'preCalculating'
        ];
    }
}
