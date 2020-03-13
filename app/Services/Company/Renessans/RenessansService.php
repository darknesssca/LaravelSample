<?php


namespace App\Services\Company\Renessans;

use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Company\Renessans\RenessansServiceContract;
use App\Services\Company\CompanyService;

class RenessansService extends CompanyService implements RenessansServiceContract
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

    public function calculate($company, $attributes, $additionalData = [])
    {
        $serviceCalculate = app(RenessansCalculateServiceContract::class);
        $dataCalculate = $serviceCalculate->run($company, $attributes, $additionalData);

    }


    protected function setAuth(&$attributes)
    {
        $attributes['key'] = $this->secretKey;
    }

    protected function getUrl($data = null)
    {
        $url = (substr($this->apiUrl, -1) == '/' ? substr($this->apiUrl, 0, -1) : $this->apiUrl) .
            $this->apiPath;
        if (!$data || !count($data)) {
            return $url;
        }
        foreach ($data as $key => $value) {
            $url = str_replace('{{'.$key.'}}', $value, $url);
        }
        return $url;
    }

    protected function transformBoolean($boolean)
    {
        return (bool)$boolean;
    }

    protected function transformBooleanToInteger($boolean)
    {
        return (int)$boolean;
    }
}
