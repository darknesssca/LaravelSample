<?php


namespace App\Services\Company;


use App\Contracts\Company\ProcessingServiceContract;
use App\Contracts\Repositories\Services\InsuranceCompanyServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use Benfin\Requests\Exceptions\AbstractException;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;
use Illuminate\Support\Facades\DB;

class ProcessingService extends CompanyService implements ProcessingServiceContract
{
    use CompanyServicesTrait, TokenTrait;

    protected $processingInterval;
    protected $maxRowsByCycle;
    protected $insuranceCompanyService;

    const processingJobs = [
        'preCalculating' => 'PreCalculatingJob',
        'segmenting' => 'SegmentingJob',
        'segmentCalculating' => 'SegmentCalculatingJob',
        'creating' => 'CreatingJob',
        'holding' => 'HoldingJob',
        'getPayment' => 'GetPaymentJob',
    ];

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService,
        InsuranceCompanyServiceContract $insuranceCompanyService
    )
    {
        $this->processingInterval = config('api_sk.processingInterval');
        $this->maxRowsByCycle = config('api_sk.maxRowsByCycle');
        $this->insuranceCompanyService = $insuranceCompanyService;
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function initDispatch()
    {
        foreach (static::processingJobs as $jobQueue => $jobClass) {
            DB::table('jobs')->where('queue', '=', $jobQueue)->delete();
            $job = 'App\Jobs\\' . $jobClass;
            dispatch((new $job)->onQueue($jobQueue));
        }
    }

    /**
     * Процессинг получение данных о предварительном рассчете
     */
    public function preCalculating()
    {
        $state = 1;
        $method = 'preCalculating';
        $this->runProcessing($state, $method);
    }

    /**
     * Процессинг получение данных сегментации
     */
    public function segmenting()
    {
        $state = 5;
        $method = 'segmenting';
        $this->runProcessing($state, $method);
    }

    /**
     * Процессинг получение данных окончательного рассчета
     */
    public function segmentCalculating()
    {
        $state = 10;
        $method = 'segmentCalculating';
        $this->runProcessing($state, $method);
    }

    /**
     * Процессинг получение статуса создания заявки
     *
     * При успешном создании может быть либо получен url оплаты, либо заявке может быть передан статус hold, если
     * она успешно была создана но на стороне СК все еще не завершилась обработка, и url оплаты еще не был сформирован
     */
    public function creating()
    {
        $state = 50;
        $method = 'creating';
        $this->runProcessing($state, $method);
    }

    /**
     * Процессинг обработки заявок со статусом hold
     *
     * При успешной обработке заявке присваивается url оплаты
     */
    public function holding()
    {
        $state = 75;
        $method = 'holding';
        $this->runProcessing($state, $method);
    }

    /**
     * Общий механизм процессинга
     *
     * @param $state
     * @param $method
     * @throws \App\Exceptions\CompanyException
     * @throws \App\Exceptions\TokenException
     * @throws \Exception - может быть выбрашен только при фатальной ошибке в коде или необработанной ошибке СК.
     *      В нормальной ситуации выбрасываться не может. Отлавливается для обеспечения стабильности всего механизма.
     */
    protected function runProcessing($state, $method)
    {
        $time = microtime(true);
        $processPool = $this->requestProcessService->getPool($state, $this->maxRowsByCycle);
        if ($processPool && $processPool->count()) {
            foreach ($processPool as $process) {
                $processItem = $process->toArray();
                $processItem['data'] = json_decode($processItem['data'], true);
                try {
                    $company = $this->getCompany($processItem['company']);
                    $this->runService($company, $processItem, $method);
                } catch (\Exception $exception) { // отлавливаем все эксепшены для обеспечения корректной работы механизма
                    dump($exception);
                    $this->processingError($company, $processItem, $exception);
                }
            }
        }
        $delta = microtime(true) - $time;
        if ($delta < $this->processingInterval) {
            sleep(ceil($this->processingInterval - $delta));
        }
    }

    /**
     * Обработка эксепшенов общего процессинга
     *
     * @param $company
     * @param $processItem
     * @param $exception
     * @throws \App\Exceptions\TokenException
     */
    protected function processingError($company, $processItem, $exception)
    {
        try {
            if ($exception instanceof AbstractException) {
                $errorMessages = $exception->getMessageData();
            } else {
                $errorMessages = [$exception->getMessage()];
            }
            $isUpdated = $this->requestProcessService->updateCheckCount($processItem['token'], $company->code);
            if ($isUpdated === false) {
                $tokenData = $this->getTokenData($processItem['token'], true);
                $tokenData[$company->code]['status'] = 'error';
                $tokenData[$company->code]['errorMessages'] = $errorMessages;
                $this->intermediateDataService->update($processItem['token'], [
                    'data' => json_encode($tokenData),
                ]);
            }
        } catch (\Exception $exception) {
            // ignore
        }
    }

    /**
     * Получение данных об оплате от СК
     *
     * Независимый процессинг, использующий иной механизм обработки
     */
    public function getPayment()
    {
        $limit = config('api_sk.maxPoliciesCountForPaymentCheck');
        $policies = $this->policyService->getNotPaidPolicies($limit, 10);
        $method = 'getPayment';
        if (!$policies) {
            return;
        }
        foreach ($policies as $policy) {
            try {
                $policyArray = $policy->toArray();
                $company = $this->getCompanyById($policyArray['insurance_company_id']);
                $this->runService($company, $policyArray, $method);
            } catch (\Exception $exception) {
                // игнорируем
            }
        }
    }
}
