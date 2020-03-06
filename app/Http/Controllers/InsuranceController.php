<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
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
        if (!method_exists($this, $method)) {
            return $this->error('Метод не найден', 404);
        }
        try
        {
            return $this->$method($company, $request);
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

    private function calculate(InsuranceCompany $company, $request)
    {
        $calculateData = $this->runService($company, $request, 'Calculate');
        $checkSegmentData = $this->runService($company, $request, 'Create', ['isCheckSegment' => true]);
        return $calculateData;
    }

    private function runService(InsuranceCompany $company, $request, $serviceMethod, $additionalData = [])
    {
        $controller = $this->getCompanyController($company, $serviceMethod);
        $attributes = $this->validate(
            $request,
            $controller->validationRules(),
            $controller->validationMessages()
        );
        return $this->useCompanyController($controller, $company, $attributes, $additionalData);
    }

    public function checkCompany($code)
    {
        return InsuranceCompany::getCompany($code);
    }

    protected function error($messages, $httpCode = 500)
    {
        $message = [
            'error' => true,
            'errors' => $messages,
        ];
        return response()->json($message, $httpCode);
    }


    protected function getCompanyController($company, $method)
    {
        $company = ucfirst(strtolower($company->code));
        $method = ucfirst(strtolower($method));
        $contract = 'App\\Contracts\\Company\\'.$company.'\\'.$company.$method.'ServiceContract';
        return app($contract);
    }

    protected function useCompanyController($controller, $company, $attributes, $additionalData)
    {
        return response()->json($controller->run($company, $attributes, $additionalData), 200);
    }
}
