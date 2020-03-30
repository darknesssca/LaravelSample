<?php


namespace App\Services\Company;


use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Exceptions\AbstractException;
use App\Traits\CompanyServicesTrait;
use App\Traits\TokenTrait;

class ProcessingService extends CompanyService
{
    use CompanyServicesTrait, TokenTrait;

    protected $processingInterval;
    protected $maxRowsByCycle;

    public function __construct(
        IntermediateDataRepositoryContract $intermediateDataRepository,
        RequestProcessRepositoryContract $requestProcessRepository,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->processingInterval = config('api_sk.processingInterval');
        $this->maxRowsByCycle = config('api_sk.maxRowsByCycle');
        parent::__construct($intermediateDataRepository, $requestProcessRepository, $policyRepository);
    }

    public function preCalculating()
    {
        $count = config('api_sk.maxRowsByCycle');
        $processPool = $this->requestProcessRepository->getPool(1, $count);
        if ($processPool) {
            foreach ($processPool as $process) {
                $processItem = $process->toArray();
                $processItem['data'] = json_decode($processItem['data'], true);
                $company = $this->getCompany($processItem['company']);
                try {
                    $this->runService($company, $processItem, 'preCalculating');
                } catch (\Exception $exception) {
                    if ($exception instanceof AbstractException) {
                        $errorMessage = $exception->getMessageData();
                    } else {
                        $errorMessage = $exception->getMessage();
                    }
                    $isUpdated = $this->requestProcessRepository->updateCheckCount($processItem['token']);
                    if ($isUpdated === false) {
                        $tokenData = $this->getTokenData($processItem['token'], true);
                        $tokenData[$company->code]['status'] = 'error';
                        $tokenData[$company->code]['errorMessages'] = $errorMessage;
                        $this->intermediateDataRepository->update($processItem['token'], [
                            'data' => json_encode($tokenData),
                        ]);
                    }
                }
            }
        } else {
            sleep($this->processingInterval);
            return;
        }
    }
}
