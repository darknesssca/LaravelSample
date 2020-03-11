<?php


namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffServiceContract;
use App\Services\Company\CompanyService;
use Illuminate\Support\Carbon;

class TinkoffService extends CompanyService implements TinkoffServiceContract
{
    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;
    protected $apiProducerCode;

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.tinkoff.wsdlUrl');
        $this->apiUser = config('api_sk.tinkoff.user');
        $this->apiPassword = config('api_sk.tinkoff.password');
        $this->apiProducerCode = config('api_sk.tinkoff.producerCode');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword && $this->apiProducerCode)) {
            throw new \Exception('tinkoff api is not configured');
        }
    }

    public function calculate($company, $attributes, $additionalData = [])
    {
        $service = app(TinkoffCalculateServiceContract::class);
        return $service->run($company, $attributes, $additionalData);
    }

    protected function setHeader(&$data)
    {
        $data['Header'] = [
            'user' => $this->apiUser,
            'password' => $this->apiPassword,
        ];
        $data['producerCode'] = $this->apiProducerCode;
    }

    protected function formatDateTimeZone($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('Y-m-d\TH:i:sP');
    }
}
