<?php

namespace App\Services\Company\Ingosstrah;

use App\Contracts\Company\Ingosstrah\IngosstrahBillLinkServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahBillStatusServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCalculateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCheckCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahCreateServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahEosagoServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Contracts\Company\Ingosstrah\IngosstrahMasterServiceContract;
use App\Contracts\Repositories\BillPolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\MethodForbiddenException;
use App\Exceptions\TokenException;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;

class IngosstrahMasterService extends IngosstrahService implements IngosstrahMasterServiceContract
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

    public function calculate($company, $attributes): array
    {
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run($company, $attributes);
        $this->pushForm($attributes);
        $attributes['sessionToken'] = $loginData['sessionToken'];
        $serviceCalculate = app(IngosstrahCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes);
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculated',
            'sessionToken' => $loginData['sessionToken'],
            'premium' => $dataCalculate['premium'],
            'reward' => $this->getReward($company->id, $tokenData['form'], $dataCalculate['premium']),
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
        return [
            'premium' => $dataCalculate['premium'],
            'reward' => $tokenData[$company->code]['reward'],
        ];
    }

    public function create($company, $attributes): array
    {
        $this->pushForm($attributes);
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        $newSessionToken = $tokenData['sessionToken'];
        $attributes['sessionToken'] = $newSessionToken;
        $serviceCreate = app(IngosstrahCreateServiceContract::class);
        $dataCreate = $serviceCreate->run($company, $attributes);
        if (isset($dataCreate['tokenError'])) {
            $serviceLogin = app(IngosstrahLoginServiceContract::class);
            $loginData = $serviceLogin->run($company, $attributes);
            $newSessionToken = $loginData['sessionToken'];
            $attributes['sessionToken'] = $newSessionToken;
            $dataCreate = $serviceCreate->run($company, $attributes);
        }
        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code]['policyId'] = $dataCreate['policyId'];
        $tokenData[$company->code]['status'] = 'processing';
        $tokenData[$company->code]['sessionToken'] = $newSessionToken;
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
                'sessionToken' => $newSessionToken,
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

    public function processing($company, $attributes): array
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        switch ($tokenData['status']) {
            case 'processing':
                return [
                    'status' => 'processing',
                ];
            case 'hold':
                return [
                    'status' => 'hold',
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
    public function preCalculating($company, $attributes): void
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
     * @inheritDoc
     */
    public function creating($company, $processData): void
    {
        $newSessionToken = $processData['data']['sessionToken'];
        $isNeedUpdateToken = false;
        $checkService = app(IngosstrahCheckCreateServiceContract::class);
        $checkData = $checkService->run($company, $processData);
        if (isset($checkData['tokenError'])) {
            $serviceLogin = app(IngosstrahLoginServiceContract::class);
            $loginData = $serviceLogin->run($company, []);
            $newSessionToken = $loginData['sessionToken'];
            $isNeedUpdateToken = true;
            $processData['data']['sessionToken'] = $newSessionToken;
            $checkData = $checkService->run($company, $processData);
        }
        switch ($checkData['state']) {
            case 'аннулирован':
            case 'прекращен страхователем':
            case 'прекращён страхователем':
            case 'прекращен страховщиком':
            case 'прекращён страховщиком':
            case 'выпущен':
                $this->requestProcessService->delete($processData['token']);
                $this->dropCreate($company, $processData['token'], [
                    'API страховой компании вернуло статус ' . $checkData['state'],
                    $checkData['message']
                ]);
                break;
            case 'заявление':
                $processData['data']['policyIsn'] = $checkData['isn'];
                $attributes = [
                    'token' => $processData['token'],
                ];
                $this->pushForm($attributes);
                $attributes['number'] = $processData['data']['policyId'];
                $tokenData = $this->getTokenDataByCompany($processData['token'], $company->code);
                $attributes['premium'] = $tokenData['premium'];
                GlobalStorage::setUser($processData['data']['user']);
                $tokenData = $this->getTokenData($processData['token'], true);
                if (!isset($tokenData['dbPolicyId'])) {
                    $dbPolicyId = $this->createPolicy($company, $attributes);
                    if ($dbPolicyId) {
                        $processData['data']['dbPolicyId'] = $dbPolicyId;
                        $tokenData['dbPolicyId'] = $dbPolicyId;
                        $this->intermediateDataService->update($processData['token'], [
                            'data' => json_encode($tokenData),
                        ]);
                    }
                }
                $eosagoService = app(IngosstrahEosagoServiceContract::class);
                $eosagoData = $eosagoService->run($company, $processData);
                if (!$eosagoData['isEosago'] && $eosagoData['hold']) {
                    $processData['data']['status'] = 'hold';
                    $processData['data']['sessionToken'] = $newSessionToken;
                    $this->requestProcessService->update($processData['token'], [
                        'state' => 75,
                        'data' => json_encode($processData['data']),
                        'checkCount' => 0,
                    ]);
                    $tokenData = $this->getTokenData($processData['token'], true);
                    $tokenData[$company->code]['status'] = 'hold';
                    $tokenData[$company->code]['sessionToken'] = $newSessionToken;
                    $tokenData[$company->code]['policyIsn'] = $processData['data']['policyIsn'];
                    $this->intermediateDataService->update($processData['token'], [
                        'data' => json_encode($tokenData),
                    ]);
                    return;
                }
                $this->createBill($company, $processData);
                break;
            default: // все остальные статусы рассматриваем как WORKING
                if ($isNeedUpdateToken) {
                    $processData['data']['sessionToken'] = $newSessionToken;
                    $this->requestProcessService->update($processData['token'], [
                        'data' => json_encode($processData['data']),
                    ]);
                }
                throw new ApiRequestsException([
                    'API страховой компании вернуло некорректный статус',
                    $checkData['message']
                ]);
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function holding($company, $processData): void
    {
        $isNeedUpdateToken = false;
        $newSessionToken = $processData['data']['sessionToken'];
        $checkService = app(IngosstrahCheckCreateServiceContract::class);
        $checkData = $checkService->run($company, $processData);
        if (isset($checkData['tokenError'])) {
            $serviceLogin = app(IngosstrahLoginServiceContract::class);
            $loginData = $serviceLogin->run($company, []);
            $newSessionToken = $loginData['sessionToken'];
            $isNeedUpdateToken = true;
            $data['data']['sessionToken'] = $newSessionToken;
            $checkData = $checkService->run($company, $data);
        }
        if (
            isset($checkData['policySerial']) && $checkData['policySerial'] &&
            isset($checkData['policyNumber']) && $checkData['policyNumber'] &&
            isset($checkData['isEosago']) && $checkData['isEosago']
        ) {
            $this->createBill($company, $processData, true);
        } else {
            if ($isNeedUpdateToken) {
                $processData['data']['sessionToken'] = $newSessionToken;
                $this->requestProcessService->update($processData['token'], [
                    'data' => json_encode($processData['data']),
                ]);
            }
            throw new ApiRequestsException([
                'API страховой компании вернуло некорректный статус',
                $checkData['message']
            ]);
        }
    }

    protected function createBill($company, $processData, $destroyToken = false)
    {
        $this->requestProcessService->delete($processData['token']);
        $billService = app(IngosstrahBillServiceContract::class);
        $billData = $billService->run($company, $processData);
        $processData['data']['billIsn'] = $billData['billIsn'];
        $form = [
            'token' => $processData['token'],
        ];
        $this->billPolicyRepository->create($processData['data']['dbPolicyId'], $processData['data']['billIsn']);
        $this->pushForm($form);
        $insurer = $this->searchSubjectById($form, $form['policy']['insurantId']);
        $processData['data']['insurerEmail'] = $insurer['email'];
        $billLinkService = app(IngosstrahBillLinkServiceContract::class);
        $billLinkData = $billLinkService->run($company, $processData);
        if ($destroyToken) {
            $this->destroyToken($processData['token']);
        } else {
            $tokenData = $this->getTokenData($processData['token'], true);
            $tokenData[$company->code]['status'] = 'done';
            $tokenData[$company->code]['billIsn'] = $billData['billIsn'];
            $tokenData[$company->code]['billUrl'] = $billLinkData['PayUrl'];
            $this->intermediateDataService->update($processData['token'], [
                'data' => json_encode($tokenData),
            ]);
        }
        $this->sendBillUrl($insurer['email'], $billLinkData['PayUrl']);
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

    public function getPayment($company, $processData): void
    {
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run($company, []);
        $attributes = [
            'data' => [
                'BillISN' => $processData['bill']['bill_id'],
                'SessionToken' => $loginData['sessionToken'],
            ]
        ];
        $serviceStatus = app(IngosstrahBillStatusServiceContract::class);
        $dataStatus = $serviceStatus->run($company, $attributes);
        if (isset($dataStatus['paid']) && $dataStatus['paid']) {
            $this->policyService->update($processData['id'], [
                'paid' => true,
            ]);
            $this->billPolicyRepository->delete($processData['id']);
        }
    }
}
