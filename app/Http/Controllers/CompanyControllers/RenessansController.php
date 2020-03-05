<?php


namespace App\Http\Controllers\CompanyControllers;


use App\InsuranceCompany;

class RenessansController extends CompanyController
{
    private $apiUrl;
    private $secretKey;

    private $calculateApiPath = '/calculate/';

    public function __construct()
    {
        if (!(
            array_key_exists('RENESSANS_API_URL', $_ENV) &&
            $_ENV['RENESSANS_API_URL'] &&
            array_key_exists('RENESSANS_API_KEY', $_ENV) &&
            $_ENV['RENESSANS_API_KEY']
        )) {
            throw new \Exception('renessans api is not configured');
        }
        $this->apiUrl = $_ENV['RENESSANS_API_URL'];
        $this->secretKey = $_ENV['RENESSANS_API_KEY'];
    }

    public function calculate(InsuranceCompany $company, $attributes): array
    {
        return ['calculate', __CLASS__];
    }

    public static function validationRules(): array
    {
        return [];
    }

    public static function validationMessages(): array
    {
        return [];
    }

    public function create(InsuranceCompany $company, $attributes): array
    {
        // TODO: Implement create() method.
        return ['create'];
    }

    public function getStatus(InsuranceCompany $company, $attributes): array
    {
        // TODO: Implement getStatus() method.
        return ['getStatus'];
    }

    public function getCatalog(InsuranceCompany $company, $attributes): array
    {
        // TODO: Implement getCatalog() method.
        return ['getCatalog'];
    }
}
