<?php


namespace App\Services\Qiwi;


use GuzzleHttp\Client;

class Qiwi
{
    private $params;

    public function __construct()
    {
        $this->params = [
            'endpoint' => 'https://api-test.qiwi.com/partner/payout/v1/',
            'agent_id' => 'pareks',
            'point_id' => 'kzewxc',
            'bearer_token' => 'pareks-b122b936-57c9-473b-8c85-d615db5eb2fe',
            'application_id' => '83cca1c0-49f2-417a-bd88-2803058dfd7a',
            'application_secret' => '2924502d-f1e4-4f06-96ed-cdce126cf49c',
        ];
    }

    /**отправка запроса
     * @param string $method
     * метод запроса
     * @param string $url
     * адрес
     * @param array $data
     * данные
     * @param bool $async
     * если истина, то запрос выполняется асинхронно и без результата
     * @return array|bool
     * если запрос прошел успешно, то true
     */
    private function sendRequest(string $method, string $url, array $data=[], bool $async = false)
    {
        $method = strtoupper($method);
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$this->params['bearer_token']}",
            "Accept" => "application/json",
        ];

        $client = new Client([
            'base_uri' => $this->params['endpoint'],
            'timeout' => 1.0,
        ]);
        if (!$async) {
            $response = $client->request($method, $url, ["form_params" => $data, "headers" => $headers]);
            $code = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            
            return [
                'success' => $code == 200,
                'content' => $content
            ];
        } else {
            $client->requestAsync($method, $url, ["form_params" => $data, "headers" => $headers]);
            return true;
        }
    }

    public function getProviders($providerCode = '')
    {
        $url = "agents/{$this->params['agent_id']}/points/{$this->params['point_id']}/providers/{$providerCode}";
        $response = $this->sendRequest('GET', $url);
        return $response['content'];
    }

    public function getProvidersDirectories($providerCode = '', $expand = true)
    {
        $url = "agents/{$this->params['agent_id']}/points/{$this->params['point_id']}/providers/directory/{$providerCode}?expand={$expand}";
        $response = $this->sendRequest('GET', $url);
        return $response['content'];
    }
}
