<?php

namespace App\Http\Controllers;


use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Http\Requests\FormSendRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\ProcessRequest;
use App\Models\Country;
use App\Services\Company\GuidesSourceTrait;
use App\Services\Company\Ingosstrah\IngosstrahGuidesService;
use App\Services\Company\Renessans\RenessansGuidesService;
use App\Services\Company\Soglasie\SoglasieGuidesService;
use App\Services\Company\Tinkoff\TinkoffGuidesService;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;
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

    /**
     * artisan команда обновления справочников
     */
    public function refreshGuides()
    {
        //список объектов, реализующих интерфейс GuidesSourceInterface
        $companies = [
            new IngosstrahGuidesService(),
            new RenessansGuidesService(),
            new SoglasieGuidesService(),
            new TinkoffGuidesService(),
        ];

        $this->loadCountries();

        foreach ($companies as $company) {
            echo "Импорт марок и моделей: " . $company->companyCode . "\n";
            if (!$company->updateCarModelsGuides()) {
                echo "!!!!!!!!!!!!!!!!!!!!!!!!ОШИБКА!!!!!!!!!!!!!!!!!!!!!!!!";
            }
        }

        echo "Удаление лишних марок...\n";
        GuidesSourceTrait::cleanDB();
    }

    /**
     * обновление общей таблицы стран
     */
    private function loadCountries()
    {
        DB::transaction(function () {
            $filename = Application::getInstance()->basePath() . "/storage/import/countries.json"; //todo: сделать импорт из minio
            $arr = (array)json_decode(file_get_contents($filename));
            $models = [];
            Country::truncate();
            foreach ($arr as $item) {
                $item = (array)$item;
                $models[] = [
                    "code" => $item["CODE"],
                    "name" => array_key_exists("FULLNAME", $item) ? $item["FULLNAME"] : $item["SHORTNAME"],
                    "short_name" => $item["SHORTNAME"],
                    "alpha2" => $item["ALFA2"],
                    "alpha3" => $item["ALFA3"],
                ];
            }
            Country::insert($models);
        });
    }
}
