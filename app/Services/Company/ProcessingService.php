<?php


namespace App\Services\Company;


use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Exceptions\AbstractException;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;

class ProcessingService extends CompanyService
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

    public function preCalculating()
    {
        $state = 1;
        $method = 'preCalculating';
        $this->runProcessing($state, $method);
    }

    public function segmenting()
    {
        $state = 5;
        $method = 'segmenting';
        $this->runProcessing($state, $method);
    }

    public function segmentCalculating()
    {
        $state = 10;
        $method = 'segmentCalculating';
        $this->runProcessing($state, $method);
    }

    public function creating()
    {
        $state = 50;
        $method = 'creating';
        $this->runProcessing($state, $method);
    }

    public function holding()
    {
        $state = 75;
        $method = 'holding';
        $this->runProcessing($state, $method);
    }

    protected function runProcessing($state, $method)
    {
        $processPool = $this->requestProcessService->getPool($state, $this->maxRowsByCycle);
        if ($processPool) {
            foreach ($processPool as $process) {
                $processItem = $process->toArray();
                $processItem['data'] = json_decode($processItem['data'], true);
                $company = $this->getCompany($processItem['company']);
                try {
                    $this->runService($company, $processItem, $method);
                } catch (\Exception $exception) {
                    $this->processingError($company, $processItem, $exception);
                }
            }
        } else {
            sleep($this->processingInterval);
            return;
        }
    }

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
}
