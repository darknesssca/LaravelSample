<?php


namespace App\Services\Company;


use App\Models\InsuranceCompany;

interface CompanyServiceInterface
{

    /**
     * @param InsuranceCompany $company
     * @param array $attributes
     * @param array $additionalFields
     * @return array
     */
    public function run(InsuranceCompany $company, array $attributes, array $additionalFields): array;


}
