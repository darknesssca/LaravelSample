<?php


namespace App\Services\Company;


use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Traits\TokenTrait;
use App\Traits\ValueSetterTrait;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\Contracts\LogMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Benfin\Api\Traits\HttpRequest;
use Benfin\Api\Traits\SoapRequest;
use Benfin\Log\Facades\Log;
use Carbon\Carbon;
use Exception;

abstract class CompanyService
{
    use HttpRequest, SoapRequest, TokenTrait, ValueSetterTrait;

    public const companyCode = '';
    protected $companyId;
    protected $logTag;

    protected $intermediateDataService;
    protected $requestProcessService;
    protected $policyService;

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
     * @throws Exception
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
            if (isset($address['address']['addressType']) && $address['address']['addressType'] == $type) { // TODO значение из справочника
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
            'kladr_id' => $needleAddress['regionKladr'],
            'car_category_id' => $formData['car']['category'],
            'car_usage_target_id' => $formData['car']['vehicleUsage'],
        ];

        /** @var CommissionCalculationMicroserviceContract $calc_service */
        $calc_service = app(CommissionCalculationMicroserviceContract::class);
        $response = $calc_service->getCommissionsList($params);

        if (count($response['content']['data']) > 0) {

            $percent_reward = intval($response['content']['data'][0]['user_reward']);

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

    public function writeRequestLog(array $data)
    {
        $this->logTag = md5(time() . random_int(000000, 999999));
        if (!config('api.debugLog')) {
            return;
        }
        $class = explode('\\', get_called_class());
        $tag = array_pop($class) . 'Request | ' . $this->logTag;
        Log::daily(
            $data,
            static::companyCode,
            $tag
        );
    }

    /**
     * Метод записи данных request и response от мс в базу данных (logs)
     * @param string $token
     * @param $requestData
     * @param $responseData
     * @param string $code
     * @param string $companyName
     * @param string $serviceName
     * @param int|null $user_id
     */
    public function writeDatabaseLog(string $token, $requestData, $responseData, string $code, string $companyName,
                                     string $serviceName, int $user_id = null)
    {
        try {
            /** @var LogMicroserviceContract $logMicroservice */
            $logMicroservice = app(LogMicroserviceContract::class);

            $fields = [
                'token' => $token,
                'sk_code' => $companyName,
                'service_name' => $serviceName,
            ];

            if (!empty($requestData)) {
                $fields['data']['request'] = $requestData;
            }

            if (!empty($responseData)) {
                $fields['data']['response'] = $responseData;
            }

            $logMicroservice->updateSkLog($fields);
        } catch (Exception $exception) {
            //ignore
        }
    }

    public function getName($full)
    {
        $tmp = explode('\\', $full);

        return(end($tmp));
    }

    public function writeResponseLog(array $data)
    {
        if (!config('api.debugLog')) {
            return;
        }
        $class = explode('\\', get_called_class());
        $tag = array_pop($class) . 'Response | ' . $this->logTag;
        $this->logTag = '';
        Log::daily(
            $data,
            static::companyCode,
            $tag
        );
    }

    protected function RubToCop($rub)
    {
        return (float)$rub * 100;
    }

    protected function CopToRub($cop)
    {
        return (int)$cop / 100;
    }
}
