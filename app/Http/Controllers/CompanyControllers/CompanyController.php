<?php


namespace App\Http\Controllers\CompanyControllers;


use App\InsuranceCompany;

abstract class CompanyController implements CompanyControllerInterface
{

    protected static $allowedHttpMethods = [
        'calculate',
        'create',
        'getStatus',
        'getCatalog',
    ];

    public static function isMethodAllowed($method)
    {
        return in_array($method, static::$allowedHttpMethods) && method_exists(__CLASS__, $method);
    }

    abstract public function calculate(InsuranceCompany $company, $attributes): array;
    abstract public function create(InsuranceCompany $company, $attributes): array;
    abstract public function getStatus(InsuranceCompany $company, $attributes): array;
    abstract public function getCatalog(InsuranceCompany $company, $attributes): array;
    abstract public static function validationRules(): array;
    abstract public static function validationMessages(): array;
}
