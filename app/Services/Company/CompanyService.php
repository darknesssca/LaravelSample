<?php


namespace App\Services\Company;


use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Traits\TokenTrait;
use App\Traits\ValueSetterTrait;
use Benfin\Api\Contracts\NotifyMicroserviceContract;
use Benfin\Api\Traits\HttpRequest;
use Benfin\Api\Traits\SoapRequest;

abstract class CompanyService
{
    use HttpRequest, SoapRequest, TokenTrait, ValueSetterTrait;

    public const companyCode = '';
    protected $companyId;

    protected $intermediateDataService;
    protected $requestProcessService;
    protected $policyRepository;


    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->intermediateDataService = $intermediateDataService;
        $this->requestProcessService = $requestProcessService;
        $this->policyRepository = $policyRepository;
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
        return true; //fixme только для теста
        $notify = app(NotifyMicroserviceContract::class);
        $notify->sendMail($email, $billUrl, config('api_sk.notifyMicroserviceCode'));
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

}
