<?php


namespace App\Jobs\Qiwi;


use App\Contracts\Services\ReportServiceContract;
use App\Exceptions\Qiwi\PayoutAlreadyExistException;
use App\Exceptions\Qiwi\PayoutInsufficientFundsException;
use Benfin\Api\GlobalStorage;
use Exception;

class QiwiCreatePayoutJob extends QiwiJob
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        /** @var ReportServiceContract $reportService */

        GlobalStorage::setUser($this->params['user']);
        $this->login();

        if ($this->getAllowPayRequests() == 1) {
            $reportService = app(ReportServiceContract::class);
            $report = $this->getReport($this->params['report_id']);

            try {
                $reportService->createPayout($report);
                dispatch((new QiwiExecutePayoutJob($this->params))->onQueue('QiwiExecutePayout'));
            } catch (PayoutAlreadyExistException $exception) {
                dispatch((new QiwiCreatePayoutJob($this->params))->onQueue('QiwiCreatePayout'));
            } catch (PayoutInsufficientFundsException $exception) {
                $this->disableAllowPayRequests();
                $this->sendNotify();
                dispatch((new QiwiCreatePayoutJob($this->params))->onQueue('QiwiCreatePayout'));
            }
        } else {
            dispatch((new QiwiCreatePayoutJob($this->params))->onQueue('QiwiCreatePayout'));
        }
    }

    public function failed(Exception $exception)
    {
        $this->stopProcessing($this->params['report_id']);
    }
}
