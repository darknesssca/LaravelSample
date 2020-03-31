<?php


namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffBillLinkServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCalculateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffCreateServiceContract;
use App\Contracts\Company\Tinkoff\TinkoffServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use App\Models\Policy;
use App\Models\PolicyStatus;
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
        $this->sendBillUrl($insurer['email'], $billLinkData['billUrl']);
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

    public function checkPaid($company, $process)
    {
        // данный метод необходим для совместимости, но получение статусов происходит другим способом
    }

    public function payment($company, $attributes)
    {
        if (
            isset($attributes['Body']['sendPaymentNotificationPartnerRequest']['paymentStatus']) &&
            $attributes['Body']['sendPaymentNotificationPartnerRequest']['paymentStatus'] &&
            (strtolower($attributes['Body']['sendPaymentNotificationPartnerRequest']['paymentStatus']) == 'confirm') &&
            isset($attributes['Body']['sendPaymentNotificationPartnerRequest']['policyNumber']) &&
            $attributes['Body']['sendPaymentNotificationPartnerRequest']['policyNumber']
        ) {
            $policy = Policy::with([
                'status',
                'company',
            ])
                ->where('number', $attributes['Body']['sendPaymentNotificationPartnerRequest']['policyNumber'])
                ->where('paid', 0)
                ->whereHas('status', function ($query) {
                    $query->where('code', 'issued');
                })
                ->first();
            if ($policy) {
                $policy->update([
                    'paid' => true,
                    'status_id' => PolicyStatus::where('code', 'paid')->first()->id, // todo справочник
                ]);
            } else {
                throw new \Exception('Нет полиса с таким номером');
            }
        } else {
            throw new \Exception('Не указан номер полиса или статус оплаты не соответсвует статусу CONFIRM');
        }
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
