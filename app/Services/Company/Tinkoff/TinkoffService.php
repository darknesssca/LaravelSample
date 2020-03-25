<?php


namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use App\Services\Company\CompanyService;

class TinkoffService extends CompanyService implements TinkoffServiceContract
{
    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;
    protected $apiProducerCode;

    public function __construct()
    {
        $this->companyCode = "tinkoff";
        $this->companyId = InsuranceCompany::where('code',$this->companyCode)->take(1)->get()[0]['id'];
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
        $tokenData = IntermediateData::getData($attributes['token']);
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

    public function create($company, $attributes, $additionalData = [])
    {
        if (!(isset($additionalData['tokenData']) && $additionalData['tokenData'])) {
            throw new \Exception('no token data');
        }
        $attributes['setNumber'] = $additionalData['tokenData']['setNumber'];
        $createService = app(TinkoffCreateServiceContract::class);
        $createData = $createService->run($company, $attributes, $additionalData);
        $billLinkService = app(TinkoffBillLinkServiceContract::class);
        $billLinkData = $billLinkService->run($company, $attributes, $additionalData);
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        RestController::sendBillUrl($insurer['email'], $billLinkData['billUrl']);
        $tokenData = IntermediateData::getData($attributes['token']);
        $tokenData[$company->code] = [
            'status' => $createData['status'],
            'billUrl' => $billLinkData['billUrl'],
        ];
        IntermediateData::where('token', $attributes['token'])->update([
            'data' => $tokenData,
        ]);
        return [
            'status' => $createData['status'],
            'billUrl' => $billLinkData['billUrl'],
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
