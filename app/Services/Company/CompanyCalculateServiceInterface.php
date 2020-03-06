<?php


namespace App\Services\Company;


use App\Models\InsuranceCompany;

interface CompanyCalculateServiceInterface
{

    /**
     * @param InsuranceCompany $company
     * @param $attributes
     * @return array
     */
    public function run(InsuranceCompany $company, array $attributes): array;


    /**
     * @return array
     */
    public function map(): array;

    /**
     * @param array $fields
     * @param string $prefix
     * @return array
     */
    public function getRules(array $fields, string $prefix): array;

    /**
     * @return array
     */
    public function validationRules(): array;

    /**
     * @return array
     */
    public function validationMessages(): array;
}
