<?php


namespace App\Services\Company;


use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Traits\TokenTrait;
use App\Traits\ValueSetterTrait;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Benfin\Api\Traits\HttpRequest;
use Benfin\Api\Traits\SoapRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

abstract class CompanyService
{
    use HttpRequest, SoapRequest, TokenTrait, ValueSetterTrait;

    public const companyCode = '';
    protected $companyId;

    protected $intermediateDataService;
    protected $requestProcessService;
    protected $policyService;
    protected $logPath;


    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService
    ) {
        $this->intermediateDataService = $intermediateDataService;
        $this->requestProcessService = $requestProcessService;
        $this->policyService = $policyService;
        $this->init();
    }

    /**отправка ссылки на оплату на почту
     * @param $email
     * @param $billUrl
     * @return bool
     * @throws \Exception
     */
    public function sendBillUrl($email, $billUrl)
    {
        $notify = app(NotifyMicroserviceContract::class);
        $data = [
            'link' => $billUrl
        ];
        $status = $notify->sendMail($email, $data, 'payment');
        if (isset($status["error"]) && !$status["error"]) {
            return true;
        }
        return false;
    }

    protected function searchDocumentByTypeAndId($attributes, $subjectId, $type)
    {
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            if ($subject['id'] != $subjectId) {
                continue;
            }
            foreach ($subject['fields']['documents'] as $iDocument => $document) {
                if ($document['document']['documentType'] == $type) { // TODO значение из справочника
                    return $document['document'];
                }
            }
        }
        return false;
    }

    protected function searchDocumentByType($subject, $type)
    {
        foreach ($subject['documents'] as $iDocument => $document) {
            if ($document['document']['documentType'] == $type) { // TODO значение из справочника
                return $document['document'];
            }
        }
        return false;
    }

    protected function searchAddressByType($subject, $type)
    {
        foreach ($subject['addresses'] as $iAddress => $address) {
            if ($address['address']['addressType'] == $type) { // TODO значение из справочника
                return $address['address'];
            }
        }
        return false;
    }

    protected function searchSubjectById($attributes, $subjectId)
    {
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            if ($subject['id'] == $subjectId) {
                return $subject['fields'];
            }
        }
        return false;
    }

    protected function searchDrivers($attributes)
    {
        $driversList = [];
        foreach ($attributes['drivers'] as $driver) {
            foreach ($attributes['subjects'] as $subject) {
                if ($subject['id'] == $driver['driver']['driverId']) {
                    $driversList[$subject['id']] = $subject['fields'];
                    $driversList[$subject['id']]['dateBeginDrive'] = $driver['driver']['drivingLicenseIssueDateOriginal'];
                }
            }
        }
        return $driversList;
    }

    protected function createPolicy($company, $attributes)
    {
        $policyService = app(PolicyServiceContract::class);
        return $policyService->createPolicyFromCustomData($company->id, $attributes);
    }

    protected function getReward($companyId, $formData, $policyPremium)
    {
        $insurerId = $formData['policy']['insurantId'];
        $insurer = [];
        $needleAddress = [];

        foreach ($formData['subjects'] as $subject) {
            if ($subject['id'] == $insurerId) {
                $insurer = $subject['fields'];
            }
        }
        if (!empty($insurer) && !empty($insurer['addresses'])) {
            foreach ($insurer['addresses'] as $address) {
                if ($address['address']['addressType'] == 'registration') {
                    $needleAddress = $address['address'];
                }
            }
        }

        $params = [
            'insurance_company_id' => $companyId,
            'policy_date' => Carbon::now()->format('Y-m-d'),
            'kladr_id' => $needleAddress['regionKladr']
        ];

        /** @var CommissionCalculationMicroserviceContract $calc_service */
        $calc_service = app(CommissionCalculationMicroserviceContract::class);
        $response = $calc_service->getCommissionsList($params);

        if (count($response['content']['data']) > 0) {
            if (GlobalStorage::userIsAgent()) {
                $percent_reward = intval($response['content']['data'][0]['agent_reward']);
            }
            if (GlobalStorage::userIsJustUser()) {
                $percent_reward = intval($response['content']['data'][0]['user_reward']);
            }
            return round(($percent_reward / 100) * $policyPremium, 2);
        }

        return 0;
    }

    public function writeLog(string $path, array $data)
    {
        if (!config('app.debug')) {
            return;
        }
        $log = Storage::disk('minio')->exists($path);
        $data['time'] = date('d.m.Y H:i:s', time());
        if ($log) {
            Storage::disk('minio')->append(
                $path,
                json_encode(
                    $data,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        } else {
            Storage::disk('minio')->put(
                $path,
                json_encode(
                    $data,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        }
    }
}
