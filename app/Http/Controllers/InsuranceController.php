<?php

namespace App\Http\Controllers;

use App\Contracts\Company\CompanyServiceContract;
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
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Application;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

class InsuranceController extends Controller
{
    protected static $companies = [];

    public function index($code, $method, Request $request)
    {
        $company = $this->checkCompany($code);
        if (!$company->count()) {
            return $this->error('Компания не найдена', 404);
        }
        $method = strtolower((string)$method);
        try
        {
            $response = $this->runService($company, $request, $method);
            if (isset($response['error']) && $response['error']) {
                return response()->json($response, 500);
            } else {
                return $this->success($response, 200);
            }
        }
        catch (ValidationException $exception)
        {
            return $this->error($exception->errors(), 400);
        } catch (BindingResolutionException $exception) {
            return $this->error('Не найден обработчик компании: ' . $exception->getMessage(), 404);
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $controller = app(CompanyServiceContract::class);
            $attributes = $this->validate(
                $request,
                $controller->validationRulesForm(),
                $controller->validationMessagesForm()
            );
            $data = [
                'form' => $attributes,
            ];
            RestController::checkToken($attributes);
            RestController::sendLog($attributes);
            $token = IntermediateData::createToken($data);
            return $this->success(['token' => $token], 200);
        }
        catch (ValidationException $exception)
        {
            return $this->error($exception->errors(), 400);
        } catch (BindingResolutionException $exception) {
            return $this->error('Не найден обработчик компании: ' . $exception->getMessage(), 404);
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage(), 500);
        }
    }

    public function payment($company, Request $request)
    {
        $company = $this->checkCompany($company);
        if (!$company->count()) {
            return $this->error('Компания не найдена', 404);
        }
        $serviceMethod = 'payment';
        try
        {
            $controller = $this->getCompanyController($company);
            if (!method_exists($controller, $serviceMethod)) {
                return $this->error('Метод не найден', 404); // todo вынести в отдельные эксепшены
            }
            $this->validate(
                $request,
                $controller->validationRulesProcess(),
                $controller->validationMessagesProcess()
            );
            $response = $this->runService($company, $request->toArray(), $serviceMethod);
            if (isset($response['error']) && $response['error']) {
                return response()->json($response, 500);
            } else {
                return $this->success($response, 200);
            }
        }
        catch (ValidationException $exception)
        {
            return $this->error($exception->errors(), 400);
        } catch (BindingResolutionException $exception) {
            return $this->error('Не найден обработчик компании: ' . $exception->getMessage(), 404);
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage(), 500);
        }
    }

    private function runService($company, $request, $serviceMethod, $additionalData = [])
    {
        $controller = $this->getCompanyController($company);
        if (!method_exists($controller, $serviceMethod)) {
            return $this->error('Метод не найден', 404); // todo вынести в отдельные эксепшены
        }
        $validatedFields = $this->validate(
            $request,
            $controller->validationRulesProcess(),
            $controller->validationMessagesProcess()
        );
        RestController::checkToken($validatedFields);
        $tokenData = IntermediateData::getData($validatedFields['token']);
        if (!$tokenData) {
            throw new \Exception('token not valid'); // todo вынести в отдельные эксепшены
        }
        if (!isset($tokenData['form']) || !$tokenData['form']) {
            throw new \Exception('token have no data'); // todo вынести в отдельные эксепшены
        }
        $additionalData['tokenData'] = isset($tokenData[$company->code]) ? $tokenData[$company->code] : false;
        $attributes = $tokenData['form'];
        $attributes['token'] = $validatedFields['token'];
        return $controller->$serviceMethod($company, $attributes, $additionalData);
    }

    public function checkCompany($code)
    {
        if (!isset(self::$companies[$code])) {
            self::$companies[$code] = InsuranceCompany::getCompany($code);
        }
        return self::$companies[$code];
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

    protected function error($messages, $httpCode = 500)
    {
        $errors = [];
        if (gettype($messages) == 'array') {
            foreach ($messages as $message) {
                $errors[] = [
                    'message' => $message,
                ];
            }
        } else {
            $errors[] = [
                'message' => (string)$messages,
            ];
        }
        $message = [
            'error' => true,
            'errors' => $errors,
        ];
        return response()->json($message, $httpCode);
    }


    protected function getCompanyController($company)
    {
        $company = ucfirst(strtolower($company->code));
        $contract = 'App\\Contracts\\Company\\' . $company . '\\' . $company . 'ServiceContract';
        return app($contract);
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
