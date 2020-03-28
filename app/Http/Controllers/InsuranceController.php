<?php

namespace App\Http\Controllers;

use App\Contracts\Company\CompanyServiceContract;
use App\Contracts\Repositories\InsuranceCompanyContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Exceptions\CompanyException;
use App\Exceptions\MethodBoundException;
use App\Http\Requests\FormSendRequest;
use App\Http\Requests\ProcessRequest;
use App\Models\Country;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use App\Models\Policy;
use App\Models\RequestProcess;
use App\Services\Company\GuidesSourceTrait;
use App\Services\Company\Ingosstrah\IngosstrahGuidesService;
use App\Services\Company\Renessans\RenessansGuidesService;
use App\Services\Company\Soglasie\SoglasieGuidesService;
use App\Services\Company\Tinkoff\TinkoffGuidesService;
use App\Traits\Token;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Benfin\Api\Services\LogMicroservice;
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Application;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

class InsuranceController extends Controller
{
    use Token;

    protected $intermediateDataRepository;
    protected $requestProcessRepository;
    protected $insuranceCompanyRepository;

    public function __construct(
        IntermediateDataRepositoryContract $intermediateDataRepository,
        RequestProcessRepositoryContract $requestProcessRepository,
        InsuranceCompanyContract $insuranceCompanyRepository
    )
    {
        $this->intermediateDataRepository = $intermediateDataRepository;
        $this->requestProcessRepository = $requestProcessRepository;
        $this->insuranceCompanyRepository = $insuranceCompanyRepository;
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
        return $this->success(['token' => $token]);
    }

    public function getCompany($code)
    {
        $company = $this->insuranceCompanyRepository->getCompany($code);
        if (!$company) {
            throw new CompanyException('Компания ' . $code . ' не найдена или не доступна');
        }
        return $company;
    }

    private function runService($company, $attributes, $serviceMethod)
    {
        $controller = $this->getCompanyController($company);
        if (!method_exists($controller, $serviceMethod)) {
            throw new MethodBoundException('Метод не найден');
        }
        return $controller->$serviceMethod($company, $attributes);
    }

    protected function getCompanyController($company)
    {
        $company = ucfirst(strtolower($company->code));
        $contract = 'App\\Contracts\\Company\\' . $company . '\\' . $company . 'MasterServiceContract';
        return app($contract);
    }


    // FIXME требуется рефакторинг


    public function payment($code, Request $request)
    {
        $company = $this->checkCompany($code);
        if (!$company->count()) {
            return $this->error('Компания не найдена', 404);
        }
        $serviceMethod = 'payment';
        try {
            $controller = $this->getCompanyController($company);
            if (!method_exists($controller, $serviceMethod)) {
                return $this->error('Метод не найден', 404); // todo вынести в отдельные эксепшены
            }
            $controller = $this->getCompanyController($company);
            $response =  $controller->$serviceMethod($company, $request->toArray(), $serviceMethod);
            if (isset($response['error']) && $response['error']) {
                return response()->json($response, 500);
            } else {
                return $this->success($response, 200);
            }
        } catch (ValidationException $exception) {
            return $this->error($exception->errors(), 400);
        } catch (BindingResolutionException $exception) {
            return $this->error('Не найден обработчик компании: ' . $exception->getMessage(), 404);
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage(), 500);
        }
    }



    public function getPreCalculate()
    {
        $count = config('api_sk.maxRowsByCycle');
        $process = RequestProcess::where('state', 1)->limit($count)->get();
        if ($process) {
            foreach ($process as $processItem) {
                try {
                    $company = $this->checkCompany($processItem->company);
                    $token = $processItem->token;
                    $tokenData = IntermediateData::getData($token);
                    $additionalData['tokenData'] = isset($tokenData[$company->code]) ? $tokenData[$company->code] : false;
                    $attributes = $tokenData['form'];
                    $attributes['token'] = $token;
                    $companyCode = ucfirst(strtolower($company->code));
                    $controller = app('App\\Contracts\\Company\\'.$companyCode.'\\'.$companyCode.'ServiceContract');
                    $response = $controller->checkPreCalculate($company, $attributes, $processItem);
                } catch (\Exception $exception) {
                    $isUpdated = RequestProcess::updateCheckCount($processItem->token);
                    if ($isUpdated === false) {
                        $tokenData = IntermediateData::getData($processItem->token);
                        $tokenData[$company->code]['status'] = 'error';
                        IntermediateData::where('token', $processItem->token)->update([
                            'data' => $tokenData,
                        ]);
                    }
                }
            }
        } else {
            sleep(5);
            return;
        }
    }

    public function getSegment()
    {
        $count = config('api_sk.maxRowsByCycle');
        $process = RequestProcess::where('state', 5)->limit($count)->get();
        if ($process) {
            foreach ($process as $processItem) {
                try {
                    $company = $this->checkCompany($processItem->company);
                    $token = $processItem->token;
                    $tokenData = IntermediateData::getData($token);
                    $additionalData['tokenData'] = isset($tokenData[$company->code]) ? $tokenData[$company->code] : false;
                    $attributes = $tokenData['form'];
                    $attributes['token'] = $token;
                    $companyCode = ucfirst(strtolower($company->code));
                    $controller = app('App\\Contracts\\Company\\'.$companyCode.'\\'.$companyCode.'ServiceContract');
                    $response = $controller->checkSegment($company, $attributes, $processItem);
                } catch (\Exception $exception) {
                    $isUpdated = RequestProcess::updateCheckCount($processItem->token);
                    if ($isUpdated === false) {
                        $tokenData = IntermediateData::getData($processItem->token);
                        $tokenData[$company->code]['status'] = 'error';
                        IntermediateData::where('token', $processItem->token)->update([
                            'data' => $tokenData,
                        ]);
                    }
                }
            }
        } else {
            sleep(5);
            return;
        }
    }

    public function getCalculate()
    {
        $count = config('api_sk.maxRowsByCycle');
        $process = RequestProcess::where('state', 10)->limit($count)->get();
        if ($process) {
            foreach ($process as $processItem) {
                try {
                    $company = $this->checkCompany($processItem->company);
                    $token = $processItem->token;
                    $tokenData = IntermediateData::getData($token);
                    $additionalData['tokenData'] = isset($tokenData[$company->code]) ? $tokenData[$company->code] : false;
                    $attributes = $tokenData['form'];
                    $attributes['token'] = $token;
                    $companyCode = ucfirst(strtolower($company->code));
                    $controller = app('App\\Contracts\\Company\\'.$companyCode.'\\'.$companyCode.'ServiceContract');
                    $response = $controller->checkCalculate($company, $attributes, $processItem);
                } catch (\Exception $exception) {
                    $isUpdated = RequestProcess::updateCheckCount($processItem->token);
                    if ($isUpdated === false) {
                        $tokenData = IntermediateData::getData($processItem->token);
                        $tokenData[$company->code]['status'] = 'error';
                        $tokenData[$company->code]['errorMessage'] = 'произошла ошибка, попробуйте позднее';
                        IntermediateData::where('token', $processItem->token)->update([
                            'data' => $tokenData,
                        ]);
                    }
                }
            }
        } else {
            sleep(5);
            return;
        }
    }

    public function getHold()
    {
        $count = config('api_sk.maxRowsByCycle');
        $process = RequestProcess::where('state', 75)->limit($count)->get();
        if ($process) {
            foreach ($process as $processItem) {
                try {
                    $company = $this->checkCompany($processItem->company);
                    $companyCode = ucfirst(strtolower($company->code));
                    $controller = app('App\\Contracts\\Company\\'.$companyCode.'\\'.$companyCode.'ServiceContract');
                    $response = $controller->checkHold($company, $processItem);
                } catch (\Exception $exception) {
                    $isUpdated = RequestProcess::updateCheckCount($processItem->token);
                    if ($isUpdated === false) {
                        $tokenData = IntermediateData::getData($processItem->token);
                        $tokenData[$company->code]['status'] = 'error';
                        $tokenData[$company->code]['errorMessage'] = 'произошла ошибка, попробуйте позднее';
                        IntermediateData::where('token', $processItem->token)->update([
                            'data' => $tokenData,
                        ]);
                    }
                }
            }
        } else {
            sleep(5);
            return;
        }
    }

    public function getCreateStatus()
    {
        $count = config('api_sk.maxRowsByCycle');
        $process = RequestProcess::where('state', 50)->limit($count)->get();
        if ($process) {
            foreach ($process as $processItem) {
                try {
                    $company = $this->checkCompany($processItem->company);
                    $companyCode = ucfirst(strtolower($company->code));
                    $controller = app('App\\Contracts\\Company\\'.$companyCode.'\\'.$companyCode.'ServiceContract');
                    $response = $controller->checkCreate($company, $processItem);
                } catch (\Exception $exception) {
                    $isUpdated = RequestProcess::updateCheckCount($processItem->token);
                    if ($isUpdated === false) {
                        $tokenData = IntermediateData::getData($processItem->token);
                        $tokenData[$company->code]['status'] = 'error';
                        $tokenData[$company->code]['errorMessage'] = 'произошла ошибка, попробуйте позднее';
                        IntermediateData::where('token', $processItem->token)->update([
                            'data' => $tokenData,
                        ]);
                    }
                }
            }
        } else {
            sleep(5);
            return;
        }
    }

    public function getPayment()
    {
        $count = config('api_sk.maxRowsByCycle');
        $policies = Policy::with([
            'status',
            'company',
            'bill',
        ])
            ->where('paid', 0)
            ->whereHas('status', function ($query) {
                $query->where('code', 'issued');
            })
            ->whereDate('registration_date', '>', (new Carbon)->subDays(2)->format('Y-m-d'))
            ->limit($count)
            ->get();
        if (!$policies) {
            return;
        }
        foreach ($policies as $policy) {
            try {
                $company = $this->checkCompany($policy->company->code);
                $companyCode = ucfirst(strtolower($company->code));
                $controller = app('App\\Contracts\\Company\\'.$companyCode.'\\'.$companyCode.'ServiceContract');
                $response = $controller->checkPaid($company, $policy);
            } catch (\Exception $exception) {
                // игнорируем
            }
        }
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
