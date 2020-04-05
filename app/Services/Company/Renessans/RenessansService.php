<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Services\Company\CompanyService;

abstract class RenessansService extends CompanyService
{
    public const companyCode = 'renessans';

    protected $apiUrl;
    protected $secretKey;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService
    )
    {
        $this->apiUrl = config('api_sk.renessans.apiUrl');
        $this->secretKey = config('api_sk.renessans.apiKey');
        if (!($this->apiUrl && $this->secretKey)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    protected function setAuth(&$attributes)
    {
        $attributes['key'] = $this->secretKey;
    }

    protected function getUrl($data = [])
    {
        $url = (substr($this->apiUrl, -1) == '/' ? substr($this->apiUrl, 0, -1) : $this->apiUrl) .
            $this->apiPath;
        if ($data) {
            foreach ($data as $key => $value) {
                $url = str_replace('{{'.$key.'}}', $value, $url);
            }
        }
        return $url;
    }
}
