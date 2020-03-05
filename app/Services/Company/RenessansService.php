<?php


namespace App\Services\Company;


use App\Models\InsuranceCompany;
use GuzzleHttp\Client;

class RenessansService extends CompanyService
{
    private $apiUrl;
    private $secretKey;

    private $apiPath = [
        'calculate' => '/calculate/?fullInformation=true',
        'create' => '/',
        'getStatus' => '/',
        'getCatalog' => '/',
    ];

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

    private function setAuth(&$attributes)
    {
        $attributes['key'] = $this->secretKey;
    }

    private  function getUrl($method)
    {
        if (!array_key_exists($method, $this->apiPath)) {
            throw new \Exception('not found api path');
        }
        return (substr($this->apiUrl, -1) == '/' ? substr($this->apiUrl, 0, -1) : $this->apiUrl) .
            $this->apiPath[$method];
    }

    public function calculate(InsuranceCompany $company, $attributes): array
    {
        $this->setAuth($attributes);
        $url = $this->getUrl(__FUNCTION__);
        return ['calculate', __CLASS__, $url, $attributes, $this->postRequest($url, $attributes)];
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

    public function postRequest($url, $data = [], $headers = [])
    {
        $client = new Client();
        $params = [];
        if ($headers and count($headers)) {
            $params['headers'] = $headers;
        }
        if ($data and count($data)) {
            $params['form_params'] = $data;
        }
        $response = $client->post($url,  $params);
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }
}
