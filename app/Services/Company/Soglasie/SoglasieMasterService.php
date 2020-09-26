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
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\MethodForbiddenException;
use App\Exceptions\TokenException;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class SoglasieMasterService extends SoglasieService implements SoglasieMasterServiceContract
{
    protected $billPolicyRepository;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService,
        BillPolicyRepositoryContract $billPolicyRepository
    )
    {
        $this->billPolicyRepository = $billPolicyRepository;
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function calculate($company, $attributes):array
    {
        $this->pushForm($attributes);
        if (!$attributes['policy']['isMultidrive']) {
            $serviceKbm = app(SoglasieKbmServiceContract::class);
            $dataKbm = $serviceKbm->run($company, $attributes, $attributes['token']);
        } else {
            $dataKbm = [
                'kbmId' => 1,
            ];
        }
        $serviceScoring = app(SoglasieScoringServiceContract::class);
        $dataScoring = $serviceScoring->run($company, $attributes, $attributes['token']);
        $attributes['serviceData'] = [
            'kbmId' => $dataKbm['kbmId'],
            'scoringId' => $dataScoring['scoringId'],
        ];
        $serviceCalculate = app(SoglasieCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes, $attributes['token']);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculated',
            'scoringId' => $dataScoring['scoringId'],
            'kbmId' => $dataKbm['kbmId'],
            'premium' => $dataCalculate['premium'],
            'reward' => $this->getReward($company->id, $tokenData['form'], $dataCalculate['premium'])
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        return [
            'status' => 'calculated',
            'premium' => $dataCalculate['premium'],
            'reward' => $tokenData[$company->code]['reward'],
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
        $dataCreate = $serviceCreate->run($company, $attributes, $attributes['token']);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code]['policyId'] = $dataCreate['policyId'];
        $tokenData[$company->code]['status'] = 'processing';
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        $user = GlobalStorage::getUser();
        $this->requestProcessService->create([
            'token' => $attributes['token'],
            'company' => $company->code,
            'state' => 50,
            'data' => json_encode([
                'policyId' => $dataCreate['policyId'],
                'status' => 'processing',
                'company' => $company->code,
                'user' => $user,
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
        switch ($tokenData['status']) {
            case 'processing':
                return [
                    'status' => 'processing',
                ];
            case 'done':
                $this->destroyToken($attributes['token']);
                return [
                    'status' => 'done',
                    'billUrl' => $tokenData['billUrl'],
                ];
            case 'error':
                throw new ApiRequestsException($tokenData['errorMessages']);
            default:
                throw new TokenException('Статус рассчета не валиден');
        }
    }

    public function creating($company, $processData): void
    {
        $checkService = app(SoglasieCheckCreateServiceContract::class);
        $checkData = $checkService->run($company, $processData, $processData['token']);
        switch ($checkData['status']) {
            case 'error':
                $this->requestProcessService->delete($processData['token'], $company->code);
                $this->dropCreate($company, $processData['token'], $checkData['messages']);
                break;
            case 'complete':
                switch ($checkData['policyStatus']) {
                    case 'RSA_SIGN_FAIL':
                    case 'RSA_CHECK_FAIL':
                    case 'SK_CHECK_FAIL':
                        $this->cancelCreate($company, $processData);
                        $this->requestProcessService->delete($processData['token'], $company->code);
                        $this->dropCreate($company, $processData['token'], $checkData['messages']);
                        break;
                    case 'SK_CHECK_OK':
                    case 'RSA_CHECK_OK':
                        $this->requestProcessService->delete($processData['token'], $company->code);
                        $billLinkService = app(SoglasieBillLinkServiceContract::class);
                        $billLinkData = $billLinkService->run($company, $processData, $processData['token']);
                        $form = [
                            'token' => $processData['token'],
                        ];
                        $this->pushForm($form);
                        GlobalStorage::setUser($processData['data']['user']);
                        $tokenData = $this->getTokenData($processData['token'], true);
                        $form['premium'] = $tokenData[$company->code]['premium'];
                        $dbPolicyId = $this->createPolicy($company, $form);
                        $this->billPolicyRepository->create($dbPolicyId, $processData['data']['policyId']);
                        $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
                        $tokenData[$company->code]['status'] = 'done';
                        $tokenData[$company->code]['billUrl'] = $billLinkData['billUrl'];
                        $this->sendBillUrl($insurer['email'], $billLinkData['billUrl']);
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
        return $cancelService->run($company, $processData, $processData['token']);
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
        $dataStatus = $checkService->run($company, $attributes, $attributes['token']);
        if (
            $dataStatus['policyStatus'] == 'SIGNED' &&
            isset($dataStatus['policySerial']) && $dataStatus['policySerial'] &&
            isset($dataStatus['policyNumber']) && $dataStatus['policyNumber']
        ) {
            $this->policyService->update($processData['id'], [
                'paid' => true,
                'number' => $dataStatus['policySerial'] . ' ' . $dataStatus['policyNumber'],
            ]);
            $this->billPolicyRepository->delete($processData['id']);
        }
    }
}
