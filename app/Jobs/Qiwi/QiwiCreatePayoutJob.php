<?php


namespace App\Jobs\Qiwi;


use App\Contracts\Services\ReportServiceContract;
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

        $reportService = app(ReportServiceContract::class);
        $report = $reportService->reportRepository()->getById($this->params['report_id']);

        try {
            $reportService->createPayout($report);
        } catch (Exception $exception) {
            //
        }

        dispatch((new QiwiExecutePayoutJob($this->params))->onQueue('QiwiExecutePayout'));
    }
}
