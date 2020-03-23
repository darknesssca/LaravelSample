<?php

namespace App\Http\Controllers;

use App\Contracts\Company\CompanyServiceContract;
use App\Models\Country;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use App\Models\RequestProcess;
use App\Services\Company\GuidesSourceTrait;
use App\Services\Company\Ingosstrah\IngosstrahGuidesService;
use App\Services\Company\Renessans\RenessansGuidesService;
use App\Services\Company\Soglasie\SoglasieGuidesService;
use App\Services\Company\Tinkoff\TinkoffCalculateService;
use App\Services\Company\Tinkoff\TinkoffGuidesService;
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
        try {
            return response()->json($this->runService($company, $request, $method), 200);
        } catch (ValidationException $exception) {
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
            return response()->json(['token' => $token], 200);
        } catch (ValidationException $exception) {
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
        $response = $controller->$serviceMethod($company, $attributes, $additionalData);
        return $response;
    }

    public function checkCompany($code)
    {
        if (!isset(self::$companies[$code])) {
            self::$companies[$code] = InsuranceCompany::getCompany($code);
        }
        return self::$companies[$code];
    }

    public function getCalculate()
    {
        $count = config('api_sk.renessans.apiCheckCountByCommand');
        $process = RequestProcess::where('state', 1)->limit($count);
        if ($process) {
            $company = $this->checkCompany('renessans'); // в данном случае мы работаем только с 1 компанией
            foreach ($process as $processItem) {
                $token = $processItem->token;
                $tokenData = IntermediateData::getData($token);
                $additionalData['tokenData'] = isset($tokenData[$company->code]) ? $tokenData[$company->code] : false;
                $attributes = $tokenData['form'];
                $attributes['token'] = $token;
                $controller = app('App\\Contracts\\Company\\Renessans\\RenessansServiceContract');
                $response = $controller->calculate($company, $attributes, $additionalData);
            }
        } else {
            sleep(5);
            return;
        }
    }

    public function getCreateStatus()
    {
        $count = config('api_sk.renessans.maxRowsByCycle');
        $process = RequestProcess::where('state', 50)->limit($count)->get();
        if ($process) {
            foreach ($process as $processItem) {
                $company = $this->checkCompany($processItem->company);
                $token = $processItem->token;
                $companyCode = ucfirst(strtolower($company->code));

                $controller = app('App\\Contracts\\Company\\' . $companyCode . '\\' . $companyCode . 'ServiceContract');
                $response = $controller->checkCreate($company, $processItem);
            }
        } else {
            sleep(5);
            return;
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
        //$method = ucfirst(strtolower($method));
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
            //new IngosstrahGuidesService(),
            //new RenessansGuidesService(),
            //new SoglasieGuidesService(),
            //new TinkoffGuidesService(),
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
