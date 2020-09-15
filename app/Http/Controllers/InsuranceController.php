<?php

namespace App\Http\Controllers;


use App\Contracts\Repositories\Services\DraftServiceContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Exceptions\AutocodException;
use App\Exceptions\CompanyException;
use App\Exceptions\LimitationsException;
use App\Exceptions\MethodNotFoundException;
use App\Exceptions\NotAvailableCommissionException;
use App\Exceptions\TokenException;
use App\Http\Requests\FormSendRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\ProcessRequest;
use App\Traits\CacheStore;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;
use App\Traits\UserTrait;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Nowakowskir\JWT\TokenEncoded;

class InsuranceController extends Controller
{
    use TokenTrait, CompanyServicesTrait, CacheStore, UserTrait;

    protected $intermediateDataService;
    protected $insuranceCompanyService;
    protected $draftService;
    protected $authMsk;
    protected $notifyMsk;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        InsuranceCompanyServiceContract $insuranceCompanyService,
        DraftServiceContract $draftService,
        AuthMicroserviceContract $authMsk,
        NotifyMicroserviceContract $notifyMsk
    )
    {
        $this->intermediateDataService = $intermediateDataService;
        $this->insuranceCompanyService = $insuranceCompanyService;
        $this->draftService = $draftService;
        $this->authMsk = $authMsk;
        $this->notifyMsk = $notifyMsk;
    }

    /**
     * @param $code
     * @param $method
     * @param ProcessRequest $request
     * @return mixed
     * @throws LimitationsException
     * @throws TokenException
     * @throws CompanyException
     * @throws MethodNotFoundException
     * @throws NotAvailableCommissionException
     */
    public function index($code, $method, ProcessRequest $request)
    {
        $validatedRequest = $request->validated();
        $company = $this->getCompany($code);
        if ($method == 'calculate') {
            $formData = $this->getTokenData($validatedRequest['token']);
            $this->checkGlobalLimitations($formData['form']);
            $this->checkCommissionAvailable($company->id, $formData['form']);
        }
        $method = strtolower((string)$method);
        return Response::success($this->runService($company, $validatedRequest, $method));
    }

    /**
     * @param FormSendRequest $request
     * @return mixed
     * @throws AutocodException
     * @throws TokenException
     */
    public function store(FormSendRequest $request)
    {
        $validatedRequest = $request->validated();

        if (isset($validatedRequest['draftId']) && $validatedRequest['draftId'] > 0) {
            $this->draftService->update($validatedRequest['draftId'], $validatedRequest);
            $draftId = $validatedRequest['draftId'];
        } else {
            $draftId = $this->draftService->create($validatedRequest);
        }

        $data = [
            'form' => $validatedRequest,
        ];
        $this->setStoredKeys($data['form']);
        if (isset($validatedRequest['prevToken']) && $validatedRequest['prevToken']) {
            try {
                $oldToken = $this->getTokenData($validatedRequest['prevToken']);
                if ($oldToken) {
                    unset($oldToken['form']);
                    if ($oldToken) {
                        $data['prevData'] = $oldToken;
                    }
                }
            } catch (Exception $exception) {
                // ignore
            }
        }
        $token = $this->createToken($data);
        $logger = app(LogMicroserviceContract::class);
        $logger->sendLog(
            'Пользователь отправил форму со следующими полями: ' . json_encode($data['form'], JSON_UNESCAPED_UNICODE),
            config('api_sk.logMicroserviceCode'),
            GlobalStorage::getUserId()
        );
        return Response::success([
            'token' => $token->token,
            'draftId' => $draftId
        ]);
    }

    /**
     * @param FormSendRequest $request
     * @return mixed
     * @throws AutocodException
     * @throws TokenException
     */
    public function storeWithRegister(FormSendRequest $request)
    {
        try {
            $validatedRequest = $request->validated();

            $registerUserData = $this->prepareUserRegistrationData($validatedRequest);

            $registerData = $this->authMsk->register($registerUserData);
            if (!array_key_exists('content', $registerData))
                return Response::error($registerData['errors'], 500);
            $registerToken = $registerData['content'];
            $payload = (new TokenEncoded($registerToken))->decode()->getPayload();
            $userEmailData = [
                'password' => $payload['password'],
                'link' => $payload['link']
            ];

            unset($payload['password'], $payload['link']);
            $payload["access_token"] = $registerToken;
            GlobalStorage::setUser($payload);
            $this->put(
                $this->getId('autocod', GlobalStorage::getUserId(), 'VIN', $validatedRequest['car']['vin'], 'isExist'),
                ['status' => true]
            );
            $this->put(
                $this->getId('autocod', GlobalStorage::getUserId(), 'VIN', $validatedRequest['car']['vin'], 'isTaxi'),
                ['status' => $validatedRequest['isTaxi']]
            );

            if (isset($validatedRequest['draftId']) && $validatedRequest['draftId'] > 0) {
                $this->draftService->update($validatedRequest['draftId'], $validatedRequest);
                $draftId = $validatedRequest['draftId'];
            } else {
                $draftId = $this->draftService->create($validatedRequest);
            }

            $userEmailData['link'] .= "&draft_id=$draftId";

            $this->notifyMsk->sendMail($registerUserData['email'], $userEmailData, 'register');

            $data = [
                'form' => $validatedRequest,
            ];
            $this->setStoredKeys($data['form']);
            if (isset($validatedRequest['prevToken']) && $validatedRequest['prevToken']) {
                try {
                    $oldToken = $this->getTokenData($validatedRequest['prevToken']);
                    if ($oldToken) {
                        unset($oldToken['form']);
                        if ($oldToken) {
                            $data['prevData'] = $oldToken;
                        }
                    }
                } catch (Exception $exception) {
                    // ignore
                }
            }
            $formToken = $this->createToken($data);
            $logger = app(LogMicroserviceContract::class);
            $logger->sendLog(
                'Пользователь отправил форму со следующими полями: ' . json_encode($data['form'], JSON_UNESCAPED_UNICODE),
                config('api_sk.logMicroserviceCode'),
                GlobalStorage::getUserId()
            );
            return Response::success([
                'form_token' => $formToken->token,
                'auth_token' => $registerToken
            ]);
        } catch (\Exception $ex) {
            return Response::error($ex->getMessage(), 500);
        }
    }

    /**
     * Получение данных об оплате входящим запросом микросервиса external_api
     *
     * в связи с особенностями механизма роутинга требуется перепустить запрос на обновление платежных данных через
     * независимый метод-фабрику не прибегая к общему механизму, описанному методом index.
     * В следствие этого целевой метод указывать требуется вручную параметром $method
     *
     * @param $code
     * @param PaymentRequest $request
     * @return mixed
     * @throws CompanyException
     * @throws MethodNotFoundException
     */
    public function payment($code, PaymentRequest $request)
    {
        $company = $this->getCompany($code);
        $method = 'payment';
        return Response::success($this->runService($company, $request->validated(), $method));
    }

    /**
     * @param $formData
     * @throws AutocodException
     */
    private function setStoredKeys(&$formData)
    {
        $autocodVinIsTaxiId = $this->getId('autocod', GlobalStorage::getUserId(), 'VIN', $formData['car']['vin'], 'isTaxi');
        $autocodVinIsExistId = $this->getId('autocod', GlobalStorage::getUserId(), 'VIN', $formData['car']['vin'], 'isExist');
        $autocodGrzIsTaxiId = $this->getId('autocod', GlobalStorage::getUserId(), 'GRZ', $formData['car']['vin'], 'isTaxi');
        $autocodGrzIsExistId = $this->getId('autocod', GlobalStorage::getUserId(), 'GRZ', $formData['car']['vin'], 'isExist');
        if(
            (!$this->exist($autocodVinIsTaxiId) || !$this->exist($autocodVinIsExistId)) &&
            (!$this->exist($autocodGrzIsTaxiId) || !$this->exist($autocodGrzIsExistId))
        ) {
            throw new AutocodException('Проверка на использование ТС в такси не выполнялась');
        }
        if($autocodVinIsExistId != null) {
            $formData['autocod'] = [
                'isTaxi' => $this->look($autocodVinIsTaxiId)['status'],
                'isExist' => $this->look($autocodVinIsExistId)['status'],
            ];
        } else {
            $formData['autocod'] = [
                'isTaxi' => $this->look($autocodGrzIsTaxiId)['status'],
                'isExist' => $this->look($autocodGrzIsExistId)['status'],
            ];
        }
    }

    /**
     * @param $formData
     * @throws LimitationsException
     */
    private function checkGlobalLimitations($formData)
    {
        if ($formData['autocod']['isTaxi']) {
            throw new LimitationsException('Автомобиль зарегистрирован в качестве такси. Оформление полиса невозможно');
        }
        if (!$formData['autocod']['isExist']) {
            $documentDateIssue = Carbon::parse($formData['car']['document']['dateIssue'])
                ->startOfDay()
                ->addDays(10)
                ->timestamp;
            $checkDate = Carbon::now()
                ->startOfDay()
                ->timestamp;
            if ($documentDateIssue <= $checkDate) {
                throw new LimitationsException('Оформление полиса для данного ТС запрещено');
            }
        }
    }

}
