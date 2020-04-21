<?php

namespace App\Http\Controllers;


use App\Contracts\Company\Ingosstrah\IngosstrahGuidesSourceContract;
use App\Contracts\Company\Renessans\RenessansGuidesSourceContract;
use App\Contracts\Company\Soglasie\SoglasieGuidesSourceContract;
use App\Contracts\Company\Tinkoff\TinkoffGuidesSourceContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Http\Requests\FormSendRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\ProcessRequest;
use App\Models\Country;
use App\Services\Company\CompanyService;
use App\Services\Company\GuidesSourceTrait;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Benfin\Requests\Exceptions\ValidationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Application;

class InsuranceController extends Controller
{
    use TokenTrait, CompanyServicesTrait;

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

    public function index($code, $method, ProcessRequest $request)
    {
        $company = $this->getCompany($code);
        $method = strtolower((string)$method);
        return Response::success($this->runService($company, $request->validated(), $method));
    }

    public function store(FormSendRequest $request)
    {
        $data = [
            'form' => $request->validated(),
        ];
        $this->checkRequiredAddresses($data['form']);
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
        return Response::success($this->runService($company, $request->toArray(), $method));
    }

    private function checkRequiredAddresses($form)
    {
        foreach ($form['subjects'] as $subject) {
            if (
                $subject['id'] == $form['policy']['ownerId'] || $subject['id'] == $form['policy']['insurantId']
            ) {
                if (!isset($subject['fields']['addresses']) || !$subject['fields']['addresses']) {
                    throw new ValidationException(['Поле addresses обязательно для заполнения для владельца автомобиля, страхователя и водителей с иностранными ВУ.']);
                }
            } else {
                foreach ($subject['fields']['documents'] as $document) {
                    if ($document['document']['documentType'] != 'license') {
                        continue;
                    }
                    if (
                        !$document['document']['isRussian'] &&
                        (!isset($subject['fields']['addresses']) || !$subject['fields']['addresses'])
                    ) {
                        throw new ValidationException(['Поле addresses обязательно для заполнения для владельца автомобиля, страхователя и водителей с иностранными ВУ.']);
                    }
                }
            }
        }
    }
}
