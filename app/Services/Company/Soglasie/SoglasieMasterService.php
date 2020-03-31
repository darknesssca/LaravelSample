<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieBillLinkServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCalculateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCancelCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCheckCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Contracts\Company\Soglasie\SoglasieMasterServiceContract;
use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Contracts\Repositories\BillPolicyRepositoryContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\MethodForbiddenException;
use App\Exceptions\TokenException;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class SoglasieMasterService extends SoglasieService implements SoglasieMasterServiceContract
{
    public function calculate($company, $attributes):array
    {
        $this->pushForm($attributes);
        if (!$attributes['policy']['isMultidrive']) {
            $serviceKbm = app(SoglasieKbmServiceContract::class);
            $dataKbm = $serviceKbm->run($company, $attributes);
        } else {
            $dataKbm = [
                'kbmId' => 1,
            ];
        }
        $serviceScoring = app(SoglasieScoringServiceContract::class);
        $dataScoring = $serviceScoring->run($company, $attributes);
        $attributes['serviceData'] = [
            'kbmId' => $dataKbm['kbmId'],
            'scoringId' => $dataScoring['scoringId'],
        ];
        $serviceCalculate = app(SoglasieCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculated',
            'scoringId' => $dataScoring['scoringId'],
            'kbmId' => $dataKbm['kbmId'],
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        return [
            'status' => 'calculated',
            'premium' => $dataCalculate['premium'],
        ];
    }

    public function create($company, $attributes):array
    {
        $this->pushForm($attributes);
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        $attributes['serviceData'] = [
            'kbmId' => $tokenData['kbmId'],
            'scoringId' => $tokenData['scoringId'],
        ];
        $serviceCreate = app(SoglasieCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'policyId' => $dataCreate['policyId'],
            'status' => 'processing',
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        $this->requestProcessService->create([
            'token' => $attributes['token'],
            'state' => 50,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'status' => 'processing',
                'company' => $company->code,
            ]),
        ]);
        $logger = app(LogMicroserviceContract::class);
        $logger->sendLog(
            'пользователь отправил запрос на создание заявки в компанию ' . $company->name,
            config('api_sk.logMicroserviceCode'),
            GlobalStorage::getUserId()
        );
        return [
            'status' => 'processing',
        ];
    }

    public function processing($company, $attributes):array
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        switch ($tokenData['tokenData']['status']) {
            case 'processing':
                return [
                    'status' => 'processing',
                ];
            case 'done':
                return [
                    'status' => 'done',
                    'billUrl' => $tokenData['billUrl'],
                ];
            case 'error':
                throw new ApiRequestsException($tokenData['errorMessage']);
            default:
                throw new TokenException('Статус рассчета не валиден');
        }
    }

    public function creating($company, $processData): void
    {
        $checkService = app(SoglasieCheckCreateServiceContract::class);
        $checkData = $checkService->run($company, $processData);
        switch ($checkData['status']) {
            case 'error':
                $this->requestProcessService->delete($processData['token']);
                $this->dropCreate($company, $processData['token'], $checkData['messages']);
                break;
            case 'complete':
                switch ($checkData['policy']['status']) {
                    case 'RSA_SIGN_FAIL':
                    case 'RSA_CHECK_FAIL':
                    case 'SK_CHECK_FAIL':
                        $this->cancelCreate($company, $processData);
                        $this->requestProcessService->delete($processData['token']);
                        $this->dropCreate($company, $processData['token'], $checkData['messages']);
                        break;
                    case 'SK_CHECK_OK':
                        $this->requestProcessService->delete($processData['token']);
                        $billLinkService = app(SoglasieBillLinkServiceContract::class);
                        $billLinkData = $billLinkService->run($company, $processData);
                        $form = [];
                        $this->pushForm($form);
                        $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
                        $tokenData = $this->getTokenData($processData['token'], true);
                        $tokenData[$company->code]['status'] = 'done';
                        $tokenData[$company->code]['billUrl'] = $billLinkData['billUrl'];
                        $this->sendBillUrl($insurer['email'], $billLinkData['PayUrl']);
                        $this->intermediateDataService->update($processData['token'], [
                            'data' => json_encode($tokenData),
                        ]);
                        break;
                    default:
                        // todo по хорошему это нужно заверонуть в callback хендлера эксепшенов сервиса процессинга
                        if (++$processData['checkCount'] >= config('api_sk.maxCheckCount')) {
                            $this->cancelCreate($company, $processData);
                        }
                        throw new ApiRequestsException($checkData['messages']);
                        break;
                }
                break;
            default: // все остальные статусы рассматриваем как WORKING
                // todo по хорошему это нужно заверонуть в callback хендлера эксепшенов сервиса процессинга
                if (++$processData['checkCount'] >= config('api_sk.maxCheckCount')) {
                    $this->cancelCreate($company, $processData);
                }
                throw new ApiRequestsException($checkData['messages']);
                break;
        }
    }

    public function cancelCreate($company, $processData)
    {
        $cancelService = app(SoglasieCancelCreateServiceContract::class);
        return $cancelService->run($company, $processData);
    }

    protected function dropCreate($company, $token, $error)
    {
        $tokenData = $this->getTokenData($token, true);
        $tokenData[$company->code]['status'] = 'error';
        $tokenData[$company->code]['errorMessages'] = $error;
        $this->intermediateDataService->update($token, [
            'data' => json_encode($tokenData),
        ]);
    }

    /**
     * Метод не используется для данного СК, но требуется для совместимости сервисов
     *
     * @param $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException
     */
    public function payment($company, $attributes): void
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
     * @inheritDoc
     */
    public function getPayment($company, $processData): void
    {
        $attributes = [
            'data' => [
                'policyId' => $processData['bill']['bill_id'],
            ]
        ];
        $checkService = app(SoglasieCheckCreateServiceContract::class);
        $dataStatus = $checkService->run($company, $attributes);
        if (
            $dataStatus['policyStatus'] == 'SIGNED' &&
            isset($dataStatus['policySerial']) && $dataStatus['policySerial'] &&
            isset($dataStatus['policyNumber']) && $dataStatus['policyNumber']
        ) {
            $this->policyRepository->update($processData['id'], [
                'paid' => true,
                'number' => $dataStatus['policySerial'] . ' ' . $dataStatus['policyNumber'],
            ]);
            $billPolicyRepository = app(BillPolicyRepositoryContract::class);
            $billPolicyRepository->delete($processData['id']);
        }
    }
}
