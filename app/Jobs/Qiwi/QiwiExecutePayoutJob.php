<?php


namespace App\Jobs\Qiwi;


use App\Contracts\Services\ReportServiceContract;
use App\Exceptions\Qiwi\PayoutInsufficientFundsException;
use Benfin\Api\GlobalStorage;
use Exception;

class QiwiExecutePayoutJob extends QiwiJob
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

        if ($this->getAllowPayRequests() == 1){
            $reportService = app(ReportServiceContract::class);
            $report = $reportService->reportRepository()->getById($this->params['report_id']);

            try {
                $reportService->executePayout($report);
            } catch (PayoutInsufficientFundsException $exception) {
                $this->disableAllowPayRequests();
                $this->sendNotify();
                dispatch((new QiwiExecutePayoutJob($this->params))->onQueue('QiwiExecutePayout'));
            }

            dispatch((new QiwiCreateXlsJob($this->params))->onQueue('QiwiCreateXls'));
        } else {
            dispatch((new QiwiExecutePayoutJob($this->params))->onQueue('QiwiExecutePayout'));
        }

    }
}
