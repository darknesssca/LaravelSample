<?php


namespace App\Services\Company;


use App\Contracts\Company\ProcessingServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\AbstractException;
use App\Jobs\CreatingJob;
use App\Jobs\GetPaymentJob;
use App\Jobs\HoldingJob;
use App\Jobs\PreCalculatingJob;
use App\Jobs\SegmentCalculatingJob;
use App\Jobs\SegmentingJob;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;

class ProcessingService extends CompanyService implements ProcessingServiceContract
{
    use CompanyServicesTrait, TokenTrait;

    protected $processingInterval;
    protected $maxRowsByCycle;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->processingInterval = config('api_sk.processingInterval');
        $this->maxRowsByCycle = config('api_sk.maxRowsByCycle');
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    public function initDispatch()
    {
        dispatch((new PreCalculatingJob)->onQueue('preCalculating'));
        dispatch((new SegmentingJob)->onQueue('segmenting'));
        dispatch((new SegmentCalculatingJob)->onQueue('segmentCalculating'));
        dispatch((new CreatingJob)->onQueue('creating'));
        dispatch((new HoldingJob)->onQueue('holding'));
        dispatch((new GetPaymentJob)->onQueue('getPayment'));
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
        $processPool = $this->requestProcessService->getPool($state, $this->maxRowsByCycle);
        if (!$processPool) {
            sleep($this->processingInterval);
            return;
        }
        foreach ($processPool as $process) {
            $processItem = $process->toArray();
            $processItem['data'] = json_decode($processItem['data'], true);
            $company = $this->getCompany($processItem['company']);
            try {
                $this->runService($company, $processItem, $method);
            } catch (\Exception $exception) { // отлавливаем все эксепшены для обеспечения корректной работы механизма
                $this->processingError($company, $processItem, $exception);
            }
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
        if ($exception instanceof AbstractException) {
            $errorMessage = $exception->getMessageData();
        } else {
            $errorMessage = $exception->getMessage();
        }
        $isUpdated = $this->requestProcessService->updateCheckCount($processItem['token']);
        if ($isUpdated === false) {
            $tokenData = $this->getTokenData($processItem['token'], true);
            $tokenData[$company->code]['status'] = 'error';
            $tokenData[$company->code]['errorMessages'] = $errorMessage;
            $this->intermediateDataService->update($processItem['token'], [
                'data' => json_encode($tokenData),
            ]);
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
        $policies = $this->policyRepository->getNotPaidPolicies($limit);
        $method = 'getPayment';
        if (!$policies) {
            return;
        }
        foreach ($policies as $policy) {
            try {
                $this->runService($policy->company, $policy->toArray(), $method);
            } catch (\Exception $exception) {
                // игнорируем
            }
        }
    }
}
