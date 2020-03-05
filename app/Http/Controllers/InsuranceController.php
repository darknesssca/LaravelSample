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
        $companyController = $this->getCompanyController($company);
        if (!$this->isCompanyControllerMethodAllowed($companyController, $method)) {
            return $this->error('метод не найден или не доступен', 404);
        }
        try
        {
            $attributes = $this->validate(
                $request,
                $companyController::validationRules($method),
                $companyController::validationMessages($method)
            );
            return $this->useCompanyController($companyController, $method, $company, $attributes);
        }
        catch (ValidationException $exception)
        {
            return $this->error($exception->errors(), 400);
        }
        catch (BindingResolutionException $exception)
        {
            return $this->error('Не найден обработчик компании: '.$exception->getMessage(), 500);
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


    protected function getCompanyController($company)
    {
        $contract = 'App\\Contracts\\Company\\'.ucfirst(strtolower($company->code)).'ServiceContract';
        return app($contract);
    }

    protected function isCompanyControllerMethodAllowed($controller, $method)
    {
        return $controller::isMethodAllowed($method);
    }

    protected function useCompanyController($controller, $method, $company, $attributes)
    {
        try
        {
            return response()->json($controller->$method($company, $attributes), 200);
        }
        catch (\Exception $exception)
        {
            return $this->error($exception->getMessage(), 500);
        }

    }
}
