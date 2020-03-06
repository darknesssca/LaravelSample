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
        $method = (string)$method;
        $companyController = $this->getCompanyController($company, $method);
        try
        {
            $attributes = $this->validate(
                $request,
                $companyController->validationRules($method),
                $companyController->validationMessages($method)
            );
            return $this->useCompanyController($companyController, $method, $company, $attributes);
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

    protected function useCompanyController($controller, $method, $company, $attributes)
    {
        try
        {
            return response()->json($controller->run($company, $attributes), 200);
        }
        catch (\Exception $exception)
        {
            return $this->error($exception->getMessage(), 500);
        }

    }
}
