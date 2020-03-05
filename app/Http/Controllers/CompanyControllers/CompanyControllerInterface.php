<?php


namespace App\Http\Controllers\CompanyControllers;


use App\InsuranceCompany;

interface CompanyControllerInterface
{

    /**
     * @param InsuranceCompany $company
     * @param $attributes
     * @return array
     */
    public function calculate(InsuranceCompany $company, $attributes): array;

    /**
     * @param InsuranceCompany $company
     * @param $attributes
     * @return array
     */
    public function create(InsuranceCompany $company, $attributes): array;

    /**
     * @param InsuranceCompany $company
     * @param $attributes
     * @return array
     */
    public function getStatus(InsuranceCompany $company, $attributes): array;

    /**
     * @param InsuranceCompany $company
     * @param $attributes
     * @return array
     */
    public function getCatalog(InsuranceCompany $company, $attributes): array;

    /**
     * @return array
     */
    public static function validationRules(): array;

    /**
     * @return array
     */
    public static function validationMessages(): array;
}
