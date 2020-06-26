<?php

namespace App\Http\Controllers;


use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Exceptions\AutocodException;
use App\Exceptions\LimitationsException;
use App\Exceptions\TokenException;
use App\Http\Requests\FormSendRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\ProcessRequest;
use App\Traits\CacheStore;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class InsuranceController extends Controller
{
    use TokenTrait, CompanyServicesTrait, CacheStore;

    protected $intermediateDataService;
    protected $insuranceCompanyService;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        InsuranceCompanyServiceContract $insuranceCompanyService
    )
    {
        $this->intermediateDataService = $intermediateDataService;
        $this->insuranceCompanyService = $insuranceCompanyService;
    }

    /**
     * @param $code
     * @param $method
     * @param ProcessRequest $request
     * @return mixed
     * @throws TokenException
     * @throws \App\Exceptions\CompanyException
     * @throws \App\Exceptions\MethodNotFoundException
     * @throws \App\Exceptions\NotAvailableCommissionException
     */
    public function index($code, $method, ProcessRequest $request)
    {
        $validatedRequest = $request->validated();
        $company = $this->getCompany($code);
        if ($method == 'calculate') {
            $formData = $this->getTokenData($validatedRequest['token']);
            $this->checkCommissionAvailable($company->id, $formData['form']);
            $this->checkGlobalLimitations($formData['form']);
        }
        $method = strtolower((string)$method);
        return Response::success($this->runService($company, $validatedRequest, $method));
    }

    public function store(FormSendRequest $request)
    {
        $validatedRequest = $request->validated();
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
            } catch (\Exception $exception) {
                // ignore
            }
        }
        $token = $this->createToken($data);
        $logger = app(LogMicroserviceContract::class);
        $logger->sendLog(
            'пользователь отправил форму со следующими полями: ' . json_encode($data['form']),
            config('api_sk.logMicroserviceCode'),
            GlobalStorage::getUserId()
        );
        return Response::success(['token' => $token->token]);
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
     * @throws \App\Exceptions\CompanyException
     * @throws \App\Exceptions\MethodNotFoundException
     */
    public function payment($code, PaymentRequest $request)
    {
        $company = $this->getCompany($code);
        $method = 'payment';
        return Response::success($this->runService($company, $request->validated(), $method));
    }

    private function setStoredKeys(&$formData)
    {
        $autocodIsTaxiId = $this->getId('autocod', GlobalStorage::getUserId(), $formData['car']['vin'], 'isTaxi');
        $autocodIsExistId = $this->getId('autocod', GlobalStorage::getUserId(), $formData['car']['vin'], 'isExist');
        if(
            !$this->exist($autocodIsTaxiId) ||
            !$this->exist($autocodIsExistId)
        ) {
            throw new AutocodException('Проверка на использование ТС в такси не выполнялась');
        }
        $formData['autocod'] = [
            'isTaxi' => $this->look($autocodIsTaxiId)['status'],
            'isExist' => $this->look($autocodIsExistId)['status'],
        ];
    }

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
