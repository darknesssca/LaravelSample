<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
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
        if (!$companyController) {
            return $this->error('Не найден обработчик компании', 500);
        }
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
        $controller = 'App\\Services\\Company\\'.ucfirst(strtolower($company->code)).'Service';
        if (!class_exists($controller) || !method_exists($controller, 'calculate')) {
            return false;
        }
        return $controller;
    }

    protected function isCompanyControllerMethodAllowed($controller, $method)
    {
        return $controller::isMethodAllowed($method);
    }

    protected function useCompanyController($controller, $method, $company, $attributes)
    {
        try
        {
            return response()->json((new $controller)->$method($company, $attributes), 200);
        }
        catch (\Exception $exception)
        {
            return $this->error($exception->getMessage(), 500);
        }

    }
}
