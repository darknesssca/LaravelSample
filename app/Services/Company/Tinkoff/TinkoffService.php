<?php


namespace App\Services\Company\Tinkoff;

use App\Contracts\Company\Tinkoff\TinkoffServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Models\Policy;
use App\Models\PolicyStatus;
use App\Repositories\IntermediateDataRepository;
use App\Repositories\RequestProcessRepository;
use App\Services\Company\CompanyService;

abstract class TinkoffService extends CompanyService implements TinkoffServiceContract
{
    const companyCode = 'tinkoff';

    protected $apiWsdlUrl;
    protected $apiUser;
    protected $apiPassword;
    protected $apiProducerCode;

    public function __construct(IntermediateDataRepository $intermediateDataRepository, RequestProcessRepository $requestProcessRepository)
    {
        $this->apiWsdlUrl = config('api_sk.tinkoff.wsdlUrl');
        $this->apiUser = config('api_sk.tinkoff.user');
        $this->apiPassword = config('api_sk.tinkoff.password');
        $this->apiProducerCode = config('api_sk.tinkoff.producerCode');
        if (!($this->apiWsdlUrl && $this->apiUser && $this->apiPassword && $this->apiProducerCode)) {
            throw new ConmfigurationException('Ошибка конфигурации API');
        }
        parent::__construct($intermediateDataRepository, $requestProcessRepository);
    }

    protected function setHeader(&$data)
    {
        $data['Header'] = [
            'user' => $this->apiUser,
            'password' => $this->apiPassword,
        ];
        $data['producerCode'] = $this->apiProducerCode;
    }

    // FIXME требуется рефакторинг

    /**
     * Данный метод необходим только для совместимости обработчиков компании
     *
     * @param $company
     * @param $process
     * @return void
     */
    public function checkPaid($company, $process)
    {
        return;
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
}
