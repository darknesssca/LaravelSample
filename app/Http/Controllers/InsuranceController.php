<?php

namespace App\Http\Controllers;

use App\Contracts\Company\CompanyServiceContract;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use App\Models\RequestProcess;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InsuranceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index($code, $method, Request $request)
    {
        $company = $this->checkCompany($code);
        if (!$company->count()) {
            return $this->error('Компания не найдена', 404);
        }
        $method = strtolower((string)$method);
        try
        {
            return response()->json($this->runService($company, $request, $method), 200);
        }
        catch (ValidationException $exception)
        {
            return $this->error($exception->errors(), 400);
        }
        catch (BindingResolutionException $exception)
        {
            return $this->error('Не найден обработчик компании: '.$exception->getMessage(), 404);
        }
        catch (\Exception $exception)
        {
            return $this->error($exception->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try
        {
            $controller = app(CompanyServiceContract::class);
            $attributes = $this->validate(
                $request,
                $controller->validationRulesForm(),
                $controller->validationMessagesForm()
            );
            $data = [
                'form' => $attributes,
            ];
            $token = IntermediateData::createToken($data);
            return response()->json(['token' => $token], 200);
        }
        catch (ValidationException $exception)
        {
            return $this->error($exception->errors(), 400);
        }
        catch (BindingResolutionException $exception)
        {
            return $this->error('Не найден обработчик компании: '.$exception->getMessage(), 404);
        }
        catch (\Exception $exception)
        {
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
        $tokenData = IntermediateData::getData($validatedFields['token']);
        if (!$tokenData) {
            throw new \Exception('token not valid'); // todo вынести в отдельные эксепшены
        }if (!isset($tokenData['form']) || !$tokenData['form']) {
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
        return InsuranceCompany::getCompany($code);
    }

    public function getCalculate()
    {
        $count = config('api_sk.renessans.apiCheckCountByCommand');
        $process = RequestProcess::where('state', 1)->limit($count);
        if ($process) {
            $company = $this->checkCompany('renessans');
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
        $contract = 'App\\Contracts\\Company\\'.$company.'\\'.$company.'ServiceContract';
        return app($contract);
    }
}
