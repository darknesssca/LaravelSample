<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCheckCreateServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;

class SoglasieCheckCreateService extends SoglasieService implements SoglasieCheckCreateServiceContract
{
    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService
    )
    {
        $this->apiRestUrl = config('api_sk.soglasie.checkCreateUrl');
        if (!$this->apiRestUrl) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $processData): array
    {
        $url = $this->getUrl([
            'policyId' => $processData['data']['policyId'],
        ]);
        $headers = $this->getHeaders();
        $response = $this->getRequest($url, [], $headers, false);
        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (
            !isset($response['status']) || !$response['status']  ||
            !isset($response['policy']) || !$response['policy'] ||
            !isset($response['policy']['status']) || !$response['policy']['status']
        ) {
            throw new ApiRequestsException(
                $this->getMessages($response, 'API страховой компании не вернуло номер созданного полиса')
            );
        }
        return [
            'status' => strtolower($response['status']),
            'policyStatus' => $response['policy']['status'],
            'policySerial' => isset($response['policy']['policyserial']) ? $response['policy']['policyserial'] : '',
            'policyNumber' => isset($response['policy']['policyno']) ? $response['policy']['policyno'] : '',
            'messages' => $this->getMessages($response),
        ];
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiPassword),
        ];
    }

    protected function getMessages($response, $errorString = null)
    {
        $messages = [];
        if ($errorString) {
            $messages[] = $errorString;
        }
        $messages[] = isset($response['lastError']) ? $response['lastError'] : 'нет данных об ошибке';
        if (isset($response['rsacheck']) && $response['rsacheck']) {
            foreach ($response['rsacheck'] as $rsaCheck) {
                if (isset($rsaCheck['status']) && (strtolower((string)$rsaCheck['status']) == 'error')) {
                    $messages[] = 'Ошибка проверки РСА: ' .
                        isset($rsaCheck['result']) ? (string)$rsaCheck['result'] : 'нет данных об ошибке';
                }
            }
        }
        return $messages;
    }
}
