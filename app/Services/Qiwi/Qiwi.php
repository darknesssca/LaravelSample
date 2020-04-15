<?php


namespace App\Services\Qiwi;


use App\Exceptions\TaxStatusNotServiceException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Qiwi
{
    private $connectionParams;
    private $payoutParams;
    private $payoutRecipient;
    private $commonParams;

    public function __construct($user_requisites, $tax_status_code, $description = 'Перевод')
    {
        $this->connectionParams = [
            'endpoint' => env('QIWI_ENDPOINT'),
            'agent_id' => env('QIWI_AGENT_ID'),
            'point_id' => env('QIWI_POINT_ID'),
            'bearer_token' => env('QIWI_BEARER_TOKEN'),
            'application_id' => env('QIWI_APPLICATION_ID'),
            'application_secret' => env('QIWI_APPLICATION_SECRET'),
        ];

        $this->commonParams = [
            'requisites' => $user_requisites,
            'tax_status' => $tax_status_code,
            'description' => $description,
        ];
    }

    //Методы запросов к api

    public function getProviders($providerCode = '')
    {
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/providers/{$providerCode}";
        $response = $this->sendRequest('GET', $url);
        return $response['content'];
    }

    public function getProvidersDirectories($providerCode = '', $expand = true)
    {
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/providers/directory/{$providerCode}?expand={$expand}";
        $response = $this->sendRequest('GET', $url);
        return $response['content'];
    }

    /**
     * @param $amount
     * @return string
     * @throws TaxStatusNotServiceException
     */
    public function makePayout($amount)
    {
        $this->createPayout($amount);
        $this->executePayout();
        return $this->commonParams['payout_id'];
    }

    /**
     * @param $amount
     * @throws TaxStatusNotServiceException
     */
    private function createPayout($amount)
    {
        $this->setPayoutRecipientParams();
        $this->setPayoutParams($amount);
        $this->commonParams['payout_id'] = $this->getGUID();
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/payments/{$this->commonParams['payout_id']}";
        $response = $this->sendRequest('PUT', $url, $this->payoutParams);

        echo '<pre>';
        print_r($response);
        echo '</pre>';

    }

    private function executePayout()
    {
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/payments/{$this->commonParams['payout_id']}/execute";
        $this->sendRequest('POST', $url, $this->payoutParams);
    }


    //Методы создания объектов

    private function setPayoutParams($amount)
    {
        $this->payoutParams = [
            'recipientDetails' => $this->payoutRecipient,
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'RUB'
            ],
            'source' => [
                'paymentType' => 'NO_EXTRA_CHARGE',
                'paymentToolType' => 'BANK_ACCOUNT',
                'paymentTerminalType' => 'INTERNET_BANKING',
            ]
        ];
    }

    /**
     * @return array
     * @throws TaxStatusNotServiceException
     */
    private function setPayoutRecipientParams()
    {
        switch ($this->commonParams['tax_status']) {
            case 'individual':
                $this->payoutRecipient = [
                    'providerCode' => 'bank-card-russia',
                    'fields' => [
                        'sinap-form-version' => 'payout::bank-card-russia, 1',
                        'pan' => $this->commonParams['requisites']['card_number']
                    ]
                ];
                break;

            case 'self_employed':
                $this->payoutRecipient = [
                    'providerCode' => 'self-employed-bank-card',
                    'fields' => [
                        'sinap-form-version' => 'payout::self-employed-bank-card, 1',
                        'inn' => $this->commonParams['requisites']['inn'],
                        'account' => $this->commonParams['requisites']['card_number'],
                        'incomeType' => 'FROM_INDIVIDUAL',
                        'description' => $this->commonParams['description'],
                        'fio_optional' => ''
                    ]
                ];
                break;

            default:
                throw new TaxStatusNotServiceException();
        }
    }

    //Вспомогательные функции

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
    private function sendRequest(string $method, string $url, array $data, bool $async = false)
    {
        $method = strtoupper($method);
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$this->connectionParams['bearer_token']}",
            "Accept" => "application/json",
        ];

        $client = new Client([
            'base_uri' => $this->connectionParams['endpoint'],
            'timeout' => 0
        ]);

        $response = $client->request($method, $url, ["json" => $data, "headers" => $headers]);

        $code = $response->getStatusCode();
        $content = $response->getBody()->getContents();


        return [
            'success' => $code == 200,
            'content' => $content
        ];
    }

    private function getGUID()
    {
        mt_srand((double)microtime() * 10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
    }
}
