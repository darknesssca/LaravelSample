<?php


namespace App\Services\Company\Renessans;

use App\Services\Company\CompanyService;

abstract class RenessansService extends CompanyService
{
    private $apiUrl;
    private $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('api_sk.renessans.apiUrl');
        $this->secretKey = config('api_sk.renessans.apiKey');
        if (!($this->apiUrl && $this->secretKey)) {
            throw new \Exception('renessans api is not configured');
        }
    }

    protected function setAuth(&$attributes)
    {
        $attributes['key'] = $this->secretKey;
    }

    protected function getUrl($method, $data = null)
    {
        $url = (substr($this->apiUrl, -1) == '/' ? substr($this->apiUrl, 0, -1) : $this->apiUrl) .
            $this->apiPath[$method];
        if (!$data || !count($data)) {
            return $url;
        }
        foreach ($data as $key => $value) {
            $url = str_replace('{{'.$key.'}}', $value, $url);
        }
        return $url;
    }
}
