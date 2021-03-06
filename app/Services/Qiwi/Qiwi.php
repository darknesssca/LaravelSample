<?php

namespace App\Services\Qiwi;

use App\Contracts\Repositories\ErrorRepositoryContract;
use App\Exceptions\Qiwi\BillingDeclinedException;
use App\Exceptions\Qiwi\CreatePayoutException;
use App\Exceptions\Qiwi\ExecutePayoutException;
use App\Exceptions\Qiwi\PayoutAlreadyExistException;
use App\Exceptions\Qiwi\PayoutInsufficientFundsException;
use App\Exceptions\Qiwi\ResolutionException;
use App\Exceptions\TaxStatusNotServiceException;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Benfin\Log\Facades\Log;
use Exception;
use GuzzleHttp\Client;

class Qiwi
{
    private $connectionParams;
    private $payoutParams;
    private $payoutRecipient;
    private $commonParams;

    /** @var ErrorRepositoryContract $errorRepository */
    private $errorRepository;

    /**
     * Qiwi constructor.
     * @param array $user_requisites
     * @param string $tax_status_code
     * @param string $description
     */
    public function __construct($user_requisites, $tax_status_code, $description = 'Услуги по заключению договора страхования')
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

        $this->errorRepository = app(ErrorRepositoryContract::class);
    }

    //Методы запросов к api

    public function getProviders($providerCode = '')
    {
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/providers/{$providerCode}";

        Log::daily(
            [
                'url' => $url,
                'payload' => [],
            ],
            'qiwi',
            'getProvidersRequest'
        );

        $response = $this->sendRequest('GET', $url);

        Log::daily(
            $response,
            'qiwi',
            'getProvidersResponse'
        );

        return $response['content'];
    }

    public function getProvidersDirectories($providerCode = '', $expand = true)
    {
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/providers/directory/{$providerCode}?expand={$expand}";

        Log::daily(
            [
                'url' => $url,
                'payload' => [],
            ],
            'qiwi',
            'getProvidersDirectoriesRequest'
        );

        $response = $this->sendRequest('GET', $url);

        Log::daily(
            $response,
            'qiwi',
            'getProvidersDirectoriesResponse'
        );

        return $response['content'];
    }

    /**
     * @param $amount
     * @return string
     * @throws BillingDeclinedException
     * @throws CreatePayoutException
     * @throws PayoutAlreadyExistException
     * @throws PayoutInsufficientFundsException
     * @throws ResolutionException
     * @throws TaxStatusNotServiceException
     */
    public function createPayout($amount)
    {
        $this->setPayoutRecipientParams();
        $this->setPayoutParams($amount);
        $this->commonParams['payout_id'] = $this->getGUID();

        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/payments/{$this->commonParams['payout_id']}";

        Log::daily(
            [
                'url' => $url,
                'payload' => $this->payoutParams,
            ],
            'qiwi',
            'createPayoutRequest'
        );

        try {
            $response = $this->sendRequest('PUT', $url, $this->payoutParams);

            Log::daily(
                $response,
                'qiwi',
                'createPayoutResponse'
            );

        } catch (Exception $e) {
            Log::daily(
                $e->getMessage(),
                'qiwi',
                'createPayoutException'
            );

            throw new CreatePayoutException($e->getMessage());
        }

        $response = json_decode($response['content'], true);

        // check billing declined !first!
        if (
            isset($response['status']['value']) && ($response['status']['value'] == 'FAILED') &&
            isset($response['status']['errorCode']) && ($response['status']['errorCode'] == 'BILLING_DECLINED')
        ) {
            try {
                app(AuthMicroserviceContract::class)->qiwiReset();
            } catch (Exception $exception) {
                // todo: should make custom exception for api error
            }

            if (
                isset($response['status']['errorMessage']) &&
                mb_strpos(
                    mb_strtolower($response['status']['errorMessage']),
                    'партнер не привязан к налогоплательщику'
                ) !== false
            ) {
                throw new ResolutionException($this->errorRepository->getReportErrorByCode(1002));
            }
            $taxStatus = GlobalStorage::getUserTaxStatus();
            $erCode = $taxStatus === 'self_employed' ? 1001 : 1003;
            throw new BillingDeclinedException($this->errorRepository->getReportErrorByCode($erCode));
        }

        // check gate errors
        if (!empty($response['errorCode'])) {
            switch ($response['errorCode']) {
                case 'payout.payment.already-exist':
                    throw new PayoutAlreadyExistException();
                    break;
                case 'payout.insufficient_funds':
                    throw new PayoutInsufficientFundsException();
                    break;
            }
        }

        // check status
        if ($response['status']['value'] != 'READY') {
            throw new CreatePayoutException('Не удалось создать выплату');
        }

        return $this->commonParams['payout_id'];
    }

    /**
     * @param $payout_id
     * @return array
     * @throws Exception
     */
    public function executePayout($payout_id)
    {
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/payments/{$payout_id}/execute";

        Log::daily(
            [
                'url' => $url,
                'payload' => [],
            ],
            'qiwi',
            'executePayoutRequest'
        );

        $response = $this->sendRequest('POST', $url);

        Log::daily(
            $response,
            'qiwi',
            'executePayoutResponse'
        );

        $response = json_decode($response['content'], true);

        return $this->getResponseStatus($response);
    }

    public function getPayoutStatus($payout_id)
    {
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/payments/{$payout_id}";

        Log::daily(
            [
                'url' => $url,
                'payload' => [],
            ],
            'qiwi',
            'getPayoutStatusRequest'
        );

        $response = $this->sendRequest('GET', $url);

        Log::daily(
            $response,
            'qiwi',
            'getPayoutStatusResponse'
        );

        $response = json_decode($response['content'], true);

        return $this->getResponseStatus($response);
    }

    private function getResponseStatus($response)
    {
        if (!empty($response['errorCode'])) {
            switch ($response['errorCode']) {
                case 'payout.insufficient_funds':
                    throw new PayoutInsufficientFundsException();
                    break;
                case 'BILLING_DECLINED':
                    $taxStatus = GlobalStorage::getUserTaxStatus();
                    $erCode = $taxStatus === 'self_employed' ? 1001 : 1003;
                    throw new BillingDeclinedException($this->errorRepository->getReportErrorByCode($erCode));
            }
        }

        $responseStatus =  $response['status']['value'] ?? false;

        $resultStatus = '';

        switch ($responseStatus) {
            case 'IN_PROGRESS':
                $resultStatus = 'progress';
                break;
            case 'EXPIRED':
                $resultStatus = 'expired';
                break;
            case 'COMPLETED':
                $resultStatus = 'done';
                break;
            default:
                throw new ExecutePayoutException('Не удалось исполнить выплату');
        }

        return [
            'status' => $resultStatus,
            'checkUrl' => $response['billingDetails']['receiptUrl'] ?? null,
        ];
    }

    public function getBalance()
    {
        $url = "agents/{$this->connectionParams['agent_id']}/points/{$this->connectionParams['point_id']}/balance";

        Log::daily(
            [
                'url' => $url,
                'payload' => [],
            ],
            'qiwi',
            'getBalanceRequest'
        );

        $response = $this->sendRequest('GET', $url);

        Log::daily(
            $response,
            'qiwi',
            'getBalanceResponse'
        );

        $response = json_decode($response['content'], true);

        return $response;
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
     * @return void
     * @throws TaxStatusNotServiceException
     */
    private function setPayoutRecipientParams()
    {
        switch ($this->commonParams['tax_status']) {
            case 'physical_no_limit':
            case 'physical':
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
                        'incomeType' => 'FROM_LEGAL_ENTITY',
                        'description' => $this->commonParams['description'],
                        'customerInn' => config('api.qiwi.organizationName'),
                        'customerOrganization' => config('api.qiwi.organizationInn')
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
     * @return array|bool
     * если запрос прошел успешно, то true
     */
    private function sendRequest(string $method, string $url, array $data = [])
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
