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


    /**
     * @return array
     */
    //public function map(): array;

    /**
     * @param array $fields
     * @param string $prefix
     * @return array
     */
    //public function getRules(array $fields, string $prefix): array;

    /**
     * @return array
     */
    //public function validationRules(): array;

    /**
     * @return array
     */
    //public function validationMessages(): array;

    /**
     * @param $url
     * @param $data
     * @param $headers
     * @return array
     */
    public function postRequest($url, $data, $headers): array;

    /**
     * @param $url
     * @param $data
     * @param $headers
     * @return array
     */
    public function getRequest($url, $data, $headers): array;
}
