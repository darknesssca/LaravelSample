<?php


namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffServiceContract;
use App\Models\IntermediateData;
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
        $data = $service->run($company, $attributes, $additionalData);
        $tokenData = IntermediateData::getData($attributes['token']); // выполняем повторно, поскольку данные могли  поменяться пока шел запрос
        $tokenData[$company->code] = [
            'setNumber' => $data['setNumber'],
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        return [
            'premium' => $data['premium'],
        ];
    }

    protected function setHeader(&$data)
    {
        $data['Header'] = [
            'user' => $this->apiUser,
            'password' => $this->apiPassword,
        ];
        $data['producerCode'] = $this->apiProducerCode;
    }

    protected function transformBoolean($boolean)
    {
        return (bool)$boolean;
    }
}
